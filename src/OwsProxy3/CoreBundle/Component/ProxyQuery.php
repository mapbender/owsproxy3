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
    protected $rowUrl;

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
        $rowUrl = parse_url($url);
        if (empty($rowUrl["host"])) {
            throw new \InvalidArgumentException("Missing host name");
        }
        if ($user !== null)
        {
            $rowUrl["user"] = $user;
            $rowUrl["pass"] = $password === null ? "" : $password;
        }
        $getParamsHelp = array();
        if (isset($rowUrl["query"]))
        {
            parse_str($rowUrl["query"], $getParamsHelp);
            unset($rowUrl["query"]);
        }
        $getParams = array_merge($getParamsHelp, $getParams);
        $method    = Utils::$METHOD_GET;
        if ($content !== null || count($postParams) > 0)
        {
            $method = Utils::$METHOD_POST;
        }

        $headers['Host'] = $rowUrl['host'];

        return new ProxyQuery($rowUrl, $method, $content, $getParams,
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
        $rowUrl = parse_url($request->query->get(Utils::$PARAMETER_URL));
        if (empty($rowUrl["host"])) {
            throw new \InvalidArgumentException("Missing host name");
        }
        $getParams = array();
        if (isset($rowUrl["query"]))
        {
            parse_str($rowUrl["query"], $getParams);
            unset($rowUrl["query"]);
        }
        $extraGetParams = $request->query->all();
        unset($extraGetParams[Utils::$PARAMETER_URL]);

        $content    = $request->getContent() ?: null;
        $postParams = $request->request->all();
        if ($content || $postParams) {
            $method     = Utils::$METHOD_POST;
            // if url containts more get parameters
            $postParams = array_merge($postParams, $extraGetParams);
        } else {
            $method = Utils::$METHOD_GET;
            $getParams = array_merge($getParams, $extraGetParams);
        }
        $headers = Utils::getHeadersFromRequest($request);

        $headers['Host'] = $rowUrl['host'];

        return new ProxyQuery($rowUrl, $method, $content, $getParams,
                $postParams, $headers);
    }

    /**
     * Creates an instance
     *
     * @param array $rowUrl the parsed url (parse_url()) without "query"
     * @param string $method the GET/POST HTTP method
     * @param string $content the POST content
     * @param array $getParams the GET parameter
     * @param array $postParams the POST parameter
     * @param array $headers the HTTP headers
     */
    private function __construct($rowUrl, $method, $content, $getParams,
            $postParams, $headers)
    {
        $this->rowUrl     = $rowUrl;
        $this->method     = $method;
        $this->content    = $content;
        $this->getParams  = array();
        $this->postParams = array();
        foreach ($getParams as $key => $value)
        {
            if (!$this->hasGetPostParamValue($key, true))
            {
                $this->getParams[$key] = $value;
            }
        }
        foreach ($postParams as $key => $value)
        {
            if (!$this->hasGetPostParamValue($key, true))
            {
                $this->postParams[$key] = $value;
            }
        }
        $this->headers = $headers;
    }

    /**
     * Adds a POST parameter if not already present
     *
     * @param string $name
     * @param string $value
     * @return boolean true if added false if not
     */
    public function addPostParameter($name, $value)
    {
        if (!$this->hasGetPostParamValue($name, true))
        {
            $this->postParams[$name] = $value;
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Adds a GET parameter if not already present
     *
     * @param string $name
     * @param string $value
     * @return boolean true if added false if not
     */
    public function addGetParameter($name, $value)
    {
        if (!$this->hasGetPostParamValue($name, true))
        {
            $this->getParams[$name] = $value;
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Adds a POST/GET parameter, depending on method, if it's not already present
     *
     * @param string $name
     * @param string $value
     * @return boolean|null true if added, false if not added, null for unsupported method
     */
    public function addQueryParameter($name, $value)
    {
        if ($this->method === Utils::$METHOD_GET)
        {
            if (!$this->hasGetPostParamValue($name, true))
            {
                $this->addGetParameter($name, $value);
                return true;
            }
            else
            {
                return false;
            }
        }
        else if ($this->method === Utils::$METHOD_POST)
        {
            if (!$this->hasGetPostParamValue($name, true))
            {
                $this->addPostParameter($name, $value);
                return true;
            }
            else
            {
                return false;
            }
        }
        return null;
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
        return $this->rowUrl;
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
     * Sets the headers
     * @param string[] key => value
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }
    
    /**
     * Appends a header, doesn't support keys.
     *
     * @internal
     * @deprecated
     * @param mixed $header
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;
    }
    
    /**
     * Generats the url for HTTP GET
     *
     * @return string the HTTP GET url
     */
    public function getGetUrl()
    {
        $scheme = empty($this->rowUrl["scheme"]) ? "http://" : $this->rowUrl["scheme"] . "://";
        $user   = empty($this->rowUrl["user"]) ? "" : $this->rowUrl["user"];
        $pass   = empty($this->rowUrl["pass"]) ? "" : $this->rowUrl["pass"];

        // if pass is there, put a : between user and pass (user:pass)
        if (!empty($pass)) {
            $user =  rawurlencode($user) .  ":";
        }

        // if user and password are there, put a @ after pass, so that user:pass@host will be constructed
        if (!empty($user) || !empty($pass)) {
            $pass = rawurlencode($pass) . "@";
        }

        $host = $this->rowUrl["host"];
        $port = empty($this->rowUrl["port"]) ? "" : ":" . $this->rowUrl["port"];

        $path = empty($this->rowUrl["path"]) ? "" : $this->rowUrl["path"];

        $urlquery = "";
        if (count($this->getParams) > 0)
        {
            $urlquery = "?" . http_build_query($this->getParams);
        }
        return $scheme . $user . $pass . $host . $port . $path . $urlquery;
    }

    /**
     * Returns the parameter value from GET/POST parameters
     *
     * @param string $name the parameter name
     * @param boolean $ignoreCase to ignore the parameter name case sensitivity
     * @return string|null the parameter value or null
     */
    public function getGetPostParamValue($name, $ignoreCase = false)
    {
        $param = $this->getGetParamValue($name, $ignoreCase);
        if ($param !== null)
        {
            return $param;
        }
        else
        {
            return $this->getPostParamValue($name, $ignoreCase);
        }
    }

    /**
     * Returns the parameter value from GET parameters
     *
     * @param string $name the parameter name
     * @param boolean $ignoreCase to ignore the parameter name case sensitivity
     * @return string|null the parameter value or null
     */
    public function getGetParamValue($name, $ignoreCase = false)
    {
        if ($ignoreCase)
        {
            $name = strtolower($name);
            foreach ($this->getParams as $key => $value)
            {
                if (strtolower($key) === $name)
                {
                    return $value;
                }
            }
        }
        else
        {
            foreach ($this->getParams as $key => $value)
            {
                if ($key === $name)
                {
                    return $value;
                }
            }
        }
        return null;
    }

    /**
     * Returns the parameter value from POST parameters
     *
     * @param string $name the parameter name
     * @param boolean $ignoreCase  to ignore the parameter name case sensitivity
     * @return string|null the parameter value or null
     */
    public function getPostParamValue($name, $ignoreCase = false)
    {
        if ($ignoreCase)
        {
            $name = strtolower($name);
            foreach ($this->postParams as $key => $value)
            {
                if (strtolower($key) === $name)
                {
                    return $value;
                }
            }
        }
        else
        {
            foreach ($this->postParams as $key => $value)
            {
                if ($key === $name)
                {
                    return $value;
                }
            }
        }
        return null;
    }

    /**
     * Removes a GET parameter.
     * @param string $name parameter name
     * @return boolean true if parameter removed
     */
    public function removeGetParameter($name)
    {
        if (isset($this->getParams[$name]))
        {
            unset($this->getParams[$name]);
            return true;
        }
        else return false;
    }

    /**
     * Removes a POST parameter.
     * @param string $name parameter name
     * @return boolean true if parameter removed
     */
    public function removePostParameter($name)
    {
        if (isset($this->postParams[$name]))
        {
            unset($this->postParams[$name]);
            return true;
        }
        else return false;
    }

    /**
     * Checks if a GET/POST parameter exists
     *
     * @param string $name the parameter name
     * @param boolean $ignoreCase to ignore the parameter name case sensitivity
     * @return boolean true if a parameter exists
     */
    public function hasGetPostParamValue($name, $ignoreCase = false)
    {
        $param = $this->hasGetParamValue($name, $ignoreCase);
        if ($param !== null)
        {
            return $param;
        }
        else
        {
            return $this->hasPostParamValue($name, $ignoreCase);
        }
    }

    /**
     * Checks if a GET parameter exists
     *
     * @param string $name the parameter name
     * @param boolean $ignoreCase to ignore the parameter name case sensitivity
     * @return boolean true if a parameter exists
     */
    public function hasGetParamValue($name, $ignoreCase = false)
    {
        if ($ignoreCase)
        {
            $name = strtolower($name);
            foreach ($this->getParams as $key => $value)
            {
                if (strtolower($key) === $name)
                {
                    return true;
                }
            }
        }
        else
        {
            foreach ($this->getParams as $key => $value)
            {
                if ($key === $name)
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks if a POST parameter exists
     *
     * @param string $name the parameter name
     * @param boolean $ignoreCase  to ignore the parameter name case sensitivity
     * @return boolean true if a parameter exists
     */
    public function hasPostParamValue($name, $ignoreCase = false)
    {
        if ($ignoreCase)
        {
            $name = strtolower($name);
            foreach ($this->postParams as $key => $value)
            {
                if (strtolower($key) === $name)
                {
                    return true;
                }
            }
        }
        else
        {
            foreach ($this->postParams as $key => $value)
            {
                if ($key === $name)
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function getServiceType()
    {
        $type = $this->getGetParamValue('service', true);
        $type = $type ?: $this->getPostParamValue('service', true);
        return $type ?: null;
    }
}
