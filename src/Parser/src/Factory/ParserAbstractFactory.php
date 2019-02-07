<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Parser\Factory;

use Interop\Container\ContainerInterface;
use Parser\AbstractParser;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Config example:
 *
 *  [
 *      LoaderAbstractFactory::class => [
 *          'requestedServiceName1' => [
 *              'class' => Product::class,
 *              'parseResultDatastore' => 'parseResultDatastoreServiceName',
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
class ParserAbstractFactory implements AbstractFactoryInterface
{
    const KEY_CLASS = 'class';

    const BASE_CLASS = AbstractParser::class;

    const KEY_PARSER_RESULT_DATASTORE = 'parseResultDatastore';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_PARSER_RESULT_DATASTORE])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_PARSER_RESULT_DATASTORE . "'");
        }

        if (!isset($serviceConfig[self::KEY_CLASS])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_CLASS . "'");
        }

        $parseResultDatastore = $container->get($serviceConfig[self::KEY_PARSER_RESULT_DATASTORE]);
        $class = $serviceConfig[self::KEY_CLASS];

        return new $class($parseResultDatastore);
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
