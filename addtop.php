<?php
// recieves form data from sds-to-generator/index.php
// load the json from dir (in form data)
$dir = $_POST['dir'];
$file;
if ($_POST['permanent'] == "on") {
    $folder = explode('/', $dir)[0];
    $file = "TOs/" . $folder . "/permanent.json";
} else {
    $file = "TOs/" . $dir . '_to.json';
}
$json = file_get_contents($file);

// decode json to array
$json_data = json_decode($json, true);

if ($_POST["edit"] == "") {
    // add new top to array
    $newtop = array(
        'id' => $_POST['id'],
        'title' => $_POST['title'],
        'content' => $_POST['content']
    );

    // add new top to array
    array_push($json_data['tops'], $newtop);
} else {
    // if the delete button was pressed
    if ($_POST["delete"] == "true") {
        // delete top
        $tops = $json_data['tops'];
        foreach ($tops as $key => $top) {
            if ($top['id'] == $_POST['edit']) {
                unset($json_data['tops'][$key]);
            }
        }
    } else {
        // edit top
        $tops = $json_data['tops'];
        foreach ($tops as $key => $top) {
            if ($top['id'] == $_POST['edit']) {
                $json_data['tops'][$key]['title'] = $_POST['title'];
                $json_data['tops'][$key]['content'] = $_POST['content'];
            }
        }
    }
}

// encode array to json and save to file
file_put_contents($file, json_encode($json_data));

// redirect to index.php
header('Location: index.php?dir=' . $dir);
?>