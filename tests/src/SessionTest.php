<?php

namespace SP\SeleniumDriver\Test;

use PHPUnit_Framework_TestCase;
use SP\SeleniumDriver\Session;

/**
 * @coversDefaultClass SP\SeleniumDriver\Session
 */
class SessionTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $browser = $this
            ->getMockBuilder('SP\SeleniumDriver\Browser')
            ->disableOriginalConstructor()
            ->getMock();

        $session = new Session($browser);

        $this->assertInstanceOf('SP\Spiderling\BrowserInterface', $session->getBrowser());

        $this->assertSame($browser, $session->getBrowser());
    }

    public function testConstructDefault()
    {
        $session = new Session();

        $this->assertInstanceOf('SP\SeleniumDriver\Browser', $session->getBrowser());
    }
}
