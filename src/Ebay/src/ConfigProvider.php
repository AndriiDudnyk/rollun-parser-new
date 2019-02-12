<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay;

use Ebay\Loader\BaseLoader;
use Ebay\Loader\CompatibleLoader;
use Ebay\Loader\SearchLoader;
use Ebay\Parser\Compatible;
use Ebay\Parser\EbayMotorsPaginationSearch;
use Ebay\Parser\EbayMotorsSearch;
use Ebay\Parser\Product;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Middleware\CallablePluginManagerFactory;
use rollun\callback\PidKiller\Factory\WorkerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerManagerAbstractFactory;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Factory\FileAdapterAbstractFactory;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\QueueClient;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\DataStore\SerializedDbTable;
use rollun\parser\Factory\LoaderAbstractFactory;
use rollun\parser\Factory\ParserAbstractFactory;
use rollun\parser\Factory\TaskSourceAbstractFactory;
use rollun\parser\ResponseValidator\StatusOk;
use rollun\parser\TaskSource;
use rollun\tableGateway\Factory\TableGatewayAbstractFactory;
use rollun\parser\ConfigProvider as ParserConfigProvider;

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
                    EbayMotorsPaginationSearch::class => EbayMotorsPaginationSearch::class,
                ],
                'aliases' => [
                    self::__NAMESPACE__ . 'searchPaginationParser' => EbayMotorsPaginationSearch::class
                ],
                'factories' => [

                ],
            ],
            // ----- WORKER MANAGERS ------
            WorkerManagerAbstractFactory::class => [
                // Ebay search
                'searchLoaderWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'searchLoaderProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebaySearchLoader',
                ],
                'searchParserWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'searchParserProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebaySearchParser',
                ],

                // Ebay pagination search
                'searchPaginationLoaderWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'searchPaginationLoaderProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebaySearchPaginationLoader',
                ],
                'searchPaginationParserWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'searchPaginationParserProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebaySearchPaginationParser',
                ],

                // Ebay product
                'productLoaderWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'productLoaderProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebayProductLoader',
                ],
                'productParserWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'productParserProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebayProductParser',
                ],

                // Ebay compatible
                'compatibleLoaderWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'compatibleLoaderProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebayCompatibleLoader',
                ],
                'compatibleParserWorkerManager' => [
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'slots',
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_PROCESS => self::__NAMESPACE__ . 'compatibleParserProcess',
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'ebayCompatibleParser',
                ],
            ],

            // ----- PROCESSES ------
            InterruptAbstractFactoryAbstract::KEY => [
                // Ebay search
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

                // Ebay pagination search
                self::__NAMESPACE__ . 'searchPaginationLoaderProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'searchPaginationLoaderWorker',
                ],
                self::__NAMESPACE__ . 'searchPaginationParserProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'searchPaginationParserWorker',
                ],

                // Ebay product
                self::__NAMESPACE__ . 'productLoaderProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'productLoaderWorker',
                ],
                self::__NAMESPACE__ . 'productParserProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'productParserWorker',
                ],

                // Ebay compatible
                self::__NAMESPACE__ . 'compatibleLoaderProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'compatibleLoaderWorker',
                ],
                self::__NAMESPACE__ . 'compatibleParserProcess' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_MAX_EXECUTE_TIME => 60,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => self::__NAMESPACE__ . 'compatibleParserWorker',
                ],
            ],

            // ----- WORKERS ------
            WorkerAbstractFactory::class => [
                // Ebay search
                self::__NAMESPACE__ . 'searchLoaderWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchTaskQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'searchLoader',
                ],
                self::__NAMESPACE__ . 'searchParserWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchDocumentQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'searchParser',
                ],

                // Ebay pagination search
                self::__NAMESPACE__ . 'searchPaginationLoaderWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchPaginationTaskQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'searchPaginationLoader',
                ],
                self::__NAMESPACE__ . 'searchPaginationParserWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchPaginationDocumentQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'searchPaginationParser',
                ],

                // Ebay product
                self::__NAMESPACE__ . 'productLoaderWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'productTaskQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'productLoader',
                ],
                self::__NAMESPACE__ . 'productParserWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'productDocumentQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'productParser',
                ],

                // Ebay compatible
                self::__NAMESPACE__ . 'compatibleLoaderWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'compatibleTaskQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'compatibleLoader',
                ],
                self::__NAMESPACE__ . 'compatibleParserWorker' => [
                    WorkerAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'compatibleDocumentQueue',
                    WorkerAbstractFactory::KEY_CALLABLE => self::__NAMESPACE__ . 'compatibleParser',
                ],
            ],

            // ----- PARSERS ------
            ParserAbstractFactory::class => [
                self::__NAMESPACE__ . 'searchParser' => [
                    ParserAbstractFactory::KEY_CLASS => EbayMotorsSearch::class,
                    ParserAbstractFactory::KEY_PARSER_RESULT_DATASTORE => 'productDataStore',
                ],
                self::__NAMESPACE__ . 'productParser' => [
                    ParserAbstractFactory::KEY_CLASS => Product::class,
                    ParserAbstractFactory::KEY_PARSER_RESULT_DATASTORE => 'productDataStore',
                ],
                self::__NAMESPACE__ . 'compatibleParser' => [
                    ParserAbstractFactory::KEY_CLASS => Compatible::class,
                    ParserAbstractFactory::KEY_PARSER_RESULT_DATASTORE => 'compatibleDataStore',
                ],
            ],

            // ----- LOADERS ------
            LoaderAbstractFactory::class => [
                self::__NAMESPACE__ . 'searchLoader' => [
                    LoaderAbstractFactory::KEY_PROXY_DATASTORE => ParserConfigProvider::PROXY_DATASTORE,
                    LoaderAbstractFactory::KEY_DOCUMENT_QUEUE => self::__NAMESPACE__ . 'searchDocumentQueue',
                    LoaderAbstractFactory::KEY_VALIDATOR => StatusOk::class,
                    LoaderAbstractFactory::KEY_CLASS => SearchLoader::class,
                    LoaderAbstractFactory::KEY_CLIENT_CONFIG => [
                        'read_timeout' => 15,
                        'connect_timeout' => 15,
                    ],
                ],
                self::__NAMESPACE__ . 'searchPaginationLoader' => [
                    LoaderAbstractFactory::KEY_PROXY_DATASTORE => ParserConfigProvider::PROXY_DATASTORE,
                    LoaderAbstractFactory::KEY_DOCUMENT_QUEUE => self::__NAMESPACE__ . 'searchPaginationDocumentQueue',
                    LoaderAbstractFactory::KEY_VALIDATOR => StatusOk::class,
                    LoaderAbstractFactory::KEY_CLASS => BaseLoader::class,
                    LoaderAbstractFactory::KEY_CLIENT_CONFIG => [
                        'read_timeout' => 15,
                        'connect_timeout' => 15,
                    ],
                ],
                self::__NAMESPACE__ . 'productLoader' => [
                    LoaderAbstractFactory::KEY_PROXY_DATASTORE => ParserConfigProvider::PROXY_DATASTORE,
                    LoaderAbstractFactory::KEY_DOCUMENT_QUEUE => self::__NAMESPACE__ . 'productDocumentQueue',
                    LoaderAbstractFactory::KEY_VALIDATOR => StatusOk::class,
                    LoaderAbstractFactory::KEY_CLASS => BaseLoader::class,
                    LoaderAbstractFactory::KEY_CLIENT_CONFIG => [
                        'read_timeout' => 15,
                        'connect_timeout' => 15,
                    ],
                ],
                self::__NAMESPACE__ . 'compatibleLoader' => [
                    LoaderAbstractFactory::KEY_PROXY_DATASTORE => ParserConfigProvider::PROXY_DATASTORE,
                    LoaderAbstractFactory::KEY_DOCUMENT_QUEUE => self::__NAMESPACE__ . 'compatibleDocumentQueue',
                    LoaderAbstractFactory::KEY_VALIDATOR => StatusOk::class,
                    LoaderAbstractFactory::KEY_CLASS => CompatibleLoader::class,
                    LoaderAbstractFactory::KEY_CLIENT_CONFIG => [
                        'read_timeout' => 15,
                        'connect_timeout' => 15,
                    ],
                ],
            ],

            // ----- QUEUES ------
            QueueClientAbstractFactory::class => [
                // Ebay search page
                self::__NAMESPACE__ . 'searchTaskQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebaySearchTaskQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
                self::__NAMESPACE__ . 'searchDocumentQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebaySearchDocumentQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],

                // Ebay search pagination
                self::__NAMESPACE__ . 'searchPaginationTaskQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebaySearchPaginationTaskQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
                self::__NAMESPACE__ . 'searchPaginationDocumentQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebaySearchPaginationDocumentQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],

                // Ebay product
                self::__NAMESPACE__ . 'productTaskQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebayProductTaskQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
                self::__NAMESPACE__ . 'productDocumentQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebayProductDocumentQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],

                // Ebay product
                self::__NAMESPACE__ . 'compatibleTaskQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebayCompatibleTaskQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
                self::__NAMESPACE__ . 'compatibleDocumentQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS => QueueClient::class,
                    QueueClientAbstractFactory::KEY_DELAY => 0,
                    QueueClientAbstractFactory::KEY_NAME => 'ebayCompatibleDocumentQueue',
                    QueueClientAbstractFactory::KEY_ADAPTER => self::__NAMESPACE__ . 'fileAdapter',
                ],
            ],
            FileAdapterAbstractFactory::class => [
                self::__NAMESPACE__ . 'fileAdapter' => [
                    FileAdapterAbstractFactory::KEY_STORAGE_DIR_PATH => '/tmp/test',
                    FileAdapterAbstractFactory::KEY_TIME_IN_FLIGHT => 10,
                ],
                'pidQueueAdapter' => [
                    FileAdapterAbstractFactory::KEY_STORAGE_DIR_PATH => '/tmp/test',

                ],
            ],
            TaskSourceAbstractFactory::class => [
                'searchPaginationTask' => [
                    TaskSourceAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchPaginationTaskQueue',
                    TaskSourceAbstractFactory::KEY_CLASS => TaskSource::class,
                    TaskSourceAbstractFactory::KEY_CONFIG => [
                        ['uri' => 'https://www.ebay.com/sch/i.html?_from=R40&_nkw=&_in_kw=1&_ex_kw=&_sacat=0&LH_Sold=1&_udlo=&_udhi=&_samilow=&_samihi=&_sadis=15&_stpos=59456&_sargn=-1%26saslc%3D1&_salic=1&_fss=1&_fsradio=%26LH_SpecificSeller%3D1&_saslop=1&_sasl=eldinisport%2Cljdpowersports%2C1_avec_plaisir%2Ccascade_lakes_motorsports%2Cmadpower5%2Cmxpowerplay%2Cuniversalpowersportsllc%2Cthe-d-zone%2Crollunlc%2Cdualsportarmory%2Cgnarlymoto-x%2Ctoyotaktmusa%2Cvitvov%2Cxelementseller%2Cimbodenmotorsportsllc%2Cmach3motorsports%2Cunhingedatv%2Ccorneraddiction&_sop=13&_dmd=1&_ipg=50&LH_Complete=1&_fosrp=1'],
                    ],
                ],
                'searchTask' => [
                    TaskSourceAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'searchTaskQueue',
                    TaskSourceAbstractFactory::KEY_CLASS => TaskSource::class,
                    TaskSourceAbstractFactory::KEY_CONFIG => [
                        ['uri' => 'https://www.ebay.com/sch/i.html?_from=R40&_nkw=&_in_kw=1&_ex_kw=&_sacat=0&LH_Sold=1&_udlo=&_udhi=&_samilow=&_samihi=&_sadis=15&_stpos=59456&_sargn=-1%26saslc%3D1&_salic=1&_fss=1&_fsradio=%26LH_SpecificSeller%3D1&_saslop=1&_sasl=eldinisport%2Cljdpowersports%2C1_avec_plaisir%2Ccascade_lakes_motorsports%2Cmadpower5%2Cmxpowerplay%2Cuniversalpowersportsllc%2Cthe-d-zone%2Crollunlc%2Cdualsportarmory%2Cgnarlymoto-x%2Ctoyotaktmusa%2Cvitvov%2Cxelementseller%2Cimbodenmotorsportsllc%2Cmach3motorsports%2Cunhingedatv%2Ccorneraddiction&_sop=13&_dmd=1&_ipg=50&LH_Complete=1&_fosrp=1'],
                    ],
                ],
                'productTask' => [
                    TaskSourceAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'productTaskQueue',
                    TaskSourceAbstractFactory::KEY_CLASS => TaskSource::class,
                    TaskSourceAbstractFactory::KEY_CONFIG => [
                        ['uri' => 'https://www.ebay.com/itm/Tusk-Top-End-Head-Gasket-Kit-HONDA-TRX-400EX-400X-1999-2014-TRX400EX-TRX400X/401474376538?hash=item5d79bcd35a:g:e4kAAOSw8-FaVoon&vxp=mtr'],
                    ],
                ],
                'compatibleTask' => [
                    TaskSourceAbstractFactory::KEY_QUEUE => self::__NAMESPACE__ . 'compatibleTaskQueue',
                    TaskSourceAbstractFactory::KEY_CLASS => TaskSource::class,
                    TaskSourceAbstractFactory::KEY_CONFIG => [
                        ['uri' => 'https://frame.ebay.com/ebaymotors/ws/eBayISAPI.dll?GetFitmentData&req=1&cid=177773&ct=100&page=1&pid=209928315'],
                    ],
                ],
            ],
            DataStoreAbstractFactory::KEY_DATASTORE => [
                'productDataStore' => [
                    DbTableAbstractFactory::KEY_CLASS => SerializedDbTable::class,
                    DbTableAbstractFactory::KEY_TABLE_GATEWAY => 'products',
                ],
                'compatibleDataStore' => [
                    DbTableAbstractFactory::KEY_CLASS => SerializedDbTable::class,
                    DbTableAbstractFactory::KEY_TABLE_GATEWAY => 'compatibles',
                ],
            ],
            TableGatewayAbstractFactory::KEY => [
                'products' => [],
                'compatibles' => [],
                'slots' => [],
            ],
            CallablePluginManagerFactory::KEY_INTERRUPTERS => [
                'abstract_factories' => [
                    TaskSourceAbstractFactory::class,
                ]
            ],
        ];
    }
}
