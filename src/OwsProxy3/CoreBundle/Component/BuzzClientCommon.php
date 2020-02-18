<?php


namespace OwsProxy3\CoreBundle\Component;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Buzz\Middleware\BasicAuthMiddleware;

/**
 * Unbound (use method arguments) portion of pre-bound (use instance attributes) CommonProxy
 * @internal
 * @todo: provide equivalent DI service
 * @todo future (break): drop CommonProxy, absorb fully into DI service
 */
class BuzzClientCommon extends CurlClientCommon
{
    /**
     * @param ProxyQuery $query
     * @param mixed[] $config
     * @return Browser
     */
    protected function browserFromQuery(ProxyQuery $query, array $config)
    {
        $curl = new Curl();
        $curlOptions = $this->getCurlOptions($query->getHostName(), $config);
        foreach ($curlOptions as $optionId => $optionValue) {
            $curl->setOption($optionId, $optionValue);
        }
        $browser = new Browser($curl);
        if ($query->getUsername()) {
            $browser->addMiddleware(new BasicAuthMiddleware($query->getUsername(), $query->getPassword()));
        }

        return $browser;
    }

    /**
     * Handles the request and returns the response.
     *
     * @param ProxyQuery $query
     * @param mixed[] $config
     * @param string[]|null $headers
     * @return Response
     * @throws \Exception
     */
    protected function handleQuery(ProxyQuery $query, $config, $headers = null)
    {
        if ($headers === null) {
            $stripHeaders = array(
                "cookie",
                "user-agent",
                "content-length",
                "referer",
                "host",
            );
            $headers = Utils::prepareHeadersForRequest($query->getHeaders(), $stripHeaders, array());
            $headers['User-Agent'] = $this->getUserAgent();
        }
        $browser = $this->browserFromQuery($query, $config);

        $method = $query->getMethod();
        switch ($method) {
            case 'POST':
                /** @var Response $response */
                $response = $browser->post($query->getUrl(), $headers, $query->getContent());
                return $response;
            case 'GET':
                /** @var Response $response */
                $response = $browser->get($query->getUrl(), $headers);
                return $response;
            default:
                throw new \RuntimeException("Unsupported method {$method}");
        }
    }
}
