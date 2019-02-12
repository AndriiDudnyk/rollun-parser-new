<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use rollun\parser\AbstractParser;

final class Compatible extends AbstractParser
{
    public const PARSER_NAME = 'ebayCompatible';

    public function __invoke($data)
    {
        if (!$this->isValid($data)) {
            throw new \RuntimeException('Invalid data for parser');
        }

        $document = file_get_contents($data['filepath']);
        $decodedDocument = json_decode($document, true);
        [$itemId,] = explode('-', basename($data['filepath']));

        if (!$itemId) {
            throw new \InvalidArgumentException("Can't parse item id from ebay compatible document");
        }

        $compatibleItems = $decodedDocument['data'];
        $compatibles = [];
        $result = [];

        foreach ($compatibleItems as $compatible) {
            $compatibles[] = [
                'make' => $compatible['Make'][0] ?? '',
                'model' => $compatible['Model'][0] ?? '',
                'submodel' => $compatible['Submodel'][0] ?? '',
                'year' => $compatible['Year'][0] ?? '',
                'item_id' => $itemId,
            ];
        }

        $result['compatibles'] = $compatibles;
        $result['currentPageNo'] = $data['pageInfo']['currentPageNo'] ?? null;
        $result['totalPageCount'] = $data['pageInfo']['totalPageCount'] ?? null;

        $this->saveResult($result);
        unlink($data['filepath']);
    }

    public function parse(string $data): array
    {
        // TODO: Implement parse() method.
    }

    public function canParse(string $data): bool
    {
        return boolval(json_decode($data, true) ?? null);
    }

    protected function saveResult($data)
    {
        $compatibles = $data['compatibles'];

        foreach ($compatibles as $compatible) {
            $compatible['id'] = $this->createCompatibleId($compatible);
            $this->parseResultDataStore->create($compatible, true);
        }
    }

    protected function createCompatibleId($record)
    {
        return $record['item_id']
            . '-' . $record['make']
            . '-' . $record['model']
            . '-' . $record['submodel']
            . '-' . $record['year'];
    }
}
