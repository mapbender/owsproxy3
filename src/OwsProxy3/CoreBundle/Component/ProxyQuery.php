<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;
use ArsGeografica\Signing\BadSignatureException;
use ArsGeografica\Signing\Signer;


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
     * @var string the parsed url (PHP parse_url()) without get parameters
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
     * @throws HTTPStatus502Exception if the host is not defined at $url
     */
    public static function createFromUrl($url, $user = null, $password = null,
            $headers = array(), $getParams = array(), $postParams = array(),
            $content = null)
    {
        $rowUrl = parse_url($url);
        if (empty($rowUrl["host"]))
        {
            throw new HTTPStatus502Exception("The host is not defined", 502);
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
     * @throws HTTPStatus502Exception if the host is not defined
     */
    public static function createFromRequest(Request $request)
    {
        $rowUrl = urldecode(Utils::getParamValue($request, Utils::$PARAMETER_URL,
                        Utils::$METHOD_GET, false));
        $rowUrl = parse_url($rowUrl);
        if (empty($rowUrl["host"]))
        {
            throw new HTTPStatus502Exception("The host is not defined", 502);
        }
        $getParams = array();
        if (isset($rowUrl["query"]))
        {
            parse_str($rowUrl["query"], $getParams);
            unset($rowUrl["query"]);
        }

        $allParams = Utils::getAllParams($request);

        if (isset($allParams[Utils::$METHOD_GET]) &&
                isset($allParams[Utils::$METHOD_GET][Utils::$PARAMETER_URL]))
        {
            unset($allParams[Utils::$METHOD_GET][Utils::$PARAMETER_URL]);
        }
        $content    = null;
        $postParams = array();
        if (isset($allParams[Utils::$CONTENT]) || isset($allParams[Utils::$METHOD_POST]))
        {
            $method     = Utils::$METHOD_POST;
            $content    = isset($allParams[Utils::$CONTENT]) ?
                $allParams[Utils::$CONTENT] : $request->getContent();
            $postParams = isset($allParams[Utils::$METHOD_POST]) ?
                    $allParams[Utils::$METHOD_POST] : array();
            // if url containts more get parameters
            if (isset($allParams[Utils::$METHOD_GET]) && count(isset($allParams[Utils::$METHOD_GET]))
                    > 0)
            {
                $postParams = array_merge($postParams,
                        $allParams[Utils::$METHOD_GET]);
            }
        }
        else
        {
            $method        = Utils::$METHOD_GET;
            $getParamshelp = isset($allParams[Utils::$METHOD_GET]) ?
                    $allParams[Utils::$METHOD_GET] : array();
            $getParams     = array_merge($getParams, $getParamshelp);
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
     * Adds a POST parameter
     *
     * @param string $name
     * @param string $value
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
     * Adds a GET parameter
     *
     * @param string $name
     * @param string $value
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
     * Adds a POST/GET parameter
     *
     * @param string $name
     * @param string $value
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
     * @return string url
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
     * Generats the url for HTTP GET
     *
     * @return string the HTTP GET url
     */
    public function getGetUrl()
    {
        $scheme = empty($this->rowUrl["scheme"]) ? "http://" : $this->rowUrl["scheme"] . "://";

        $user = empty($this->rowUrl["user"]) ? "" : $this->rowUrl["user"];
        $pass = empty($this->rowUrl["pass"]) ? "" : $this->rowUrl["pass"];

        if (!empty($pass)) $user .= ":";
        if (!empty($user) || !empty($pass)) $pass .= "@";

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

    public function getServiceType() {
        if($this->hasGetParamValue('service', true)) return $this->getGetParamValue('service', true);
        if($this->hasPostParamValue('service', true)) return $this->getPostParamValue('service', true);

        $dom = new \DOMDocument();
        $dom->loadXML($this->content);
        return $dom->documentElement->getAttribute('service');
    }

}
