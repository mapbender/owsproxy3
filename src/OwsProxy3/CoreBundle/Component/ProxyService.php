<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProxyService
 *
 * @package OwsProxy3\CoreBundle\Component
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class ProxyService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * ProxyService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($this->container);
    }

    /**
     * /**
     * Creates an instance from parameters
     *
     * @param string $url        URL
     * @param string $user       User name for basic authentication
     * @param string $password   User password for basic authentication
     * @param array  $headers    HTTP headers
     * @param array  $getParams
     * @param array  $postParams the POST parameters
     * @param null   $content
     * @return \Buzz\Message\MessageInterface|ProxyQuery
     */
    public function request($url,
        $user = null,
        $password = null,
        $headers = array(),
        $getParams = array(),
        $postParams = array(),
        $content = null)
    {
        $configuration = $this->container->getParameter("owsproxy.proxy");
        $proxy         = new CommonProxy($configuration, ProxyQuery::createFromUrl(
            $url,
            $user,
            $password,
            $headers, $getParams, $postParams,
            $content));
        return $proxy->handle();
    }
}