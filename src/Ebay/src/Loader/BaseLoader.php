<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Loader;

use Psr\Http\Message\ResponseInterface;
use rollun\parser\AbstractLoader;

class BaseLoader extends AbstractLoader
{
    const OPTIMAL_TIME_INTERVAL = 3;
    const FILE_EXTENSION = '.html';

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

    /**
     * Create filepath 'tmp-directory-in-your-system/documents/some-hash' directory of OS
     *
     * Example for linux:
     * '/tmp/documents/queue-name/ec28346356cd2e430f58d523bcf937a05c5954d731c83'
     *
     * @return string
     */
    protected function createFilename()
    {
        $storageDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::STORAGE_DIR;

        if (!file_exists($storageDir)) {
            throw new \RuntimeException("Directory '$storageDir' doesn't exist");
        }

        $dirName = $storageDir . DIRECTORY_SEPARATOR . $this->documentQueue->getName();

        if (!file_exists($dirName)) {
            mkdir($dirName);
        }

        $filename = microtime(true);

        return $dirName . DIRECTORY_SEPARATOR . $filename . self::FILE_EXTENSION;
    }
}
