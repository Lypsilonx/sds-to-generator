<?php
// start session
session_start();
// prevent script injection
if (!isset($_GET['date']) || !isset($_GET['time']) || !isset($_GET['title'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['date'] = htmlspecialchars($_GET['date']);
$_GET['time'] = htmlspecialchars($_GET['time']);
$_GET['title'] = htmlspecialchars($_GET['title']);

// recieves date, time and title from sds-to-generator/index.php

// set date to format from dd.mm.yyyy or dd.mm. to yyyy-mm-dd
if (str_contains($_GET['date'], ".")) {
    $day = preg_split("/\./", $_GET['date'])[0];
    $month = preg_split("/\./", $_GET['date'])[1];
    $year = preg_split("/\./", $_GET['date'])[2];

    if (strlen($day) == 1) {
        $day = "0" . $day;
    }
    if (strlen($month) == 1) {
        $month = "0" . $month;
    }
    if (strlen($year) == 2) {
        $year = "20" . $year;
    } elseif (strlen($year) == 0) {
        $year = date("Y");
    }

    $_GET['date'] = $year . "-" . $month . "-" . $day;
}

// if time is not set, set it to 08:00
if (!isset($_GET['time']) || $_GET['time'] == "") {
    $_GET['time'] = "08:00";
} else {
    // set time to format from hhUhr or hh:mm or hh Uhr to hh:mm
    $hour = preg_split("/(( )?Uhr|:)/", $_GET['time'])[0];
    $minute = preg_split("/(( )?Uhr|:)/", $_GET['time'])[1];

    if (strlen($hour) == 1) {
        $hour = "0" . $hour;
    }
    if (strlen($minute) == 1) {
        $minute = "0" . $minute;
    } elseif (strlen($minute) == 0) {
        $minute = "00";
    }

    $_GET['time'] = $hour . ":" . $minute;
}

// if title is not set, set it to ""
if (!isset($_GET['title'])) {
    $_GET['title'] = "";
} else {

    // replace html special chars
    $_GET['title'] = str_replace("&amp;", "&", $_GET['title']);
    $_GET['title'] = str_replace("&lt;", "<", $_GET['title']);
    $_GET['title'] = str_replace("&gt;", ">", $_GET['title']);
    $_GET['title'] = str_replace("&quot;", "", $_GET['title']);
    $_GET['title'] = str_replace("&#039;", "'", $_GET['title']);
}

// create ics file and download it
ICS($_GET['date'] . " " . $_GET['time'], date("Y-m-d H:i", strtotime($_GET['date'] . " " . $_GET['time'] . " +1 hour")), $_GET['title'], "An event created with SDS TO Generator", "");

function ICS($start, $end, $name, $description, $location)
{
    $data = "BEGIN:VCALENDAR\nVERSION:2.0\nMETHOD:PUBLISH\nBEGIN:VEVENT\nDTSTART:" . date("Ymd\THis", strtotime($start)) . "\nDTEND:" . date("Ymd\THis", strtotime($end)) . "\nLOCATION:" . $location . "\nTRANSP: OPAQUE\nSEQUENCE:0\nUID:\nDTSTAMP:" . date("Ymd\THis") . "\nSUMMARY:" . $name . "\nDESCRIPTION:" . $description . "\nPRIORITY:1\nCLASS:PUBLIC\nBEGIN:VALARM\nTRIGGER:-PT10080M\nACTION:DISPLAY\nDESCRIPTION:Reminder\nEND:VALARM\nEND:VEVENT\nEND:VCALENDAR\n";
    header("Content-type:text/calendar");
    header('Content-Disposition: attachment; filename="' . $name . '.ics"');
    Header('Content-Length: ' . strlen($data));
    Header('Connection: close');
    echo $data;
}
?>