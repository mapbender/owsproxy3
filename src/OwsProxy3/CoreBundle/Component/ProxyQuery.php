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

    /** @var string HTTP method (GET/POST) */
    protected $method;

    /** @var array */
    protected $getParams;

    /** @var string the POST content */
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
            $method = Utils::$METHOD_POST;
        } elseif ($content !== null) {
            $method = Utils::$METHOD_POST;
        } else {
            $method = Utils::$METHOD_GET;
        }

        return new ProxyQuery($url, $method, $content, $headers);
    }

    /**
     * Creates an instance from a Symfony Request
     *
     * @param Request $request
     * @return ProxyQuery
     * @throws \InvalidArgumentException for invalid url
     */
    public static function createFromRequest(Request $request)
    {
        $url = $request->query->get(Utils::$PARAMETER_URL);
        $extraGetParams = $request->query->all();
        unset($extraGetParams[Utils::$PARAMETER_URL]);
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
     * @param string $method
     * @param string $content for POST
     * @param array $headers
     */
    private function __construct($url, $method, $content, $headers)
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

        $this->method     = $method;
        $this->content    = $content;
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
        return $this->method;
    }

    /**
     * Returns most url parts (as per parse_url) minus 'query'
     * @return string[]
     * @deprecated for weird wording, low utility / complexity ratio; just use the url
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
     */
    public function getGetUrl()
    {
        return $this->url;
    }
}
