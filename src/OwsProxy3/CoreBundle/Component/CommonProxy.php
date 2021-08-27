<?php

namespace OwsProxy3\CoreBundle\Component;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Paul Schmidt
 * @deprecated for excessive constructor bindings; prefer owsproxy.http_foundation_client service for Symfony-style Response
 * @todo v3.3: remove.
 */
class CommonProxy extends HttpFoundationClient
{
    /** @var ProxyQuery */
    protected $proxy_query;

    /**
     * Creates a common proxy
     *
     * @param array $proxy_config
     * @param ProxyQuery $proxy_query
     * @param LoggerInterface|null $logger
     */
    public function __construct(array $proxy_config, ProxyQuery $proxy_query, $logger = null)
    {
        @trigger_error("Deprecated: CommonProxy is deprecated since v3.1.6 and will be removed in v3.3. Use owsproxy.owsproxy.http_foundation_client service instead (returns PSR7 / Symfony response)", E_USER_DEPRECATED);
        parent::__construct($proxy_config, null, $logger);
        $this->proxy_query = $proxy_query;
    }

    /**
     * Handles the request and returns the response.
     *
     * @return \Buzz\Message\Response
     * @throws \Exception
     */
    public function handle()
    {
        $this->logger->debug("CommonProxy->handle {$this->proxy_query->getMethod()}", array(
            'url' => $this->proxy_query->getUrl(),
            'headers' => $this->proxy_query->getHeaders(),
        ));
        return $this->toBuzz($this->handleQueryInternal($this->proxy_query));
    }

    /**
     * @param Response $response
     * @return \Buzz\Message\Response
     */
    protected static function toBuzz(Response $response)
    {
        $statusCode = $response->getStatusCode();
        if (!empty(Response::$statusTexts[$statusCode])) {
            $statusText = Response::$statusTexts[$statusCode];
        } else {
            $statusText = 'Unknown status';
        }
        $statusLine = "HTTP/{$response->getProtocolVersion()} {$statusCode} {$statusText}";
        $headers = array_merge(array($statusLine), static::flattenHeaders($response->headers->all()));
        $buzzResponse = new \Buzz\Message\Response();
        $buzzResponse->addHeaders($headers);
        $buzzResponse->setContent($response->getContent() ?: '');
        return $buzzResponse;
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }
}
