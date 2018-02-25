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
    public function __construct($event_dispatcher, array $proxy_config, ProxyQuery $proxy_query, $userAgent = 'OWSProxy3')
    {
        parent::__construct($proxy_config, $proxy_query, null, null, null, $userAgent);
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
            if ($this->proxy_query->getContent() !== null) {
                $content = $this->proxy_query->getContent();
            } else {
                $content = $this->proxy_query->getPostQueryString();
            }
            $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
                    $this->headerWhiteList);
            $headers['User-Agent'] = $this->userAgent;
            $browserResponse = $browser->post($this->proxy_query->getGetUrl(), $headers, $content);
        } catch (\Exception $e) {
            throw new HTTPStatus502Exception($e->getMessage(), 502);
        }
        if ($browserResponse->isOk()) {
            return $browserResponse;
        } else {
            throw new HTTPStatus502Exception();
        }
    }

}
