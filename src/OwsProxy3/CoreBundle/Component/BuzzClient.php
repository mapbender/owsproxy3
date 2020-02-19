<?php


namespace OwsProxy3\CoreBundle\Component;


use Buzz\Message\Response;

/**
 * Default implementation for service owsproxy.buzz_client.
 *
 * Handles ProxyQuery requests and returns native Buzz Responses.
 *
 * Does not care about cookies or sessions or signatures.
 * Use this service to replace internal direct usages of CommonProxy.
 *
 * @since v3.1.6
 */
class BuzzClient extends BuzzClientCommon
{
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
}
