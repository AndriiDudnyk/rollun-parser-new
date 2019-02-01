<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

abstract class AbstractParser
{
    public function __invoke()
    {
        // TODO: read task from queue, parse and save to db result
    }

    abstract public function parse(string $data): array;

    abstract public function canParse(string $data): bool;
}
