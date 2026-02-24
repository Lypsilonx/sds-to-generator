<?php
// start session
session_start();
// prevent script injection
if (!isset($_GET['id']) || !isset($_GET['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['id'] = htmlspecialchars($_GET['id']);
$_GET['dir'] = htmlspecialchars($_GET['dir']);

// recieves form data from sds-to-generator/index.php
// load the json from dir (in form data)
$serverPath = $_GET['dir'];
$folder = explode('/', $serverPath)[0];

// check if user is signed in
if (!isset($_SESSION['signedin']) || $_SESSION['signedin'] != $folder) {
    header('Location: ../index.php');
    exit();
}

$file = __DIR__ . "/../TOs/" . $folder . "/events.json";
$json = file_get_contents($file);

// decode json to array
$json_data = json_decode($json, true);

// delete top
$events = $json_data['events'];
foreach ($events as $key => $top) {
    if ($top['id'] == $_GET['id']) {
        unset($json_data['events'][$key]);
    }
}

// encode array to json and save to file
file_put_contents($file, json_encode($json_data, JSON_PRETTY_PRINT));

// redirect to index.php
header('Location: ../index.php?dir=' . $serverPath);