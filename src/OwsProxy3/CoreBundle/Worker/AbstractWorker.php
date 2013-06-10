<?php

namespace OwsProxy3\CoreBundle\Worker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;

/**
 * 
 */
interface AbstractWorker
{

    /**
     * 
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container);

    /**
     * 
     * @param \OwsProxy3\CoreBundle\Event\BeforeProxyEvent $event
     */
    public function onBeforeProxyEvent(BeforeProxyEvent $event);

    /**
     * 
     * @param \OwsProxy3\CoreBundle\Event\AfterProxyEvent $event
     */
    public function onAfterProxyEvent(AfterProxyEvent $event);
}
