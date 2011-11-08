<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of gpxProcessor
 *
 * @author Vittorio
 */
class gpxProcessor {
    //put your code here
    public $trkseg;
    private $sampledTrack;
    private $step;
    var $a;
    var $b;
    var $f;
    
    function __construct($trkseg) {
        $this->trkseg = $trkseg;
        $this->a = 6378137;
        $this->b = 6356752.3142;
        $this->f = 1/298.257223563;
        $this->step = 500;
    }

    function setStep($step) {
        $this->step = $step;
    }
        
    function getInterval($startPoint, $endPoint) {
        return $this->trkseg[$endPoint]['ts'] - $this->trkseg[$startPoint]['ts'];
    }

    function getRise ($startPoint, $endPoint) {
        return $this->trkseg[$endPoint]['ele'] - $this->trkseg[$startPoint]['ele'];
    }
    
    function getP2PDistance($startPoint, $endPoint) {
        
        $lat1 = $this->trkseg[$startPoint]['lat'];
        $lon1 = $this->trkseg[$startPoint]['lon'];
        $lat2 = $this->trkseg[$endPoint]['lat'];
        $lon2 = $this->trkseg[$endPoint]['lon'];
        $L = deg2rad($lon2-$lon1);
       // echo $L . "</br>";
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
                //echo "Punti coincidenti </br>";
                return 0;
            }
            $cosSigma = $sinU1*$sinU2 + $cosU1*$cosU2*$cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
            $cosSqAlpha = 1 - $sinAlpha*$sinAlpha;
            $cos2SigmaM = $cosSigma - 2*$sinU1*$sinU2/$cosSqAlpha;
            if ($cos2SigmaM != $cos2SigmaM){
                // Siamo sull equatore
                //echo "equatore</br>";
                $cos2SigmaM = 0;
            }
            $C = $this->f/16*$cosSqAlpha*(4+$this->f*(4-3*$cosSqAlpha));
            $lambdaP = $mylambda;
            $mylambda = $L + (1-$C) * $this->f * $sinAlpha * ($sigma + $C*$sinSigma*($cos2SigmaM+$C*$cosSigma*(-1+2*$cos2SigmaM*$cos2SigmaM)));
        }

        if ($iterLimit==0){
            // La formula non converge
            //echo "non converge </br>";
            return 0;
        }
        $uSq = $cosSqAlpha * ($this->a*$this->a - $this->b*$this->b) / ($this->b*$this->b);
        $A = 1 + $uSq/16384*(4096+$uSq*(-768+$uSq*(320-175*$uSq)));
        $B = $uSq/1024 * (256+$uSq*(-128+$uSq*(74-47*$uSq)));
        $deltaSigma = $B*$sinSigma*($cos2SigmaM+$B/4*($cosSigma*(-1+2*$cos2SigmaM*$cos2SigmaM)-$B/6*$cos2SigmaM*(-3+4*$sinSigma*$sinSigma)*(-3+4*$cos2SigmaM*$cos2SigmaM)));
        $s = $this->b*$A*($sigma-$deltaSigma);
        //echo "</br>" . $this->b . ".. b .." . $sigma . ".. sigma .." . $deltaSigma . ".. delta sigma</br>";
        //echo $s . ".. s </br>";

        return $s;
        
    }
    
    function processData() {
        
        if ($this->trkseg['totalDistance'])
            return $this->trkseg['totalDistance'];
        
        $distance = 0;
        for ($i = 1; $i < count($this->trkseg); $i++) {
            
            $p2pDistance = $this->getP2PDistance($i-1, $i);
            
            $this->trkseg[$i]['distance'] = $p2pDistance;
            $this->trkseg[$i]['interval'] = $this->getInterval($i-1, $i);
            $this->trkseg[$i]['rise'] = $this->getRise($i-1, $i);
            $distance += $p2pDistance;
        }
        //var_dump($this->trkseg);
        //echo $distance . " distance <br />";
        $this->trkseg['totalDistance'] = $distance;
        return $distance;
    }
    
    function sampleData($step = 50) {
        
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
        return $sampledTrack;
    }
    
    function nSampleData($sampleNumber = 100) {
        
    }
    
    function calculateSlope() {
        
        $this->sampledTrack[0]['gradient'] = 0;
        for ($i = 1; $i < count($this->sampledTrack); $i++) {
            
            $slope = ($this->sampledTrack[$i]['ele'] - $this->sampledTrack[$i-1][ele]) / $this->step;
            $this->sampledTrack[$i]['gradient'] = $slope;
        }
        return $this->sampledTrack;
    }
    
    function getTrackIndex($gradient) {
        
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
    
    function splitTrack($adjustSlope = false) {
        
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
            
            $idx = $this->getTrackIndex(($this->sampledTrack[$i]['gradient'])*100);
            
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
        
        return $this->sampledTrack;
    }
    
    function getSlope() {
        
    }
    
    function getMaxSlope() {
        
    }
}

?>
