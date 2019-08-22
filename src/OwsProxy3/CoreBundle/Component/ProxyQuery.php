<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;


/**
 * ProxyQuery class provides methods for
 *
 * @author A.R.Pour
 * @author Paul Schmidt
 */
class ProxyQuery
{

    /**
     *
     * @var string[] the parsed url (PHP parse_url()) without get parameters
     */
    protected $urlParts;

    /**
     *
     * @var string HTTP method (GET/POST)
     */
    protected $method;

    /**
     *
     * @var array the GET parameters
     */
    protected $getParams;

    /**
     *
     * @var array the POST parameter
     */
    protected $postParams;

    /**
     *
     * @var string the POST content
     */
    protected $content;

    /**
     *
     * @var array the query headers
     */
    protected $headers;

    /**
     * Creates an instance from parameters
     *
     * @param string $url the url
     * @param string $user the user name for basic authentication
     * @param string $password the user password for basic authentication
     * @param array $headers the HTTP headers
     * @param array $getParams the GET parameters
     * @param array $postParams the POST parameters
     * @param string $content the POST content
     * @return ProxyQuery a new instance
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
            return static::createFromUrl($url, $user, $password, $headers, array(), $postParams, $content);
        }

        if ($user) {
            $credentialsEnc = implode(':', array(
                rawurlencode($user),
                rawurlencode($password ?: ''),
            ));
            $url = preg_replace('#(?<=//)([^@]+@)?#', $credentialsEnc . '@', $url, 1);
            return static::createFromUrl($url, null, null, $headers, array(), $postParams, $content);
        }
        $parts = parse_url($url);
        if (!empty($parts['user'])) {
            $parts['user'] = rawurldecode($parts['user']);
            if (!empty($parts['pass'])) {
                $parts['pass'] = rawurldecode($parts['pass']);
            }
        } else {
            unset($parts['user']);
            unset($parts['pass']);
        }

        $getParams = array();
        if (isset($parts["query"])) {
            parse_str($parts["query"], $getParams);
            unset($parts["query"]);
        }

        if ($content !== null || $postParams) {
            $method = Utils::$METHOD_POST;
        } else {
            $method = Utils::$METHOD_GET;
        }

        return new ProxyQuery($parts, $method, $content, $getParams,
                $postParams, $headers);
    }

    /**
     * Creates an instance
     *
     * @param Request $request
     * @return ProxyQuery a new instance
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
     * Creates an instance
     *
     * @param array $urlParts the parsed url (parse_url()) without "query"
     * @param string $method the GET/POST HTTP method
     * @param string $content the POST content
     * @param array $getParams the GET parameter
     * @param array $postParams the POST parameter
     * @param array $headers the HTTP headers
     */
    private function __construct($urlParts, $method, $content, $getParams,
                                 $postParams, $headers)
    {
        if (empty($urlParts["host"])) {
            throw new \InvalidArgumentException("Missing host name");
        }
        $headers['Host'] = $urlParts['host'];

        $this->urlParts = $urlParts;
        $this->method     = $method;
        $this->content    = $content;
        $this->getParams  = array();
        $this->postParams = array();
        $usedKeys = array();
        foreach ($getParams as $key => $value) {
            $lcKey = strtolower($key);
            if (!in_array($lcKey, $usedKeys)) {
                $this->getParams[$key] = $value;
                $usedKeys[] = $lcKey;
            }
        }
        foreach ($postParams as $key => $value) {
            $lcKey = strtolower($key);
            if (!in_array($lcKey, $usedKeys)) {
                $this->postParams[$key] = $value;
                $usedKeys[] = $lcKey;
            }
        }
        $this->headers = $headers;
    }

    public function getHostname()
    {
        return $this->urlParts['host'];
    }

    /**
     * Returns the query string for POST request
     *
     * @return string the query string for POST request
     */
    public function getPostQueryString()
    {
        return http_build_query($this->postParams);
    }

    /**
     * Returns the POST content
     *
     * @return string content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the GET/POST method
     *
     * @return string method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the row url (without GET parameter)
     * @return string[]
     */
    public function getRowUrl()
    {
        return $this->urlParts;
    }

    public function getUsername()
    {
        if (!empty($this->urlParts['user'])) {
            return $this->urlParts['user'];
        } else {
            return null;
        }
    }

    public function getPassword()
    {
        if (!empty($this->urlParts['pass'])) {
            return $this->urlParts['pass'];
        } else {
            return null;
        }
    }

    /**
     * Returns the headers
     *
     * @return array headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Generats the url for HTTP GET
     *
     * @return string the HTTP GET url
     */
    public function getGetUrl()
    {
        $scheme = empty($this->urlParts["scheme"]) ? "http://" : $this->urlParts["scheme"] . "://";
        $user   = empty($this->urlParts["user"]) ? "" : $this->urlParts["user"];
        $pass   = empty($this->urlParts["pass"]) ? "" : $this->urlParts["pass"];

        // if pass is there, put a : between user and pass (user:pass)
        if (!empty($pass)) {
            $user =  rawurlencode($user) .  ":";
        }

        // if user and password are there, put a @ after pass, so that user:pass@host will be constructed
        if (!empty($user) || !empty($pass)) {
            $pass = rawurlencode($pass) . "@";
        }

        $host = $this->urlParts["host"];
        $port = empty($this->urlParts["port"]) ? "" : ":" . $this->urlParts["port"];

        $path = empty($this->urlParts["path"]) ? "" : $this->urlParts["path"];

        $urlquery = "";
        if (count($this->getParams) > 0)
        {
            $urlquery = "?" . http_build_query($this->getParams);
        }
        return $scheme . $user . $pass . $host . $port . $path . $urlquery;
    }
}
