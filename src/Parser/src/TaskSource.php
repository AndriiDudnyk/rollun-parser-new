<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Parser;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use rollun\callback\Queues\QueueInterface;

class TaskSource extends QueueFiller
{
    /**
     * Config for loader tasks
     * Example:
     *  [
     *      'uri' => 'site://example.com,
     *      // ...
     *  ]
     * @var array
     */
    protected $config;

    /**
     * TaskSource constructor.
     * @param QueueInterface $queue
     * @param array $config
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(QueueInterface $queue, array $config, ?LoggerInterface $logger = null)
    {
        parent::__construct($queue, $logger);
        $this->config = $config;
    }

    /**
     * @param mixed $value
     * @return PayloadInterface
     * @throws \rollun\utils\Json\Exception
     */
    public function __invoke($value): PayloadInterface
    {
        $result = [];

        foreach ($this->config as $taskConfig) {
            $payload = parent::__invoke($taskConfig);
            $result[] = $payload->getPayload();
        }

        return new SimplePayload(null, $result);
    }

    public function __sleep()
    {
        $properties = parent::__sleep();

        return array_merge($properties, ['config']);
    }
}
