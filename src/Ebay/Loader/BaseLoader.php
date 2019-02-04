<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use rollun\callback\Queues\QueueInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

class BaseLoader
{
    /**
     * @var DataStoresInterface
     */
    protected $proxyDataStore;

    /**
     * @var QueueInterface
     */
    protected $documentQueue;

    public function __construct(DataStoresInterface $proxyDataStore, QueueInterface $documentQueue)
    {
        $this->proxyDataStore = $proxyDataStore;
        $this->documentQueue = $documentQueue;
    }

    public function __invoke($data)
    {

    }

    protected function createClient()
    {
        
    }
}
