<?php
// start session
session_start();
require_once "../sds-to-functions.php";

// check if user is signed in
if (!isset($_GET['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['dir'] = htmlspecialchars($_GET['dir']);
if (isset($_GET['token'])) {
    $_GET['token'] = htmlspecialchars($_GET['token']);
    $token = $_GET['token'];
} else {
    $token = "";
}
$folder = $_GET['dir'];
$group = explode("/", $folder)[0];
$signedin = false;

if ($token != "") {
    $token = $_GET['token'];

    // check if token is set
    if (isset($_GET['token'])) {
        // check if token is valid
        // load Bot/tokens.json
        $json = file_get_contents("../Bot/tokens.json");
        $tokens = json_decode($json, true);
        // check if dir is in tokens.json as "group"
        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i]["group"] == $group) {
                // check if token is in tokens.json
                if (in_array($_GET['token'], $tokens[$i]["tokens"])) {
                    // set session variable
                    $_SESSION['signedin'] = $group;
                    $signedin = true;
                }
            }
        }
    }
}

// check if user is signed in
if (isset($_SESSION['signedin'])) {
    // check if user is signed in to the right group
    if ($_SESSION['signedin'] == $group) {
        $signedin = true;
    }
}

// check if user is signed in
if (!$signedin) {
    header('Location: ../index.php?dir=' . $folder);
    exit();
}

$result = renderMarkDown($folder);
upload($result['markdown'], $result['filename'], $folder);
header('Location: ../index.php?dir=' . $folder);