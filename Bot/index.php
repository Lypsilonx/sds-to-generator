<?php
// load token from token.txt
$token = file_get_contents("token.txt");

$input = file_get_contents('php://input');
$update = json_decode($input, true);
if (isset($update['message'])) {
    $message = $update['message'];
    $message_id = $message['message_id'];
    if (isset($message['text'])) {
        $text = $message['text'];
        $chat_id = $message['chat']['id'];
        if (strpos($text, "/start") === 0) {
            $response = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und kann deshalb noch nicht viel.";
            send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
        }
        // Initialize a group
        else if (strpos($text, "/init") === 0) {
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);

            $rest = explode(" ", $text);

            // check if rest of message is valid
            if (count($rest) < 3) {
                $response = "Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                return;
            }

            // get name
            $name = $rest[1];

            // get password
            $password = $rest[2];

            // if folder for Ortsgruppe does not exist
            if (!file_exists("../TOs/" . $name)) {

                if (count($rest) < 4) {
                    // default weekday is the current weekday
                    $weekday = date("l");
                } else {
                    // get weekday
                    $weekday = $rest[3];
                }

                // enter chat id and name into chats.json
                array_push($chats["groups"], array("name" => $name, "password" => hash("sha256", $password), "weekday" => $weekday, "members" => array($chat_id)));
                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                // create folder for Ortsgruppe
                mkdir("../TOs/" . $name);
                // create Plenum_to.json (with title, date (next $weekday) and tops array)
                $date = new DateTime();
                $date->modify('next ' . $weekday);
                // to format: yyyy-mm-dd
                $date = $date->format('Y-m-d');
                $to = array("title" => "Plenum", "date" => $date, "tops" => array());
                file_put_contents("../TOs/" . $name . "/Plenum_to.json", json_encode($to, JSON_PRETTY_PRINT));
                // create permanent.json (tops array)
                $permanent = array("tops" => array());
                file_put_contents("../TOs/" . $name . "/permanent.json", json_encode($permanent, JSON_PRETTY_PRINT));
                // create events.json (events array)
                $events = array("events" => array());
                file_put_contents("../TOs/" . $name . "/events.json", json_encode($events, JSON_PRETTY_PRINT));

                // send response
                $response = "Ortsgruppe " . $name . " wurde erfolgreich hinzugefügt.";
                send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
            } else {
                // enter chat id into  group members
                foreach ($chats["groups"] as &$g) {
                    if ($g["name"] == $name) {
                        if (hash("sha256", $password) != $g["password"]) {
                            $response = "Das Passwort ist falsch.";
                            send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                            return;
                        }
                        array_push($g["members"], $chat_id);

                        file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                        $response = "Du bist der Ortsgruppe " . $name . " beigetreten.";
                        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                        break;
                    }
                }

                // send response
                $response = "Ortsgruppe " . $name . " wurde nicht gefunden.";
                send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
            }
        }
        // Help
        else if (strpos(strtolower($text), "/help") === 0) {
            $response = "Hier ist eine Liste aller Befehle:"
                . PHP_EOL
                . PHP_EOL . "/init <Ortsgruppe> <Wochentag>"
                . PHP_EOL . "Initialisiert eine neue Ortsgruppe"
                . PHP_EOL . "/init <Ortsgruppe>"
                . PHP_EOL . "(Privatchat) Fügt dich einer Ortsgruppe hinzu"
                . PHP_EOL
                . PHP_EOL . "/leave <Ortsgruppe>"
                . PHP_EOL . "Verlässt eine Ortsgruppe"
                . PHP_EOL
                . PHP_EOL . "/getto"
                . PHP_EOL . "Liefert einen Link zum Download der TO"
                . PHP_EOL
                . PHP_EOL . "/upto"
                . PHP_EOL . "Lädt die TO auf den SDS Server hoch (WIP)"
                . PHP_EOL
                . PHP_EOL . "/seeto"
                . PHP_EOL . "Liefert einen Link zum Ansehen der TO"
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
            send_message($token, $chat_id, $response, deleteCmd: $message_id);
        } else {
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // check if chat id is in any group in chats.json
            $found = false;
            $group;
            $groups = array();
            foreach ($chats["groups"] as $g) {
                if (in_array($chat_id, $g["members"])) {
                    $found = true;
                    array_push($groups, $g["name"]);
                }
            }
            $group = $groups[0];
            if ($found) {
                // Get TO
                if (strpos(strtolower($text), "/getto") === 0) {
                    $response = "Hier ist der Link zum Download der TO: "
                        . PHP_EOL . "https://www.politischdekoriert.de/sds-to-generator/downloadto.php?dir=" . $group;
                    send_message($token, $chat_id, $response, deleteCmd: $message_id);
                }
                // Upload TO
                else if (strpos(strtolower($text), "/upto") === 0) {
                    $response = "Clicke hier um die TO Hochzuladen: "
                        . PHP_EOL . "https://www.politischdekoriert.de/sds-to-generator/uploadto.php?dir=" . $group;
                    send_message($token, $chat_id, $response, deleteCmd: $message_id, delTime: 10, deleteAnswer: true);
                }
                // Look at TO
                else if (strpos(strtolower($text), "/seeto") === 0) {
                    $response = "Hier ist der Link zum Download der TO: "
                        . PHP_EOL . "https://www.politischdekoriert.de/sds-to-generator/index.php?dir=" . $group . "/Plenum";
                    send_message($token, $chat_id, $response, deleteCmd: $message_id, delTime: 10, deleteAnswer: true);
                }
                // /top or #top (not regarding capitalization)
                else if (strpos(strtolower($text), "#top") === 0 || strpos(strtolower($text), "/top") === 0) {
                    // get rest of message
                    // set title to first line
                    $lines = explode(PHP_EOL, $text);
                    // first line without first 4 characters
                    if (strlen($lines[0]) > 4) {
                        $title = substr($lines[0], 5);
                        // slice title from rest of message
                        $content = substr($text, strlen($title) + 6);
                    } else {
                        $title = "Kein Titel";
                        $content = substr($text, 5);
                    }

                    // get date from text using regex (yyyy-mm-dd or dd.mm.yyyy or dd.mm.yy or dd.mm.)
                    $matches = array();
                    preg_match("/\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4}|\d{2}\.\d{2}\.\d{2}|\d{2}\.\d{2}\./", $content, $matches);

                    // if no date is found, set date to today
                    if (count($matches) == 0) {
                        saveTOP($group, $title, $content);

                        // send response
                        $response = "TOP \"" . $title . "\" wurde erfolgreich hinzugefügt.";
                        send_message($token, $chat_id, $response, deleteAnswer: true);
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
                        saveEvent($group, $title, "(Siehe TOP)", $date);
                        saveTOP($group, $title, $content);

                        // send response
                        $response = "Event am " . $date->format("d.m.") . " erkannt. Event wurde hinzugefügt.";
                        send_message($token, $chat_id, $response, deleteAnswer: true);
                        $response = "TOP \"" . $title . "\" wurde erfolgreich hinzugefügt.";
                        send_message($token, $chat_id, $response, deleteAnswer: true);
                    }
                }
                // /termin or #termin (not regarding capitalization)
                else if (strpos(strtolower($text), "#termin") === 0 || strpos(strtolower($text), "/termin") === 0) {
                    // get rest of message
                    // set title to first line
                    $lines = explode(PHP_EOL, $text);
                    // first line without first 7 characters
                    if (strlen($lines[0]) > 7) {
                        $title = substr($lines[0], 8);
                        // slice title from rest of message
                        $content = substr($text, strlen($title) + 9);
                    } else {
                        $title = "Kein Titel";
                        $content = substr($text, 8);
                    }

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

                    saveEvent($group, $title, $content, $date);

                    // send response
                    $response = "Termin \"" . $title . "\" wurde erfolgreich hinzugefügt.";
                    send_message($token, $chat_id, $response, deleteAnswer: true);
                }
                // /del or #del (not regarding capitalization)
                else if (strpos(strtolower($text), "#del") === 0 || strpos(strtolower($text), "/del") === 0) {
                    // get rest of message
                    // set title to first line
                    $lines = explode(PHP_EOL, $text);
                    // first line without first 4 characters
                    $title = substr($lines[0], 5);

                    // delete top
                    if (deleteTOP($group, $title)) {
                        // send response
                        $response = "TOP \"" . $title . "\" wurde erfolgreich gelöscht.";
                        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                    } else {
                        $response = "TOP \"" . $title . "\" konnte nicht gefunden werden.";
                        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                    }

                    // delete event
                    if (deleteEvent($group, $title)) {
                        // send response
                        $response = "Termin \"" . $title . "\" wurde erfolgreich gelöscht.";
                        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                    } else {
                        $response = "Termin \"" . $title . "\" konnte nicht gefunden werden.";
                        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                    }
                }
                // Leave Group
                else if (strpos(strtolower($text), "/leave") === 0) {
                    if (strlen($text) > 7) {
                        $group = substr($text, 7);
                    }
                    // if group name in groups
                    if (in_array($group, $groups)) {
                        // remove chat_id from group members array of group in chats.jsons groups array
                        foreach ($chats["groups"] as &$g) {
                            if ($g["name"] == $group) {
                                $g["members"] = array_diff($g["members"], array($chat_id));
                            }
                        }
                        file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                        // send response
                        $response = "Du hast die Ortsgruppe " . $group . " erfolgreich verlassen.";
                        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
                    } else {
                        // send response
                        $response = "Du bist nicht in der Ortsgruppe " . $group . "";
                        send_message($token, $chat_id, $response, $message_id);
                    }
                }
            } else {
                $response = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> um eine neue Ortsgruppe hinzuzufügen.";
                send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAnswer: true);
            }
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
    file_put_contents("../TOs/" . $og . "/Plenum_to.json", json_encode($to, JSON_PRETTY_PRINT));
}

