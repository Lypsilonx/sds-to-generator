<?php
// Uplaod a .md file to the webdav server

// Get the arguments from $_POST
$markdown = $_POST['markdown'];
$filename = $_POST['filename'];
$dir = $_POST['dir'];
$user = $_POST['user'];
$password = $_POST['password'];

// Create the url
$url = "https://cloud.linke-sds.org/remote.php/dav/files/" . $user . '/' . $dir . $filename;

// Create the context
$context = stream_context_create(
    array(
        'http' => array(
            'method' => 'PUT',
            'header' => 'Authorization: Basic ' . base64_encode($user . ':' . $password) . "\r\n" .
            'Content-Type: text/markdown',
            'content' => $markdown
        )
    )
);

// Upload the file
$result = file_get_contents($url, false, $context);

// Return the result
echo $result;

?>