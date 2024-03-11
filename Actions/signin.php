<?php
// start session
session_start();
// prevent script injection
if (!isset($_POST['dir']) || !isset($_POST['password'])) {
    header('Location: ../index.php');
    exit();
}

$_POST['dir'] = htmlspecialchars($_POST['dir']);
$_POST['password'] = htmlspecialchars($_POST['password']);

// checks $_POST['dir'] and $_POST['password']
$serverPath = $_POST['dir'];
$folder = explode('/', $serverPath)[0];

// load chats.json from ../Bot
$json = file_get_contents("../Bot/chats.json");
// decode json to array
$chats = json_decode($json, true);

// check if password is correct (hashed)
$folderid = -1;
for ($i = 0; $i < count($chats["groups"]); $i++) {
    if ($chats["groups"][$i]["name"] == $folder) {
        $folderid = $i;
        break;
    }
}
$salt = file_get_contents("../salt.txt");
if ($folderid != -1 && $chats["groups"][$folderid]["password"] == hash("sha256", $_POST['password'] . $salt)) {
    // set session variable
    $_SESSION['signedin'] = $folder;
    // redirect to index.php
    header('Location: ../index.php?dir=' . $serverPath);
    exit();
} else {
    // redirect to index.php
    header('Location: ../index.php?dir=' . $serverPath);
    exit();
}