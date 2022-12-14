<?php
// start session
session_start();
// prevent script injection
if (!isset($_POST['title']) || !isset($_POST['date']) || !isset($_POST['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_POST['title'] = htmlspecialchars($_POST['title']);
$_POST['date'] = htmlspecialchars($_POST['date']);
$_POST['dir'] = htmlspecialchars($_POST['dir']);

// recieves form data from sds-to-generator/index.php
$dir = $_POST['dir'];
$folder = explode('/', $dir)[0];

// check if user is signed in
if (!isset($_SESSION['signedin']) || $_SESSION['signedin'] != $folder) {
    header('Location: ../index.php');
    exit();
}

// decode json to array
$json_data = json_decode($json, true);

if ($_POST["edit"] == "") {
    // create new json file from form data
    $json_data = array(
        'title' => $_POST['title'],
        'date' => $_POST['date'],
        'tops' => array()
    );
} else {
    // if the delete button was pressed
    if ($_POST["delete"] == "true") {
        // delete to file
        unlink("../TOs/" . $dir . '_to.json');
    } else {
        // edit to file
        $json_data['title'] = $_POST['title'];
        $json_data['date'] = $_POST['date'];
    }
}

// encode array to json and save to file
file_put_contents("../TOs/" . $dir . '_to.json', json_encode($json_data, JSON_PRETTY_PRINT));

// redirect to index.php
header('Location: ../index.php?dir=' . $dir);
?>