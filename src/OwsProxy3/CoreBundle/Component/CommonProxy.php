<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Listener\BasicAuthListener;
use Buzz\Message\MessageInterface;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;

/**
 * CommonProxy class for
 *
 * @author Paul Schmidt
 */
class CommonProxy
{
    /**
     *
     * @var ContainerInterface the container
     */
    protected $proxy_query;

    /**
     *
     * @var array the proxy configuration
     */
    protected $proxy_config;

    /**
     *
     * @var Logger the logger
     */
    protected $logger;

    /**
     *
     * @var array headernames
     */
    protected $headerBlackList = array("cookie", "user-agent", "content-length", "referer", "host");

    /**
     *
     * @var array headernames
     */
    protected $headerWhiteList = array();

    /**
     * The user-agent to send along with proxy requests
     * @var String
     */
    protected $userAgent;

    /**
     * Creates a common proxy
     *
     * @param array $proxy_config the proxy configuration
     * @param ContainerInterface $container
     */
    public function __construct(array $proxy_config, ProxyQuery $proxy_query, $logger = null, $headerBlackList = null,
        $headerWhiteList = null, $userAgent = 'OWSProxy3')
    {
        $this->proxy_config = $proxy_config;
        $this->proxy_query = $proxy_query;
        $this->logger = $logger;
        if ($headerBlackList !== null) {
            $this->headerBlackList = $headerBlackList;
        }
        if ($headerWhiteList !== null) {
            $this->headerWhiteList = $headerWhiteList;
        }
        $this->userAgent = $userAgent;
    }

    /**
     * Creates the browser
     *
     *
     * @return \Buzz\Browser
     */
    protected function createBrowser()
    {
        if($this->logger !== null){
            $this->logger->debug("CommonProxy->createBrowser rowUrl:" . print_r($this->proxy_query->getRowUrl(), true));
        }
        $rowUrl = $this->proxy_query->getRowUrl();
        $proxy_config = $this->proxy_config;
        $curl = new Curl();
        $curl->setOption(CURLOPT_TIMEOUT, 60);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
        if ($proxy_config !== null && $proxy_config['timeout'] !== null) {
            $curl->setOption(CURLOPT_TIMEOUT, $proxy_config['timeout']);
        }
        if ($proxy_config !== null && $proxy_config['connecttimeout'] !== null) {
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, $proxy_config['connecttimeout']);
        }
        if ($proxy_config !== null && $proxy_config['host'] !== null
            && !in_array($rowUrl['host'], $proxy_config['noproxy'])) {
            $curl->setOption(CURLOPT_PROXY, $proxy_config['host']);
            $curl->setOption(CURLOPT_PROXYPORT, $proxy_config['port']);
            if ($proxy_config['user'] !== null && $proxy_config['password'] !== null) {
                $curl->setOption(CURLOPT_PROXYUSERPWD, $proxy_config['user'] . ':' . $proxy_config['password']);
            }
        }
        $browser = new Browser($curl);

        if(array_key_exists('user', $rowUrl) && $rowUrl['user'] != ''
            && array_key_exists('pass', $rowUrl) && $rowUrl['pass'] != '') {
            $browser->addListener(new BasicAuthListener($rowUrl['user'], $rowUrl['pass']));
        }

        return $browser;
    }

    /**
     * Handles the request and returns the response.
     *
     * @return MessageInterface the browser response
     * @throws Exception\HTTPStatus502Exception
     */
    public function handle()
    {
        $browser = $this->createBrowser();
        try {
            if ($this->proxy_query->getMethod() === Utils::$METHOD_POST) {
                if ($this->proxy_query->getContent() !== null) {
                    $content = $this->proxy_query->getContent();
                } else {
                    $content = $this->proxy_query->getPostQueryString();
                }
                if ($this->logger !== null) {
                    $this->logger->debug("CommonProxy->handle POST:" . $this->proxy_query->getGetUrl());
                    $this->logger->debug("CommonProxy->handle Headers: " . print_r($this->proxy_query->getHeaders(), true));
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
                        $this->headerWhiteList);
                $headers['User-Agent'] = $this->userAgent;
                $browserResponse = $browser->post($this->proxy_query->getGetUrl(), $headers, $content);
            } else if ($this->proxy_query->getMethod() === Utils::$METHOD_GET) {

                if ($this->logger !== null) {
                    $this->logger->debug("CommonProxy->handle GET:" . $this->proxy_query->getGetUrl());
                    $this->logger->debug("CommonProxy->handle Headers: " . print_r($this->proxy_query->getHeaders(), true));
                }
                $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
                        $this->headerWhiteList);
                $headers['User-Agent'] = $this->userAgent;
                $browserResponse = $browser->get($this->proxy_query->getGetUrl(), $headers);
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->err("CommonProxy->handle :" . $e->getMessage());
            }
            throw new HTTPStatus502Exception($e->getMessage(), 502);
        }
        return $browserResponse;
    }

}
