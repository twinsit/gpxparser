<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GpxFilesProcessor
 *
 * @author Vittorio
 * @version 1.0
 * @date 2012/05/10
 */
class GpxFileProcessor {

    private $filePath;
    private $tkrseg;
    private $sampledTrack;
    private $step;
    private $a;
    private $b;
    private $f;
    private $distance;
    private $maxAltitude;
    private $minAltitude;

    /**
     * @brief Constructor of class.
     *
     * @param string $filePath the path where is stored the gpx file.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/09
     */
    function __construct($filePath) {

        $this->filePath = $filePath;
        $this->tkrseg = NULL;
        /* Variable for calculating distance beetween two point */
        $this->a = 6378137;
        $this->b = 6356752.3142;
        $this->f = 1/298.257223563;
        /* Default value for sampling track */
        $this->step = 500;
        $this->maxAltitude = -1000;
        $this->minAltitude = 10000;
    }

    /**
     * @brief Check if a gpx file is valid.
     *
     * @param string $path path of gpx file to check.
     * @return boolean true if is a valid gpx file, false otherwise.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/08
     */
    public static function isValidGpxFile($path) {

        /* Check if is well formatted xml file */
        $parsed = @ simplexml_load_file($path);

        if ($parsed === false)
            return false;

        /* Support only version 1.1 */
        $attributes = $parsed->attributes();

        if (strcmp($attributes['version'], "1.1"))
            return false;
        /* Check if exists trk name */
        if ($parsed->trk->name == NULL)
            return false;

        echo "***" . $parsed->trk->name  . "**";

        return true;
    }

