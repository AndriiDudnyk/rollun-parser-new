<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types = 1);

namespace AppTest\Handler;

use App\Handler\HomePageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class HomePageHandlerTest extends TestCase
{
    public function testProcess()
    {
        $object = new HomePageHandler();

        /** @var ServerRequestInterface $requestMock */
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $expectedResponse = new HtmlResponse('Home page!');

        $this->assertEquals(
            $expectedResponse->getBody()->getContents(),
            $object->handle($requestMock)->getBody()->getContents()
        );
    }
}
