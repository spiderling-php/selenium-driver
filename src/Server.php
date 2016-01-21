<?php

namespace SP\SeleniumDriver;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * @author    Ivan Kerin <ikerin@gmail.com>
 * @copyright 2015, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Server
{
    /**
     * @var GuzzleHttp\Client
     */
    private $client;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->client = new Client(
            array_merge(
                ['base_uri' => 'http://127.0.0.1:4444/wd/hub/'],
                $config
            )
        );
    }

    /**
     * @return GuzzleHttp\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param  array  $desiredCapabilities
     * @return string
     */
    public function newSessionBaseUri(array $desiredCapabilities = [])
    {
        $options = $desiredCapabilities
            ? ['desiredCapabilities' => $desiredCapabilities]
            : ['desiredCapabilities' => ['browserName' => 'firefox']];

        $client = $this->getClient();

        $response = $client->request('post', 'session', ['body' => json_encode($options)]);
        $json = json_decode($response->getBody()->getContents(), true);

        if (empty($json['sessionId'])) {
            throw new RuntimeException(sprintf(
                'No "sessionId" response. Maybe %s is not a valid selenium server',
                $client->getConfig('base_uri')
            ));
        }

        return $client->getConfig('base_uri')."session/{$json['sessionId']}/";
    }

    /**
     * @param  array  $desiredCapabilities
     * @return GuzzleHttp\Client
     */
    public function newSessionClient(array $desiredCapabilities = [])
    {
        return new Client(['base_uri' => $this->newSessionBaseUri($desiredCapabilities)]);
    }
}
