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

final class Product extends AbstractParser
{
    public const PARSER_NAME = 'ebayProduct';

    const PID_COMPATIBLE_URI = 'https://frame.ebay.com/ebaymotors/ws/eBayISAPI.dll'
    . '?GetFitmentData&req=1&cid=177773&ct=100&page=1&pid=';

    const ITEM_COMPATIBLE_URI = 'https://frame.ebay.com/ebaymotors/ws/eBayISAPI.dll'
    . '?GetFitmentData&req=2&ct=1000&page=1&item=';

    /**
     * @var QueueInterface
     */
    protected $compatibleTaskQueue;

    public function __construct(
        DataStoresInterface $parseResultDataStore = null,
        ?LoggerInterface $logger = null,
        QueueInterface $compatibleTaskQueue = null
    ) {
        parent::__construct($parseResultDataStore, null);
        InsideConstruct::setConstructParams([
            'compatibleTaskQueue' => ConfigProvider::__NAMESPACE__ . 'compatibleTaskQueue',
            'logger' => LoggerInterface::class,
        ]);
    }

    /**
     * @param string $data
     * @return array|mixed
     */
    public function parse(string $data): array
    {
        $document = PhpQuery::newDocument($data);

        $sellerUrl = $document->find('#mbgLink')->attr('href');
        $parts = parse_url($sellerUrl);
        parse_str($parts['query'], $sellerId);

        $href = $document->find('link[rel="canonical"]')->attr('href');

        if (preg_match('/(\d+)$/', $href, $matches) === false || !$matches[1]) {
            throw new \InvalidArgumentException("Can't parse item id from ebay product document");
        }

        $product['item_id'] = $matches[1];
        $product['title'] = $document->find('.it-ttl')->text();

        if ($document->find('#vi-cdown_timeLeft')->count()) {
            $product['price'] = $document->find('#prcIsum_bidPrice')->text();
        } else {
            $product['price'] = $document->find('#prcIsum')->text();
        }

        $product['seller'] = trim($document->find('#RightSummaryPanel .mbg-nw')->text());

        $product['shipping']['cost'] = trim($document->find('#fshippingCost>span')->text());
        $product['shipping']['service'] = trim($document->find('#fShippingSvc')->text());

        $catLine = $document->find('.vi-VR-brumb-hasNoPrdlnks li a span');
        $product['category'] = '';

        foreach ($catLine as $cat) {
            $pq = pq($cat);
            $product['category'] .= '>' . $pq->text();
        }

        $itemImages = $document->find('#mainImgHldr>img');

        foreach ($itemImages as $img) {
            $pq = pq($img);
            $product['imgs'][] = $pq->attr('src');
        }

        if ($document->find('.pLftB div')->count()) {
            [, , , $ebayId] = explode(' ', trim($document->find('.pLftB div')->text()));
            $product['ebay_id'] = str_replace('EPID', '', $ebayId);
        } else {
            $product['ebay_id'] = '';
        }

        $itemSpecs = $document->find('.itemAttr tr');
        $specs = [];

        foreach ($itemSpecs as $tr) {
            $pq = pq($tr);
            $key = count($specs);

            $specs[$key]['name'] = trim(trim($pq->find('td')->eq(0)->text()), ':');
            $specs[$key]['value'] = trim($pq->find('td')->eq(1)->text());

            if ($pq->find('td')->eq(2)->count()) {
                $key++;
                $specs[$key]['name'] = trim(trim($pq->find('td')->eq(2)->text()), ':');
                $specs[$key]['value'] = trim($pq->find('td')->eq(3)->text());
            }
        }

        $product['specs'] = $specs;

        return $product;
    }

    public function canParse(string $data): bool
    {
        $document = PhpQuery::newDocument($data);

        return boolval($document->find('#mbgLink')->attr('href'));
    }

    protected function saveResult($product)
    {
        $checkedProduct = [
            'id' => $product['item_id'],
            'title' => $product['title'],
            'price' => $product['price'],
            'seller' => $product['seller'],
            'shipping' => json_encode($product['shipping']),
            'category' => $product['category'],
            'ebay_id' => $product['ebay_id'],
            'imgs' => json_encode($product['imgs']),
            'specs' => json_encode($product['specs']),
        ];

        $this->parseResultDataStore->update($checkedProduct);
        $this->addCompatibleParsingTask($checkedProduct);
    }

    protected function addCompatibleParsingTask($product)
    {
        if ($product['ebay_id']) {
            $compatibleUri = self::PID_COMPATIBLE_URI . $product['ebay_id'];
        } else {
            $compatibleUri = self::ITEM_COMPATIBLE_URI . $product['id'];
        }

        $request = new ServerRequest('GET', $compatibleUri);
        $serializedData = QueueFiller::serializeMessage($request);
        $message = new Message($serializedData);
        $this->compatibleTaskQueue->addMessage($message);
    }

    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            'compatibleTaskQueue' => ConfigProvider::__NAMESPACE__ . 'compatibleTaskQueue',
            'logger' => LoggerInterface::class,
        ]);
    }
}
