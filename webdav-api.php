<?php
class WebdavApi
{
    private $user;
    private $password;
    private $url;

    function __construct($url, $filename)
    {
        // load webdavuser.config from the same directory as this script
        $webdavuser = json_decode(file_get_contents($filename), true);

        $this->user = $webdavuser["user"];
        $this->password = $webdavuser["password"];

        $this->url = $url;
    }

    public function uploadFile($filename, $content, $dir = "", $contentType = "text/markdown")
    {
        if (!$this->folderExists($dir)) {
            $this->createFolder($dir);
        }

        $url = $this->url . "/remote.php/dav/files/" . $this->user . "/" . $dir . $filename;

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'PUT',
                    'header' => 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password) . "\r\n" .
                        'Content-Type: ' . $contentType,
                    'content' => $content
                )
            )
        );

        $result = file_get_contents($url, false, $context);

        return $this->getFileLink($filename, $dir);
    }

    private function getFileID($filename, $dir = "")
    {
        $url = $this->url . "/remote.php/dav/files/" . $this->user . "/" . $dir . $filename;

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'PROPFIND',
                    'header' => 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password),
                    'content' => '<?xml version="1.0" encoding="UTF-8"?>
                                <d:propfind xmlns:d="DAV:">
                                    <d:prop xmlns:oc="http://owncloud.org/ns">
                                        <oc:fileid/>
                                    </d:prop>
                                </d:propfind>'
                )
            )
        );

        $result = file_get_contents($url, false, $context);

        $fileid = substr($result, strpos($result, '<oc:fileid>') + 11, strpos($result, '</oc:fileid>') - strpos($result, '<oc:fileid>') - 11);

        return $fileid;
    }

    public function fileExists($filename, $dir = "")
    {
        $url = $this->url . "/remote.php/dav/files/" . $this->user . "/" . $dir . $filename;

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'PROPFIND',
                    'header' => 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password),
                    'content' => '<?xml version="1.0"?>
                                <propfind xmlns="DAV:">
                                    <prop>
                                        <resourcetype />
                                    </prop>
                                </propfind>'
                )
            )
        );

        $result = file_get_contents($url, false, $context);
        if ($http_response_header[0] == "HTTP/1.1 404 Not Found") {
            return false;
        }
        return true;
    }

    private function folderExists($dir)
    {
        $url = $this->url . "/remote.php/dav/files/" . $this->user . "/" . $dir;

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'PROPFIND',
                    'header' => 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password),
                    'content' => '<?xml version="1.0"?>
                                <propfind xmlns="DAV:">
                                    <prop>
                                        <resourcetype />
                                    </prop>
                                </propfind>'
                )
            )
        );

        $result = file_get_contents($url, false, $context);
        if ($http_response_header[0] == "HTTP/1.1 404 Not Found") {
            return false;
        }
        return true;
    }

    private function createFolder($dir)
    {
        $url = $this->url . "/remote.php/dav/files/" . $this->user . "/" . $dir;

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'MKCOL',
                    'header' => 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password)
                )
            )
        );

        $result = file_get_contents($url, false, $context);
    }

    private function getFileLink($filename, $dir = "")
    {
        $fileid = $this->getFileID($filename, $dir);

        return $this->url . "/index.php/f/" . $fileid;
    }
}