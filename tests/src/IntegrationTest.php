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

        $base_uri = sprintf(
            'http://%s:%s@localhost:4445/wd/hub/',
            getenv('SAUCE_USERNAME'),
            getenv('SAUCE_ACCESS_KEY')
        );

        $client = new Client(['base_uri' => $base_uri]);

        $driver = new Browser($client->newSessionClient());

        self::setDriver($driver);
    }
}
