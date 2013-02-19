<?php

namespace OwsProxy3\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


/**
 * Description of OwsProxy3CoreExtension
 *
 * @author A.R.Pour
 */
class OwsProxy3CoreExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container) {
    	$configuration = new Configuration();
    	$config = $this->processConfiguration($configuration, $configs);

        $container->setParameter("owsproxy.logging", $config["logging"]);
    	$container->setParameter("owsproxy.obfuscate_client_ip", $config["obfuscate_client_ip"]);
        $container->setParameter("owsproxy.proxy", $config["proxy"]);

        $loader = new XmlFileLoader($container,
                new FileLocator(__DIR__.'/../Resources/config'));
        
        $loader->load('services.xml');
    }
    
    public function getAlias() {
        return 'ows_proxy3_core';
    }
}
