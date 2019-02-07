<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace test\functional\Ebay;

use Ebay\Loader\ResponseValidator\StatusOk;
use Parser\AbstractLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use rollun\callback\PidKiller\Worker;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\QueueClient;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use test\functional\Ebay\Assets\RollunParser;
use Zend\Http\Client;

class WorkflowTest extends TestCase
{
    protected function setUp()
    {
        if (!file_exists('/tmp/documents')) {
            mkdir('/tmp/documents');
        }
    }

    protected function tearDown()
    {
        $this->rrmdir('/tmp/documents');
        $this->rrmdir('/tmp/test');
    }

    public function testWorkflowWithoutWorkers()
    {
        $uri = 'https://rollun.com/';
        $proxyManagerUri = getenv('PROXY_MANAGER_URI');
        $proxyDataStore = new HttpClient(new Client(), $proxyManagerUri);

        $documentQueue = new QueueClient(new FileAdapter('/tmp/test'), 'test');
        $client = new \GuzzleHttp\Client();
        $validator = new StatusOk();
        $loader = new AbstractLoader($proxyDataStore, $documentQueue, $client, $validator);

        do {
            $continue = false;

            try {
                $loader(['uri' => $uri]);
            } catch (\Throwable $e) {
                $continue = true;
            }
        } while ($continue);

        /** @var DataStoresInterface|MockObject $parseResultDsMock */
        $parseResultDsMock = $this->getMockBuilder(DataStoresInterface::class)->getMock();
        $parseResultDsMock->expects($this->once())->method('create')->with(['About Us']);
        $parser = new RollunParser($parseResultDsMock);

        $worker = new Worker($documentQueue, $parser, null);
        $worker();
        $this->assertTrue(true);
    }

    protected function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
