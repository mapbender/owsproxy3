<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\MessageInterface;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;

/**
 * CommonProxy class for 
 *
 * @author Paul Schmidt
 */
class CommonProxy
{

    /**
     *
     * @var ContainerInterface the container
     */
    protected $proxy_query;

    /**
     *
     * @var array the proxy configuration
     */
    protected $proxy_config;
    
    protected $logger;

    /**
     * Creates a common proxy
     * 
     * @param array $proxy_config the proxy configuration
     * @param ContainerInterface $container
     */
    public function __construct(array $proxy_config, ProxyQuery $proxy_query, $logger = null)
    {
        $this->proxy_config = $proxy_config;
        $this->proxy_query = $proxy_query;
        $this->logger = $logger;
    }

    /**
     * Creates the browser
     * 
     * 
     * @return \Buzz\Browser
     */
    protected function createBrowser()
    {
        if($this->logger !== null){
            $this->logger->debug("CommonProxy->createBrowser rowUrl:" . $this->proxy_query->getRowUrl());
        }
        $rowUrl = $this->proxy_query->getRowUrl();
        $proxy_config = $this->proxy_config;
        $curl = new Curl();
        $curl->setOption(CURLOPT_TIMEOUT, 60);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
        if($proxy_config !== null && $proxy_config['timeout'] !== null)
        {
            $curl->setOption(CURLOPT_TIMEOUT, $proxy_config['timeout']);
        }
        if($proxy_config !== null && $proxy_config['connecttimeout'] !== null)
        {
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, $proxy_config['connecttimeout']);
        }
        if($proxy_config !== null && $proxy_config['host'] !== null
                && !in_array($rowUrl['host'], $proxy_config['noproxy']))
        {
            $curl->setOption(CURLOPT_PROXY, $proxy_config['host']);
            $curl->setOption(CURLOPT_PROXYPORT, $proxy_config['port']);
            if($proxy_config['user'] !== null && $proxy_config['password'] !== null)
            {
                $curl->setOption(CURLOPT_PROXYUSERPWD,
                        $proxy_config['user'] . ':' . $proxy_config['password']);
            }
        }
        return new Browser($curl);
    }

    /**
     * Handles the request and returns the response.
     * 
     * @return MessageInterface the browser response
     * @throws Exception\HTTPStatus502Exception
     */
    public function handle()
    {
        $browser = $this->createBrowser();
        try
        {
            if($this->proxy_query->getMethod() === Utils::$METHOD_POST)
            {
                if($this->proxy_query->getContent() !== null)
                {
                    $content = $this->proxy_query->getContent();
                } else
                {
                    $content = $this->proxy_query->getPostQueryString();
                }
                if($this->logger !== null){
                    $this->logger->debug("CommonProxy->handle POST:" . $this->proxy_query->getGetUrl());
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders());
                $browserResponse = $browser->post($this->proxy_query->getGetUrl(),
                                                  $headers, $content);
            } else if($this->proxy_query->getMethod() === Utils::$METHOD_GET)
            {
                
                if($this->logger !== null){
                    $this->logger->debug("CommonProxy->handle GET:" . $this->proxy_query->getGetUrl());
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders());
                $browserResponse = $browser->get($this->proxy_query->getGetUrl(),
                                                 $headers);
            }
        } catch(\Exception $e)
        {
            if($this->logger !== null){
                $this->logger->err("CommonProxy->handle :" . $e->getMessage());
            }
            throw new HTTPStatus502Exception($e->getMessage(), 502);
        }
        return $browserResponse;
    }

}