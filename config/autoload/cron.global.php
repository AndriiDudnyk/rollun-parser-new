<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Callback\Ticker;

return [
    CallbackAbstractFactoryAbstract::KEY => [
        'min_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                'searchPaginationLoaderWorkerManager',
                'searchPaginationParserWorkerManager',

                'searchLoaderWorkerManager',
                'searchParserWorkerManager',

                'productLoaderWorkerManager',
                'productParserWorkerManager',

                'compatibleLoaderWorkerManager',
                'compatibleParserWorkerManager',
            ],
        ],
        'sec_ticker' => [
            TickerAbstractFactory::KEY_CLASS => Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => 'min_multiplexer',
        ]
    ],
    InterruptAbstractFactoryAbstract::KEY => [
        'cron' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'min_multiplexer',
        ],
        'sec-cron' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'sec_ticker',
        ],
    ],
];
