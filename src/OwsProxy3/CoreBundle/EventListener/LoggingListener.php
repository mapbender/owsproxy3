<?php

namespace OwsProxy3\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
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
     * @param PostResponseEvent $event
     * @throws \Exception
     */
    public function onTerminate(PostResponseEvent $event)
    {
        if(!$this->owsproxyLogging) {
            return;
        }
        $token = $this->tokenStorage->getToken();
        // User is either an object or string for anonymous users
        // If kernel.terminate Event is fired from outside of any firewall there will be no token object e.G. mapbender_core_login_login
        if($token){
            $user =$token->getUser();
        } else {
            $user = "anon.";
        }
        
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

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
