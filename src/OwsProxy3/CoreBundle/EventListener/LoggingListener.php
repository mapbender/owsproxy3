<?php

namespace OwsProxy3\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use OwsProxy3\CoreBundle\Event\ProxyTerminateEvent;
use OwsProxy3\CoreBundle\Entity\Log;

/**
 * LoggingListener
 *
 * @author A.R.Pour
 */
class LoggingListener
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onTerminate(ProxyTerminateEvent $event)
    {
        if(!$this->container->getParameter('owsproxy.logging'))
        {
            return;
        }

        // User is either an object or string for anonymous users
        $user = $this->container->get('security.context')->getToken()->getUser();
        if(is_object($user))
        {
            $user_id = get_class($user) . '-' .
                    $user->getId() . '-' .
                    $user->getUsername();

            $roles = implode(',', $user->getRoles());
        } else
        {
            $user_id = $user;
            $roles = '';
        }

        // IP is obfuscated for privacy reasons, see bundle configuration
        $ip = $event->getRequest()->getClientIp();
        if($this->container->getParameter('owsproxy.obfuscate_client_ip'))
        {
            if(false !== ($pos = strrpos($ip, '.')) ||
                    false !== ($pos = strrpos($ip, ':')))
            {
                $ip = substr($ip, 0, $pos);
            }
        }

        $log = new Log();

		// spaces to avoid running into oracle null constraints, do not remove!
        $log->setUserName($user_id);
        $log->setRoles($roles . ' ');
        $log->setIp($ip);

        $log->setTimestamp(new \DateTime());

        $log->setRequestUrl($event->getRequest()->getUri());
        $log->setRequestBody($event->getRequest()->getContent() . ' ');
        $log->setRequestMethod($event->getRequest()->getMethod());

        $log->setResponseMimetype($event->getResponse()->headers->get('Content-Type',
                                                                      ''));
        $log->setResponseCode($event->getResponse()->getStatuscode());
        $log->setResponseSize(strlen($event->getResponse()->getContent()));

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($log);
        $em->flush();
    }

}
