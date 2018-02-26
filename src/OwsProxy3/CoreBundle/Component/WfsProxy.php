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
    protected $event_dispatcher;

    public function __construct($event_dispatcher, array $proxy_config, ProxyQuery $proxy_query, $userAgent = 'OWSProxy3')
    {
        parent::__construct($proxy_config, $proxy_query, null, null, null, $userAgent);
        $this->event_dispatcher = $event_dispatcher;
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
        if ($browserResponse->isOk()) {
            return $browserResponse;
        } else {
            throw new HTTPStatus502Exception();
        }
    }

}
