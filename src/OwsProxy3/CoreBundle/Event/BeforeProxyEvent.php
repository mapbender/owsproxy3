<?php

namespace OwsProxy3\CoreBundle\Event;

use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\EventDispatcher\Event;

/**
 * Description of BeforeProxyEvent
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class BeforeProxyEvent extends Event
{

    /**
     *
     * @var ProxyQuery $proxy_query the proxy query
     */
    protected $proxy_query;

    /**
     * Creates an instance
     * 
     * @param ProxyQuery $proxy_query the proxy query
     */
    public function __construct(ProxyQuery $proxy_query)
    {
        $this->proxy_query = $proxy_query;
    }

    /**
     * Returns the proxy query
     * 
     * @return ProxyQuery the proxy query
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }

}
