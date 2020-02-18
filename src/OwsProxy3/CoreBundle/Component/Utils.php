<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Utils class with help functions
 *
 * @author Paul Schmidt
 */
class Utils
{

    /**
     *
     * @var string the identifier for HTTP GET
     * @todo 3.2: no usages, remove
     */
    public static $METHOD_GET = "GET";

    /**
     *
     * @var string the identifier for HTTP POST
     * @todo 3.2: no usages, remove
     */
    public static $METHOD_POST = "POST";

    /**
     *
     * @var string the identifier for parameter "url"
     * @todo 3.2: no usages, remove
     */
    public static $PARAMETER_URL = "url";

    /**
     * Returns the headers from Request
     * 
     * @param Request $request
     * @return array
     */
    public static function getHeadersFromRequest(Request $request)
    {
        $headers = array();
        foreach($request->headers as $key => $value)
        {
            if(isset($key) && isset($value) && isset($value[0]))
            {
                $headers[$key] = $value[0];
            }
        }
        return $headers;
    }

    /**
     * Returns the headers from BrowserResponse; converts Buzz-format "Name: Value" single strings
     * into a "Name" => "Value" mapping.
     *
     * @param MessageInterface $browserResponse
     * @return array
     */
    public static function getHeadersFromBrowserResponse(MessageInterface $browserResponse)
    {
        $newheaders = array();
        $headers = $browserResponse->getHeaders();
        foreach($headers as $header)
        {
            $pos = stripos($header, ":");
            if(is_int($pos))
            {
                $newheaders[substr($header, 0, $pos)] = substr($header, $pos + 1);
            }
        }
        return $newheaders;
    }

    /**
     * Sets the headers from proxy's browser response into proxy response
     * 
     * @param Response $response
     * @param MessageInterface $browserResponse
     */
    public static function setHeadersFromBrowserResponse(Response $response,
            MessageInterface $browserResponse)
    {
        $heasers_resp = Utils::getHeadersFromBrowserResponse($browserResponse);
        foreach($heasers_resp as $key => $value)
        {
            if(strtolower($key) !== "transfer-encoding"){
                $response->headers->set($key, $value);
            }
        }
    }

    /**
     * Filters the HTTP headers according blacklist / whitelist. Key comparisons use the lower-case version of
     * the input keys, so blacklist / whitelist must also use lower-case keys to work.
     *
     * @param array $headers the HTTP headers
     * @param array $blackList the array with header names to remove MUST BE LOWER CASE TO BE EFFECTIVE
     * @param array $whiteList the array with header names to remove MUST BE LOWER CASE TO BE EFFECTIVE
     * @return array the prepared HTTP headers
     */
    public static function prepareHeadersForRequest(array $headers, array $blackList, array $whiteList)
    {
        foreach ($headers as $key => $value) {
            $lkey = strtolower($key);
            if (in_array($lkey, $blackList) && !in_array($lkey, $whiteList)) {
                unset($headers[$key]);
            }
        }
        return $headers;
    }

    /**
     * Convert a Buzz Response to a Symfony HttpFoundation Response.
     *
     * Preserves original status code and message.
     *
     * Note: We do not do anything about cookies here. Buzz\Response::getHeaders() DOES NOT return received cookies.
     *       When using the Mapbender host as a proxy, none of the upstream cookies have any meaning to begin with,
     *       as they are for a domain the client never talked to directly. As such, cookie handling of any form
     *       is redundant for the proxy use case via Buzz.
     *
     * @param \Buzz\Message\Response $buzzResponse
     * @return Response
     */
    public static function buzzResponseToResponse($buzzResponse)
    {
        // adapt header formatting: Buzz uses a flat list of lines, HttpFoundation expects a name: value mapping
        $headers = array();
        foreach ($buzzResponse->getHeaders() as $headerLine) {
            $parts = explode(':', $headerLine, 2);
            /**
             * Forward all headers except Transfer-Encoding.
             * Buzz (actually curl) pipes this through even though the content is
             * never chunked and never compressed.
             * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Transfer-Encoding
             */
            if (count($parts) == 2 && strtolower($parts[0]) != 'transfer-encoding') {
                $headers[$parts[0]] = $parts[1];
            }
        }
        $response = new Response($buzzResponse->getContent(), $buzzResponse->getStatusCode(), $headers);
        $response->setProtocolVersion($buzzResponse->getProtocolVersion());
        $statusText = $buzzResponse->getReasonPhrase();
        if ($statusText) {
            $response->setStatusCode($buzzResponse->getStatusCode(), $statusText);
        }
        return $response;
    }
}
