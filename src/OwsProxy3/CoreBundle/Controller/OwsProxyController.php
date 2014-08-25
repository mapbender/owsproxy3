<?php
namespace OwsProxy3\CoreBundle\Controller;

use Buzz\Message\MessageInterface;
use OwsProxy3\CoreBundle\Component\Utils;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\WmsProxy;
use OwsProxy3\CoreBundle\Component\WfsProxy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ArsGeografica\Signing\Signer;

//use OwsProxy3\CoreBundle\Component\Url;

/**
 * Description of OwsProxyController
 *
 * @author A.R.Pour
 * @author P. Schmidt
 */
class OwsProxyController extends Controller
{
    protected $logger = null;

    /**
     * Handles the client's request
     *
     * @param type $url the url
     * @param type $content the POST content
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    public function genericProxyAction($url, $content = null)
    {
        $this->logger = $this->container->get('logger');
        $request = $this->get('request');
        try {
            $this->logger->debug("OwsProxyController->genericProxyAction");
            $proxy_config = $this->container->getParameter("owsproxy.proxy");
            $headers_req = Utils::getHeadersFromRequest($request);
            $getParams = Utils::getParams($request, Utils::$METHOD_GET);
            $postParams = Utils::getParams($request, Utils::$METHOD_POST);
            if (null === $content) {
                $content = $request->getContent();
            }
            $proxy_query = ProxyQuery::createFromUrl($url, null, null,
                                                     $headers_req, $getParams,
                                                     $postParams, $content);
            $proxy = new CommonProxy($proxy_config, $proxy_query, $this->get('logger'));
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
        } catch (HTTPStatus403Exception $e) {
            $this->logger->err("OwsProxyController->genericProxyAction 403: " . $e->getMessage() . " " . $e->getCode());
            return $this->exceptionImage($e, $request);
        } catch (HTTPStatus502Exception $e) {
            $this->logger->err("OwsProxyController->genericProxyAction 502: " . $e->getMessage() . " " . $e->getCode());
            return $this->exceptionImage($e, $request);
        } catch (\Exception $e) {
            $this->logger->err("OwsProxyController->genericProxyAction : " . $e->getMessage() . " " . $e->getCode());
            if ($e->getCode() === 0) $e = new \Exception($e->getMessage(), 500);
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
        $this->logger = $this->container->get('logger');
        $request = $this->get('request');
        $signer = $this->get('signer');
        $proxy_query = ProxyQuery::createFromRequest($request);
        try {
            $signer->checkSignedUrl($proxy_query->getGetUrl());
        } catch(BadSignatureException $e) {
            throw new HTTPStatus403Exception('Invalid URL signature: ' . $e->getMessage());
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
        $service = $proxy_query->getServiceType();
        // Switch proxy
        switch (strtoupper($service)) {
            case 'WMS':
                try {
                    $this->logger->debug("OwsProxyController->entryPointAction");
                    $dispatcher = $this->container->get('event_dispatcher');
                    $proxy_config = $this->container->getParameter("owsproxy.proxy");
                    $proxy = new WmsProxy($dispatcher, $proxy_config,
                        $proxy_query);
                    $browserResponse = $proxy->handle();

                    $cookies_req = $request->cookies;
                    $response = new Response();
                    Utils::setHeadersFromBrowserResponse($response,
                        $browserResponse);
                    foreach ($cookies_req as $key => $value) {
                        $response->headers->removeCookie($key);
                        $response->headers->setCookie(new Cookie($key, $value));
                    }
                    $content = $browserResponse->getContent();
                    $response->setContent($content);
                    return $response;
                } catch (HTTPStatus403Exception $e) {
                    $this->logger->err("OwsProxyController->entryPointAction WMS 403: " . $e->getMessage() . " " . $e->getCode());
                    return $this->exceptionImage($e, $request);
                } catch (HTTPStatus502Exception $e) {
                    $this->logger->err("OwsProxyController->entryPointAction WMS 502: " . $e->getMessage() . " " . $e->getCode());
                    return $this->exceptionImage($e, $request);
                } catch (\Exception $e) {
                    $this->logger->err("OwsProxyController->entryPointAction WMS : " . $e->getMessage() . " " . $e->getCode());
                    if ($e->getCode() === 0)
                            $e = new \Exception($e->getMessage(), 500);
                    return $this->exceptionHtml($e);
                }
            case 'WFS':
                try {
                    $dispatcher = $this->container->get('event_dispatcher');
                    $proxy_config = $this->container->getParameter("owsproxy.proxy");
                    $proxy = new WfsProxy($dispatcher, $proxy_config,
                        $proxy_query);
                    $browserResponse = $proxy->handle();

                    $cookies_req = $request->cookies;
                    $response = new Response();
                    Utils::setHeadersFromBrowserResponse($response,
                        $browserResponse);
                    foreach ($cookies_req as $key => $value) {
                        $response->headers->removeCookie($key);
                        $response->headers->setCookie(new Cookie($key, $value));
                    }
                    $response->setContent($browserResponse->getContent());
                    return $response;
                } catch (\RuntimeException $e) {
                    $this->logger->err("OwsProxyController->entryPointAction WFS : " . $e->getMessage() . " " . $e->getCode());
                    return $this->exceptionHtml(new \Exception($e->getMessage(),
                            500));
                }
            default: //@TODO ?
                return $this->exceptionHtml(new \Exception('Unknown Service Type',
                        404));
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
        $html = $this->render("OwsProxy3CoreBundle::exception.html.twig",
            array("exception" => $e));
        $response->headers->set('Content-Type', 'text/html');
        $response->setStatusCode($e->getCode());
        $response->setContent($html->getContent());
        return $response;
    }

    /**
     * Creates a response with an exception as png image
     *
     * @param \Exception $e the exception
     * @param Request $request the request
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    private function exceptionImage(\Exception $e, $request)
    {
        $format = Utils::getParamValueFromAll($request, "format", true);
        $w = Utils::getParamValueFromAll($request, "width", true);
        $h = Utils::getParamValueFromAll($request, "height", true);
        if ($format === null || $w === null || $h === null
            || !is_int(strpos(strtolower($format), "image"))
            || intval($w) === 0 || intval($h) === 0) {
            return $this->exceptionHtml($e);
        }
        return $this->exceptionHtml($e);
        try {
            $image = new \Imagick();
            $draw = new \ImagickDraw();
            $pixel = new \ImagickPixel('none');

            $image->newImage(intval($w), intval($h), $pixel);

            $draw->setFillColor('grey');
            $draw->setFontSize(30);
            $st_x = 200;
            $st_y = 200;
            $ang = -45;
            for ($x = 10; $x < $w; $x += $st_x) {
                for ($y = 10; $y < $h; $y += $st_y) {
                    $image->annotateImage($draw, $x, $y, $ang,
                        $this->container->get('translator')->trans($e->getMessage()));
                }
            }

            $image->setImageFormat('png');

            $response = new Response();
            $response->headers->set('Content-Type', "image/png");
            $response->setStatusCode($e->getCode());
            $response->setContent($image->getimageblob());

            return $response;
        } catch (\Exception $e) {
            return $this->exceptionHtml($e);
        }
    }

}
