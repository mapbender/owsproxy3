<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;
use Buzz\Browser;

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
     * @param EventDispatcherInterface $event_dispatcher
     * @param array $proxy_config the proxy configuration
     * @param ProxyQuery $proxy_query
     * @param LoggerInterface $logger
     * @param string $userAgent
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
     * @return Response the browser response
     * @throws \Exception
     */
    public function handle()
    {
        $event = new BeforeProxyEvent($this->proxy_query);
        $this->event_dispatcher->dispatch('owsproxy.before_proxy', $event);

        $browserResponse = parent::handle();

        if ($browserResponse->isOk() || $browserResponse->isEmpty()) {
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
            $rawUrl = $this->proxy_query->getRowUrl();
            $message = "Server {$rawUrl['host']} says: {$browserResponse->getStatusCode()} / '{$browserResponse->getReasonPhrase()}'";
            throw new \RuntimeException($message);
        }
        return $browserResponse;
    }

    /**
     * @deprecated
     * You don't need this. Just let the browser object go out of scope and it'll clean up after itself.
     * @see https://github.com/kriswallsmith/Buzz/blob/v0.15/lib/Buzz/Client/Curl.php#L54
     *
     * @param $browser
     * @throws \ReflectionException
     */
    protected function closeConnection(Browser $browser)
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
