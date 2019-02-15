<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Handler;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Queues\QueueInterface;
use Zend\Diactoros\Response\JsonResponse;

class PurgeQueue implements RequestHandlerInterface
{
    const MESSAGE_COUNT = 1000;

    /**
     * @var QueueInterface[]
     */
    protected $queues;

    public function __construct(array $queues)
    {
        $this->queues = $queues;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queue = $this->getSpecifiedQueue($request);

        if ($queue->isEmpty()) {
            return new JsonResponse('Queue is empty');
        }

        $queue->purgeQueue();

        return new JsonResponse('Queue successfully purged');
    }

    protected function getSpecifiedQueue(ServerRequestInterface $request) : ?QueueInterface
    {
        $queueName = $request->getAttribute('queueName');

        if (!$queueName) {
            throw new \InvalidArgumentException("invalid attribute 'queueName'");
        }

        $specifiedQueue = null;

        foreach ($this->queues as $queue) {
            if ($queue->getName() == $queueName) {
                return $queue;
            }
        }

        throw new \InvalidArgumentException("Unknown queue $queueName");
    }
}
