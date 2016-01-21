<?php

namespace SP\SeleniumDriver\Test;

use SP\DriverTest\BrowserDriverTest;
use SP\SeleniumDriver\Browser;
use SP\SeleniumDriver\Server;

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

        $server = new Server(['base_uri' => $base_uri]);

        $capabilities = [
            'browserName' => 'firefox',
        ];

        if (getenv('TRAVIS_JOB_NUMBER')) {
            $capabilities['tunnel-identifier'] = getenv('TRAVIS_JOB_NUMBER');
        }

        $driver = new Browser($server->newSessionClient($capabilities));

        self::setDriver($driver);
    }
}
