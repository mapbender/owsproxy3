<?php

namespace OwsProxy3\CoreBundle\Controller;

use Buzz\Message\MessageInterface;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus403Exception;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\WmsProxy;
use OwsProxy3\CoreBundle\Component\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use OwsProxy3\CoreBundle\Component\Url;

/**
 * Description of OwsProxyController
 *
 * @author P. Schmidt
 */
class OwsProxyController extends Controller
{

    /**
     * @Route("/")
     */
    public function entryPointAction()
    {
        $request = $this->get('request');
        $service = Utils::getParamValueFromAll($request, "service", true);
        // Switch proxy
        switch(strtoupper($service))
        {
            case 'WMS':
                try
                {
                    $dispatcher = $this->container->get('event_dispatcher');
                    $proxy_config = $this->container->getParameter("owsproxy.proxy");
                    $proxy_query = ProxyQuery::createFromRequest($request);
                    $proxy = new WmsProxy($dispatcher, $proxy_config, $proxy_query);
                    $browserResponse = $proxy->handle();
                    $headers = $browserResponse->getHeaders();
                    $response = new Response();
                    foreach($headers as $header)
                    {
                        $pos = stripos($header, ":");
                        if(is_int($pos))
                        {
                            $response->headers->set(substr($header, 0, $pos),
                                                           substr($header,
                                                                  $pos + 1));
                        }
                    }
                    $content = $browserResponse->getContent();
                    $response->setContent($content);
                    return $response;
                } catch(HTTPStatus403Exception $e)
                {
                    return $this->exceptionImage($e, $request);
                } catch(HTTPStatus502Exception $e)
                {
                    return $this->exceptionImage($e, $request);
                } catch(\Exception $e)
                {
                    if($e->getCode() === 0)
                            $e = new \Exception($e->getMessage(), 500);
                    return $this->exceptionHtml($e);
                }
            default: //@TODO ?
                return $this->exceptionHtml(new \Exception('Unknown Service Type', 404));
        }
    }

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

    private function exceptionImage(\Exception $e, $request)
    {
        $format = Utils::getParamValueFromAll($request, "format", true);
        $w = Utils::getParamValueFromAll($request, "width", true);
        $h = Utils::getParamValueFromAll($request, "height", true);
        if($format === null || $w === null || $h === null
                || !is_int(strpos(strtolower($format), "image"))
                || intval($w) === 0 || intval($h) === 0)
        {
            return exceptionHtml($e);
        }
        $image = new \Imagick();
        $draw = new \ImagickDraw();
        $pixel = new \ImagickPixel('none');

        $image->newImage(intval($w), intval($h), $pixel);

        $draw->setFillColor('grey');
        $draw->setFontSize(30);
        $st_x = 200;
        $st_y = 200;
        $ang = -45;
        for($x = 10; $x < $w; $x += $st_x)
        {
            for($y = 10; $y < $h; $y += $st_y)
            {
                $image->annotateImage($draw, $x, $y, $ang, $e->getMessage());
            }
        }

        $image->setImageFormat('png');

        $response = new Response();
        $response->headers->set('Content-Type', "image/png");
        $response->setStatusCode($e->getCode());
        $response->setContent($image->getimageblob());

        return $response;
    }

}
