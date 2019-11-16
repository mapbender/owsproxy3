<?php

namespace OwsProxy3\CoreBundle\Component;

/**
 * Clipping class
 *
 * @author Paul Schmidt
 */
class Clipping
{

    /**
     *
     * @var string svg envelope 
     */
    private $svgEnvelope;

    /**
     *
     * @var string svg geometry 
     */
    private $svgGeometry;
    
    /**
     *
     * @var string svg geometry 
     */
    private $wktGeometry;

    /**
     *
     * @var int the count of the pixels at svg geometry
     */
    private $pixelNumber = 0;
    
    /**
     *
     * @var int the count of colors at result image
     */
    private $colorNumber = 0;

    /**
     *
     * @var boolean
     */
    private $intersects = false;

    /**
     *
     * @var boolean
     */
    private $containts = false;
    
    
    public function getPixelNumber()
    {
        return $this->pixelNumber;
    }
    
    
    public function getColorNumber()
    {
        return $this->colorNumber;
    }

    /**
     * @return boolean
     */
    public function getContains()
    {
        return $this->containts;
    }

    /**
     * @return boolean
     */
    public function getIntersects()
    {
        return $this->intersects;
    }
    
    /**
     * Returns the geometry as wkt
     * 
     * @return string
     */
    public function getWktGeometry()
    {
        return $this->wktGeometry;
    }

