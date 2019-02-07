<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Parser;

use Parser\Factory\LoaderAbstractFactory;
use Parser\Factory\ParserAbstractFactory;
use Parser\Factory\TaskSourceAbstractFactory;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Factory\HttpClientAbstractFactory;
use rollun\datastore\DataStore\HttpClient;

class ConfigProvider
{
    const PROXY_DATASTORE = 'proxyDatastore';

    public function __invoke()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [
                    LoaderAbstractFactory::class,
                    TaskSourceAbstractFactory::class,
                    ParserAbstractFactory::class,
                ],
            ],
            DataStoreAbstractFactory::KEY_DATASTORE => [
                self::PROXY_DATASTORE => [
                    HttpClientAbstractFactory::KEY_CLASS => HttpClient::class,
                    HttpClientAbstractFactory::KEY_URL => getenv('PROXY_MANAGER_URI'),
                ],
            ],
        ];
    }
}
