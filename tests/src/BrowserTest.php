<?php

namespace SP\SeleniumDriver\Test;

use PHPUnit_Framework_TestCase;
use SP\SeleniumDriver\Browser;
use GuzzleHttp\Psr7\Uri;
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
        $this->client = $this
            ->getMockBuilder('SP\SeleniumDriver\SessionClient')
            ->disableOriginalConstructor()
            ->getMock();

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
            ->method('deleteJson')
            ->with('cookie');

        $this->driver->removeAllCookies();
    }

    /**
     * @covers ::getAlertText
     */
    public function testGetAlertText()
    {
        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with('alert_text')
            ->willReturn('alert...');

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
            ->method('postJson')
            ->with('accept_alert');

        $this->client
            ->expects($this->at(1))
            ->method('postJson')
            ->with('dismiss_alert');

        $this->driver->confirm(true);
        $this->driver->confirm(false);
    }

    /**
     * @covers ::getUri
     */
    public function testGetUri()
    {
        $expected = new Uri('http://example.com');

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with('url')
            ->willReturn((string) $expected);

        $result = $this->driver->getUri();

        $this->assertEquals($expected, $result);
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
            ->method('postJson')
            ->with($uri);

        call_user_func_array([$this->driver, $method], $params);
    }


    /**
     * @covers ::executeJs
     */
    public function testExecuteJs()
    {
        $expected = 'return value';

        $this->client
            ->expects($this->once())
            ->method('postJson')
            ->with('execute', ['script' => 'console.log("a")', 'args' => []])
            ->willReturn($expected);

        $result = $this->driver->executeJs('console.log("a")');

        $this->assertEquals($expected, $result);
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
            ->method('postJson')
            ->with($uri, $value)
            ->willReturn([['ELEMENT' => 3], ['ELEMENT' => 6]]);

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
            ->method('postJson')
            ->with('url', ['url' => 'http://example.com']);

        $this->driver->open(new Uri('http://example.com'));
    }

    /**
     * @covers ::setValue
     */
    public function testSetValue()
    {
        $this->client
            ->expects($this->at(0))
            ->method('postJson')
            ->with('element/3/clear');

        $this->client
            ->expects($this->at(1))
            ->method('postJson')
            ->with('element/3/value', ['value' => ['n', 'e', 'w']]);

        $this->driver->setValue(3, 'new');
    }

    /**
     * @covers ::setFile
     */
    public function testSetFile()
    {
        $this->client
            ->expects($this->once())
            ->method('postJson')
            ->with('element/3/value', ['value' => ['f', 'i', 'l', 'e', '.', 'j', 'p', 'g']]);

        $this->driver->setFile(3, 'file.jpg');
    }

    /**
     * @covers ::getValue
     */
    public function testGetValueTextarea()
    {
        $this->client
            ->expects($this->at(0))
            ->method('getJson')
            ->with('element/3/name')
            ->willReturn('textarea');

        $this->client
            ->expects($this->at(1))
            ->method('getJson')
            ->with('element/3/text')
            ->willReturn('some text');


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
            ->method('getJson')
            ->with('element/5/name')
            ->willReturn('input');

        $this->client
            ->expects($this->at(1))
            ->method('getJson')
            ->with('element/5/attribute/value')
            ->willReturn('some input text');

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
            ->method('postJson')
            ->with(
                'execute',
                [
                    'script' => 'return arguments[0].outerHTML',
                    'args' => [['ELEMENT' => 4]],
                ]
            )
            ->willReturn('text html');

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
            ->method('getJson')
            ->willReturn(base64_encode(file_get_contents(__FILE__)));

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
        $expected = 'some value';

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with($uri)
            ->willReturn($expected);

        $result = call_user_func_array([$this->driver, $method], $params);

        $this->assertEquals($expected, $result);
    }
}
