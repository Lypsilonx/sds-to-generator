<?php
// start session
session_start();
// get dir from $_POST
$serverPath = $_POST['dir'];

// unset session variables
unset($_SESSION['signedin']);
// redirect to index.php
header('Location: ../index.php?dir=' . $serverPath);
exit();