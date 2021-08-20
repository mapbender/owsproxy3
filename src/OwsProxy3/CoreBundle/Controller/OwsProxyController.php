<?php
namespace OwsProxy3\CoreBundle\Controller;

// @todo v3.2: remove BadSignatureException references
use ArsGeografica\Signing\BadSignatureException;
use Mapbender\CoreBundle\Component\Signer;
use OwsProxy3\CoreBundle\Component\HttpFoundationClient;
use OwsProxy3\CoreBundle\Component\Utils;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author A.R.Pour
 * @author P. Schmidt
 */
class OwsProxyController
{
    /** @var HttpFoundationClient */
    protected $client;
    /** @var Signer */
    protected $signer;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(HttpFoundationClient $client,
                                Signer $signer,
                                LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->signer = $signer;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Handles the client's request
     * NOTE: no route; only reachable via Symfony internal SubRequest
     * @deprecated for complex usage, uncontrollable query param merging and uncontrollable POST behaviour
     * Use owsproxy.http_foundation_client service to internally retrieve Response objects from ProxyQuery objects (GET + POST)
     * For Mapbender >= 3.0.8-beta1, you can also use getUrl method on mapbender.http_transport.service for simple url-based GET requests
     * Use Utils:: methods to ease URL and post content construction.
     *
     * @see https://github.com/mapbender/mapbender/blob/v3.0.8.4/src/Mapbender/Component/Transport/OwsProxyTransport.php
     *
     * @param Request $request
     * @param string $url
     * @param string $content for POST
     * @return Response
     * @todo v3.3: remove
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
            $url = Utils::filterDuplicateQueryParams($url, false);
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
        $url = $request->query->get('url');

        try {
            $proxy_query = ProxyQuery::createFromRequest($request, 'url');
            $this->signer->checkSignedUrl($url);
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
     * @param ProxyQuery $query
     * @param Request $request
     * @return Response
     */
    protected function getQueryResponse(ProxyQuery $query, Request $request)
    {
        $response = $this->client->handleQuery($query);
        $this->restoreOriginalCookies($response, $request);
        return $response;
    }

    protected function restoreOriginalCookies(Response $response, Request $request)
    {
        foreach ($request->cookies as $key => $value) {
            $response->headers->removeCookie($key);
            $response->headers->setCookie(new Cookie($key, $value));
        }
    }
}
