<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;


/**
 * @author A.R.Pour
 * @author Paul Schmidt
 */
class ProxyQuery
{
    /** @var string */
    protected $url;

    /** @var array */
    protected $getParams;

    /** @var string|null the POST content or null on GET requests */
    protected $content;

    /** @var array */
    protected $headers;

    /**
     * Creates an instance from parameters
     *
     * @param string $url
     * @param string $user the user name for basic authentication
     * @param string $password the user password for basic authentication
     * @param array $headers
     * @param array $getParams
     * @param array $postParams
     * @param string $content for POST
     * @return ProxyQuery
     * @throws \InvalidArgumentException for invalid url
     */
    public static function createFromUrl($url, $user = null, $password = null,
            $headers = array(), $getParams = array(), $postParams = array(),
            $content = null)
    {
        // strip fragment
        $url = preg_replace('/#.*$/', '', $url);
        $url = rtrim($url, '&?');
        if ($getParams) {
            $extraQuery = \http_build_query($getParams);
            if (preg_match('#\?#', $url)) {
                $url = "{$url}&{$extraQuery}";
            } else {
                $url = "{$url}?{$extraQuery}";
            }
        }

        if ($user) {
            $credentialsEnc = implode(':', array(
                rawurlencode($user),
                rawurlencode($password ?: ''),
            ));
            $url = preg_replace('#(?<=//)([^@]+@)?#', $credentialsEnc . '@', $url, 1);
        }

        if ($postParams) {
            if ($content) {
                $content .= '&';
            }
            $content .= \http_build_query($postParams);
        }

        return new ProxyQuery($url, $content, $headers);
    }

    /**
     * Creates an instance from a Symfony Request
     *
     * @param Request $request
     * @param string|null $forwardUrlParamName
     * @return ProxyQuery
     * @throws \InvalidArgumentException for invalid url
     */
    public static function createFromRequest(Request $request, $forwardUrlParamName = null)
    {
        if (!$forwardUrlParamName) {
            @trigger_error("Deprecated: " . __CLASS__ . '::' . __METHOD__ . ': expects explicit specification of "url" query parameter name', E_USER_DEPRECATED);
            $forwardUrlParamName = 'url';
        }
        $url = $request->query->get($forwardUrlParamName);
        $extraGetParams = $request->query->all();
        unset($extraGetParams[$forwardUrlParamName]);
        $headers = Utils::getHeadersFromRequest($request);
        if ($request->getMethod() === 'POST') {
            $content = $request->getContent();
        } else {
            $content = null;
        }
        return static::createFromUrl($url, null, null, $headers, $extraGetParams, array(), $content);
    }

    /**
     * @param string $url
     * @param string|null $content for POST
     * @param array $headers
     */
    private function __construct($url, $content, $headers)
    {
        $parts = parse_url($url);
        if (empty($parts["host"])) {
            throw new \InvalidArgumentException("Missing host name");
        }
        $this->headers = array_replace($headers, array(
            'Host' => $parts['host'],
        ));

        $this->getParams = array();
        if (isset($parts["query"])) {
            parse_str($parts["query"], $this->getParams);
            // legacy quirk: filter repeated get params that differ only in case (first occurrence stays)
            $usedKeys = array();
            foreach ($this->getParams as $key => $value) {
                $lcKey = strtolower($key);
                if (in_array($lcKey, $usedKeys)) {
                    unset($this->getParams[$key]);
                    $url = rtrim(preg_replace('#(?<=[&?])' . preg_quote($key, '#') . '[^&]*(&|$)#', '', $url), '&?');
                } else {
                    $usedKeys[] = $lcKey;
                }
            }
        }
        $this->url = $url;
        $this->content = $content;
    }

    public function getHostname()
    {
        return \parse_url($this->url, PHP_URL_HOST);
    }

    /**
     * Returns the POST content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the GET/POST method
     *
     * @return string
     */
    public function getMethod()
    {
        if ($this->content !== null) {
            return 'POST';
        } else {
            return 'GET';
        }
    }

    /**
     * Returns most url parts (as per parse_url) minus 'query'
     * @return string[]
     * @deprecated for weird wording, low utility / complexity ratio; just use the url
     * @todo v3.2: remove
     */
    public function getRowUrl()
    {
        $parts = \parse_url($this->url);
        unset($parts['query']);
        if (empty($parts['user'])) {
            unset($parts['user']);
            unset($parts['pass']);
        } else {
            $parts['user'] = rawurldecode($parts['user']);
            if (isset($parts['pass'])) {
                $parts['pass'] = rawurldecode($parts['pass']);
            } else {
                $parts['pass'] = '';
            }
        }

        return $parts;
    }

    public function getUsername()
    {
        return rawurldecode(\parse_url($this->url, PHP_URL_USER) ?: '') ?: null;
    }

    public function getPassword()
    {
        if (\parse_url($this->url, PHP_URL_USER)) {
            return rawurldecode(\parse_url($this->url, PHP_URL_PASS) ?: '');
        } else {
            return null;
        }
    }

    /**
     * Returns the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the url
     *
     * @return string
     * @deprecated alias for getUrl; URLs don't depend on HTTP methods
     * @todo v3.2: remove
     */
    public function getGetUrl()
    {
        return $this->url;
    }
}
