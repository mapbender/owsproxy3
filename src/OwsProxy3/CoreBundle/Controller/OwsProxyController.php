<?php
namespace OwsProxy3\CoreBundle\Controller;

use ArsGeografica\Signing\BadSignatureException;
use Mapbender\CoreBundle\Component\Signer;
use OwsProxy3\CoreBundle\Component\Utils;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author A.R.Pour
 * @author P. Schmidt
 */
class OwsProxyController extends Controller
{

    /**
     * Handles the client's request
     * NOTE: no route; only reachable via Symfony internal SubRequest
     * @deprecated
     * For Mapbender >= 3.0.8-beta1, use getUrl method on mapbender.http_transport.service
     * @see https://github.com/mapbender/mapbender/blob/v3.0.8.4/src/Mapbender/Component/Transport/OwsProxyTransport.php
     *
     * @param Request $request
     * @param string $url
     * @param string $content for POST
     * @return Response
     */
    public function genericProxyAction(Request $request, $url, $content = null)
    {
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
        $proxy = $this->proxyFactory($proxy_query);
        return $this->getProxyResponse($proxy, $request);
    }

    /**
     * Handles the client's request
     *
     * @Route("/")
     * @param Request $request
     * @return Response
     */
    public function entryPointAction(Request $request)
    {
        /** @var Signer $signer */
        $signer = $this->get('signer');
        $url = $request->query->get('url');

        try {
            $proxy_query = ProxyQuery::createFromRequest($request);
            $signer->checkSignedUrl($url);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
            // NOTE: ProxySignatureException is not defined in Mapbender < 3.0.8.1
            //       PHP is supposed to tolerate undefined classes in catch clauses
        } catch (\Mapbender\CoreBundle\Component\Exception\ProxySignatureException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        } catch (BadSignatureException $e) {
            throw new AccessDeniedHttpException('Invalid URL signature: ' . $e->getMessage());
        }

        $proxy = $this->proxyFactory($proxy_query);
        return $this->getProxyResponse($proxy, $request);
    }

    /**
     * Creates a response with an exception as HTML
     *
     * @param \Exception $e
     * @return Response
     */
    private function exceptionHtml(\Exception $e)
    {
        $response = $this->render("OwsProxy3CoreBundle::exception.html.twig", array(
            "exception" => $e,
        ));
        $response->headers->set('Content-Type', 'text/html');
        if ($e instanceof HttpException) {
            $response->setStatusCode($e->getCode());
        } else {
            $response->setStatusCode(500);
        }
        return $response;
    }

    /**
     * @param CommonProxy $proxy
     * @param Request $request
     * @return Response
     */
    protected function getProxyResponse(CommonProxy $proxy, Request $request)
    {
        try {
            $browserResponse = $proxy->handle();
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage() . " " . $e->getCode() . " " . get_class($proxy));
            return $this->exceptionHtml($e);
        }
        if (!($browserResponse->isOk() || $browserResponse->isEmpty())) {
            $statusCode = $browserResponse->getStatusCode();
            $host = $proxy->getProxyQuery()->getHostname();
            $message = "{$host} says: {$statusCode} {$browserResponse->getReasonPhrase()}";
            $response = $this->render("OwsProxy3CoreBundle::exception.html.twig", array(
                'exception' => array(
                    'message' => $message,
                ),
            ));
            $response->setStatusCode($statusCode, $browserResponse->getReasonPhrase());
            return $response;
        }

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
     * @return CommonProxy
     */
    protected function proxyFactory(ProxyQuery $query)
    {
        $config = $this->container->getParameter("owsproxy.proxy");
        $logger = $this->getLogger();
        return new CommonProxy($config, $query, $logger);
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
