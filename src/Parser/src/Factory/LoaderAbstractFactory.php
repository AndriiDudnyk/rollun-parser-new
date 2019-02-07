<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Parser\Factory;

use Interop\Container\ContainerInterface;
use Parser\AbstractLoader;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Config example:
 *
 *  [
 *      LoaderAbstractFactory::class => [
 *          'requestedServiceName1' => [
 *              'class' => ConcreteLoader::class,
 *              'proxyDataStore' => 'proxyDataStoreServiceName',
 *              'documentQueue' => 'documentQueueServiceName',
 *              'clientConfig' => 'clientServiceName',
 *              'validator' => 'validatorServiceName',
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
class LoaderAbstractFactory implements AbstractFactoryInterface
{
    const KEY_CLASS = 'class';

    const BASE_CLASS = AbstractLoader::class;

    const KEY_PROXY_DATASTORE = 'proxyDataStore';

    const KEY_DOCUMENT_QUEUE = 'documentQueue';

    const KEY_CLIENT_CONFIG = 'clientConfig';

    const KEY_VALIDATOR = 'validator';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_PROXY_DATASTORE])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_PROXY_DATASTORE . "'");
        }

        if (!isset($serviceConfig[self::KEY_DOCUMENT_QUEUE])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_DOCUMENT_QUEUE . "'");
        }

        if (!isset($serviceConfig[self::KEY_VALIDATOR])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_VALIDATOR . "'");
        }

        if (!isset($serviceConfig[self::KEY_CLASS])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_CLASS . "'");
        }

        $proxyDataStore = $container->get($serviceConfig[self::KEY_PROXY_DATASTORE]);
        $documentQueue = $container->get($serviceConfig[self::KEY_DOCUMENT_QUEUE]);
        $clientConfig = $serviceConfig[self::KEY_CLIENT_CONFIG] ?? [];
        $validator = $container->get($serviceConfig[self::KEY_VALIDATOR]);
        $class = $serviceConfig[self::KEY_CLASS];

        return new $class($proxyDataStore, $documentQueue, $clientConfig, $validator);
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
