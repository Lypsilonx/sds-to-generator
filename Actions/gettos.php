<?php
// returns all file names in the TOs folder (and subfolders)
function getTos($dir)
{
    $files = array();
    $dir = "../" . $dir;
    $dir = realpath($dir);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        if (
            $file->getFilename() == "events.json"
            || $file->getFilename() == "permanent.json"
            || $file->getFilename() == "fallback_to.json"
        ) {
            continue;
        }

        // append full path (from TOs/ folder)
        $pos = strpos($file->getPathname(), "TOs");
        $files[] = substr($file->getPathname(), $pos + 4);
    }

    // remove filextension
    for ($i = 0; $i < count($files); $i++) {
        $files[$i] = substr($files[$i], 0, strrpos($files[$i], ".") - 3);
    }

    // order by: */Plenum first, then */Plenum-(Date) by Date
    usort($files, function ($a, $b) {
        $a = explode("/", $a);
        $b = explode("/", $b);

        if ($a[1] == "Plenum" && $b[1] == "Plenum") {
            return strcmp($a[0], $b[0]);
        } else if ($a[1] == "Plenum") {
            return -1;
        } else if ($b[1] == "Plenum") {
            return 1;
        } else if (strpos($a[1], "Plenum-") !== false && strpos($b[1], "Plenum-") !== false) {
            $a = explode("-", $a[1]);
            $b = explode("-", $b[1]);

            $a = strtotime($a[1]);
            $b = strtotime($b[1]);

            return $a < $b;
        } else {
            // sort by name
            return strcmp($a[0] . $a[1], $b[0] . $b[1]);
        }
    });

    return $files;
}

header('Content-Type: application/json');
echo json_encode(getTos("TOs"));