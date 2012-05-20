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
App::uses('GpxFileProcessor', 'Lib');

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

    public function __getFilePath($filename) {
        /* Check if file exists */
        $path = WWW_ROOT . "files/" . $filename;
        if (!file_exists($path))
            return $path;

        $now = date('Y-m-d-His');
        return WWW_ROOT . "files/" . $now . $fileName;
    }

    public function upload() {

        /* Check if file is a valid .gpx */
        /* Copy file into webroot/files/username folder */
        /* Parse file and generate json to store into database */
        /* Add file info to database */

        /*var_dump($this->data);
        echo "<br />";
        var_dump($this->request->data['Track']['file']['tmp_name']);*/

        $file = new File($this->request->data['Track']['file']['tmp_name']);
        $data = $file->read();

        /* Validate uploaded file */
        if (GpxFileProcessor::isValidGpxFile($this->request->data['Track']['file']['tmp_name'])) {
            /* Save file */
            $filePath = $this->__getFilePath($this->request->data['Track']['file']['name']);

            $gpxProcessor = new GpxFileProcessor($this->request->data['Track']['file']['tmp_name']);
            $json = $gpxProcessor->getJson();
            echo "**" . $json . "**";

        }
        else {
            /* Error in upload file */
            echo "Error!!!";
        }
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
