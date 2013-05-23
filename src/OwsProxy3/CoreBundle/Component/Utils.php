<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;

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
     */
    public static $METHOD_GET = "GET";

    /**
     *
     * @var string the identifier for HTTP POST
     */
    public static $METHOD_POST = "POST";

    /**
     *
     * @var string the identifier for parameter "url"
     */
    public static $PARAMETER_URL = "url";

    /**
     *
     * @var string the identifier for HTTP POST content
     */
    public static $CONTENT = "CONTENT";

    /**
     * Returns the array  with parameters.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request the request
     * @param string $method  the HTTP method POST/GET
     * (default null -> the method is determined from request)
     * @return array the parameters
     */
    public static function getParams(Request $request, $method = null)
    {
        if($method === null)
        {
            $method = $request->getMethod();
        }
        if(strtoupper($method) === Utils::$METHOD_GET)
        {
            return $request->query->all();
        } else if(strtoupper($method) === Utils::$METHOD_POST)
        {
            return $request->request->all();
        } else
        {
            return null;
        }
    }

    /**
     * Returns the parameter value from request
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request the request
     * @param string $method the HTTP method POST/GET
     * (default null -> the method is determined from request)
     * @param boolean $ignoreCase false if the parameter name is case sensitive
     * (default false)
     * @param string $name the parameter name
     * @return string the parameter value or null
     */
    public static function getParamValue(Request $request, $name,
            $method = null, $ignoreCase = false)
    {
        $params = Utils::getParams($request, $method);
        if($params === null)
        {
            return null;
        }
        if($ignoreCase)
        {
            $name = strtolower($name);
            foreach($params as $key => $value)
            {
                if(strtolower($key) === $name)
                {
                    return urldecode($value);
                }
            }
        } else
        {
            foreach($params as $key => $value)
            {
                if($key === $name)
                {
                    return urldecode($value);
                }
            }
        }
        return null;
    }

    /**
     * Returns the associative array with all parameters 
     * ("GET"=> array(),"POST"=>array(),"CONTENT"=>value).
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array the associative array with all parameters
     */
    public static function getAllParams(Request $request)
    {
        $params = array();
        $get = Utils::getParams($request, Utils::$METHOD_GET);
        if($get !== null && count($get) > 0)
        {
            $params[Utils::$METHOD_GET] = $get;
        }
        $post = Utils::getParams($request, Utils::$METHOD_POST);
        if($post !== null && count($post) > 0)
        {
            $params[Utils::$METHOD_POST] = $post;
        }
        if($request->getContent() !== null && strlen($request->getContent()) > 0)
        {
            $params[Utils::$CONTENT] = $request->getContent();
        }
        return $params;
    }

    /**
     * Returns the parameter value
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request the request
     * @param string $name the parameter name
     * @param boolean $ignoreCase if the parameter name is case sensitive
     *  => false (default false)
     * @return string|null the parameter value
     */
    public static function getParamValueFromAll(Request $request, $name,
            $ignoreCase = false)
    {
        $value = Utils::getParamValue($request, $name, "GET", $ignoreCase);
        if($value === null)
        {
            $value = Utils::getParamValue($request, $name, "POST", $ignoreCase);
        }
        return $value;
    }

    /**
     * Returns the headers from Request
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array the headers
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
     * Returns the headers from BrowserResponse
     * 
     * @param Buzz\Message\MessageInterface $request
     * @return array the headers
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
     * @param Response $response the response
     * @param MessageInterface $browserResponse the browser response
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
     * Prepares the HTTP headers
     * 
     * @param array $headers the HTTP headers
     * @return array the prepared HTTP headers
     */
    public static function prepareHeadersForRequest($headers)
    {
        foreach($headers as $key => $value)
        {
            $lkey = strtolower($key);
            if($lkey === "cookie"
                    || $lkey === "user-agent"
                    || $lkey === "content-length"
                    || $lkey === "referer"
                    || $lkey === "host"){
                unset($headers[$key]);
            }
                
        }
        return $headers;
    }

}