    /**
     * @brief Estract a string contained two beetwen tag.
     *
     * @param string $something the return string.
     * @param string $start_tag the initial tag.
     * @param string $end_tag the final tag.
     *
     * @return Nothing.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __extract_between_tag(&$something, $init_tag, $end_tag) {

        $something = stristr($something, $init_tag);

        if(!$something) return NULL;
        $end = stripos($something, $end_tag, strlen($init_tag));
        $treasure = trim(substr($something, strlen($init_tag), $end - strlen($init_tag)));
        $something = substr($something, $end + strlen($end_tag));
        return $treasure;
    }

    /**
     * @brief Calculate time interval beetween two point.
     *
     * @param int $startPoint index of start point.
     * @param int $endPoint index of end point.
     * @return int second beetween two point.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __getInterval($startPoint, $endPoint) {
        return $this->trkseg[$endPoint]['ts'] - $this->trkseg[$startPoint]['ts'];
    }

    /**
     * @brief Calculate delta elevation beetween two point and update maxAltitude
     * and minAltitude.
     * @param int $startPoint index of start point.
     * @param int $endPoint index of end point.
     * @return float delta elevation.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __getRise ($startPoint, $endPoint) {

        if ($this->trkseg[$endPoint]['ele'] > $this->maxAltitude)
            $this->maxAltitude = $this->trkseg[$endPoint]['ele'];

        if ($this->trkseg[$endPoint]['ele'] < $this->minAltitude)
            $this->minAltitude = $this->trkseg[$endPoint]['ele'];

        return $this->trkseg[$endPoint]['ele'] - $this->trkseg[$startPoint]['ele'];
    }

    /**
     * @brief Calculate distance beetween two point in meter.
     *
     * @param int $startPoint index of start point.
     * @param int $endPoint index of end point
     * @return float the distance.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __getP2PDistance($startPoint, $endPoint) {

        $lat1 = $this->trkseg[$startPoint]['lat'];
        $lon1 = $this->trkseg[$startPoint]['lon'];
        $lat2 = $this->trkseg[$endPoint]['lat'];
        $lon2 = $this->trkseg[$endPoint]['lon'];
        $L = deg2rad($lon2-$lon1);

        $U1 = atan((1 - $this->f) * tan(deg2rad($lat1)));
        $U2 = atan((1 - $this->f) * tan(deg2rad($lat2)));

        $sinU1 = sin($U1);
        $cosU1 = cos($U1);
        $sinU2 = sin($U2);
        $cosU2 = cos($U2);
        $mylambda = $L;
        $lambdaP = 2*M_PI;
        $iterLimit = 20;

        while( abs($mylambda-$lambdaP) > 1e-12 && (--$iterLimit)>0 ){
            $sinLambda = sin($mylambda);
            $cosLambda = cos($mylambda);
            $sinSigma = sqrt(($cosU2*$sinLambda) * ($cosU2*$sinLambda) + ($cosU1*$sinU2-$sinU1*$cosU2*$cosLambda) * ($cosU1*$sinU2-$sinU1*$cosU2*$cosLambda));
            if ($sinSigma==0) {
                // Punti coincidenti
                return 0;
            }
            $cosSigma = $sinU1*$sinU2 + $cosU1*$cosU2*$cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
            $cosSqAlpha = 1 - $sinAlpha*$sinAlpha;
            $cos2SigmaM = $cosSigma - 2*$sinU1*$sinU2/$cosSqAlpha;
            if ($cos2SigmaM != $cos2SigmaM){
                // Siamo sull equatore
                $cos2SigmaM = 0;
            }
            $C = $this->f/16*$cosSqAlpha*(4+$this->f*(4-3*$cosSqAlpha));
            $lambdaP = $mylambda;
            $mylambda = $L + (1-$C) * $this->f * $sinAlpha * ($sigma + $C*$sinSigma*($cos2SigmaM+$C*$cosSigma*(-1+2*$cos2SigmaM*$cos2SigmaM)));
        }

        if ($iterLimit==0){
            // La formula non converge
            return 0;
        }
        $uSq = $cosSqAlpha * ($this->a*$this->a - $this->b*$this->b) / ($this->b*$this->b);
        $A = 1 + $uSq/16384*(4096+$uSq*(-768+$uSq*(320-175*$uSq)));
        $B = $uSq/1024 * (256+$uSq*(-128+$uSq*(74-47*$uSq)));
        $deltaSigma = $B*$sinSigma*($cos2SigmaM+$B/4*($cosSigma*(-1+2*$cos2SigmaM*$cos2SigmaM)-$B/6*$cos2SigmaM*(-3+4*$sinSigma*$sinSigma)*(-3+4*$cos2SigmaM*$cos2SigmaM)));
        $s = $this->b*$A*($sigma-$deltaSigma);


        return $s;

    }

    /**
     * @brief Calculate distance, interval and delta elevation beetween all point
     * of track.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __point2interval() {

        //if ($this->trkseg['totalDistance'])
        //    return $this->trkseg['totalDistance'];

        $distance = 0;
        $this->trkseg[0]['distance'] = 0;
        $this->trkseg[0]['interval'] = 0;
        $this->trkseg[0]['rise'] = 0;

        for ($i = 1; $i < count($this->trkseg); $i++) {

            $p2pDistance = $this->__getP2PDistance($i-1, $i);

            $this->trkseg[$i]['distance'] = $p2pDistance;
            $this->trkseg[$i]['interval'] = $this->__getInterval($i-1, $i);
            $this->trkseg[$i]['rise'] = $this->__getRise($i-1, $i);
            $distance += $p2pDistance;
        }

        $this->trkseg['totalDistance'] = $distance;
        $this->distance = $distance;
    }

    /**
     * @brief Sample the track.
     *
     * @param int $step the sampling step
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __sampleData($step = 50) {

        /**
         * FIXME: Check $step value
         */
        $step = $this->step;

        $dist = 0;
        $limit = $step;
        $prevDist = 0;
        $sampledTrack = array();
        $j = 0;
        $idx = 0;

        $elem['ele'] = $this->trkseg['0']['ele'];
        $elem['interval'] = 0;
        $elem['ts'] = $this->trkseg['0']['ts'];
        array_push($sampledTrack, $elem);

        for ($i = 0; $i < count($this->trkseg) -1; $i++) {
            $dist += $this->trkseg[$i]['distance'];

            if ($dist >= $limit){
                $elem['ele'] = round(((($this->trkseg[$i]['ele'] - $this->trkseg[$i-1]['ele'])/($dist - $this->trkseg[$i-1]['ele']))*($limit - $prevDist))+ $this->trkseg[$i-1]['ele']);
                $elem['ts'] = round(((($this->trkseg[$i]['ts'] - $this->trkseg[$i-1]['ts'])/($dist - $this->trkseg[$i-1]['ts']))*($limit - $prevDist))+ $this->trkseg[$i-1]['ts']);
                $elem['interval'] = $elem['ts'] - $sampledTrack[$idx]['ts'];
                $elem['dist'] = $step * ($idx +1);
                $idx++;
                array_push($sampledTrack, $elem);
                $limit += $this->step;
            }

            $prevDist = $dist;
        }

