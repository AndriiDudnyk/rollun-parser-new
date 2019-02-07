<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Loader;

use Parser\AbstractLoader;
use Psr\Http\Message\ResponseInterface;

class BaseLoader extends AbstractLoader
{
    const OPTIMAL_TIME_INTERVAL = 3;

    /**
     * Create rating about proxy from 1 to 10
     *
     * @param ResponseInterface $response
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return int
     */
    protected function createRating(
        ?ResponseInterface $response,
        \DateTime $startTime,
        \DateTime $endTime
    ): int {
        if (!$response instanceof ResponseInterface) {
            return 1;
        }

        if (($endTime->getTimestamp() - $startTime->getTimestamp()) < self::OPTIMAL_TIME_INTERVAL) {
            return 6;
        } else {
            return 4;
        }
    }
}
