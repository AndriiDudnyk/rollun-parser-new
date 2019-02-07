<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay;

use Ebay\Loader\BaseLoader;
use Ebay\Loader\ResponseValidator\StatusOk;
use Ebay\Parser\Search;
use Parser\Factory\LoaderAbstractFactory;
use Parser\Factory\ParserAbstractFactory;
use Parser\Factory\TaskSourceAbstractFactory;
use Parser\TaskSource;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\Factory\WorkerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerManagerAbstractFactory;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Factory\FileAdapterAbstractFactory;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\QueueClient;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use Parser\ConfigProvider as ParserConfigProvider;
use rollun\datastore\DataStore\SerializedDbTable;
use rollun\tableGateway\Factory\TableGatewayAbstractFactory;

class ConfigProvider
{
    const __NAMESPACE__ = __NAMESPACE__;

    public function __invoke()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [],
                'invokables' => [
                    StatusOk::class => StatusOk::class,
                ],
                'factories' => [

                ],
            ],
            WorkerManagerAbstractFactory::class => [
                'searchLoaderWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'searchLoaderProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'searchLoader'
                ],
                'searchParserWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'searchParserProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'searchParser'
                ],
            ],
            InterruptAbstractFactoryAbstract::KEY => [
                self::__NAMESPACE__ . 'searchLoaderProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'searchLoaderWorker',
                ],
                self::__NAMESPACE__ . 'searchParserProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'searchParserWorker',
                ],
            ],
            WorkerAbstractFactory::class => [
                self::__NAMESPACE__ . 'searchLoaderWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'taskQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'searchLoader',
                ],
                self::__NAMESPACE__ . 'searchParserWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchDocumentQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'searchParser',
                ],
            ],
            QueueClientAbstractFactory::class => [
                self::__NAMESPACE__ . 'taskQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'taskQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
                self::__NAMESPACE__ . 'searchDocumentQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'searchDocumentQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
            ],
            ParserAbstractFactory::class => [
                self::__NAMESPACE__ . 'searchParser' => [
                    ParserAbstractFactory::KEY_CLASS => Search::class,
                    ParserAbstractFactory::KEY_PARSER_RESULT_DATASTORE => 'productDataStore'
                ]
            ],
            FileAdapterAbstractFactory::class => [
                self::__NAMESPACE__ . 'fileAdapter' => [
                    FileAdapterAbstractFactory::KEY_STORAGE_DIR_PATH => '/tmp/test',
                ],
                'pidQueueAdapter' => [
                    FileAdapterAbstractFactory::KEY_STORAGE_DIR_PATH => '/tmp/test',
                ],
            ],
            LoaderAbstractFactory::class => [
                self::__NAMESPACE__ . 'searchLoader' => [
                    LoaderAbstractFactory::KEY_PROXY_DATASTORE => ParserConfigProvider::PROXY_DATASTORE,
                    LoaderAbstractFactory::KEY_DOCUMENT_QUEUE => self::__NAMESPACE__ . 'searchDocumentQueue',
                    LoaderAbstractFactory::KEY_VALIDATOR => StatusOk::class,
                    LoaderAbstractFactory::KEY_CLASS => BaseLoader::class,
                ],
            ],
            DataStoreAbstractFactory::KEY_DATASTORE => [
                'productDataStore' => [
                    DbTableAbstractFactory::KEY_CLASS => SerializedDbTable::class,
                    DbTableAbstractFactory::KEY_TABLE_GATEWAY => 'products',
                ]
            ],
            TableGatewayAbstractFactory::KEY => [
                'products' => [],
                'slots' => []
            ],
            TaskSourceAbstractFactory::class => [
                'searchTask' => [
                    TaskSourceAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'taskQueue',
                    TaskSourceAbstractFactory::KEY_CLASS => TaskSource::class,
                    TaskSourceAbstractFactory::KEY_CONFIG => [
                        ['uri' => 'https://free-proxy-list.net/']
                    ]
                ]
            ]
        ];
    }
}
