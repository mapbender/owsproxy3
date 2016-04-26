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
    public function __construct($event_dispatcher, array $proxy_config, ProxyQuery $proxy_query, $logger = null,
                                $userAgent = 'OWSProxy3')
    {
        parent::__construct($proxy_config, $proxy_query, $logger, null, null, $userAgent);
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
        try {
            $event = new BeforeProxyEvent($this->proxy_query);
            $this->event_dispatcher->dispatch('owsproxy.before_proxy', $event);
        } catch (\RuntimeException $e) {
            throw new HTTPStatus502Exception();
        }
        try {
            if ($this->proxy_query->getMethod() === Utils::$METHOD_POST) {
                if ($this->proxy_query->getContent() !== null) {
                    $content = $this->proxy_query->getContent();
                } else {
                    $content = $this->proxy_query->getPostQueryString();
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
                                                           $this->headerWhiteList);
                $headers['User-Agent'] = $this->userAgent;
                if ($this->logger !== null) {
                    $this->logger->debug("WmsProxy->handle POST:" . $this->proxy_query->getGetUrl());
                    $this->logger->debug("WmsProxy->handle Headers: " . print_r($this->proxy_query->getHeaders(), true));
                }
                $browserResponse = $browser->post($this->proxy_query->getGetUrl(), $headers, $content);
            } else if ($this->proxy_query->getMethod() === Utils::$METHOD_GET) {
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
                                                           $this->headerWhiteList);
                $headers['User-Agent'] = $this->userAgent;
                if ($this->logger !== null) {
                    $this->logger->debug("WmsProxy->handle GET:" . $this->proxy_query->getGetUrl());
                    $this->logger->debug("WmsProxy->handle Headers: " . print_r($this->proxy_query->getHeaders(), true));
                }
                $browserResponse = $browser->get($this->proxy_query->getGetUrl(), $headers);
            }
        } catch (\Exception $e) {
            $this->closeConnection($browser);
            if ($this->logger !== null) {
                $this->logger->err("WmsProxy->handle :" . $e->getMessage());
            }
            throw new HTTPStatus502Exception($e->getMessage(), 502);
        }

        $this->closeConnection($browser);

        if ($browserResponse->isOk()) {
            $event = new AfterProxyEvent($this->proxy_query, $browserResponse);
            $this->event_dispatcher->dispatch('owsproxy.after_proxy', $event);
        } else {
            // pass auth challenge down to client, but alter realm to make it
            // unique as it will be applied to this server as the auth
            // partition key!
            if (401 === $browserResponse->getStatusCode()) {
                $headers = $browserResponse->getHeaders();
                $needle = 'www-authenticate: basic realm="';
                foreach ($headers as $idx => &$header) {
                    $haystack = strtolower($header);
                    if (0 === strpos($haystack, $needle)) {
                        $origRealm = substr($header, strlen($needle), strlen($header) - strlen($needle) - 1);
                        $rawUrl = $this->proxy_query->getRowUrl(); // ;)

                        $scheme = empty($rawUrl["scheme"]) ? "http://" : $rawUrl["scheme"] . "://";
                        $host = $rawUrl["host"];
                        $port = empty($rawUrl["port"]) ? "" : ":" . $rawUrl["port"];
                        $path = empty($rawUrl["path"]) ? "" : $rawUrl["path"];
                        $server = $scheme . $host . $port . $path;
                        $realm = 'Server: ' . $server . ' - ' . $origRealm;
                        $header = 'WWW-Authenticate: Basic realm="' . $realm . '"';

                        $browserResponse->setHeaders($headers);
                        break;
                    }
                }
                return $browserResponse;
            }
            $message = null;
            if ($browserResponse->getReasonPhrase() !== null) {
                $rawUrl = $this->proxy_query->getRowUrl();
                $message = 'Server "' . $rawUrl['host'] . '" says: ' . $browserResponse->getReasonPhrase();
                throw new HTTPStatus502Exception($message);
            } else {
                throw new HTTPStatus502Exception();
            }
            if ($this->logger !== null) {
                $this->logger->err($message !== null ? $message : "WmsProxy->handle browserResponse is not OK.");
            }
        }
        return $browserResponse;
    }

    protected function closeConnection($browser)
    {
        // Kick cURL, which tries to hold open connections...
        $curl = $browser->getClient();
        $class = new \ReflectionClass(get_class($curl));
        $property = $class->getProperty("lastCurl");
        $property->setAccessible(true);
        if (is_resource($property->getValue($curl))) {
            curl_close($property->getValue($curl));
        }
    }

}
