<?php


namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Response;

/**
 * Unbound (use method arguments) portion of pre-bound (use instance attributes) CommonProxy
 * @internal
 */
class BuzzClientCommon extends HttpFoundationClient
{
    /**
     * Handles the request and returns the response.
     *
     * @param ProxyQuery $query
     * @return \Buzz\Message\Response
     * @throws \Exception
     */
    protected function handleQueryInternal(ProxyQuery $query)
    {
        return $this->toBuzz(parent::handleQuery($query));
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
}
