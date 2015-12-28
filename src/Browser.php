<?php

namespace SP\SeleniumDriver;

use SP\Spiderling\BrowserInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use SP\Spiderling\Query;
use GuzzleHttp\Psr7\Uri;
use SP\Attempt\Attempt;
use GuzzleHttp\Psr7\Request;

/**
 * @author    Ivan Kerin <ikerin@gmail.com>
 * @copyright 2015, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Browser implements BrowserInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(SessionClient $client = null)
    {
        $this->client = $client ?: (new Client())->newSessionClient();
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
        return $this->client->getJson('alert_text');
    }

    /**
     * @param  string $confirm
     */
    public function confirm($confirm)
    {
        if ($confirm) {
            $this->client->postJson('accept_alert');
        } else {
            $this->client->postJson('dismiss_alert');
        }
    }

    public function removeAllCookies()
    {
        $this->client->deleteJson('cookie');
    }

    /**
     * @param  string $javascript
     * @return mixed
     */
    public function executeJs($javascript)
    {
        return $this->client->postJson('execute', [
            'script' => $javascript,
            'args' => [],
        ]);
    }

    /**
     * @param  string $id
     */
    public function moveMouseTo($id)
    {
        $this->client->postJson("moveto", ['element' => $id]);
    }

    /**
     * @param  string $file
     */
    public function saveScreenshot($file)
    {
        $data = $this->client->getJson('screenshot');

        file_put_contents($file, base64_decode($data));
    }


    /**
     * @param  UriInterface $uri
     */
    public function open(UriInterface $uri)
    {
        $this->client->postJson('url', ['url' => (string) $uri]);
    }

    /**
     * @return UriInterface
     */
    public function getUri()
    {
        return new Uri(urldecode($this->client->getJson('url')));
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getText($id)
    {
        $text = $this->client->getJson("element/{$id}/text");
        $text = preg_replace('/[ \s\f\n\r\t\v ]+/u', ' ', $text);

        return trim($text);
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getTagName($id)
    {
        return $this->client->getJson("element/{$id}/name");
    }

    /**
     * @param  string $id
     * @param  string $name
     * @return string
     */
    public function getAttribute($id, $name)
    {
        return $this->client->getJson("element/{$id}/attribute/{$name}");
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getHtml($id)
    {
        return $this->client->postJson('execute', [
            'script' => 'return arguments[0].outerHTML',
            'args' => [['ELEMENT' => $id]],
        ]);
    }

    /**
     * @return string
     */
    public function getFullHtml()
    {
        return $this->client->getJson('source');
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getValue($id)
    {
        $type = $this->client->getJson("element/{$id}/name");

        if ($type === 'textarea') {
            return $this->client->getJson("element/{$id}/text");
        } else {
            return $this->client->getJson("element/{$id}/attribute/value");
        }
    }

    /**
     * @param  string $id
     * @return boolean
     */
    public function isVisible($id)
    {
        return $this->client->getJson("element/{$id}/displayed");
    }

    /**
     * @param  string $id
     * @return boolean
     */
    public function isSelected($id)
    {
        return $this->client->getJson("element/{$id}/selected");
    }

    /**
     * @param  string $id
     * @return boolean
     */
    public function isChecked($id)
    {
        return $this->client->getJson("element/{$id}/selected");
    }

    /**
     * @param  string $id
     * @param  mixed  $value
     */
    public function setValue($id, $value)
    {
        $this->client->postJson("element/{$id}/clear", []);
        $this->client->postJson("element/{$id}/value", ['value' => str_split($value)]);
    }

    /**
     * @param  string $id
     * @param  mixed  $file
     */
    public function setFile($id, $file)
    {
        $this->client->postJson("element/{$id}/value", ['value' => str_split($file)]);
    }

    /**
     * @param  string $id
     */
    public function click($id)
    {
        $this->client->postJson("element/{$id}/click");
    }

    /**
     * @param  string $id
     */
    public function select($id)
    {
        $this->client->postJson("element/{$id}/click");
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
        $elements = $this->client->postJson(
            'elements',
            ['using' => 'xpath', 'value' => '.'.$query->getXPath()]
        );

        return array_map([$this, 'convertElement'], $elements);
    }

    /**
     * @param  Query\AbstractQuery $query
     * @param  string              $parentId
     * @return array
     */
    public function getChildElementIds(Query\AbstractQuery $query, $parentId)
    {
        $elements = $this->client->postJson(
            "element/{$parentId}/elements",
            ['using' => 'xpath', 'value' => '.'.$query->getXPath()]
        );

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
