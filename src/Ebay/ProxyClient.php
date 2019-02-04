<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay;

use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

class ProxyClient implements CliIn
{
    public $proxyDataStore;

    public function __construct(DataStoresInterface $proxyDataStore)
    {
        $this->proxyDataStore = $proxyDataStore;
    }


}
