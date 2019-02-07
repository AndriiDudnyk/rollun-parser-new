<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Parser\Factory;

use Interop\Container\ContainerInterface;
use Parser\TaskSource;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Config example:
 *
 *  [
 *      LoaderAbstractFactory::class => [
 *          'requestedServiceName1' => [
 *              'class' => Product::class,
 *              'queue' => 'queueServiceName',
 *              'config' => [
 *                  [
 *                      'uri' => 'site://example.com',
 *                      // ...
 *                  ],
 *                  [
 *                      // ...
 *                  ],
 *                  // ...
 *              ],
 *          ],
 *          'requestedServiceName2' => [
 *              // ...
 *          ],
 *      ]
 *  ]
 *
 * Class AbstractLoaderFactory
 * @package Ebay\Parser\Factory
 */
class TaskSourceAbstractFactory implements AbstractFactoryInterface
{
    const KEY_CLASS = 'class';

    const BASE_CLASS = TaskSource::class;

    const KEY_QUEUE = 'queue';

    const KEY_CONFIG = 'config';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_QUEUE])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_QUEUE . "'");
        }

        if (!isset($serviceConfig[self::KEY_CONFIG])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_CONFIG . "'");
        }

        if (!isset($serviceConfig[self::KEY_CLASS])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_CLASS . "'");
        }

        $queue = $container->get($serviceConfig[self::KEY_QUEUE]);
        $config = $serviceConfig[self::KEY_CONFIG];
        $class = $serviceConfig[self::KEY_CLASS];

        return new $class($queue, $config);
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $class = $container->get('config')[self::class][$requestedName][self::KEY_CLASS] ?? null;

        if (is_a($class, self::BASE_CLASS, true)) {
            return true;
        }

        return false;
    }
}
