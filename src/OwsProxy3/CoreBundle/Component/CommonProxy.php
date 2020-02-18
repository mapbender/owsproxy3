<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Paul Schmidt
 */
class CommonProxy extends BuzzClientCommon
{
    /** @var ProxyQuery */
    protected $proxy_query;

    /** @var array */
    protected $proxy_config;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array headernames */
    protected $headerBlackList = array("cookie", "user-agent", "content-length", "referer", "host");

    /** @var array headernames */
    protected $headerWhiteList = array();

    /**
     * The user-agent to send along with proxy requests
     * @var string|null
     */
    protected $userAgent;

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
        $this->proxy_config = $proxy_config;
        $this->proxy_query = $proxy_query;
        $this->logger = $logger ?: new NullLogger();
        if ($headerBlackList !== null) {
            $this->headerBlackList = $headerBlackList;
        }
        if ($headerWhiteList !== null) {
            $this->headerWhiteList = $headerWhiteList;
        }
        $this->userAgent = $userAgent;
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
        return $this->handleQuery($this->proxy_query, $this->proxy_config, $headers);
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }

    /**
     * @return string
     */
    protected function getUserAgent()
    {
        return $this->userAgent ?: parent::getUserAgent();
    }
}
