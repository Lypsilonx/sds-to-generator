<?php
// load token from token.txt
$token = file_get_contents("token.txt");

$input = file_get_contents('php://input');
$update = json_decode($input, true);
if (isset($update['message'])) {
    $message = $update['message'];
    if (isset($message['text'])) {
        $text = $message['text'];
        if (strpos($text, "/start") === 0) {
            $chat_id = $message['chat']['id'];
            $response = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und kann deshalb noch nicht viel.";
            send_message($token, $chat_id, $response);
        }
        // Initialize a group
        else if (strpos($text, "/init") === 0) {
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // get chat id
            $chat_id = $message['chat']['id'];
            // get rest of message
            // split rest of message
            $rest = explode(" ", $text);
            // get name
            $name = $rest[1];
            // get weekday
            $weekday = $rest[2];

            if ($weekday == null) {
                // default weekday is the current weekday
                $weekday = date("l");
            }

            // enter chat id and name into chats.json
            $chats[$chat_id] = array("name" => $name, "weekday" => $weekday);
            file_put_contents("chats.json", json_encode($chats));


            // create folder for Ortsgruppe
            mkdir("../TOs/" . $name);
            // create Plenum_to.json (with title, date (next $weekday) and tops array)
            $date = new DateTime();
            $date->modify('next ' . $weekday);
            // to format: yyyy-mm-dd
            $date = $date->format('Y-m-d');
            $to = array("title" => "Plenum", "date" => $date, "tops" => array());
            file_put_contents("../TOs/" . $name . "/Plenum_to.json", json_encode($to));
            // create permanent.json (tops array)
            $permanent = array("tops" => array());
            file_put_contents("../TOs/" . $name . "/permanent.json", json_encode($permanent));
            // create events.json (events array)
            $events = array("events" => array());
            file_put_contents("../TOs/" . $name . "/events.json", json_encode($events));

            // send response
            $response = "Ortsgruppe " . $name . " wurde erfolgreich hinzugefügt.";
            send_message($token, $chat_id, $response);
        }
        // Get TO
        else if (strpos(strtolower($text), "/getto") === 0) {
            // check if chat is initialized
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // get chat id
            $chat_id = $message['chat']['id'];
            // check if chat id is in chats.json
            if (array_key_exists($chat_id, $chats)) {
                $response = "Hier ist der Link zum Download der TO: "
                    . PHP_EOL . "https://www.politischdekoriert.de/sds-to-generator/downloadto.php?dir=" . $chats[$chat_id]["name"];
                send_message($token, $chat_id, $response);
            } else {
                $response = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response);
            }
        }
        // Upload TO
        else if (strpos(strtolower($text), "/upto") === 0) {
            // check if chat is initialized
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // get chat id
            $chat_id = $message['chat']['id'];
            // check if chat id is in chats.json
            if (array_key_exists($chat_id, $chats)) {
                $response = "Clicke hier um die TO Hochzuladen: "
                    . PHP_EOL . "https://www.politischdekoriert.de/sds-to-generator/uploadto.php?dir=" . $chats[$chat_id]["name"];
                send_message($token, $chat_id, $response);
            } else {
                $response = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response);
            }
        }
        // /top or #top (not regarding capitalization)
        else if (strpos(strtolower($text), "#top") === 0 || strpos(strtolower($text), "/top") === 0) {
            // check if chat is initialized
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // get chat id
            $chat_id = $message['chat']['id'];
            // check if chat id is in chats.json
            if (array_key_exists($chat_id, $chats)) {
                // get rest of message
                // set title to first line
                $lines = explode(PHP_EOL, $text);
                // first line without first 4 characters
                $title = substr($lines[0], 5);
                // slice title from rest of message
                $content = substr($text, strlen($title) + 6);

                // get date from text using regex (yyyy-mm-dd or dd.mm.yyyy or dd.mm.yy or dd.mm.)
                $matches = array();
                preg_match("/\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4}|\d{2}\.\d{2}\.\d{2}|\d{2}\.\d{2}\./", $content, $matches);

                // if no date is found, set date to today
                if (count($matches) == 0) {
                    saveTOP($chats[$chat_id]["name"], $title, $content);

                    // send response
                    $response = "TOP \"" . $title . "\" wurde erfolgreich hinzugefügt.";
                    send_message($token, $chat_id, $response);
                } else {
                    // bring date to format yyyy-mm-dd
                    $date = $matches[0];
                    if (preg_match("/\d{2}\.\d{2}\.\d{4}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.Y", $date);
                        $date = $date->format("Y-m-d");
                    } else if (preg_match("/\d{2}\.\d{2}\.\d{2}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.y", $date);
                        $date = $date->format("Y-m-d");
                    } else if (preg_match("/\d{2}\.\d{2}\./", $date)) {
                        $date = DateTime::createFromFormat("d.m.", $date);
                        $date = $date->format("Y-m-d");
                    }
                    saveEvent($chats[$chat_id]["name"], $title, "(Siehe TOP)", $date);
                    saveTOP($chats[$chat_id]["name"], $title, $content);

                    // send response
                    $response = "Event am " . $date->format("d.m.") . " erkannt. Event wurde hinzugefügt.";
                    send_message($token, $chat_id, $response);
                    $response = "TOP \"" . $title . "\" wurde erfolgreich hinzugefügt.";
                    send_message($token, $chat_id, $response);
                }
            } else {
                $response = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response);
            }
        }
        // /termin or #termin (not regarding capitalization)
        else if (strpos(strtolower($text), "#termin") === 0 || strpos(strtolower($text), "/termin") === 0) {
            // check if chat is initialized
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // get chat id
            $chat_id = $message['chat']['id'];
            // check if chat id is in chats.json
            if (array_key_exists($chat_id, $chats)) {
                // get rest of message
                // set title to first line
                $lines = explode(PHP_EOL, $text);
                // first line without first 7 characters
                $title = substr($lines[0], 8);
                // slice title from rest of message
                $content = substr($text, strlen($title) + 9);

                // get date from text using regex (yyyy-mm-dd or dd.mm.yyyy or dd.mm.yy or dd.mm.)
                $matches = array();
                preg_match("/\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4}|\d{2}\.\d{2}\.\d{2}|\d{2}\.\d{2}\./", $content, $matches);

                // if no date is found, set date to today
                if (count($matches) == 0) {
                    $date = new DateTime();
                    $date = $date->format('Y-m-d');
                } else {
                    // bring date to format yyyy-mm-dd
                    $date = $matches[0];
                    if (preg_match("/\d{2}\.\d{2}\.\d{4}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.Y", $date);
                        $date = $date->format("Y-m-d");
                    } else if (preg_match("/\d{2}\.\d{2}\.\d{2}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.y", $date);
                        $date = $date->format("Y-m-d");
                    } else if (preg_match("/\d{2}\.\d{2}\./", $date)) {
                        $date = DateTime::createFromFormat("d.m.", $date);
                        $date = $date->format("Y-m-d");
                    }
                }

                saveEvent($chats[$chat_id]["name"], $title, $content, $date);

                // send response
                $response = "Termin \"" . $title . "\" wurde erfolgreich hinzugefügt.";
                send_message($token, $chat_id, $response);
            } else {
                $response = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response);
            }
        }
        // /del or #del (not regarding capitalization)
        else if (strpos(strtolower($text), "#del") === 0 || strpos(strtolower($text), "/del") === 0) {
            // check if chat is initialized
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // get chat id
            $chat_id = $message['chat']['id'];
            // check if chat id is in chats.json
            if (array_key_exists($chat_id, $chats)) {
                // get rest of message
                // set title to first line
                $lines = explode(PHP_EOL, $text);
                // first line without first 4 characters
                $title = substr($lines[0], 5);

                // delete top
                if (deleteTOP($chats[$chat_id]["name"], $title)) {
                    // send response
                    $response = "TOP \"" . $title . "\" wurde erfolgreich gelöscht.";
                    send_message($token, $chat_id, $response);
                } else {
                    $response = "TOP \"" . $title . "\" konnte nicht gefunden werden.";
                    send_message($token, $chat_id, $response);
                }

                // delete event
                if (deleteEvent($chats[$chat_id]["name"], $title)) {
                    // send response
                    $response = "Termin \"" . $title . "\" wurde erfolgreich gelöscht.";
                    send_message($token, $chat_id, $response);
                } else {
                    $response = "Termin \"" . $title . "\" konnte nicht gefunden werden.";
                    send_message($token, $chat_id, $response);
                }
            } else {
                $response = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response);
            }
        }
        // Help
        else if (strpos(strtolower($text), "/help") === 0) {
            $chat_id = $message['chat']['id'];
            $response = "Ich habe dich leider nicht verstanden. Bitte benutze einen der folgenden Befehle: "
                . PHP_EOL
                . PHP_EOL . "/init <Ortsgruppe> <Wochentag>"
                . PHP_EOL . "Initialisiert eine neue Ortsgruppe"
                . PHP_EOL
                . PHP_EOL . "/getto"
                . PHP_EOL . "Liefert einen Link zum Download der TO"
                . PHP_EOL
                . PHP_EOL . "/upto"
                . PHP_EOL . "Lädt die TO auf den SDS Server hoch"
                . PHP_EOL
                . PHP_EOL . "/top <Titel>"
                . PHP_EOL . "<Text>"
                . PHP_EOL . "Fügt einen neuen TOP hinzu"
                . PHP_EOL
                . PHP_EOL . "/termin <Titel>"
                . PHP_EOL . "<Text>"
                . PHP_EOL . "Fügt einen neuen Termin hinzu"
                . PHP_EOL
                . PHP_EOL . "/del <Titel>"
                . PHP_EOL . "Löscht einen TOP";
            send_message($token, $chat_id, $response);
        }
    }
}

