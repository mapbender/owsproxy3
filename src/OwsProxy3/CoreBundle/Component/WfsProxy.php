<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OwsProxy3\CoreBundle\Component\Url;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;
use Buzz\Browser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * WFS Proxy
 *
 * @author C. Wygoda
 */
class WfsProxy extends CommonProxy
{
    protected $event_dispatcher;

    /**
     * @param Url $url
     */
    public function __construct($event_dispatcher, array $proxy_config, ProxyQuery $proxy_query)
    {
        parent::__construct($proxy_config, $proxy_query);
        $this->event_dispatcher = $event_dispatcher;
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle()
    {
        $browser = $this->createBrowser();
        try {
            if($this->proxy_query->getContent() !== null) {
                $content = $this->proxy_query->getContent();
            } else {
                $content = $this->proxy_query->getPostQueryString();
            }
            $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders());
            $browserResponse = $browser->post($this->proxy_query->getGetUrl(), $headers, $content);
        } catch(\Exception $e) {
            throw new HTTPStatus502Exception($e->getMessage(), 502);
        }
        if($browserResponse->isOk()) {
            return $browserResponse;
        } else {
            throw new HTTPStatus502Exception();
        }

        $response = new Response();
        $browser = new Browser();

        $dispatcher = $this->container->get('event_dispatcher');

        try {
            $event = new BeforeProxyEvent($url);
            $dispatcher->dispatch('owsproxy.before_proxy', $event);
        } catch(\RuntimeException $e) {
            return;
        }

        $incoming = $this->container->get('request');
        $body = $incoming->getContent();
        $headers = array_map(function($value) {
            return implode('; ', $value);
        }, $incoming->headers->all());

        $browserResponse = $browser->post($url->toString(), $headers, $body);

        if($browserResponse->isOk()) {
            $event = new AfterProxyEvent($url, $browserResponse);
            $dispatcher->dispatch('owsproxy.after_proxy', $event);

            // Set received headers to our response
            foreach($browserResponse->getHeaders() as $header) {
                if(strstr($header, ":") === false) continue;

                list($key, $val) = explode(":", $header, 2);
                //$response->headers->set($key, $val);
            }

            // Set received content to our response
            $response->setContent( $browserResponse->getContent() );

        } else {
            throw new HTTPStatus502Exception();
        }

        return $response;
    }
}
