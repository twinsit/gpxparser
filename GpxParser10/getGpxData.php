<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

  
    require_once 'gpxXmlParser.php';
    require_once 'gpxArray.php';
    require_once 'gpxProcessor.php';
    
    if ($_REQUEST['filename'])
        $gpxFile = "tracks/" . $_REQUEST['filename'];
    else
        $gpxFile = "tracks/PratoValentino.gpx";
    
    $xmlParser = new gpxXmlParser();
    $xmlArr = $xmlParser->newGetTrack($gpxFile);
    $gpxArr = new gpxArray($xmlArr);
    unset($xmlArr);
    unset($xmlParser);
    if (!$gpxArr->isValid()) {
        echo '{"success": "false", "message" : "Gpx file not valid!"}';
    }
            
    $trk = $gpxArr->getTtkseg();
    unset($gpxArr);
    $gpxProc = new gpxProcessor($trk);
    unset($trk);
    $gpxProc->setStep(100);
    $distance = $gpxProc->processData();
    //unset($distance);
    if ($distance > 10000){
        $step = ceil(($distance / 100) /100) * 100;
        $gpxProc->setStep($step);
    }
    $sampled = $gpxProc->sampleData();
    unset ($sampled);
    //$sampled = $gpxProc->calculateSlope();
    $gpxProc->calculateSlope();
    
    
    $sampled = $gpxProc->splitTrack();
    //var_dump($sampled);
    //echo "<pre>";
    //var_dump(get_defined_vars());
    //echo "</pre>";
    $jsonArray['success'] = "true";
    $jsonArray['distance'] = $distance;
    $jsonArray['root'] = $sampled;
    $json = json_encode($jsonArray);
    
    
    echo $json;
    
?>
