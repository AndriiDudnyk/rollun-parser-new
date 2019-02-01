<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use phpQuery as PhpQuery;

final class EbayMotors extends AbstractParser
{
    public const PARSER_NAME = self::class;

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
                [$from, ,$to] = explode(' ', $priceRange);
                $products[$key]['price'] = trim($from) . '-' . trim($to);
            } else {
                $products[$key]['price'] = trim($pq->find('.lvprice > span')->text());
            }

            $products[$key]['shipping']['cost'] = trim($pq->find('.lvshipping .fee')->text());

            // Filter trash
            $products[$key]['shipping']['cost'] = str_replace(
                [' shipping', '+'],
                '',
                $products[$key]['shipping']['cost']
            );

            $products[$key]['shipping'] = implode(' ', $products[$key]['shipping']);

            $sellerInfo = trim($pq->find('.lvdetails li')->eq(1)->text());
            preg_match('/Seller:\s+([\w\W]+)\(.+\)/', $sellerInfo, $matches);
            $products[$key]['seller'] = $matches[1] ?? '';

            $hotnessText = trim($pq->find('.watch a')->text());

            $products[$key]['date'] = $pq->find('.timeleft .tme span')->text();

            if (stristr($hotnessText, 'Watch')) {
                $products[$key]['watch'] = $hotnessText;
            }

            if (stristr($hotnessText, 'Sold')) {
                $products[$key]['sold'] = $hotnessText;
            }
        }

        $result['products'] = $products;
        $result['nextPage'] = $document->find('#Pagination .pages .curr + a')->attr('href');

        return $result;
    }

    public function canParse(string $data): bool
    {
        $document = PhpQuery::newDocument($data);
        return boolval($document->find('#ListViewInner > li')->count());
    }
}
