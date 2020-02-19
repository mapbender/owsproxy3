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

    /**
     * Appends given $params to $url as (additional) query parameters.
     *
     * @param string $url
     * @param string[] $params
     * @return string
     * @since v3.1.6
     */
    public static function appendQueryParams($url, $params)
    {
        $fragmentParts = explode('#', $url, 2);
        if (count($fragmentParts) === 2) {
            return static::appendQueryParams($fragmentParts[0], $params) . '#' . $fragmentParts[1];
        }
        if ($params) {
            $dangle = preg_match('/[&?]$/', $url);
            $url = rtrim($url, '&?');
            $extraQuery = \http_build_query($params);
            if (preg_match('#\?#', $url)) {
                $url = "{$url}&{$extraQuery}";
            } else {
                $url = "{$url}?{$extraQuery}";
            }
            // restore dangling param separator, if input url had it
            if ($dangle) {
                $url .= '&';
            }
        }
        return $url;
    }

    /**
     * Remove repeated query params from given url
     *
     * @param string $url
     * @param boolean $caseSensitiveNames
     * @return string
     * @internal
     */
    public static function filterDuplicateQueryParams($url, $caseSensitiveNames)
    {
        $fragmentParts = explode('#', $url, 2);
        if (count($fragmentParts) === 2) {
            return static::filterDuplicateQueryParams($fragmentParts[0], $caseSensitiveNames) . '#' . $fragmentParts[1];
        }
        $queryString = parse_url($url, PHP_URL_QUERY);
        $paramPairs = explode('&', $queryString);
        $paramPairsOut = array();
        foreach ($paramPairs as $pairIn) {
            if (!$pairIn || $pairIn == '?') {
                // at this stage, we don't need dangling param separators anymore => strip them
                continue;
            }
            $name = preg_replace('#[=].*$#', '', $pairIn);
            $dedupeKey = $caseSensitiveNames ? strtolower($name) : $name;
            if (!array_key_exists($dedupeKey, $paramPairsOut)) {
                $paramPairsOut[$dedupeKey] = $pairIn;
            }
        }
        return str_replace('?' . $queryString, '?' . implode('&', $paramPairsOut), $url);
    }

    /**
     * Inject (or replace) given basic auth credentials into $url.
     *
     * @param string $url
     * @param string $user plain text (unencoded input)
     * @param string $password plain text (unencoded input)
     * @return string
     * @since v3.1.6
     */
    public static function addBasicAuthCredentials($url, $user, $password)
    {
        $fragmentParts = explode('#', $url, 2);
        if ($user && count($fragmentParts) === 2) {
            return static::addBasicAuthCredentials($fragmentParts[0], $user, $password) . '#' . $fragmentParts[1];
        }
        if ($user) {
            $credentialsEnc = implode(':', array(
                rawurlencode($user),
                rawurlencode($password ?: ''),
            ));
            return preg_replace('#(?<=//)([^@]+@)?#', $credentialsEnc . '@', $url, 1);
        } else {
            return $url;
        }
    }

    /**
     * Adds more key-value pairs from $params to given scalar POST content.
     * Returns null ONLY IF input $content is null and $params is empty.
     *
     * @param string|null $content
     * @param string[] $params
     * @return string|null
     * @since v3.1.6
     */
    public static function extendPostContent($content, $params)
    {
        if ($params) {
            return implode('&', array_filter(array(
                $content,
                \http_build_query($params),
            )));
        } else {
            return $content;
        }
    }
}
