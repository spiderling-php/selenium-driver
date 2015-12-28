<?php

namespace SP\SeleniumDriver;

use SP\Spiderling\BrowserSession;

/**
 * @author    Ivan Kerin <ikerin@gmail.com>
 * @copyright 2015, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Session extends BrowserSession
{
    public function __construct(Browser $browser = null)
    {
        parent::__construct($browser ?: new Browser());
    }
}
