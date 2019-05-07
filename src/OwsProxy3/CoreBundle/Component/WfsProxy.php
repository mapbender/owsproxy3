<?php

namespace OwsProxy3\CoreBundle\Component;

/**
 * WFS Proxy
 *
 * @author C. Wygoda
 * @deprecated same as CommonProxy with divergent constructor signature
 */
class WfsProxy extends CommonProxy
{
    public function __construct($event_dispatcher, array $proxy_config, ProxyQuery $proxy_query, $userAgent = 'OWSProxy3', $logger = null)
    {
        parent::__construct($proxy_config, $proxy_query, $logger, null, null, $userAgent);
    }
}
