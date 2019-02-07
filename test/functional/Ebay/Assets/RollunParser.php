<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace test\functional\Ebay\Assets;

use Parser\AbstractParser;
use phpQuery as PhpQuery;

class RollunParser extends AbstractParser
{
    public function parse(string $data): array
    {
        $document = PhpQuery::newDocument($data);
        $h1 = trim($document->find('h1')->text());
        return [$h1];
    }

    public function canParse(string $data): bool
    {
        return true;
    }

    protected function saveResult($records)
    {
        $this->parseResultDataStore->create($records);
    }
}
