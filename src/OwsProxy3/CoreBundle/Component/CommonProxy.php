<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\Response;
use Psr\Log\LoggerInterface;

/**
 * @author Paul Schmidt
 * @deprecated for excessive constructor bindings; prefer using owsproxy.buzz_client service for Buzz response; prefer owsproxy.http_foundation_client service for Symfony-style Response
 * @todo v3.3: remove.
 */
class CommonProxy extends BuzzClientCommon
{
    /** @var ProxyQuery */
    protected $proxy_query;

    /** @var array headernames */
    protected $headerBlackList = array("cookie", "user-agent", "content-length", "referer", "host");

    /** @var array headernames */
    protected $headerWhiteList = array();

    /**
     * Creates a common proxy
     *
     * @param array $proxy_config
     * @param ProxyQuery $proxy_query
     * @param LoggerInterface|null $logger
     * @param string[]|null $headerBlackList omit for defaults
     * @param string[]|null $headerWhiteList omit for defaults
     * @param string $userAgent
     * @todo v3.2: remove user agent argument
     * @todo v3.2: remove header blacklist / whitelist arguments
     */
    public function __construct(array $proxy_config, ProxyQuery $proxy_query, $logger = null, $headerBlackList = null,
        $headerWhiteList = null, $userAgent = 'OWSProxy3')
    {
        @trigger_error("Deprecated: CommonProxy is deprecated since v3.1.6 and will be removed in v3.3. Use owsproxy.buzz_client service instead", E_USER_DEPRECATED);
        if (func_num_args() >= 4) {
            @trigger_error("Deprecated: constructor arguments headerBlackList, headerWhiteList and userAgent are deprecated since v3.1.6 and will be ignored in v3.2", E_USER_DEPRECATED);
        }
        parent::__construct($proxy_config, $userAgent, $logger);
        $this->proxy_query = $proxy_query;
        if ($headerBlackList !== null) {
            $this->headerBlackList = $headerBlackList;
        }
        if ($headerWhiteList !== null) {
            $this->headerWhiteList = $headerWhiteList;
        }
    }

    /**
     * Handles the request and returns the response.
     *
     * @return Response
     * @throws \Exception
     */
    public function handle()
    {
        $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
            $this->headerWhiteList);
        $headers['User-Agent'] = $this->getUserAgent();

        $this->logger->debug("CommonProxy->handle {$this->proxy_query->getMethod()}", array(
            'url' => $this->proxy_query->getUrl(),
            'headers' => $headers,
        ));
        return $this->handleQueryInternal($this->proxy_query, $this->proxyParams, $headers);
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }
}
