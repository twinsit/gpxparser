<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TracksController
 *
 * @author Vittorio
 */
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class TracksController extends AppController{
    
    public $helpers = array('Html', 'Form');

    public function index() {
        
        $this->set('tracks', $this->Track->find('all'));
    }
    
    public function getGpxFile($id = NULL) {
        
        $this->layout = "ajax";
         
        $this->Track->id = $id;
        $track = $this->Track->read();
        
        echo $track['Track']['path'];
        
        $file = new File(WWW_ROOT . "files/" . $track['Track']['path'], false, 777);
        $data = $file->read();
        $this->set("content", $data);
    }
    
    public function sample($id = NULL, $step = 100) {
        
        /* 
         * Here parse the gpx file end find the coordinate for drawing
         * Climbing graph.
         */
        $this->layout = "ajax";
        $this->Track->id = $id;
        $this->set('track', $this->Track->read());
    }
}

?>
