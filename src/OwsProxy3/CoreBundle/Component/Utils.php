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
     * Returns the headers from Request
     * 
     * @param Request $request
     * @return array
     */
    public static function getHeadersFromRequest(Request $request)
    {
        $headers = array();
        foreach ($request->headers->keys() as $key) {
            $value = $request->headers->get($key, null, true);
            if ($value !== null) {
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
     * @return string[]
     */
    public static function getHeadersFromBrowserResponse(MessageInterface $browserResponse)
    {
        $headers = array();
        foreach ($browserResponse->getHeaders() as $headerLine) {
            $parts = explode(':', $headerLine, 2);
            if (count($parts) === 2) {
                $headers[$parts[0]] = ltrim($parts[1]);
            }
        }
        return $headers;
    }

    /**
     * Returns a new array containing only the key => value pairs from $headers where the key
     * does not occur in $namesToRemove. Matching is case insensitive, because HTTP header names
     * are case insensitive.
     *
     * @param string[] $headers
     * @param string[] $namesToRemove
     * @return string[] remaining headers
     */
    public static function filterHeaders($headers, $namesToRemove)
    {
        $namesToRemove = array_map('strtolower', $namesToRemove);
        $filtered = array();
        foreach ($headers as $name => $value) {
            if (!\in_array(strtolower($name), $namesToRemove)) {
                $filtered[$name] = $value;
            }
        }
        return $filtered;
    }

    /**
     * Sets the headers from proxy's browser response into proxy response
     * 
     * @param Response $response
     * @param MessageInterface $browserResponse
     * @deprecated remove in v3.2. Use buzzResponseToResponse or individual header processing methods, depending on needs.
     */
    public static function setHeadersFromBrowserResponse(Response $response,
            MessageInterface $browserResponse)
    {
        $headers = static::getHeadersFromBrowserResponse($browserResponse);
        $headers = static::filterHeaders($headers, array(
            'transfer-encoding',
        ));
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }

    /**
     * Filters the HTTP headers according blacklist / whitelist. Key comparisons use the lower-case version of
     * the input keys, so blacklist / whitelist must also use lower-case keys to work.
     *
     * @param array $headers the HTTP headers
     * @param array $blackList the array with header names to remove
     * @param array $whiteList the array with header names to keep @deprecated
     * @return array the prepared HTTP headers
     */
    public static function prepareHeadersForRequest(array $headers, array $blackList, array $whiteList = null)
    {
        if (func_num_args() >= 3) {
            @trigger_error("Deprecated: whiteList argument to prepareHeadersForRequest is deprecated and will be ignored in v.3.2. Use array_diff to build your blackList properly", E_USER_DEPRECATED);
            $blackList = array_diff(array_map('strtolower', $blackList), array_map('strtolower', $whiteList ?: array()));
        }
        return static::filterHeaders($headers, $blackList);
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
        $headers = static::getHeadersFromBrowserResponse($buzzResponse);
        /**
         * Forward all headers except Transfer-Encoding.
         * Buzz (actually curl) pipes this through even though the content is
         * never chunked and never compressed.
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Transfer-Encoding
         */
        $headers = static::filterHeaders($headers, array(
            'transfer-encoding',
        ));
        $response = new Response($buzzResponse->getContent(), $buzzResponse->getStatusCode(), $headers);
        # TBD: safe to copy protocol version?
        # $response->setProtocolVersion($buzzResponse->getProtocolVersion());
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
     * Remove repeated query params from given url and returns url with repeated params
     * removed. First occurence will remain.
     * NOTE: internal param separator chains will be contracted collaterally. E.g.
     *   "dog&&cat=hat" => "dog&cat=hat"
     *
     * @param string $url
     * @param boolean $caseSensitiveNames
     * @return string
     * @since v3.1.6
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
                // internal chained param separators => strip them
                continue;
            }
            // NOTE: this will also support (and deduplicate) no-value params, e.g.
            // ?one&two&one
            $name = preg_replace('#[=].*$#', '', $pairIn);
            $dedupeKey = $caseSensitiveNames ? $name : strtolower($name);
            if (!array_key_exists($dedupeKey, $paramPairsOut)) {
                $paramPairsOut[$dedupeKey] = $pairIn;
            }
        }
        $dangle = preg_match('/[&?]$/', $url);
        $replacement = '?' . implode('&', $paramPairsOut);
        if ($dangle) {
            if ($paramPairsOut) {
                $replacement .= '&';
            }
        } else {
            $replacement = rtrim($replacement, '?');
        }
        return str_replace('?' . $queryString, $replacement, $url);
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
