<?php

namespace SP\SeleniumDriver\Test;

use SP\DriverTest\BrowserDriverTest;
use SP\SeleniumDriver\Browser;
use SP\SeleniumDriver\Client;

class IntegrationTest extends BrowserDriverTest
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $driver = new Browser();

        self::setDriver($driver);
    }
}
