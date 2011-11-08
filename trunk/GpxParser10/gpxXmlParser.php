<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/*
     * Funzione che trova il tag iniziale
     */
    function startElement($parser, $name, $attrs)
    {
        //global $struct;
        $tag = array("name"=>$name,"attrs"=>$attrs);
        array_push(gpxXmlParser::$xmlParsed,$tag);
    }

    /*
     * Funzione per l'estrazione dei dati
     */
    function data($parser, $data)
    {
      global $j;

      if(trim($data)) {
        gpxXmlParser::$xmlParsed[count(gpxXmlParser::$xmlParsed)-1]['data']=$data;
      }
    }

    /*
     * Funzione che trova il tag finale
     */
    function endElement($parser, $name)
    {
      //global $struct;

      gpxXmlParser::$xmlParsed[count(gpxXmlParser::$xmlParsed)-2]['child'][] = gpxXmlParser::$xmlParsed[count(gpxXmlParser::$xmlParsed)-1];

      array_pop(gpxXmlParser::$xmlParsed);
    }

/**
 * Description of gpxXmlParser
 *
 * @author Vittorio
 */
class gpxXmlParser {

    //put your code here
    public static $xmlParsed;
    var $xmlParser;
    
    function gpxXmlParser () {
        
        self::$xmlParsed = array();
        $this->xmlParser = xml_parser_create();
        xml_set_element_handler($this->xmlParser, "startElement", "endElement");
        xml_set_character_data_handler($this->xmlParser, "data");
        
    }
    
    function newGetTrack($path){

        $error = xml_parse($this->xmlParser, file_get_contents($path));

        if(!$error) {
            die("XML parsing error");
        }
        
        xml_parser_free($this->xmlParser);
        //return self::$xmlParsed[0]['child'][0]['child'];
        return self::$xmlParsed;
    }
}

?>
