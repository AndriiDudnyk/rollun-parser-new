<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use rollun\callback\Queues\QueueInterface;
use Zend\Diactoros\Response\JsonResponse;

class ShowQueueMessages implements RequestHandlerInterface
{
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

        return new JsonResponse($queue->getNumberMessages());
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
