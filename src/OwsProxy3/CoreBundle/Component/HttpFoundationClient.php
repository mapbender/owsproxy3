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
 */
class HttpFoundationClient extends CurlClientCommon
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

        $ch = $this->openHandle($query);
        $rawResponse = \curl_exec($ch);
        if ($rawResponse !== false) {
            $response = $this->parseResponse($ch, $rawResponse);
        } else {
            $curlError = \curl_error($ch);
            $response = Response::create('');
            $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE, $curlError ?: null);
        }
        \curl_close($ch);
        return $response;
    }

    /**
     * @param resource $ch
     * @param string|false $rawResponse
     * @return Response
     */
    protected function parseResponse($ch, $rawResponse)
    {
        $headerLength = strlen($rawResponse) - \curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $body = substr($rawResponse, $headerLength);
        $response = Response::create($body, \curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $responseHeaders = $this->parseHeaders(substr($rawResponse, 0, $headerLength));
        $responseHeaders =  Utils::filterHeaders($responseHeaders, array(
            'transfer-encoding',
        ));
        $response->headers->add($responseHeaders);
        return $response;
    }

    /**
     * @param ProxyQuery $query
     * @return resource
     */
    protected function openHandle(ProxyQuery $query)
    {
        $options = $this->getCurlOptions($query->getHostName(), $this->proxyParams);
        $headers = $this->prepareHeaders($query);
        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $this->flattenHeaders($headers);
        }
        if ($query->getMethod() === 'POST') {
            $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            $options[CURLOPT_POSTFIELDS] = $query->getContent() ?: '';
        }
        $ch = \curl_init($query->getUrl());
        if ($ch === false) {
            throw new \RuntimeException("Cannot open curl handle");
        }
        \curl_setopt_array($ch, $options);
        return $ch;
    }

    protected function prepareHeaders(ProxyQuery $query)
    {
        $headers = Utils::filterHeaders($query->getHeaders(), array(
            "cookie",
            "user-agent",
            "content-length",
            "referer",
            "host",
        ));
        $headers['User-Agent'] = $this->getUserAgent();

        if ($query->getUsername()) {
            $headers['Authorization'] = 'Basic ' . \base64_encode($query->getUserName() . ':' . $query->getPassword());
        }
        return $headers;
    }

    public static function getCurlOptions($hostName, $config)
    {
        $options = parent::getCurlOptions($hostName, $config);
        $options += array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 3,
        );
        return $options;
    }

    /**
     * @param string $rawHeaders
     * @return string[]
     */
    protected static function parseHeaders($rawHeaders)
    {
        $headers = array();
        foreach (\preg_split('#\\r?\\n#', $rawHeaders) as $i => $line) {
            $line = trim($line);
            if ($line) {
                if ($i === 0 && !\preg_match('#^[\w\d\-_]+:#', $line)) {
                    // = status line ~ "HTTP/1.1 200 OK"
                    continue;
                }
                $parts = \preg_split('#:\s*#', $line, 2);
                $headers[$parts[0]] = $parts[1];
            }
        }
        return $headers;
    }
}
