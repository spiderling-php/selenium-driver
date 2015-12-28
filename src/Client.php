<?php

namespace SP\SeleniumDriver;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * @author    Ivan Kerin <ikerin@gmail.com>
 * @copyright 2015, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Client extends GuzzleClient
{
    public function __construct(array $config = array())
    {
        parent::__construct(
            array_merge(
                ['base_uri' => 'http://127.0.0.1:4444/wd/hub/'],
                $config
            )
        );
    }

    public function newSessionClient(array $desiredCapabilities = [])
    {
        $options = $desiredCapabilities
            ? ['desiredCapabilities' => $desiredCapabilities]
            : ['desiredCapabilities' => ['browserName' => 'firefox']];

        $session = $this->postJson('session', $options);
        $baseSessionUri = $this->getConfig('base_uri')."session/{$session['sessionId']}/";

        return new SessionClient($baseSessionUri, $this->getConfig());
    }

    public function parseResponse(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param  string $uri
     */
    public function deleteJson($uri)
    {
        return $this->parseResponse($this->delete($uri));
    }

    /**
     * @param  string $uri
     */
    public function getJson($uri)
    {
        return $this->parseResponse($this->get($uri));
    }

    /**
     * @param  string $uri
     * @param  string $value
     */
    public function postJson($uri, $value = null)
    {
        $options = $value ? ['body' => json_encode($value)] : [];

        return $this->parseResponse($this->post($uri, $options));
    }
}
