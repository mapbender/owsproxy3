<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Buzz\Browser;

/**
 * WMS Proxy
 *
 * @author A.R.Pour
 * @author P. Schmidt
 */
class WmsProxy extends CommonProxy
{

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
    }

    /**
     * Handles the request and returns the response.
     *
     * @return Response the browser response
     * @throws \Exception
     */
    public function handle()
    {
        $browserResponse = parent::handle();
        if ($browserResponse->isOk() || $browserResponse->isEmpty()) {
            return $browserResponse;
        } else {
            $rawUrl = $this->proxy_query->getRowUrl();
            $message = "Server {$rawUrl['host']} says: {$browserResponse->getStatusCode()} / '{$browserResponse->getReasonPhrase()}'";
            throw new \RuntimeException($message);
        }
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
