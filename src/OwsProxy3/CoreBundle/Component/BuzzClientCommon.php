<?php


namespace OwsProxy3\CoreBundle\Component;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Buzz\Middleware\BasicAuthMiddleware;

/**
 * Unbound (use method arguments) portion of pre-bound (use instance attributes) CommonProxy
 * @internal
 * @todo future (break): drop CommonProxy, absorb fully into DI service
 */
class BuzzClientCommon extends CurlClientCommon
{
    /**
     * @param ProxyQuery $query
     * @return Browser
     */
    protected function browserFromQuery(ProxyQuery $query)
    {
        $curl = new Curl();
        $curlOptions = $this->getCurlOptions($query->getHostName(), $this->proxyParams);
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
     * @return Response
     * @throws \Exception
     */
    protected function handleQueryInternal(ProxyQuery $query)
    {
        $stripHeaders = array(
            "cookie",
            "user-agent",
            "content-length",
            "referer",
            "host",
        );
        $headers = Utils::filterHeaders($query->getHeaders(), $stripHeaders);
        $headers['User-Agent'] = $this->getUserAgent();
        $browser = $this->browserFromQuery($query);

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
