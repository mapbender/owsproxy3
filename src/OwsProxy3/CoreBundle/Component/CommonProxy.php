<?php

namespace OwsProxy3\CoreBundle\Component;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Buzz\Middleware\BasicAuthMiddleware;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * CommonProxy class for
 *
 * @author Paul Schmidt
 */
class CommonProxy
{
    /**
     *
     * @var ProxyQuery
     */
    protected $proxy_query;

    /**
     *
     * @var array the proxy configuration
     */
    protected $proxy_config;

    /**
     *
     * @var LoggerInterface the logger
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

    /** @var string the class name */
    protected $logMessagePrefix;

    /**
     * Creates a common proxy
     *
     * @param array $proxy_config the proxy configuration
     * @param ProxyQuery $proxy_query
     * @param LoggerInterface|null $logger
     * @param string[]|null $headerBlackList omit for defaults
     * @param string[]|null $headerWhiteList omit for defaults
     * @param string $userAgent
     */
    public function __construct(array $proxy_config, ProxyQuery $proxy_query, $logger = null, $headerBlackList = null,
        $headerWhiteList = null, $userAgent = 'OWSProxy3')
    {
        $this->proxy_config = $proxy_config;
        $this->proxy_query = $proxy_query;
        $this->logger = $logger ?: new NullLogger();
        if ($headerBlackList !== null) {
            $this->headerBlackList = $headerBlackList;
        }
        if ($headerWhiteList !== null) {
            $this->headerWhiteList = $headerWhiteList;
        }
        $this->userAgent = $userAgent;

        // strip namespace separators, get local class name
        $this->logMessagePrefix = substr(get_class($this), strrpos(get_class($this), '\\') + 1);
    }

    /**
     * Creates the browser
     *
     *
     * @return \Buzz\Browser
     */
    protected function createBrowser()
    {
        $pq = $this->proxy_query;
        $parts = $pq->getRowUrl();
        $this->logger->debug("CommonProxy->createBrowser rowUrl:" . print_r($parts, true));
        $curl = new Curl();
        $curlOptions = $this->getCurlOptions($pq->getHostName(), $this->proxy_config);
        foreach ($curlOptions as $optionId => $optionValue) {
            $curl->setOption($optionId, $optionValue);
        }
        $browser = new Browser($curl);
        if ($pq->getUsername()) {
            $browser->addMiddleware(new BasicAuthMiddleware($pq->getUsername(), $pq->getPassword()));
        }

        return $browser;
    }

    /**
     * Handles the request and returns the response.
     *
     * @return Response the browser response
     * @throws \Exception
     */
    public function handle()
    {
        $browser = $this->createBrowser();

        $method = $this->proxy_query->getMethod();
        $headers = Utils::prepareHeadersForRequest($this->proxy_query->getHeaders(), $this->headerBlackList,
            $this->headerWhiteList);
        $headers['User-Agent'] = $this->userAgent;
        $url = $this->proxy_query->getGetUrl();

        $this->logger->debug("{$this->logMessagePrefix}->handle {$method}:" . $url);
        $this->logger->debug("{$this->logMessagePrefix}->handle Headers: " . print_r($headers, true));

        /** @var Response $browserResponse */
        if ($method === Utils::$METHOD_POST) {
            if ($this->proxy_query->getContent() !== null) {
                $content = $this->proxy_query->getContent();
            } else {
                $content = $this->proxy_query->getPostQueryString();
            }
            $browserResponse = $browser->post($url, $headers, $content);
            return $browserResponse;
        } else if ($method === Utils::$METHOD_GET) {
            $browserResponse = $browser->get($url, $headers);
            return $browserResponse;
        }
        throw new \RuntimeException("Unsupported method {$method}");
    }

    /**
     * @return ProxyQuery
     */
    public function getProxyQuery()
    {
        return $this->proxy_query;
    }

    /**
     * @param string $hostName
     * @param array $config
     * @return array
     */
    public static function getCurlOptions($hostName, $config)
    {
        $options = array(
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
        );
        if (isset($config['timeout'])) {
            $options[CURLOPT_TIMEOUT] = $config['timeout'];
        }
        if (isset($config['connecttimeout'])) {
            $options[CURLOPT_CONNECTTIMEOUT] = $config['connecttimeout'];
        }
        if (isset($config['checkssl'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = !!$config['checkssl'];
        }
        if (isset($config['host']) && (empty($config['noproxy']) || !in_array($hostName, $config['noproxy']))) {
            $proxyOptions = array(
                CURLOPT_PROXY => $config['host'],
                CURLOPT_PROXYPORT => $config['port'],
            );
            if (isset($config['user']) && isset($config['password'])) {
                $proxyOptions = array_replace($proxyOptions, array(
                    CURLOPT_PROXYUSERPWD => "{$config['user']}:{$config['password']}",
                ));
            }
            $options = array_replace($options, $proxyOptions);
        }
        return $options;
    }

}
