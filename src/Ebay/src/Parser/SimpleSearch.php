<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use phpQuery as PhpQuery;
use rollun\parser\AbstractParser;

final class SimpleSearch extends AbstractParser
{
    public const PARSER_NAME = self::class;

    /**
     * @param string $data
     * @return array
     */
    public function parse(string $data): array
    {
        $document = PhpQuery::newDocument($data);
        $itemCards = $document->find('.s-item__wrapper');
        $products = [];

        foreach ($itemCards as $key => $itemCard) {
            $pq = pq($itemCard);

            $products[$key]['uri'] = $pq->find('.s-item__link')->attr('href');
            $urlComponents = parse_url($products[$key]['uri']);
            $path = $urlComponents['path'];
            $parts = explode('/', $path);

            $products[$key]['item_id'] = end($parts);
            $products[$key]['imgs'] = $pq->find('.s-item__image-img')->attr('src');
            $products[$key]['price'] = $pq->find('span.s-item__price')->text();
            $products[$key]['shipping']['cost'] = $pq->find('.s-item__shipping')->text();

            // Filter trash
            $products[$key]['shipping']['cost'] = str_replace(
                [' shipping', '+'],
                '',
                $products[$key]['shipping']['cost']
            );

            $products[$key]['shipping'] = implode(' ', $products[$key]['shipping']);

            $sellerInfo = $pq->find('.s-item__seller-info-text')->text();
            [, $sellerId, , ,] = explode(' ', $sellerInfo);
            $products[$key]['seller'] = $sellerId;

            $hotnessText = $pq->find('.s-item__hotness>.NEGATIVE')->text();
            $products[$key]['date'] = $pq->find('.timeleft .tme span')->text();

            $products[$key]['watch'] = stristr($hotnessText, 'Watch') ? $hotnessText : '';
            $products[$key]['sold'] = stristr($hotnessText, 'Sold') ? $hotnessText : '';
        }

        return $products;
    }

    public function canParse(string $data): bool
    {
        $document = PhpQuery::newDocument($data);

        return boolval($document->find('.s-item__wrapper')->count());
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
                'imgs' => json_encode([$record['imgs']]),
                'price' => $record['price'],
                'shipping' => $record['shipping'],
                'seller' => $record['seller'],
                'watch' => $record['watch'],
                'sold' => $record['sold'],
            ];

            if ($this->parseResultDataStore->has($product['id'])) {
                $this->logger->debug("EBAY-SEARCH (ebay motors). Product exist with id # {$product['id']}", [
                    'existProduct' => $this->parseResultDataStore->read($product['id']),
                    'newProduct' => $product
                ]);
            }

            $this->parseResultDataStore->create($product, true);
            $this->logger->debug('EBAY-SEARCH (ebay motors). Create (rewrite) product', [
                'product' => $product
            ]);
        }
    }
}
