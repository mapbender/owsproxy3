<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\Response;
use Psr\Log\LoggerInterface;

/**
 * @author Paul Schmidt
 * @deprecated for excessive constructor bindings; prefer owsproxy.http_foundation_client service for Symfony-style Response
 * @todo v3.3: remove.
 */
class CommonProxy extends BuzzClientCommon
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
     * @return Response
     * @throws \Exception
     */
    public function handle()
    {
        $this->logger->debug("CommonProxy->handle {$this->proxy_query->getMethod()}", array(
            'url' => $this->proxy_query->getUrl(),
            'headers' => $this->proxy_query->getHeaders(),
        ));
        return $this->handleQueryInternal($this->proxy_query);
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }
}
