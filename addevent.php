<?php
// recieves form data from sds-to-generator/index.php
// load the json from dir (in form data)
$dir = $_POST['dir'];
$folder = explode('/', $dir)[0];
$file = "TOs/" . $folder . "/events.json";
$json = file_get_contents($file);

// decode json to array
$json_data = json_decode($json, true);

if ($_POST["edit"] == "") {
    // add new top to array
    $newevent = array(
        'id' => $_POST['id'],
        'title' => $_POST['title'],
        'date' => $_POST['date'],
        'content' => $_POST['content']
    );

    // add new top to array
    array_push($json_data['events'], $newevent);
} else {
    // if the delete button was pressed
    if ($_POST["delete"] == "true") {
        // delete top
        $events = $json_data['events'];
        foreach ($events as $key => $top) {
            if ($top['id'] == $_POST['edit']) {
                unset($json_data['events'][$key]);
            }
        }
    } else {
        // edit top
        $events = $json_data['events'];
        foreach ($events as $key => $top) {
            if ($top['id'] == $_POST['edit']) {
                $json_data['events'][$key]['title'] = $_POST['title'];
                $json_data['events'][$key]['date'] = $_POST['date'];
                $json_data['events'][$key]['content'] = $_POST['content'];
            }
        }
    }
}

// encode array to json and save to file
file_put_contents($file, json_encode($json_data));

// redirect to index.php
header('Location: index.php?dir=' . $dir);
?>