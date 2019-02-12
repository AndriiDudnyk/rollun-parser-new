<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use Ebay\ConfigProvider;
use GuzzleHttp\Psr7\ServerRequest;
use phpQuery as PhpQuery;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\dic\InsideConstruct;
use rollun\parser\AbstractParser;

final class EbayMotorsSearch extends AbstractParser
{
    public const PARSER_NAME = self::class;

    /**
     * @var QueueInterface
     */
    protected $productTaskQueue;

    public function __construct(
        DataStoresInterface $parseResultDataStore = null,
        ?LoggerInterface $logger = null,
        QueueInterface $productTaskQueue = null
    ) {
        parent::__construct($parseResultDataStore, null);
        InsideConstruct::setConstructParams([
            'productTaskQueue' => ConfigProvider::__NAMESPACE__ . 'productTaskQueue',
            'logger' => LoggerInterface::class
        ]);
    }

    public function parse(string $data): array
    {
        $document = PhpQuery::newDocument($data);
        $itemCards = $document->find('#ListViewInner > li');
        $products = [];

        foreach ($itemCards as $key => $itemCard) {
            $pq = pq($itemCard);

            if ($pq->find('.kand-expansion')->count()) {
                continue;
            }

            $products[$key]['uri'] = $pq->find('.lvtitle a')->attr('href');
            $urlComponents = parse_url($products[$key]['uri']);
            $path = $urlComponents['path'];
            $parts = explode('/', $path);

            $products[$key]['item_id'] = end($parts);
            $products[$key]['imgs'] = $pq->find('.full-width a .img')->attr('src');

            if ($pq->find('.prRange')->count()) {
                $priceRange = $pq->find('.prRange')->text();
                [$from, , $to] = explode(' ', $priceRange);
                $products[$key]['price'] = trim($from) . '-' . trim($to);
            } else {
                $products[$key]['price'] = trim($pq->find('.lvprice > span')->text());
            }

            $products[$key]['shipping']['cost'] = trim($pq->find('.lvshipping .fee')->text());

            // Filter trash
            $products[$key]['shipping']['cost'] = str_replace([' shipping', '+'], '',
                $products[$key]['shipping']['cost']);

            $products[$key]['shipping'] = implode(' ', $products[$key]['shipping']);

            $sellerInfo = trim($pq->find('.lvdetails li')->eq(1)->text());
            preg_match('/Seller:\s+([\w\W]+)\(.+\)/', $sellerInfo, $matches);
            $products[$key]['seller'] = $matches[1] ?? '';

            $hotnessText = trim($pq->find('.watch a')->text());

            $products[$key]['date'] = $pq->find('.timeleft .tme span')->text();

            $products[$key]['watch'] = stristr($hotnessText, 'Watch') ? $hotnessText : '';
            $products[$key]['sold'] = stristr($hotnessText, 'Sold') ? $hotnessText : '';
        }

        return $products;
    }

    public function canParse(string $data): bool
    {
        $document = PhpQuery::newDocument($data);

        return boolval($document->find('#ListViewInner > li')->count());
    }

    protected function saveResult($records)
    {
        if (!$records) {
            throw new \InvalidArgumentException("Invalid data after search parsing");
        }

        foreach ($records as $record) {
            $product = [
                'id' => $record['item_id'],
                'uri' => $record['uri'],
                'price' => $record['price'],
                'imgs' => json_encode([$record['imgs']]),
                'shipping' => $record['shipping'],
                'seller' => $record['seller'],
                'watch' => $record['watch'],
                'sold' => $record['sold'],
            ];

            if ($this->parseResultDataStore->has($product['id'])) {
                $this->logger->debug("EBAY-SEARCH (ebay motors). Product exist with id # {$product['id']}", [
                    'existProduct' => $this->parseResultDataStore->read($product['id']),
                    'newProduct' => $product,
                ]);
            }

            $this->parseResultDataStore->create($product, true);
            $this->logger->debug('EBAY-SEARCH (ebay motors). Create (rewrite) product', [
                'product' => $product,
            ]);

            $this->addProductParsingTask($product);
        }
    }

    protected function addProductParsingTask($product)
    {
        $request = new ServerRequest('GET', $product['uri']);
        $serializedData = QueueFiller::serializeMessage($request);
        $message = new Message($serializedData);
        $this->productTaskQueue->addMessage($message);
    }

    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            'productTaskQueue' => ConfigProvider::__NAMESPACE__ . 'productTaskQueue',
            'logger' => LoggerInterface::class,
        ]);
    }
}
