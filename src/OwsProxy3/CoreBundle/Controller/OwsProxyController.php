<?php
namespace OwsProxy3\CoreBundle\Controller;

use ArsGeografica\Signing\BadSignatureException;
use Mapbender\CoreBundle\Component\Signer;
use OwsProxy3\CoreBundle\Component\BuzzClient;
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
        try {
            $url = Utils::appendQueryParams($url, $request->query->all());
            $headers = Utils::getHeadersFromRequest($request);
            if (null === $content) {
                $rq = $request->getContent();
                if ($rq || $request->getMethod() === Request::METHOD_POST) {
                    // force outgoing request to POST, even with empty body
                    $content = $rq ?: '';
                }
            }
            $content = Utils::extendPostContent($content, $request->request->all());
            if ($content !== null) {
                $proxy_query = ProxyQuery::createPost($url, $content, $headers);
            } else {
                $proxy_query = ProxyQuery::createGet($url, $headers);
            }
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        return $this->getQueryResponse($proxy_query, $request);
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
            $proxy_query = ProxyQuery::createFromRequest($request, 'url');
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
        return $this->getQueryResponse($proxy_query, $request);
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

    protected function getQueryResponse(ProxyQuery $query, Request $request)
    {
        /** @var BuzzClient $buzzClient */
        $buzzClient = $this->get('owsproxy.buzz_client');
        try {
            $browserResponse = $buzzClient->handleQuery($query);
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage() . " " . $e->getCode());
            return $this->exceptionHtml($e);
        }
        return $this->convertBuzzResponse($browserResponse, $request, $query);
    }

    /**
     * @param Response $response
     * @param ProxyQuery $query
     * @return Response
     */
    protected function formatResponse(Response $response, ProxyQuery $query)
    {
        // Emulate Buzz behaviour: treat HTTP 201 / Created as empty ok response
        if (!($response->isOk() || $response->isEmpty() || $response->getStatusCode() === Response::HTTP_CREATED)) {
            $statusCode = $response->getStatusCode();
            $statusText = Response::$statusTexts[$statusCode];
            $host = $query->getHostname();
            $message = "{$host} says: {$statusCode} {$statusText}";
            $response = $this->render("OwsProxy3CoreBundle::exception.html.twig", array(
                'exception' => array(
                    'message' => $message,
                ),
            ));
            $response->setStatusCode($statusCode);
        }
        return $response;
    }

    /**
     * @param \Buzz\Message\Response $browserResponse
     * @param Request $request
     * @param ProxyQuery $query
     * @return Response
     */
    protected function convertBuzzResponse(\Buzz\Message\Response $browserResponse, Request $request, ProxyQuery $query)
    {
        $response = new Response();
        Utils::setHeadersFromBrowserResponse($response, $browserResponse);
        $this->restoreOriginalCookies($response, $request);
        $content = $browserResponse->getContent();
        $response->setContent($content);
        $response->setStatusCode($browserResponse->getStatusCode(), $browserResponse->getReasonPhrase() ?: null);
        $response = $this->formatResponse($response, $query);
        return $response;
    }

    protected function restoreOriginalCookies(Response $response, Request $request)
    {
        foreach ($request->cookies as $key => $value) {
            $response->headers->removeCookie($key);
            $response->headers->setCookie(new Cookie($key, $value));
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
