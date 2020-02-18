<?php


namespace OwsProxy3\CoreBundle\Component;


use Buzz\Message\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Default implementation for service owsproxy.buzz_client.
 *
 * Handles ProxyQuery reuqests and returns native Buzz Responses.
 *
 * @since v3.1.6
 */
class BuzzClient extends BuzzClientCommon
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
        $this->logger->debug("BuzzClient::handleQuery {$query->getMethod()}", array(
            'url' => $query->getUrl(),
            'headers' => $query->getHeaders(),
        ));
        return $this->handleQueryInternal($query, $this->proxyParams);
    }

    protected function getUserAgent()
    {
        return $this->userAgent;
    }
}
