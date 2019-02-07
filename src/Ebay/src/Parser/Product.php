<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use Parser\AbstractParser;
use phpQuery as PhpQuery;

final class Product extends AbstractParser
{
    public const PARSER_NAME = 'ebayProduct';

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

    protected function saveResult($records)
    {
        // TODO: Implement saveResult() method.
    }
}
