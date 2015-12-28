<?php

namespace SP\SeleniumDriver\Test;

use PHPUnit_Framework_TestCase;
use SP\SeleniumDriver\Client;
use GuzzleHttp\Psr7\Response;


/**
 * @coversDefaultClass SP\SeleniumDriver\Client
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $client = new Client();

        $this->assertEquals('http://127.0.0.1:4444/wd/hub/', (string) $client->getConfig('base_uri'));
    }

    /**
     * @covers ::newSessionClient
     */
    public function testNewSessionClient()
    {
        $client = $this->getMock('SP\SeleniumDriver\Client', ['postJson']);

        $client
            ->expects($this->once())
            ->method('postJson')
            ->with('session', ['desiredCapabilities' => ['browserName' => 'firefox']])
            ->willReturn(['state' => 'success', 'sessionId' => '379128379']);

        $return = $client->newSessionClient();

        $this->assertInstanceOf('SP\SeleniumDriver\Client', $return);
        $this->assertEquals('http://127.0.0.1:4444/wd/hub/session/379128379/', (string) $return->getConfig('base_uri'));
    }

    /**
     * @covers ::getJson
     * @covers ::parseResponse
     */
    public function testGetJson()
    {
        $client = $this->getMock('SP\SeleniumDriver\Client', ['get']);

        $client
            ->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn(new Response(200, [], '["test","big"]'));

        $return = $client->getJson('test');

        $this->assertEquals(['test', 'big'], $return);
    }

    /**
     * @covers ::deleteJson
     * @covers ::parseResponse
     */
    public function testDeleteJson()
    {
        $client = $this->getMock('SP\SeleniumDriver\Client', ['delete']);

        $client
            ->expects($this->once())
            ->method('delete')
            ->with('test')
            ->willReturn(new Response(200, [], '["test","big"]'));

        $return = $client->deleteJson('test');

        $this->assertEquals(['test', 'big'], $return);
    }

    /**
     * @covers ::postJson
     */
    public function testPostJson()
    {
        $client = $this->getMock('SP\SeleniumDriver\Client', ['post']);

        $client
            ->expects($this->once())
            ->method('post')
            ->with('test', ['body' => json_encode(['value' => 'big'])])
            ->willReturn(new Response(200, [], '["test","big"]'));

        $return = $client->postJson('test', ['value' => 'big']);

        $this->assertEquals(['test', 'big'], $return);
    }
}
