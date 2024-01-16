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



// Send a PROPFIND request to the server to get the ETag
$context = stream_context_create(
    array(
        'http' => array(
            'method' => 'PROPFIND',
            'header' => 'Authorization: Basic ' . base64_encode($user . ':' . $password),
            'content' => '<?xml version="1.0" encoding="UTF-8"?>
                                <d:propfind xmlns:d="DAV:">
                                    <d:prop xmlns:oc="http://owncloud.org/ns">
                                        <oc:fileid/>
                                    </d:prop>
                                </d:propfind>'
        )
    )
);

// Get the result
$result = file_get_contents($url, false, $context);

// Get the FileID
$fileid = substr($result, strpos($result, '<oc:fileid>') + 11, strpos($result, '</oc:fileid>') - strpos($result, '<oc:fileid>') - 11);

// Return the link to the file
echo "https://cloud.linke-sds.org/index.php/f/" . $fileid;