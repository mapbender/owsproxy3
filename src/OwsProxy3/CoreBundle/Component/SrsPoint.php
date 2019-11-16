<?php

namespace OwsProxy3\CoreBundle\Component;

/**
 * @author Paul Schmidt
 */
class SrsPoint
{

    public static $POSTGIS = "POSTGIS";

    /**
     *
     * @var float the point's longitude 
     */
    protected $lon;

    /**
     *
     * @var float the point's latitude 
     */
    protected $lat;

    /**
     *
     * @var int the point's srs  
     */
    protected $srs;

    /**
     * 
     * @param float $lon the point's longitude 
     * @param float $lat the point's latitude 
     * @param int $srs
     */
    public function __construct($lon, $lat, $srs)
    {
        $this->lon = $lon;
        $this->lat = $lat;
        $this->srs = $srs;
    }

    /**
     * Gets the point's longitude
     * 
     * @return float
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * Gets the point's latitude
     * 
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @return int
     */
    public function getSrs()
    {
        return $this->srs;
    }

    /**
     * Generates the "geomfromtext function"
     * 
     * @param int $databasetype
     * @return string
     */
    public function getGeomFromText($databasetype)
    {
        $wkt = "";
        switch(strtoupper($databasetype))
        {
            case SrsPoint::$POSTGIS:
                $wkt = "ST_GEOMFROMTEXT('POINT(" . $this->lon . " " . $this->lat . ")'," . $this->srs . ")";
                break;

            default:
                break;
        }
        return $wkt;
    }

    /**
     * Generates the "geomfromtext function" for multipoint object
     * 
     * @param int $databasetype
     * @param static[] $points
     * @return string
     */
    public static function getGeomFromTextMultiPoint(
        $databasetype, $points)
    {
        $wkt = "";
        switch(strtoupper($databasetype))
        {
            case SrsPoint::$POSTGIS:
//                $wkt .= "ST_GEOMFROMTEXT('MULTIPOINT(";
                foreach($points as $point)
                {
                    $wkt .= "," . $point->getLon() . " " . $point->getLat();
                }
                $wkt = "ST_GEOMFROMTEXT('MULTIPOINT("
                        . substr($wkt, 1) . ")'," . $point->getSrs() . ")";
                break;

            default:
                break;
        }
        return $wkt;
    }

}
