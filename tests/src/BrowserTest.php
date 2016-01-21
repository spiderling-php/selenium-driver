<?php

namespace SP\SeleniumDriver\Test;

use PHPUnit_Framework_TestCase;
use SP\SeleniumDriver\Browser;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Response;
use SP\Spiderling\Query;

/**
 * @coversDefaultClass SP\SeleniumDriver\Browser
 */
class BrowserTest extends PHPUnit_Framework_TestCase
{
    private $driver;
    private $server;
    private $client;

    public function setUp()
    {
        $this->client = $this->getMock('GuzzleHttp\ClientInterface');
        $this->driver = new Browser($this->client);
    }

    /**
     * @covers ::__construct
     * @covers ::getClient
     */
    public function testConstruct()
    {
        $driver = new Browser($this->client);

        $this->assertSame($this->client, $driver->getClient());
    }

    /**
     * @covers ::removeAllCookies
     */
    public function testRemoveAllCookies()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('delete', 'cookie');

        $this->driver->removeAllCookies();
    }

    /**
     * @covers ::getAlertText
     */
    public function testGetAlertText()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('get', 'alert_text')
            ->willReturn(new Response(200, [], '{"value":"alert..."}'));

        $result = $this->driver->getAlertText();

        $this->assertEquals('alert...', $result);
    }

    /**
     * @covers ::confirm
     */
    public function testConfirm()
    {
        $this->client
            ->expects($this->at(0))
            ->method('request')
            ->with('post', 'accept_alert');

        $this->client
            ->expects($this->at(1))
            ->method('request')
            ->with('post', 'dismiss_alert');

        $this->driver->confirm(true);
        $this->driver->confirm(false);
    }

    /**
     * @covers ::getUri
     */
    public function testGetUri()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('get', 'url')
            ->willReturn(new Response(200, [], '{"value":"http:\/\/example.com"}'));

        $result = $this->driver->getUri();

        $this->assertEquals(new Uri('http://example.com'), $result);
    }

    public function dataActions()
    {
        return [
            ['click', [12], 'element/12/click'],
            ['select', [11], 'element/11/click'],
            ['moveMouseTo', [13], 'moveto'],
        ];
    }

    /**
     * @dataProvider dataActions
     * @covers ::click
     * @covers ::select
     * @covers ::moveMouseTo
     */
    public function testActions($method, $params, $uri)
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('post', $uri);

        call_user_func_array([$this->driver, $method], $params);
    }


    /**
     * @covers ::executeJs
     */
    public function testExecuteJs()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('post', 'execute', ['body' => '{"script":"console.log(\"a\")","args":[]}'])
            ->willReturn(new Response(200, [], '{"value":"return value"}'));

        $result = $this->driver->executeJs('console.log("a")');

        $this->assertEquals('return value', $result);
    }


    public function dataQuerySelectors()
    {
        return [
            'Query without parent' => [
                new Query\Css('#test'),
                null,
                'elements',
                ['using' => 'xpath', 'value' => './/*[@id = \'test\']']
            ],
            'Query with parent node' => [
                new Query\Css('#me'),
                12,
                'element/12/elements',
                ['using' => 'xpath', 'value' => './/*[@id = \'me\']']
            ],
        ];
    }

    /**
     * @dataProvider dataQuerySelectors
     * @covers ::convertElement
     * @covers ::queryIds
     * @covers ::getElementIds
     * @covers ::getChildElementIds
     */
    public function testQuerySelectors($query, $parent, $uri, $value)
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('post', $uri, ['body' => json_encode($value)])
            ->willReturn(new Response(200, [], '{"value":[{"ELEMENT":3},{"ELEMENT":6}]}'));

        $result = $this->driver->queryIds($query, $parent);

        $this->assertEquals([3, 6], $result);
    }

    /**
     * @covers ::open
     */
    public function testOpen()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('post', 'url', ['body' => '{"url":"http:\/\/example.com"}']);

        $this->driver->open(new Uri('http://example.com'));
    }

    /**
     * @covers ::setValue
     */
    public function testSetValue()
    {
        $this->client
            ->expects($this->at(0))
            ->method('request')
            ->with('post', 'element/3/clear');

        $this->client
            ->expects($this->at(1))
            ->method('request')
            ->with('post', 'element/3/value', ['body' => '{"value":["n","e","w"]}']);

        $this->driver->setValue(3, 'new');
    }

    /**
     * @covers ::setFile
     */
    public function testSetFile()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('post', 'element/3/value', ['body' => '{"value":["f","i","l","e",".","j","p","g"]}']);

        $this->driver->setFile(3, 'file.jpg');
    }

    /**
     * @covers ::getValue
     */
    public function testGetValueTextarea()
    {
        $this->client
            ->expects($this->at(0))
            ->method('request')
            ->with('get', 'element/3/name')
            ->willReturn(new Response(200, [], '{"value":"textarea"}'));

        $this->client
            ->expects($this->at(1))
            ->method('request')
            ->with('get', 'element/3/text')
            ->willReturn(new Response(200, [], '{"value":"some text"}'));

        $result = $this->driver->getValue(3);

        $this->assertEquals('some text', $result);
    }

    /**
     * @covers ::getValue
     */
    public function testGetValueInput()
    {
        $this->client
            ->expects($this->at(0))
            ->method('request')
            ->with('get', 'element/5/name')
            ->willReturn(new Response(200, [], '{"value":"input"}'));

        $this->client
            ->expects($this->at(1))
            ->method('request')
            ->with('get', 'element/5/attribute/value')
            ->willReturn(new Response(200, [], '{"value":"some input text"}'));

        $result = $this->driver->getValue(5);

        $this->assertEquals('some input text', $result);
    }

    /**
     * @covers ::getHtml
     */
    public function testGetHtml()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'post',
                'execute',
                ['body' => '{"script":"return arguments[0].outerHTML","args":[{"ELEMENT":4}]}']
            )
            ->willReturn(new Response(200, [], '{"value":"text html"}'));

        $result = $this->driver->getHtml(4);

        $this->assertEquals('text html', $result);
    }

    /**
     * @covers ::saveScreenshot
     */
    public function testSaveScreenshot()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'screenshot');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('get', 'screenshot')
            ->willReturn(new Response(200, [], json_encode(['value' => base64_encode(file_get_contents(__FILE__))])));

        $this->driver->saveScreenshot($tempFile);

        $this->assertFileEquals(__FILE__, $tempFile);

        unlink($tempFile);
    }

    public function dataGetters()
    {
        return [
            'getFullHtml' => ['getFullHtml', [], 'source'],
            'getText' => ['getText', [1], 'element/1/text'],
            'getTagName' => ['getTagName', [2], 'element/2/name'],
            'getAttribute' => ['getAttribute', [3, 'href'], 'element/3/attribute/href'],
            'isVisible' => ['isVisible', [6], 'element/6/displayed'],
            'isSelected' => ['isSelected', [7], 'element/7/selected'],
            'isChecked' => ['isChecked', [8], 'element/8/selected'],
        ];
    }

    /**
     * @dataProvider dataGetters
     * @covers ::getFullHtml
     * @covers ::getText
     * @covers ::getTagName
     * @covers ::getAttribute
     * @covers ::isVisible
     * @covers ::isSelected
     * @covers ::isChecked
     */
    public function testElementGetters($method, $params, $uri)
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('get', $uri)
            ->willReturn(new Response(200, [], '{"value":"some value"}'));

        $result = call_user_func_array([$this->driver, $method], $params);

        $this->assertEquals('some value', $result);
    }
}