    /**
     * Queries the svgEnvelope and the svgGeometry from the database
     * 
     * @param type $conn the database connection
     * @param array $MultiPointBbox the bounding box as a collection of the 
     * SrsPoints
     * @param string $database the name of the database
     * @param string $geomColumn the name of the geometry column
     * @param string $whereCol the name of the column at the where clause
     * @param array $whereColValues the values for the column at the where clause
     * @param int $width the width of the image
     * @param int $height the height of the image
     */
    public function findSvgGeometry($conn, $MultiPointBbox, $database,
            $geomColumn, $whereCol, $whereColValues, $width, $height)
    {
        $bboxGeoText = "ST_ENVELOPE("
                . SrsPoint::getGeomFromTextMultiPoint("POSTGIS", $MultiPointBbox)
                . ")";
        $paramArray = array();
        if(isset($whereColValues[0]))
        {
            if(is_string($whereColValues[0]))
            {
                $paramArray = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
            } else if(is_int($whereColValues[0]))
            {
                $paramArray = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }
        $srsInt = $MultiPointBbox[0]->getSrs();
        $sql = "";
        if("POSTGIS")
        {
            $sql .= "SELECT st_assvg(ST_INTERSECTION(" . $bboxGeoText
                    . ", ST_UNION(ST_TRANSFORM(" . $geomColumn . ","
                    . $srsInt . ")))) as svg_geom"
                    . ",ST_INTERSECTS(ST_UNION(ST_TRANSFORM(" . $geomColumn
                    . "," . $srsInt . "))," . $bboxGeoText . ") as intersects"
                    . ",ST_CONTAINS(ST_UNION(ST_TRANSFORM(" . $geomColumn . ","
                    . $srsInt . "))," . $bboxGeoText . ") as contains"
                    . ",area2d(" . $bboxGeoText . ") as bboxarea"
                    . ",area2d(ST_INTERSECTION(" . $bboxGeoText
                    . ",ST_UNION(ST_TRANSFORM(" . $geomColumn
                    . "," . $srsInt . ")))) as geomarea"
                    . ",st_assvg(" . $bboxGeoText . ") as envelope"
                    . " FROM " . $database
                    . " WHERE " . $whereCol . " IN (?) AND ST_INTERSECTS("
                    . $bboxGeoText . ",ST_TRANSFORM(" . $geomColumn . "," . $srsInt . "))";
        }
        $stmt = $conn->executeQuery($sql, array($whereColValues), $paramArray);

        $row = $stmt->fetch();

        $this->svgEnvelope = $row["envelope"];
        $this->svgGeometry = $row["svg_geom"];
        $this->intersects = $row["intersects"] === null ? false : $row["intersects"];
        $this->contains = $row["contains"] === null ? false : $row["contains"];


        $geomarea = $row["geomarea"] !== null ? floatval($row["geomarea"]) : 0.0;
        $bboxarea = $row["bboxarea"];
        $factor = $geomarea / $bboxarea;
        $this->pixelNumber = intval($factor * $width * $height);
    }
    
    /**
     * Queries the svgEnvelope and the svgGeometry from the database
     * 
     * @param type $conn the database connection
     * @param array $MultiPointBbox the bounding box as a collection of the 
     * SrsPoints
     * @param string $database the name of the database
     * @param string $geomColumn the name of the geometry column
     * @param string $whereCol the name of the column at the where clause
     * @param array $whereColValues the values for the column at the where clause
     * @param int $width the width of the image
     * @param int $height the height of the image
     */
    public function findWktGeometry($conn, $MultiPointBbox, $database,
            $geomColumn, $whereCol, $whereColValues, $width, $height)
    {
        $bboxGeoText = "ST_ENVELOPE("
                . SrsPoint::getGeomFromTextMultiPoint("POSTGIS", $MultiPointBbox)
                . ")";
        $paramArray = array();
        if(isset($whereColValues[0]))
        {
            if(is_string($whereColValues[0]))
            {
                $paramArray = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
            } else if(is_int($whereColValues[0]))
            {
                $paramArray = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }
        $srsInt = $MultiPointBbox[0]->getSrs();
        $sql = "";
        if("POSTGIS")
        {
            $sql .= "SELECT astext(ST_INTERSECTION(" . $bboxGeoText
                    . ", ST_UNION(ST_TRANSFORM(" . $geomColumn . ","
                    . $srsInt . ")))) as wkt_geom"
                    . ",ST_INTERSECTS(ST_UNION(ST_TRANSFORM(" . $geomColumn
                    . "," . $srsInt . "))," . $bboxGeoText . ") as intersects"
                    . ",ST_CONTAINS(ST_UNION(ST_TRANSFORM(" . $geomColumn . ","
                    . $srsInt . "))," . $bboxGeoText . ") as contains"
                    . ",area2d(" . $bboxGeoText . ") as bboxarea"
                    . ",area2d(ST_INTERSECTION(" . $bboxGeoText
                    . ",ST_UNION(ST_TRANSFORM(" . $geomColumn
                    . "," . $srsInt . ")))) as geomarea"
                    . " FROM " . $database
                    . " WHERE " . $whereCol . " IN (?) AND ST_INTERSECTS("
                    . $bboxGeoText . ",ST_TRANSFORM(" . $geomColumn . "," . $srsInt . "))";
        }
        $stmt = $conn->executeQuery($sql, array($whereColValues), $paramArray);

        $row = $stmt->fetch();

        $this->wktGeometry = $row["wkt_geom"];
        $this->intersects = $row["intersects"] === null ? false : $row["intersects"];
        $this->contains = $row["contains"] === null ? false : $row["contains"];


        $geomarea = $row["geomarea"] !== null ? intval($row["geomarea"]) : 0;
        $bboxarea = $row["bboxarea"];
        $factor = $geomarea / $bboxarea;
        $this->pixelNumber = intval($factor * $width * $height);
    }

    /**
     * Creates SVG for svgEnvelope and svgGeometry
     * 
     * @param type $geometry
     * @param \stdClass $viewbox viewbox from $this->getViewBox($envelope)
     * @return string svg as string
     */
    public function getSVG($width, $height)
    {
        if($this->svgEnvelope === null)
        {
            return null;
        }
        $min_max = explode(" ",
                           preg_replace('/M\s?|\sL|\sZ/', "", $this->svgEnvelope));
        $min_x = floatval($min_max[0]);
        $min_y = floatval($min_max[1]);
        $max_x = floatval($min_max[0]);
        $max_y = floatval($min_max[1]);
        for($i = 1; $i < count($min_max); $i = $i + 2)
        {
            $min_x = min($min_x, floatval($min_max[$i - 1]));
            $max_x = max($max_x, floatval($min_max[$i - 1]));
            $min_y = min($min_y, floatval($min_max[$i]));
            $max_y = max($max_y, floatval($min_max[$i]));
        }
        $vb = $min_x . " " . $min_y . " " . ($max_x - $min_x) . " " . ($max_y - $min_y);

        return '<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="' . $width . 'px" height="' . $height . 'px" viewBox="' . $vb . '" version="1.1" xmlns="http://www.w3.org/2000/svg">
	<path d="' . ($this->svgGeometry === null
                            ? '' : $this->svgGeometry) . '" fill="black" />
</svg>';
    }

    /**
     * Clips the svgGeometry from the image
     * 
     * @param type $sourceImage
     * @param type $maskImage
     * @param string $format the image format
     * @return \Imagick the clipped image
     */
    public function clipImage($sourceImage, $maskImage, $format)
    {
        $source = new \Imagick();
        $source->readImageBlob($sourceImage);

        $mask = new \Imagick();
        $mask->setBackgroundColor(new \ImagickPixel('transparent'));

        $mask->readImageBlob($maskImage);
        $mask->setImageFormat($format);

        $source->setImageMatte(1);
        $source->compositeImage($mask, \Imagick::COMPOSITE_DSTIN, 0, 0);
        $mask->destroy();
        unset($mask);
        $this->colorNumber = $source->getImageColors();
        $content = $source->getimageblob();
//        $mask->compositeImage($source, \Imagick::COMPOSITE_DSTOUT, 0, 0);
//        $content = $mask->getimageblob();
        $source->destroy();
        unset($source);
        return $content;
    }

    /**
     * Creates the graphical image
     * 
     * @param int $width the image widht
     * @param int $height the image height
     * @param string $format the image format
     * @return \Imagick the image
     */
    public function createImage($width, $height, $format)
    {
        $image = new \Imagick();
        $pixel = new \ImagickPixel('none');
        $image->newImage(intval($width), intval($height), $pixel);
        $image->setImageFormat($format);
        $this->colorNumber = $image->getImageColors();
        $this->pixelNumber = 0;
        $content = $image->getimageblob();
        $image->destroy();
        unset($image);
        return $content;
    }

    /**
     * Checks if 
     * 
     * @param type $conn the database connection
     * @param string $database the name of the database
     * @param string $whereCol the name of the column at the where clause
     * @param array $whereColValues the values for the column at the where clause
     * @param string $geomColumn the name of the geometry column
     * @param SrsPoint $point the world coordinate of the GetFeatureInfo click
     * @return boolean true if 
     */
    public function checkFeatureInfo($conn, $database, $whereCol,
            $whereColValues, $geomCol, SrsPoint $point)
    {
        $paramArray = array();
        if(isset($whereColValues[0]))
        {
            if(is_string($whereColValues[0]))
            {
                $paramArray = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
            } else if(is_int($whereColValues[0]))
            {
                $paramArray = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            }
        }
        $sql = "";
        if("POSTGIS")
        {
            $sql .= "SELECT count(*) AS num_hits FROM " . $database
                    . " WHERE " . $whereCol . " IN (?) AND"
                    . " ST_INTERSECTS(" . $point->getGeomFromText("POSTGIS") . ","
                    . "ST_TRANSFORM(" . $geomCol . "," . $point->getSrs() . "))";
        }
        $stmt = $conn->executeQuery($sql, array($whereColValues), $paramArray);
        $row = $stmt->fetch();
        return $row["num_hits"] == 0 ? false : true;
    }

}
