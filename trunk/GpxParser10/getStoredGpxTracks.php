<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if ($handle = opendir('tracks')) {
    $files = array();
    /* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) {
        if (!strcmp($file, ".") || (!strcmp($file, "..")))
            continue;
        $elem['fileName'] = $file;
        $elem['title'] = strtok($file, ".");
        array_push($files, $elem);
    }
    
    $jsonArray['succes'] = true;
    $jsonArray['root'] = $files;
    echo json_encode($jsonArray);
    closedir($handle);
}
?>
