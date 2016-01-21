<?php

namespace SP\SeleniumDriver\Test;

use PHPUnit_Framework_TestCase;
use SP\SeleniumDriver\Server;
use GuzzleHttp\Psr7\Response;


/**
 * @coversDefaultClass SP\SeleniumDriver\Server
 */
class ServerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $server = new Server(['base_uri' => 'http://127.0.0.1:44412/wd/hub/']);

        $this->assertEquals('http://127.0.0.1:44412/wd/hub/', (string) $server->getClient()->getConfig('base_uri'));
    }

    /**
     * @covers ::newSessionBaseUri
     */
    public function testNewSessionBaseUri()
    {
        $client = $this->getMock('GuzzleHttp\Client');

        $client
            ->expects($this->once())
            ->method('request')
            ->with('post', 'session', ['body' => json_encode(['desiredCapabilities' => ['browserName' => 'firefox']])])
            ->willReturn(new Response(200, [], '{"sessionId":"123123"}'));

        $client
            ->expects($this->once())
            ->method('getConfig')
            ->with('base_uri')
            ->willReturn('http://127.0.0.1:44412/wd/hub/');

        $server = $this->getMockBuilder('SP\SeleniumDriver\Server')
            ->disableOriginalConstructor()
            ->setMethods(['getClient'])
            ->getMock();

        $server
            ->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $base_uri = $server->newSessionBaseUri();

        $this->assertEquals('http://127.0.0.1:44412/wd/hub/session/123123/', (string) $base_uri);
    }

    /**
     * @covers ::newSessionClient
     */
    public function testNewSessionClient()
    {
        $server = $this->getMockBuilder('SP\SeleniumDriver\Server')
            ->disableOriginalConstructor()
            ->setMethods(['newSessionBaseUri'])
            ->getMock();

        $server
            ->expects($this->once())
            ->method('newSessionBaseUri')
            ->willReturn('http://127.0.0.1:44412/wd/hub/session/123123/');

        $client = $server->newSessionClient();

        $this->assertInstanceOf('GuzzleHttp\Client', $client);
        $this->assertEquals('http://127.0.0.1:44412/wd/hub/session/123123/', (string) $client->getConfig('base_uri'));
    }
}
