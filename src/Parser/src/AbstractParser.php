<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Parser;

use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\dic\InsideConstruct;

abstract class AbstractParser
{
    /**
     * @var DataStoresInterface
     */
    protected $parseResultDataStore;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * AbstractParser constructor.
     * @param DataStoresInterface $parseResultDataStore
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(
        DataStoresInterface $parseResultDataStore,
        LoggerInterface $logger = null
    ) {
        $this->parseResultDataStore = $parseResultDataStore;
        InsideConstruct::setConstructParams(['logger' => LoggerInterface::class]);
    }

    public function __invoke($data)
    {
        if (!$this->isValid($data)) {
            throw new \RuntimeException('Invalid data for parser');
        }

        $document = file_get_contents($data['filepath']);
        $records = $this->parse($document);
        $this->saveResult($records);
    }

    protected function isValid($data)
    {
        return isset($data['filepath']) && file_exists($data['filepath']);
    }

    abstract protected function saveResult($records);

    abstract public function parse(string $data): array;

    abstract public function canParse(string $data): bool;

    public function __sleep()
    {
        return ['parseResultDataStore'];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(['logger' => LoggerInterface::class]);
    }
}
