<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

final class Compatible extends AbstractParser
{
    public const PARSER_NAME = 'ebayCompatible';

    public function parse(string $data): array
    {
        $data = json_decode($data, true);

        $compatibleItems = $data['data'];
        $compatibles = [];
        $result = [];

        foreach ($compatibleItems as $compatible) {
            $compatibles[] = [
                'make' => $compatible['Make'][0] ?? '',
                'model' => $compatible['Model'][0] ?? '',
                'submodel' => $compatible['Submodel'][0] ?? '',
                'year' => $compatible['Year'][0] ?? '',
            ];
        }

        $result['compatibles'] = $compatibles;
        $result['currentPageNo'] = $data['pageInfo']['currentPageNo'] ?? null;
        $result['totalPageCount'] = $data['pageInfo']['totalPageCount'] ?? null;

        return $result;
    }

    public function canParse(string $data): bool
    {
        return boolval(json_decode($data, true) ?? null);
    }
}
