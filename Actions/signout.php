<?php
// start session
session_start();
// get dir from $_POST
$dir = $_POST['dir'];

// unset session variables
unset($_SESSION['signedin']);
// redirect to index.php
header('Location: ../index.php?dir=' . $dir);
exit();