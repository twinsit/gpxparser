<?php
    sleep(1);
    /*
     * TODO: Check if file exist, concatenate with random int
     */
    //copy($_FILES['photo-path']['tmp_name'], "tmpFile1.gpx");
    copy($_FILES['fileGpx']['tmp_name'], "tracks/" . $_FILES['fileGpx']['name']);
    echo '{success:true, file:'.json_encode($_FILES['fileGpx']['name']).'}';
?>
