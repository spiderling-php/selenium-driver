<?php

namespace SP\SeleniumDriver;

use SP\Spiderling\BrowserInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SP\Spiderling\Query;
use GuzzleHttp\Psr7\Uri;
use SP\Attempt\Attempt;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;

/**
 * @author    Ivan Kerin <ikerin@gmail.com>
 * @copyright 2015, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Browser implements BrowserInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getJson(ResponseInterface $response)
    {
        $json = json_decode($response->getBody()->getContents(), true);

        return isset($json['value']) ? $json['value'] : null;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getAlertText()
    {
        return $this->getJson($this->client->request('get', 'alert_text'));
    }

    /**
     * @param  string $confirm
     */
    public function confirm($confirm)
    {
        if ($confirm) {
            $this->client->request('post', 'accept_alert');
        } else {
            $this->client->request('post', 'dismiss_alert');
        }
    }

    public function removeAllCookies()
    {
        $this->client->request('delete', 'cookie');
    }

    /**
     * @param  string $javascript
     * @return mixed
     */
    public function executeJs($javascript)
    {
        $data = [
            'script' => $javascript,
            'args' => [],
        ];

        return $this->getJson($this->client->request('post', 'execute', ['body' => json_encode($data)]));
    }

    /**
     * @param  string $id
     */
    public function moveMouseTo($id)
    {
        $this->client->request('post', 'moveto', ['body' => json_encode(['element' => $id])]);
    }

    /**
     * @param  string $file
     */
    public function saveScreenshot($file)
    {
        $data = $this->getJson($this->client->request('get', 'screenshot'));

        file_put_contents($file, base64_decode($data));
    }


    /**
     * @param  UriInterface $uri
     */
    public function open(UriInterface $uri)
    {
        $this->client->request('post', 'url', ['body' => json_encode(['url' => (string) $uri])]);
    }

    /**
     * @return UriInterface
     */
    public function getUri()
    {
        $uri = $this->getJson($this->client->request('get', 'url'));

        return new Uri(urldecode($uri));
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getText($id)
    {
        $text = $this->getJson($this->client->request('get', "element/{$id}/text"));
        $text = preg_replace('/[ \s\f\n\r\t\v ]+/u', ' ', $text);

        return trim($text);
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getTagName($id)
    {
        return $this->getJson($this->client->request('get', "element/{$id}/name"));
    }

    /**
     * @param  string $id
     * @param  string $name
     * @return string
     */
    public function getAttribute($id, $name)
    {
        return $this->getJson($this->client->request('get', "element/{$id}/attribute/{$name}"));
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getHtml($id)
    {
        $data = [
            'script' => 'return arguments[0].outerHTML',
            'args' => [['ELEMENT' => $id]],
        ];

        return $this->getJson($this->client->request('post', 'execute', ['body' => json_encode($data)]));
    }

    /**
     * @return string
     */
    public function getFullHtml()
    {
        return $this->getJson($this->client->request('get', 'source'));
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getValue($id)
    {
        $type = $this->getJson($this->client->request('get', "element/{$id}/name"));

        if ($type === 'textarea') {
            return $this->getJson($this->client->request('get', "element/{$id}/text"));
        } else {
            return $this->getJson($this->client->request('get', "element/{$id}/attribute/value"));
        }
    }

    /**
     * @param  string $id
     * @return boolean
     */
    public function isVisible($id)
    {
        return $this->getJson($this->client->request('get', "element/{$id}/displayed"));
    }

    /**
     * @param  string $id
     * @return boolean
     */
    public function isSelected($id)
    {
        return $this->getJson($this->client->request('get', "element/{$id}/selected"));
    }

    /**
     * @param  string $id
     * @return boolean
     */
    public function isChecked($id)
    {
        return $this->getJson($this->client->request('get', "element/{$id}/selected"));
    }

    /**
     * @param  string $id
     * @param  mixed  $value
     */
    public function setValue($id, $value)
    {
        $this->client->request('post', "element/{$id}/clear");
        $this->client->request('post', "element/{$id}/value", ['body' => json_encode(['value' => str_split($value)])]);
    }

    /**
     * @param  string $id
     * @param  mixed  $file
     */
    public function setFile($id, $file)
    {
        $this->client->request('post', "element/{$id}/value", ['body' => json_encode(['value' => str_split($file)])]);
    }

    /**
     * @param  string $id
     */
    public function click($id)
    {
        $this->client->request('post', "element/{$id}/click");
    }

    /**
     * @param  string $id
     */
    public function select($id)
    {
        $this->client->request('post', "element/{$id}/click");
    }

    /**
     * Convert a selenium element to an id
     *
     * @param  array $element
     * @return string
     */
    public function convertElement(array $element)
    {
        return $element['ELEMENT'];
    }

    /**
     * @param  Query\AbstractQuery $query
     * @return array
     */
    public function getElementIds(Query\AbstractQuery $query)
    {
        $data = ['using' => 'xpath', 'value' => '.'.$query->getXPath()];

        $elements = $this->getJson($this->client->request('post', 'elements', ['body' => json_encode($data)]));

        return array_map([$this, 'convertElement'], $elements);
    }

    /**
     * @param  Query\AbstractQuery $query
     * @param  string              $parentId
     * @return array
     */
    public function getChildElementIds(Query\AbstractQuery $query, $parentId)
    {
        $data = ['using' => 'xpath', 'value' => '.'.$query->getXPath()];
        $response = $this->client->request('post', "element/{$parentId}/elements", ['body' => json_encode($data)]);
        $elements = $this->getJson($response);

        return array_map([$this, 'convertElement'], $elements);
    }

    /**
     * @param  Query\AbstractQuery $query
     * @param  string              $parentId
     * @return array
     */
    public function queryIds(Query\AbstractQuery $query, $parentId = null)
    {
        $attempt = new Attempt(function() use ($query, $parentId) {
            $ids = $parentId === null
                ? $this->getElementIds($query)
                : $this->getChildElementIds($query, $parentId);

            return $query->getFilters()->matchAll($this, (array) $ids);
        });

        return $attempt->execute();
    }
}
