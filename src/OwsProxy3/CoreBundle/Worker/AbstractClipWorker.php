<?php

namespace OwsProxy3\CoreBundle\Worker;

use OwsProxy3\CoreBundle\Component\Clipping;
use OwsProxy3\CoreBundle\Component\SrsPoint;
use OwsProxy3\CoreBundle\Component\Utils;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ClipWorker for clipping
 *
 * @author Paul Schmidt
 */
abstract class AbstractClipWorker implements AbstractWorker
{
    /**
     *
     * @var logger
     */
    protected $logger;

    /**
     *
     * @var ContainerInterface container 
     */
    protected $container;

    /**
     * Creates an instance
     * 
     * @param ContainerInterface $container container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    /**
     * Handles the parameters before a request
     * 
     * @param ProxyEvent $event event
     * @throws HTTPStatus403Exception if no gds found
     */
    public function onBeforeProxyEvent(BeforeProxyEvent $event)
    {
        
    }

    /**
     * Handles the parameters after a request
     * 
     * @param ProxyEvent $event event
     * @throws HTTPStatus403Exception if no gds found
     */
    public function onAfterProxyEvent(AfterProxyEvent $event)
    {
        
    }

    /**
     * 
     * 
     * @param \OwsProxy3\CoreBundle\Event\BeforeProxyEvent $event
     * @param type $connection database connection for $databaseName
     * @param string $databaseName database name
     * @param string $whereColumnName column name for "where" 
     * @param array $whereColumnValues values for $whereColumnName
     * @param string $geometryColumnName geometry column name
     * @return \OwsProxy3\CoreBundle\Component\Clipping 
     * @throws HTTPStatus403Exception if user not authorised
     */
    protected function handleFeatureInfoBefore(BeforeProxyEvent $event,
            $connection, $databaseName, $whereColumnName,
            array $whereColumnValues, $geometryColumnName)
    {
        $this->logger->info('AbstractClipWorker->handleFeatureInfoBefore');
        $x      = $event->getProxyQuery()->getGetPostParamValue("x", true);
        $y      = $event->getProxyQuery()->getGetPostParamValue("y", true);
        $bbox   = explode(",",
                $event->getProxyQuery()->getGetPostParamValue('bbox', true));
        $width  = $event->getProxyQuery()->getGetPostParamValue('width', true);
        $height = $event->getProxyQuery()->getGetPostParamValue('height', true);
        $srs    = $event->getProxyQuery()->getGetPostParamValue('crs', true);
        $srs    = $srs === null ? $event->getProxyQuery()->getGetPostParamValue('srs',
                        true) : $srs;
        if ($x === null || $y === null || $srs === null || $bbox === null || $width
                === null || $height === null || $srs === null)
        {
            throw new HTTPStatus403Exception();
        }

        $tmp      = explode(":", $srs);
        $srsInt   = intval($tmp[count($tmp) - 1]);
        $x        = floatval($x);
        $y        = floatval($y);
        $xsrs     = floatval($bbox[0]) + $x * ((floatval($bbox[2]) - floatval($bbox[0]))
                / floatval($width));
        $ysrs     = floatval($bbox[3]) - $y * ((floatval($bbox[3]) - floatval($bbox[1]))
                / floatval($height));
        $clipping = new Clipping();
        if (!$clipping->checkFeatureInfo($connection, $databaseName,
                        $whereColumnName, $whereColumnValues,
                        $geometryColumnName, new SrsPoint($xsrs, $ysrs, $srsInt)))
        {
            $this->logger->info('AbstractClipWorker->handleFeatureInfoBefore clipping->checkFeatureInfo == false');
            throw new HTTPStatus403Exception("403 FeatureInfoBefore checkFeatureInfo == false");
        }
        return $clipping;
    }

    protected function handleGetMapAfter(AfterProxyEvent $event, $connection,
            $databaseName, $whereColumnName, array $whereColumnValues,
            $geometryColumnName)
    {
        $this->logger->info('AbstractClipWorker->handleGetMapAfter');
        $bbox_str = $event->getProxyQuery()->getGetPostParamValue('bbox', true);
        $bbox     = explode(",", $bbox_str);
        $width    = $event->getProxyQuery()->getGetPostParamValue('width', true);
        $height   = $event->getProxyQuery()->getGetPostParamValue('height', true);
        $srs      = $event->getProxyQuery()->getGetPostParamValue('crs', true);
        $tmp      = explode(":",
                $srs === null ? $event->getProxyQuery()->getGetPostParamValue('srs',
                                true) : $srs);
        $srs      = intval($tmp[count($tmp) - 1]);

        $browserResponse = $event->getBrowserMessage();
        $contentType     = $browserResponse->getHeader("Content-Type");
        if (is_int(strpos($contentType, "image/")))
        {
            $this->logger->info('AbstractClipWorker->handleGetMapAfter response: '.$contentType);
            $clipping = new Clipping();

            $format_ = explode("/", $contentType);
            $format  = strtolower($format_[1]);

            $multiPointBbox = array(
                new SrsPoint(floatval($bbox[0]), floatval($bbox[1]), $srs),
                new SrsPoint(floatval($bbox[2]), floatval($bbox[3]), $srs));

            $clipping->findSvgGeometry($connection, $multiPointBbox,
                    $databaseName, $geometryColumnName, $whereColumnName,
                    $whereColumnValues, intval($width), intval($height));

            if ($clipping->getContains())
            {
                $this->logger->info('AbstractClipWorker->handleGetMapAfter Contains');
            }
            else if ($clipping->getIntersects())
            {
                $this->logger->info('AbstractClipWorker->handleGetMapAfter Intersects');
                $browserResponse->setContent($clipping->clipImage($browserResponse->getContent(),
                                $clipping->getSVG($width, $height), $format));
            }
            else
            {
                $this->logger->info('AbstractClipWorker->handleGetMapAfter keine Überschneidung');
                $browserResponse->setContent($clipping->createImage($width,
                                $height, $format));
            }
            return $clipping;
        }
        else
        {
            $this->logger->info('AbstractClipWorker->handleGetMapAfter response: '.$contentType);
            return null;
        }
    }

}