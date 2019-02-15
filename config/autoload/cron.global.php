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
use rollun\callback\PidKiller\LinuxPidKiller;

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

                'pid-killer_ticker'
            ],
        ],
        'sec_ticker' => [
            TickerAbstractFactory::KEY_CLASS => Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => 'min_multiplexer',
            TickerAbstractFactory::KEY_TICK_DURATION => 6,
            TickerAbstractFactory::KEY_TICKS_COUNT => 10,
        ],
        'pid-killer_ticker' => [
            TickerAbstractFactory::KEY_CLASS => Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => LinuxPidKiller::class,
            TickerAbstractFactory::KEY_TICK_DURATION => 1,
            TickerAbstractFactory::KEY_TICKS_COUNT => 5,
        ],
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
