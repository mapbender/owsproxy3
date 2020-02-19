<?php


namespace OwsProxy3\CoreBundle\Component;

use OwsProxy3\CoreBundle\Controller\OwsProxyController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation for service owsproxy.http_foundation_client.
 *
 * Handles ProxyQuery requests and returns Symfony HttpFoundation Requests
 *
 * Does not care about cookies or sessions or signatures.
 * Use this service to replace internal direct usages of CommonProxy.
 * Use this service to replace internal kernel subrequests to
 * @see OwsProxyController::genericProxyAction()
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
        $buzzResponse = $this->handleQueryInternal($query);
        return Utils::buzzResponseToResponse($buzzResponse);
    }
}
