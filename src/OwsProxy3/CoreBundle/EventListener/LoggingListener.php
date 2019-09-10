<?php

namespace OwsProxy3\CoreBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use OwsProxy3\CoreBundle\Event\ProxyTerminateEvent;
use OwsProxy3\CoreBundle\Entity\Log;

/**
 * LoggingListener
 *
 * @author A.R.Pour
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
     * @param ProxyTerminateEvent $event
     * @throws \Exception
     */
    public function onTerminate(ProxyTerminateEvent $event)
    {
        if(!$this->owsproxyLogging) {
            return;
        }

        // User is either an object or string for anonymous users
        $user = $this->tokenStorage->getToken()->getUser();
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
        if($this->owsproxyObfuscateClientIp) {
            if(false !== ($pos = strrpos($ip, '.')) || false !== ($pos = strrpos($ip, ':'))) {
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

        $this->entityManager->getUnitOfWork()->clear();
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
