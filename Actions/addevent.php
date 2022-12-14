<?php
// start session
session_start();
// prevent script injection
if (!isset($_POST['id']) || !isset($_POST['title']) || !isset($_POST['date']) || !isset($_POST['content']) || !isset($_POST['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_POST['id'] = htmlspecialchars($_POST['id']);
$_POST['title'] = htmlspecialchars($_POST['title']);
$_POST['date'] = htmlspecialchars($_POST['date']);
$_POST['content'] = htmlspecialchars($_POST['content']);
$_POST['dir'] = htmlspecialchars($_POST['dir']);

// recieves form data from sds-to-generator/index.php
// load the json from dir (in form data)
$dir = $_POST['dir'];
$folder = explode('/', $dir)[0];

// check if user is signed in
if (!isset($_SESSION['signedin']) || $_SESSION['signedin'] != $folder) {
    header('Location: ../index.php');
    exit();
}

$file = "../TOs/" . $folder . "/events.json";
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
file_put_contents($file, json_encode($json_data, JSON_PRETTY_PRINT));

// redirect to index.php
header('Location: ../index.php?dir=' . $dir);
?>