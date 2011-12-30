<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of gpxArray
 *
 * @author Vittorio
 */
class gpxArray {
    //put your code here
    var $array;
    var $trk;
    var $lastTrkseg = 0;
    
    function __construct($array) {
        $this->array = $array;
        $this->trk = $this->array[0]['child'][0]['child'];
    }

    function isValid() {
        
        /* Must find string GPX in [0]['nam'] */
        if (strcmp($this->array[0]['name'], 'GPX') != 0) {
            return false;
        }
        
        /* Must find tag trk*/
        if (strcmp($this->array[0]['child'][0]['name'], "TRK") != 0) {
            return false;
        }
        
        return true;
    }
    
    function getName() {
        
        return $this->trk[0][data];
    }
    
    function getDescription() {
        
        return $this->trk[1][data];
    }
    
    function getDate() {
        
        echo $this->trk[2]['child'][0]['child'][1]['data'];
    }
    
    /*
     * Funzione per estrarre il contenuto di una stringa compreso fra due
     * delimitatori
     */
    function extract_between_tag(&$something, $init_tag, $end_tag) {

	$something = stristr($something, $init_tag);
	//if(!$something) die("Non trovo il tag: " . $init_tag);
	if(!$something) return NULL;
	$end = stripos($something, $end_tag, strlen($init_tag));
	$treasure = trim(substr($something, strlen($init_tag), $end - strlen($init_tag)));
	$something = substr($something, $end + strlen($end_tag));
	return $treasure;
    }
    
    function getAttribute($value, $name) {
        
        return $value['attrs'][$name];
    }
    
    function getElevation($value) {
        
        return $value['child'][0]['data'];
    }
    
    function getRawTime($value) {
        
        return $value['child'][1]['data'];
    }
    
    function getTimestamp($value) {
        
        $time = $this->getRawTime($value);
        $time = $this->extract_between_tag($time, "T", "Z");
        $time = strtotime($time);
        return $time;
    }
    /**
     * Return an array with all point of next segment
     * 
     * Format:
     * [0] ->
     *  ['lat']
     *  ['long']
     *  ['ele']
     *  ['ts']
     *  ['hra']
     */
    function getTtkseg() {
        
        /* Fix to support Garmin - only for one track segment */
        $trkseg = $this->trk[1]['child'];
	
        if (! $trkseg)
            $trkseg = $this->trk[2 + $this->lastTrkseg]['child'];
        
        if ($trkseg == NULL)
            return NULL;
        
        $points = array();
        foreach ($trkseg as $value) {
            
            $elem ['lon'] = $this->getAttribute($value, "LON");
            $elem ['lat'] = $this->getAttribute($value, "LAT");
            $elem ['ele'] = $this->getElevation($value);
            $elem ['ts'] = $this->getTimestamp($value);
            array_push($points, $elem);
        }
        
        $this->lastTrkseg++;
        return $points;
        
    }
    
}

?>
