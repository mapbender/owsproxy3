<?php

namespace OwsProxy3\CoreBundle\Event;

use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\EventDispatcher\Event;
use Buzz\Message\MessageInterface;

/**
 * Description of BeforeProxyEvent
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class AfterProxyEvent extends Event
{

    /**
     *
     * @var ProxyQuery $proxy_query the proxy query
     */
    protected $proxy_query;

    /**
     *
     * @var MessageInterface the browser's message
     */
    protected $browserMessage;

    /**
     * Creates an instance
     * 
     * @param Request $request the HTTP request
     * @param MessageInterface $browserMessage the browser's message
     */
    public function __construct(ProxyQuery $proxy_query,
            MessageInterface $browserMessage)
    {
        $this->proxy_query = $proxy_query;
        $this->browserMessage = $browserMessage;
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

    /**
     * Returns the borwser's message
     * 
     * @return MessageInterface the browser's message
     */
    public function getBrowserMessage()
    {
        return $this->browserMessage;
    }

}
