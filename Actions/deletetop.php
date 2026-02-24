<?php
// start session
session_start();

error_reporting(E_ALL);

// prevent script injection
if (!isset($_GET['id']) || !isset($_GET['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['dir'] = htmlspecialchars($_GET['dir']);
$_GET['id'] = htmlspecialchars($_GET['id']);

// recieves form data from sds-to-generator/index.php
// load the json from dir (in form data)
$serverPath = $_GET['dir'];
$file;
$folder = explode('/', $serverPath)[0];
if ($_GET['permanent'] == "on") {
    $file = __DIR__ . "/../TOs/" . $folder . "/permanent.json";
} else {
    $file = __DIR__ . "/../TOs/" . $serverPath . '_to.json';
}
$json = file_get_contents($file);

// check if user is signed in
if (!isset($_SESSION['signedin']) || $_SESSION['signedin'] != $folder) {
    header('Location: ../index.php');
    exit();
}

// decode json to array
$json_data = json_decode($json, true);

// delete top
$tops = $json_data['tops'];
foreach ($tops as $key => $top) {
    if ($top['id'] == $_GET['id']) {
        unset($json_data['tops'][$key]);
    }
}

// encode array to json and save to file
file_put_contents($file, json_encode($json_data, JSON_PRETTY_PRINT));

// redirect to index.php
header('Location: ../index.php?dir=' . $serverPath);