function saveEvent($og, $title, $content, $date)
{
    // enter TOP into TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);
    // generate unique id
    $id = uniqid();
    // add top to tops array
    array_push($events["events"], array("id" => $id, "title" => $title, "content" => $content, "date" => $date));
    file_put_contents("../TOs/" . $og . "/events.json", json_encode($events, JSON_PRETTY_PRINT));
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
            file_put_contents("../TOs/" . $og . "/Plenum_to.json", json_encode($to, JSON_PRETTY_PRINT));
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
            file_put_contents("../TOs/" . $og . "/events.json", json_encode($events, JSON_PRETTY_PRINT));
            return true;
        }
    }
    return false;
}

function send_message($token, $chat_id, $response, $deleteCmd = null, $delTime = 5, $deleteAnswer = false)
{
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($response) . "&disable_notification=true";
    // send message and get message id
    $message = json_decode(file_get_contents($url), true);
    $message_id = $message["result"]["message_id"];

    // delete message after delTime seconds
    $url = "https://api.telegram.org/bot" . $token . "/deleteMessage?chat_id=" . $chat_id . "&message_id=" . $message_id;
    $url2 = "https://api.telegram.org/bot" . $token . "/deleteMessage?chat_id=" . $chat_id . "&message_id=" . $deleteCmd;

    sleep($delTime);

    // if deleteAnswer is true, delete answer message
    if ($deleteAnswer) {
        file_get_contents($url);
    }
    // if deleteCmd is not null, delete command message
    if ($deleteCmd != null) {
        file_get_contents($url2);
    }
}

function send_document($token, $chat_id, $file, $caption)
{
    $url = "https://api.telegram.org/bot" . $token . "/sendDocument?chat_id=" . $chat_id . "&document=" . urlencode($file) . "&caption=" . urlencode($caption);
    // send message and get message id
    $message = json_decode(file_get_contents($url), true);
    $message_id = $message["result"]["message_id"];
}

function logToFile($message)
{
    $log = fopen("log.txt", "a");
    fwrite($log, $message . PHP_EOL);
    fclose($log);
}
?>