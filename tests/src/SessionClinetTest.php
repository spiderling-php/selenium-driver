<?php

namespace SP\SeleniumDriver\Test;

use PHPUnit_Framework_TestCase;
use SP\SeleniumDriver\SessionClient;
use GuzzleHttp\Psr7\Response;


/**
 * @coversDefaultClass SP\SeleniumDriver\SessionClient
 */
class SessionClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $client = new SessionClient('http://127.0.0.1:4444/wd/hub/session/3213123');

        $this->assertEquals('http://127.0.0.1:4444/wd/hub/session/3213123', (string) $client->getConfig('base_uri'));
    }

    /**
     * @covers ::parseResponse
     */
    public function testParseResponse()
    {
        $client = $this->getMockBuilder('SP\SeleniumDriver\SessionClient')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $client
            ->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn(new Response(200, [], '{"value":"big"}'));

        $return = $client->getJson('test');

        $this->assertEquals('big', $return);
    }
}