function saveTOP($og, $title, $content)
{
    // enter TOP into TOs/Ortsgruppe/Plenum_to.json
    $to = json_decode(file_get_contents("../TOs/" . $og . "/Plenum_to.json"), true);
    // generate unique id
    $id = uniqid();
    // add top to tops array
    array_push($to["tops"], array("id" => $id, "title" => $title, "content" => $content));
    file_put_contents("../TOs/" . $og . "/Plenum_to.json", json_encode($to));
}

function saveEvent($og, $title, $content, $date)
{
    // enter TOP into TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);
    // generate unique id
    $id = uniqid();
    // add top to tops array
    array_push($events["events"], array("id" => $id, "title" => $title, "content" => $content, "date" => $date));
    file_put_contents("../TOs/" . $og . "/events.json", json_encode($events));
}

function deleteTOP($og, $title)
{
    // load TOs/Ortsgruppe/Plenum_to.json
    $to = json_decode(file_get_contents("../TOs/" . $og . "/Plenum_to.json"), true);
    // search for top with title
    foreach ($to["tops"] as $key => $top) {
        if ($top["title"] == $title) {
            // delete top
            unset($to["tops"][$key]);
            // save file
            file_put_contents("../TOs/" . $og . "/Plenum_to.json", json_encode($to));
            return true;
        }
    }
    return false;
}

function deleteEvent($og, $title)
{
    // load TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);
    // search for top with title
    foreach ($events["events"] as $key => $event) {
        if ($event["title"] == $title) {
            // delete top
            unset($events["events"][$key]);
            // save file
            file_put_contents("../TOs/" . $og . "/events.json", json_encode($events));
            return true;
        }
    }
    return false;
}

function send_message($token, $chat_id, $response)
{
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($response);
    file_get_contents($url);
}
?>