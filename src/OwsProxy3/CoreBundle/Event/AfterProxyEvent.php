<?php

namespace OwsProxy3\CoreBundle\Event;

use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\EventDispatcher\Event;
use Buzz\Message\MessageInterface;

/**
 * @author A.R.Pour
 * @author Paul Schmidt
 */
class AfterProxyEvent extends Event
{

    /** @var ProxyQuery */
    protected $proxy_query;

    /** @var MessageInterface */
    protected $browserMessage;

    /**
     * @param ProxyQuery $proxy_query
     * @param MessageInterface $browserMessage
     */
    public function __construct(ProxyQuery $proxy_query,
            MessageInterface $browserMessage)
    {
        $this->proxy_query = $proxy_query;
        $this->browserMessage = $browserMessage;
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }

    /**
     * @return MessageInterface
     */
    public function getBrowserMessage()
    {
        return $this->browserMessage;
    }

}
