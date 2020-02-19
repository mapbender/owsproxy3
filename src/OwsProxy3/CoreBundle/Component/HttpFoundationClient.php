<?php


namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation for service owsproxy.http_foundation_client.
 *
 * Handles ProxyQuery requests and returns Symfony HttpFoundation Requests
 *
 * @since v3.1.6
 * @todo: eliminate Buzz usage
 */
class HttpFoundationClient extends BuzzClientCommon
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
}
