<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Loader;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Queues\Message;

class CompatibleLoader extends BaseLoader
{
    const OPTIMAL_TIME_INTERVAL = 3;
    const FILE_EXTENSION = '.json';

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

    protected function saveDocument(ResponseInterface $response, ServerRequestInterface $request)
    {
        $uri = $request->getUri()->__toString();

        if (preg_match('/(\d+)$/', $uri, $matches) === false || !$matches[1]) {
            throw new \InvalidArgumentException("Can't parse item id from compatible uri");
        }

        $storageDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::STORAGE_DIR;

        if (!file_exists($storageDir)) {
            throw new \RuntimeException("Directory '$storageDir' doesn't exist");
        }

        $dirName = $storageDir . DIRECTORY_SEPARATOR . $this->documentQueue->getName();

        if (!file_exists($dirName)) {
            mkdir($dirName);
        }

        $itemId = $matches[1];
        $filename = $itemId . '-' . microtime(true);
        $filename =  $dirName . DIRECTORY_SEPARATOR . $filename . self::FILE_EXTENSION;

        try {
            $data = $response->getBody()->getContents();
            file_put_contents($filename, $data);
            $message = Message::createInstance(QueueFiller::serializeMessage(['filepath' => $filename]));
            $this->documentQueue->addMessage($message);
        } catch (\Throwable $t) {
            throw new \RuntimeException("Error when trying save document", 0, $t);
        }
    }
}
