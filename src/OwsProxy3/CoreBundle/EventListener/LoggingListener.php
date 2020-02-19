<?php

namespace OwsProxy3\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use OwsProxy3\CoreBundle\Entity\Log;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LoggingListener
 *
 * @author A.R.Pour
 * @deprecated remove in v3.2.0. Redundant vs web server logs and a constant source of ORM UnitOfWork inconsistencies.
 */
class LoggingListener
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var EntityManagerInterface */
    protected $entityManager;

    protected $owsproxyLogging;

    protected $owsproxyObfuscateClientIp;

    /**
     * LoggingListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $entityManager
     * @param $owsproxyLogging
     * @param $owsproxyObfuscateClientIp
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        $owsproxyLogging,
        $owsproxyObfuscateClientIp
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->owsproxyLogging = $owsproxyLogging;
        $this->owsproxyObfuscateClientIp = $owsproxyObfuscateClientIp;
    }

    /**
     * @param PostResponseEvent $event
     * @throws \Exception
     */
    public function onTerminate(PostResponseEvent $event)
    {
        if (!$this->owsproxyLogging || $event->getRequest()->attributes->get('_route') !== "owsproxy3_core_owsproxy_entrypoint") {
            return;
        }

        $log = new Log();
        $token = $this->tokenStorage->getToken();
        // User is either an object or string for anonymous users
        // If kernel.terminate Event is fired from outside of any firewall there will be no token object e.G. mapbender_core_login_login
        if ($token) {
            $log->setUsername($token->getUsername());
            $user = $token->getUser();
            if ($user && ($user instanceof UserInterface)) {
                // spaces to avoid running into oracle null constraints
                $log->setRoles(implode(',', $user->getRoles()) ?: null);
            }
        } else {
            $log->setUsername("anon.");
        }
        
        // IP is obfuscated for privacy reasons, see bundle configuration
        $ip = $event->getRequest()->getClientIp();
        if ($this->owsproxyObfuscateClientIp) {
            if(false !== ($pos = strrpos($ip, '.')) || false !== ($pos = strrpos($ip, ':'))) {
                $ip = substr($ip, 0, $pos);
            }
        }

        $log->setIp($ip);

        $log->setTimestamp(new \DateTime());

        $log->setRequestUrl($event->getRequest()->getUri());
        $log->setRequestBody($event->getRequest()->getContent() . ' ');
        $log->setRequestMethod($event->getRequest()->getMethod());

        $log->setResponseMimetype($event->getResponse()->headers->get('Content-Type',
                                                                      ''));
        $log->setResponseCode($event->getResponse()->getStatuscode());
        $log->setResponseSize(strlen($event->getResponse()->getContent()));

        $this->entityManager->getUnitOfWork()->clear();
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
