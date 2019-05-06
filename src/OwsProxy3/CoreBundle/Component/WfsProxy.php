<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Message\Response;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;

/**
 * WFS Proxy
 *
 * @author C. Wygoda
 */
class WfsProxy extends CommonProxy
{
    public function __construct($event_dispatcher, array $proxy_config, ProxyQuery $proxy_query, $userAgent = 'OWSProxy3', $logger = null)
    {
        parent::__construct($proxy_config, $proxy_query, $logger, null, null, $userAgent);
    }

    /**
     *
     * @return Response
     */
    public function handle()
    {
        $browserResponse = parent::handle();
        // quirks mode: on: CommonProxy / WmsProxy let the caller decide what to do with the response
        // WfsProxy throws
        if ($browserResponse->isOk() || $browserResponse->isEmpty()) {
            return $browserResponse;
        } else {
            throw new HTTPStatus502Exception();
        }
    }

}
