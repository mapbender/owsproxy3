<?php

namespace OwsProxy3\CoreBundle\Event;

use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author A.R.Pour
 * @author Paul Schmidt
 */
class BeforeProxyEvent extends Event
{

    /** @var ProxyQuery */
    protected $proxy_query;

    /**
     * @param ProxyQuery $proxy_query
     */
    public function __construct(ProxyQuery $proxy_query)
    {
        $this->proxy_query = $proxy_query;
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }

}
