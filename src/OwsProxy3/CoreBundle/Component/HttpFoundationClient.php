<?php


namespace OwsProxy3\CoreBundle\Component;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation for service owsproxy.http_foundation_client.
 *
 * Handles ProxyQuery reuqests and returns Symfony HttpFoundation Requests
 *
 * @since v3.1.6
 * @todo: resolve obvious copy&paste with BuzzClient
 * @todo: eliminate Buzz usage
 */
class HttpFoundationClient extends BuzzClientCommon
{
    /** @var array */
    protected $proxyParams;
    /** @var string */
    protected $userAgent;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(array $proxyParams, $userAgent, LoggerInterface $logger = null)
    {
        if (empty($proxyParams['host'])) {
            $proxyParams = array();
        }
        $this->proxyParams = $proxyParams;
        $this->userAgent = $userAgent;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Handles the request and returns the response.
     *
     * @param ProxyQuery $query
     * @return Response
     * @throws \Exception
     */
    public function handleQuery(ProxyQuery $query)
    {
        $this->logger->debug("HttpFoundationClient::handleQuery {$query->getMethod()}", array(
            'url' => $query->getUrl(),
            'headers' => $query->getHeaders(),
        ));
        $buzzResponse = $this->handleQueryInternal($query, $this->proxyParams);
        $response = new Response();
        Utils::setHeadersFromBrowserResponse($response, $buzzResponse);
        $response->setContent($buzzResponse->getContent());
        $response->setStatusCode($buzzResponse->getStatusCode(), $buzzResponse->getReasonPhrase() ?: null);
        return $response;
    }

    protected function getUserAgent()
    {
        return $this->userAgent;
    }
}
