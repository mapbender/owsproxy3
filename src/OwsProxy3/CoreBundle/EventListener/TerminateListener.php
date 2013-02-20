<?php

namespace OwsProxy3\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use OwsProxy3\CoreBundle\Event\ProxyTerminateEvent;

/**
 * EventListener
 *
 * @author A.R.Pour
 */
class TerminateListener
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        if($request->get('_route') !== "owsproxy3_core_owsproxy_entrypoint")
        {
            return;
        }

        $event = new ProxyTerminateEvent($event->getKernel(),
                        $event->getRequest(), $event->getResponse());
        $dispatcher = $this->container->get('event_dispatcher');

        $dispatcher->dispatch('owsproxy.terminate', $event);
    }

}