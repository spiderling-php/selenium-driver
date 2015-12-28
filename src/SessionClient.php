<?php

namespace SP\SeleniumDriver;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use InvalidArgumentException;

/**
 * @author    Ivan Kerin <ikerin@gmail.com>
 * @copyright 2015, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class SessionClient extends Client
{
    public function __construct($baseSessionUri, array $config = [])
    {
        parent::__construct(
            array_merge(
                $config,
                ['base_uri' => $baseSessionUri]
            )
        );
    }

    public function parseResponse(ResponseInterface $response)
    {
        $data = parent::parseResponse($response);

        return $data['value'];
    }
}