        unset($this->trkseg);
        $this->sampledTrack = $sampledTrack;
    }

    /**
     * @brief Calculate slope beetween two points into gpx array.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/10/05
     */
    private function __calculateSlope() {

        $this->sampledTrack[0]['gradient'] = 0;
        for ($i = 1; $i < count($this->sampledTrack); $i++) {

            $slope = ($this->sampledTrack[$i]['ele'] - $this->sampledTrack[$i-1]['ele']) / $this->step;
            $this->sampledTrack[$i]['gradient'] = $slope;
        }
        //return $this->sampledTrack;
    }

    /**
     * @brief Convert sting in unix timestamp.
     *
     * @param string $time the string to convert.
     *
     * @return int the unix timestamp.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __getTimestamp($time) {

        $time = $this->__extract_between_tag($time, "T", "Z");
        $time = strtotime($time);
        return $time;
    }

    /**
     * @brief Convert simple xml array in gpx array.
     *
     * @param array simpleXml array.
     *
     * @return arrray the converted array.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __simpleXml2gpx($points) {

        $gpxArray =  array();
        foreach ($points as $point) {
            $pointAttribute = $point->attributes();
            $elem ['lon'] = (float)$pointAttribute['lon'];
            $elem ['lat'] = (float)$pointAttribute['lat'];
            $elem ['ele'] = (float)$point->ele;
            $elem ['ts'] = $this->__getTimestamp($point->time);
            array_push($gpxArray, $elem);
        }

        return $gpxArray;
    }

    /**
     * @brief Calculate track index based on gradient.
     *
     * @param float $gradient the gradient of track.
     * @return int the index of the segment.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __getTrackIndex($gradient) {

        if ($gradient < 0)
            return 1;
        if ( $gradient >= 0 && $gradient < 2)
            return 2;
        if ( $gradient >= 2 && $gradient < 4)
            return 3;
        if ( $gradient >= 4 && $gradient < 6)
            return 4;
        if ( $gradient >= 6 && $gradient < 7)
            return 5;
        if ( $gradient >= 7 && $gradient < 8)
            return 6;
        if ( $gradient >= 8 && $gradient < 9)
            return 7;
        if ( $gradient >= 9 && $gradient < 10)
            return 8;
        if ( $gradient >= 10 && $gradient < 12)
            return 9;
        if ( $gradient >= 12)
            return 10;
    }

    /**
     * @brief Split in 10 track the current track segment based on gradient.
     *
     * @param boolean $adjustSlope if true normalize slope. Note: unused.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/10
     */
    private function __splitTrack($adjustSlope = false) {

        if ($adjustSlope)
            $this->getMaxSlope();

        /*
         * Ten tracks:
         *
         *   1. < -1
         *   2. -1 < x < 2
         *   3. 2 < x < 4
         *   4. 4 < x < 6
         *   5. 6 < x < 7
         *   6. 7 < x < 8
         *   7. 8 < x < 9
         *   8. 9 < x < 10
         *   9. 10 < x < 12
         *  10.  > 12
         */

        for ($i = 1; $i < count($this->sampledTrack); $i++) {

            $idx = $this->__getTrackIndex(($this->sampledTrack[$i]['gradient'])*100);

            for ($j = 0; $j < 11; $j++) {
                if ($idx == $j){
                    if ($i == 1)
                        $this->sampledTrack[0]['track' . $j] = $this->sampledTrack[0]['ele'];
                    $this->sampledTrack[$i]['track' . $j] = $this->sampledTrack[$i]['ele'];
                    //$this->sampledTrack[$i -1]['track' . $j] = $this->sampledTrack[$i -1]['ele'];
                }
                else
                    $this->sampledTrack[$i]['track' . $j] = 0;
            }
        }

        //return $this->sampledTrack;
    }

    /**
     * @brief Convert gpx to json.
     *
     * @author Vittorio
     * @version 1.0
     * @date 2012/05/2012
     */
    public function getJson() {
        /* Read file */
        $gpx = simplexml_load_file($this->filePath);

        //echo "**" . $gpx->trk->desc . "**";
        //var_dump($gpx->trk->trkseg);
        /* Convert array */
        $points = $gpx->trk->trkseg->trkpt;

        /*echo "track seg num: **" . count($gpx->trk->trkseg) . "**";
        var_dump($gpx->trk->trkseg[1]);*/

        $this->trkseg = $this->__simpleXml2gpx($points);

        $this->__point2interval();

        if ($this->distance > 1000)
            $this->step = ceil(($this->distance / 100) /100) * 100;

        $this->__sampleData();
        $this->__calculateSlope();

        /* Do not split track, save only the processed track */
        $this->__splitTrack();

        return json_encode($this->sampledTrack);
        
    }

}

?>
