<?php
namespace OwsProxy3\CoreBundle\Controller;

use ArsGeografica\Signing\BadSignatureException;
use Mapbender\CoreBundle\Component\Signer;
use OwsProxy3\CoreBundle\Component\Utils;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\WmsProxy;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Description of OwsProxyController
 *
 * @author A.R.Pour
 * @author P. Schmidt
 */
class OwsProxyController extends Controller
{

    /**
     * Handles the client's request
     * NOTE: no route; only reachable via Symfony internal SubRequest
     *
     * @param Request $request
     * @param string $url the url
     * @param string $content the POST content
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    public function genericProxyAction(Request $request, $url, $content = null)
    {
        $request->getSession()->save();
        $logger = $this->getLogger();
        $proxy = null;
        try {
            $headers_req = Utils::getHeadersFromRequest($request);
            if (null === $content) {
                $content = $request->getContent();
            }
            $proxy_query = ProxyQuery::createFromUrl(
                $url,
                null,
                null,
                $headers_req,
                $request->query->all(),
                $request->request->all(),
                $content
            );
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        try {
            $proxy = $this->proxyFactory($proxy_query, null);
            return $this->getProxyResponse($proxy, $request);
        } catch (\Exception $e) {
            $logger->error($e->getMessage() . " " . $e->getCode() . ($proxy ? (" " . get_class($proxy)) : ''));
            return $this->exceptionHtml($e);
        }
    }

    /**
     * Handles the client's request
     *
     * @Route("/")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response the response
     */
    public function entryPointAction(Request $request)
    {
        $request->getSession()->save();
        $logger = $this->getLogger();
        /** @var Signer $signer */
        $signer = $this->get('signer');

        try {
            $proxy_query = ProxyQuery::createFromRequest($request);
            $signer->checkSignedUrl($proxy_query->getGetUrl());
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
            // NOTE: ProxySignatureException is not defined in Mapbender < 3.0.8.1
            //       PHP is supposed to tolerate undefined classes in catch clauses
        } catch (\Mapbender\CoreBundle\Component\Exception\ProxySignatureException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        } catch (BadSignatureException $e) {
            throw new AccessDeniedHttpException('Invalid URL signature: ' . $e->getMessage());
        }

        $service = strtoupper($proxy_query->getServiceType());
        $proxy = $this->proxyFactory($proxy_query, $service);
        try {
            return $this->getProxyResponse($proxy, $request);
        } catch (\Exception $e) {
            $logger->error($e->getMessage() . " " . $e->getCode() . ($proxy ? (" " . get_class($proxy)) : ''));
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
        if ($e instanceof HttpException) {
            $response->setStatusCode($e->getCode());
        } else {
            $response->setStatusCode(500);
        }
        $response->setContent($html->getContent());
        return $response;
    }

    /**
     * @param CommonProxy $proxy
     * @param Request $request
     * @return Response
     */
    protected function getProxyResponse(CommonProxy $proxy, Request $request)
    {
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
        $response->setStatusCode($browserResponse->getStatusCode());
        return $response;
    }

    /**
     * @param ProxyQuery $query
     * @param string|null $serviceType
     * @return CommonProxy
     */
    protected function proxyFactory(ProxyQuery $query, $serviceType)
    {
        $config = $this->container->getParameter("owsproxy.proxy");
        $logger = $this->getLogger();

        switch (strtoupper($serviceType)) {
            case 'WMS':
                /** @var EventDispatcherInterface $dispatcher */
                $dispatcher = $this->get('event_dispatcher');
                return new WmsProxy($dispatcher, $config, $query, $logger);
            default:
                return new CommonProxy($config, $query, $logger);
        }
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        return $logger;
    }
}
