<?php
// print errors
require_once __DIR__ . "/../sds-to-functions.php";

// prevent script injection
if (!isset($_GET['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['dir'] = htmlspecialchars($_GET['dir']);
$folder = $_GET['dir'];

$result = renderMarkDown($folder);
download($result['markdown'], $result['filename']);