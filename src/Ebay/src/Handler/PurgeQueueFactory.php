<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Handler;

use Ebay\ConfigProvider;
use Psr\Container\ContainerInterface;
use rollun\callback\Queues\DeadLetterQueue;

class PurgeQueueFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $queues = [
            $container->get(ConfigProvider::__NAMESPACE__ . 'searchTaskQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'searchDocumentQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'searchPaginationTaskQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'searchPaginationDocumentQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'productTaskQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'productDocumentQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'compatibleTaskQueue'),
            $container->get(ConfigProvider::__NAMESPACE__ . 'compatibleDocumentQueue'),
            $container->get(DeadLetterQueue::class),
            $container->get('pidKillerQueue'),
        ];

        return new PurgeQueue($queues);
    }
}
