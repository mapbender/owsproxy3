<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;
use Buzz\Browser;
use Buzz\Client\Curl;

/**
 * WMS Proxy
 *
 * @author A.R.Pour
 * @author P. Schmidt
 */
class WmsProxy extends CommonProxy
{

    protected $event_dispatcher;

    /**
     * Creates a wms proxy
     * 
     * @param array $proxy_config the proxy configuration
     * @param ContainerInterface $container
     */
    public function __construct($event_dispatcher, array $proxy_config,
            ProxyQuery $proxy_query, $logger = null)
    {
        parent::__construct($proxy_config, $proxy_query, $logger);
        $this->event_dispatcher = $event_dispatcher;
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
            $event = new BeforeProxyEvent($this->proxy_query);
            $this->event_dispatcher->dispatch('owsproxy.before_proxy', $event);
        } catch(\RuntimeException $e)
        {
            throw new HTTPStatus502Exception();
        }
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
                    $this->logger->debug("WmsProxy->handle POST:" . $this->proxy_query->getGetUrl());
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders());
                $browserResponse = $browser->post($this->proxy_query->getGetUrl(),
                                                  $headers, $content);
            } else if($this->proxy_query->getMethod() === Utils::$METHOD_GET)
            {
                if($this->logger !== null){
                    $this->logger->debug("WmsProxy->handle GET:" . $this->proxy_query->getGetUrl());
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders());
                $browserResponse = $browser->get($this->proxy_query->getGetUrl(),
                                                 $headers);
            }
        } catch(\Exception $e)
        {
            if($this->logger !== null){
                $this->logger->err("WmsProxy->handle :" . $e->getMessage());
            }
            throw new HTTPStatus502Exception($e->getMessage(), 502);
        }
        if($browserResponse->isOk())
        {
            
            $event = new AfterProxyEvent($this->proxy_query, $browserResponse);
            $this->event_dispatcher->dispatch('owsproxy.after_proxy', $event);
        } else
        {
            if($this->logger !== null){
                $this->logger->err("WmsProxy->handle browserResponse is not OK.");
            }
            throw new HTTPStatus502Exception();
        }
        return $browserResponse;
    }

}
