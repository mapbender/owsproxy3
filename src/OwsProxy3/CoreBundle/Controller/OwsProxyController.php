<?php
namespace OwsProxy3\CoreBundle\Controller;

use ArsGeografica\Signing\BadSignatureException;
use Mapbender\CoreBundle\Component\Signer;
use OwsProxy3\CoreBundle\Component\Utils;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\WmsProxy;
use OwsProxy3\CoreBundle\Component\WfsProxy;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

//use OwsProxy3\CoreBundle\Component\Url;

/**
 * Description of OwsProxyController
 *
 * @author A.R.Pour
 * @author P. Schmidt
 */
class OwsProxyController extends Controller
{
    /** @var LoggerInterface */
    protected $logger = null;

    /**
     * Handles the client's request
     * NOTE: no route; only reachable via Symfony internal SubRequest
     *
     * @param string $url the url
     * @param string $content the POST content
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    public function genericProxyAction($url, $content = null)
    {
        $this->container->get('session')->save();
        $this->logger = $this->container->get('logger');
        /** @var Request $request */
        $request = $this->get('request_stack')->getCurrentRequest();
        $errorMessagePrefix = "OwsProxyController->genericProxyAction";
        try {
            $this->logger->debug("OwsProxyController->genericProxyAction");
            $proxy_config = $this->container->getParameter("owsproxy.proxy");
            $headers_req = Utils::getHeadersFromRequest($request);
            $getParams = Utils::getParams($request, Utils::$METHOD_GET);
            $postParams = Utils::getParams($request, Utils::$METHOD_POST);
            if (null === $content) {
                $content = $request->getContent();
            }
            $proxy_query = ProxyQuery::createFromUrl(
                $url,
                null,
                null,
                $headers_req,
                $getParams,
                $postParams,
                $content
            );
            $proxy = new CommonProxy($proxy_config, $proxy_query, $this->logger);
            $cookies_req = $request->cookies;
            $response = new Response();
            $browserResponse = $proxy->handle();
            Utils::setHeadersFromBrowserResponse($response, $browserResponse);
            foreach ($cookies_req as $key => $value) {
                $response->headers->removeCookie($key);
                $response->headers->setCookie(new Cookie($key, $value));
            }
            $response->setContent($browserResponse->getContent());
            return $response;
        } catch (\Exception $e) {
            $this->logger->error("{$errorMessagePrefix} : " . $e->getMessage() . " " . $e->getCode());
            return $this->exceptionHtml($e);
        }
    }

    /**
     * Handles the client's request
     *
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    public function entryPointAction()
    {
        $this->container->get('session')->save();
        $this->logger = $this->container->get('logger');
        /** @var Request $request */
        $request = $this->get('request_stack')->getCurrentRequest();
        /** @var Signer $signer */
        $signer = $this->get('signer');
        $proxy_query = ProxyQuery::createFromRequest($request);
        try {
            $signer->checkSignedUrl($proxy_query->getGetUrl());
        } catch (HttpException $e) {
            // let http exceptions run through unmodified
            throw $e;
        } catch (BadSignatureException $e) {
            throw new HTTPStatus403Exception('Invalid URL signature: ' . $e->getMessage());
        }

        $service = strtoupper($proxy_query->getServiceType());
        $errorMessagePrefix = "OwsProxyController->entryPointAction {$service}";
        $this->logger->debug("OwsProxyController->entryPointAction");
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');
        $proxy_config = $this->container->getParameter("owsproxy.proxy");

        switch ($service) {
            case 'WMS':
                $proxy = new WmsProxy($dispatcher, $proxy_config, $proxy_query, $this->logger);
                break;
            case 'WFS':
                $proxy = new WfsProxy($dispatcher, $proxy_config, $proxy_query, 'OWSProxy3', $this->logger);
                break;
            default:
                //@TODO ?
                return $this->exceptionHtml(new \Exception('Unknown Service Type', 404));
        }

        try {
            $browserResponse = $proxy->handle();

            $cookies_req = $request->cookies;
            $response = new Response();
            Utils::setHeadersFromBrowserResponse($response, $browserResponse);
            foreach ($cookies_req as $key => $value) {
                $response->headers->removeCookie($key);
                $response->headers->setCookie(new Cookie($key, $value));
            }
            $content = $browserResponse->getContent();
            $response->setContent($content);
            return $response;
        } catch (\Exception $e) {
            $this->logger->error("{$errorMessagePrefix}: {$e->getCode()} " . $e->getMessage());
            return $this->exceptionHtml($e);
        }
    }

    /**
     * Creates a response with an exception as HTML
     *
     * @param \Exception $e the exception
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    private function exceptionHtml(\Exception $e)
    {
        $response = new Response();
        $html = $this->render("OwsProxy3CoreBundle::exception.html.twig", array("exception" => $e));
        $response->headers->set('Content-Type', 'text/html');
        $response->setStatusCode($e->getCode() ?: 500);
        $response->setContent($html->getContent());
        return $response;
    }
}
