<?php

// load token from token.txt
$token = file_get_contents("token.txt");
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!isset($update['message'])) {
    return;
}

$message = $update['message'];
$text = $message['text'];

if (!isset($message['text'])) {
    return;
}

$domain = "https://www.politischdekoriert.de/sds-to-generator/";
$message_id = $message['message_id'];
$username = $message['from']['username'];

if (!isset($username)) {
    // use id if no username is set
    $username = $message['from']['id'];
}

//log message
if ($text[0] == "/" || $text[0] == "#") {
    // if /init
    if (strpos($text, "/init") === 0) {
        // log /init without password
        $rest = explode(" ", $text);
        if (count($rest) > 3) {
            logToFile($username . ": " . $rest[0] . " " . $rest[1] . " " . $rest[2] . " ********");
        } else if (count($rest) > 2) {
            logToFile($username . ": " . $rest[0] . " " . $rest[1] . " ********");
        } else {
            logToFile($username . ": " . $text);
        }
    } else if (strpos($text, "/changepw") === 0) {
        // log /changepw without password
        $rest = explode(" ", $text);
        if (count($rest) > 2) {
            logToFile($username . ": " . $rest[0] . " ********");
        } else {
            logToFile($username . ": " . $text);
        }
    } else {
        logToFile($username . ": " . $text);
    }
} else {
    return;
}

$chat_id = $message['chat']['id'];
// user is in a group
$ingroup = $chat_id < 0;
// Start the bot
if (strpos($text, "/start") === 0) {
    if ($ingroup) {
        send_message($token, $chat_id, getMessage("start group"), deleteCmd: $message_id);
    } else {
        send_message($token, $chat_id, getMessage("start"), deleteCmd: $message_id);
    }
}
// Initialize a group
else if (strpos($text, "/init") === 0) {
    // load chats.json
    $chats = json_decode(file_get_contents("chats.json"), true);

    $rest = explode(" ", $text);

    // check if rest of message is valid
    if (count($rest) < 3) {
        send_message($token, $chat_id, getMessage("not correct init"), deleteCmd: $message_id, deleteAnswer: true);
        return;
    }

    // get name
    $name = $rest[1];

    // if folder for Ortsgruppe does not exist
    if (!file_exists("../TOs/" . $name)) {

        // check if name is valid
        if (preg_match("/[^a-zA-Z0-9äüöß]/", $name)) {
            send_message($token, $chat_id, getMessage("not correct init characters"), deleteCmd: $message_id, deleteAnswer: true);
            return;
        }

        if (count($rest) < 4) {
            // default weekday is the current weekday
            $weekday = strtolower(date("l"));

            // get password
            $password = $rest[2];
        } else {
            // get weekday
            $weekday = weekdayDE($rest[2]);

            // get password
            $password = $rest[3];
        }

        // enter chat id and name into chats.json
        array_push($chats['groups'], array("name" => $name, "dir" => "Ortsgruppe" . $name . "/", "password" => hash("sha256", $password), "weekday" => $weekday, "members" => array($chat_id)));
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
        send_message($token, $chat_id, getMessage("init", [$name]), deleteCmd: $message_id, deleteAnswer: true);
    } else {

        // get password
        $password = $rest[2];

        // enter chat id into group members
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $name) {
                // check if user is already in group
                if (in_array($chat_id, $g['members'])) {
                    send_message($token, $chat_id, getMessage("already in group", [$name]), deleteCmd: $message_id, deleteAnswer: true);
                    return;
                }

                // check if message is 3 words long
                if (count($rest) > 3) {
                    send_message($token, $chat_id, getMessage("not correct init private"), deleteCmd: $message_id, deleteAnswer: true);
                    return;
                }

                // check if password is correct
                if (hash("sha256", $password) != $g['password']) {
                    send_message($token, $chat_id, getMessage("wrong password"), deleteCmd: $message_id, deleteAnswer: true);
                    return;
                }
                array_push($g['members'], $chat_id);

                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                send_message($token, $chat_id, getMessage("joined group", [$name]), deleteCmd: $message_id, deleteAnswer: true);
                return;
            }
        }

        // send response
        send_message($token, $chat_id, getMessage("group not found", [$name]), deleteCmd: $message_id, deleteAnswer: true);
    }
}
// Help
else if (strpos(strtolower($text), "/help") === 0) {
    send_message($token, $chat_id, getMessage("help"), deleteCmd: $message_id);
} else {
    // load chats.json
    $chats = json_decode(file_get_contents("chats.json"), true);
    // check if chat id is in any group in chats.json
    $found = false;
    $group;
    $groups = array();
    foreach ($chats['groups'] as $g) {
        if (in_array($chat_id, $g['members'])) {
            $found = true;
            array_push($groups, $g['name']);
        }
    }
    $group = $groups[0];

    if (!$found) {
        if (count(explode(" ", $text)) > 1) {
            send_message($token, $chat_id, getMessage("not initialized"), deleteCmd: $message_id, deleteAnswer: true);
            return;
        } else {
            send_message($token, $chat_id, getMessage("not initialized"), deleteAnswer: true);
            return;
        }
    }
    // Get TO
    if (strpos(strtolower($text), "/getto") === 0) {
        $response = getMessage("get to")
            . PHP_EOL . $domain . "Actions/downloadto.php?dir=" . $group . "&chatid=" . $chat_id;
        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAtMidnight: true);
    }
    // Upload TO
    else if (strpos(strtolower($text), "/upto") === 0) {
        $mtoken = createToken($group);

        $response = getMessage("upload to")
            . PHP_EOL . $domain . "Actions/uploadto.php?dir=" . $group . "&token=" . $mtoken;
        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAtMidnight: true);
    }
    // Look at TO
    else if (strpos(strtolower($text), "/seeto") === 0) {
        $mtoken = createToken($group);

        $response = getMessage("see to")
            . PHP_EOL . $domain . "index.php?dir=" . $group . "/Plenum&token=" . $mtoken;
        send_message($token, $chat_id, $response, deleteCmd: $message_id, deleteAtMidnight: true);
    }
    // Change Password
    else if (strpos(strtolower($text), "/changepw") === 0) {
        // get rest of message
        $password = substr($text, 10);

        if (strlen($password) < 4) {
            send_message($token, $chat_id, getMessage("password too short"), deleteCmd: $message_id, deleteAnswer: true);
            return;
        }
        // set new password
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $group) {
                $g['password'] = hash("sha256", $password);
                break;
            }
        }
        file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));
        send_message($token, $chat_id, getMessage("password changed", [$group]), deleteCmd: $message_id, deleteAnswer: true);
    }
    // Change Weekday
    else if (strpos(strtolower($text), "/plenum") === 0) {
        // get rest of message (lowercase)
        $weekday = weekdayDE(strtolower(substr($text, 8)));

        $weekdays = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

        if (!in_array($weekday, $weekdays)) {
            send_message($token, $chat_id, getMessage("has to be weekday"), deleteCmd: $message_id, deleteAnswer: true);
            return;
        }

        // set new weekday
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $group) {
                $g['weekday'] = $weekday;
                send_message($token, $chat_id, getMessage("plenum changed", [$group, weekdayED($weekday)]), deleteCmd: $message_id, deleteAnswer: true);
                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                // change date in TOs/group/Plenum_to.json
                $to = json_decode(file_get_contents("../TOs/" . $group . "/Plenum_to.json"), true);
                // set date to next weekday from today (if today is weekday, set date to today)
                if (date("l") == $weekday) {
                    $to['date'] = date("Y-m-d");
                } else {
                    $to['date'] = date("Y-m-d", strtotime("next " . $weekday));
                }
                file_put_contents("../TOs/" . $group . "/Plenum_to.json", json_encode($to, JSON_PRETTY_PRINT));
                break;
            }
        }
    }
    // Change Directory
    else if (strpos(strtolower($text), "/folder") === 0) {
        // get rest of message
        $folder = substr($text, 8);

        // set new directory
            if ($g['name'] == $group) {
                $g['dir'] = $folder;
                send_message($token, $chat_id, getMessage("folder changed", [$group, $folder]), deleteCmd: $message_id, deleteAnswer: true);
                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                break;
            }
        }
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
        preg_match("/\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4}|\d{2}\.\d{2}\.\d{2}|\d{2}\.\d{2}\./", $text, $matches);

        // if no date is found, set date to today
        if (count($matches) == 0) {
            saveTOP($group, $title, $content);

            // send response
            $message_id = send_message($token, $chat_id, getMessage("top saved", [$title]), deleteAnswer: true);
        } else {
            // bring date to format yyyy-mm-dd
            $date = $matches[0];
            if (preg_match("/\d{2}\.\d{2}\.\d{4}/", $date)) {
                $date = DateTime::createFromFormat("d.m.Y", $date);
            } else if (preg_match("/\d{2}\.\d{2}\.\d{2}/", $date)) {
                $date = DateTime::createFromFormat("d.m.y", $date);
            } else if (preg_match("/\d{2}\.\d{2}\./", $date)) {
                $date = DateTime::createFromFormat("d.m.", $date);
            }
            $datef = $date->format("Y-m-d");
            saveEvent($group, $title, "(Siehe TOP)", $datef);

            saveTOP($group, $title, $content);

            // send response
            send_message($token, $chat_id, getMessage("event recognized", [$date->format("d.m.")]), deleteAnswer: true);
            $message_id = send_message($token, $chat_id, getMessage("top saved", [$title]), deleteAnswer: true);
        }

        // react to message with tick
        react($token, $chat_id, $message_id, "✅");
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
        $message_id = send_message($token, $chat_id, getMessage("event saved", [$title]), deleteAnswer: true);

        // react to message with tick
        react($token, $chat_id, $message_id, "✅");
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
            send_message($token, $chat_id, getMessage("top deleted", [$title]), deleteCmd: $message_id, deleteAnswer: true);
        } else {
            send_message($token, $chat_id, getMessage("top not found", [$title]), deleteCmd: $message_id, deleteAnswer: true);
        }

        // delete event
        if (deleteEvent($group, $title)) {
            // send response
            send_message($token, $chat_id, getMessage("event deleted", [$title]), deleteCmd: $message_id, deleteAnswer: true);
        } else {
            send_message($token, $chat_id, getMessage("event not found", [$title]), deleteCmd: $message_id, deleteAnswer: true);
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
            foreach ($chats['groups'] as &$g) {
                if ($g['name'] == $group) {
                    $g['members'] = array_diff($g['members'], array($chat_id));
                }
            }
            file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

            // send response
            send_message($token, $chat_id, getMessage("left group", [$group]), deleteCmd: $message_id, deleteAnswer: true);
        } else {
            // send response
            send_message($token, $chat_id, getMessage("not in group", [$group]), deleteCmd: $message_id, deleteAnswer: true);
        }
    }
}

function getMessage($id, $args = [])
{
    switch ($id) {
        case "start":
            $msg = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und deshalb manchmal etwas buggy."
                . PHP_EOL
                . PHP_EOL . "Du kannst deiner Ortsgruppe beitreten indem du /init <Ort> <Passwort> eingibst."
                . PHP_EOL . "Beispiel: /init Berlin 1234"
                . PHP_EOL
                . PHP_EOL . "Falls ihr noch keine Ortsgruppe eingerichtet habt, kannst du das tun, indem du mich in deiner Ortsgruppe hinzufügst und danach in dieser /init <Ort> <Plenumstag> <Passwort> eingibst."
                . PHP_EOL . "Beispiel: /init Berlin Mittwoch 1234"
                . PHP_EOL . "(Das Passwort kannst du dir aussuchen und später ändern.)"
                . PHP_EOL
                . PHP_EOL . "Falls ihr einen Fehler findet, meldet ihn bitte an"
                . PHP_EOL . "support@politischdekoriert.de"
                . PHP_EOL
                . PHP_EOL . "Falls du Hilfe brauchst, gib einfach /help ein.";
            break;
        case "start group":
            $msg = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und deshalb manchmal etwas buggy."
                . PHP_EOL
                . PHP_EOL . "Starte am besten indem du in deiner Ortsgruppe /init <Ort> <Plenumstag> <Passwort> eingibst."
                . PHP_EOL . "Beispiel: /init Berlin Mittwoch 1234"
                . PHP_EOL
                . PHP_EOL . "Du kannst Befehle auch aus deimem Privatchat mit mir eingeben. Dazu musst du dort einfach /init <Ort> <Passwort> eingeben."
                . PHP_EOL
                . PHP_EOL . "Falls ihr einen Fehler findet, meldet ihn bitte an"
                . PHP_EOL . "support@politischdekoriert.de"
                . PHP_EOL
                . PHP_EOL . "Falls du Hilfe brauchst, gib einfach /help ein.";
            break;
        case "help":
            $msg = "Hier ist eine Liste aller Befehle:"
                . PHP_EOL
                . PHP_EOL . "/top <Titel>"
                . PHP_EOL . "<Text>"
                . PHP_EOL . "Fügt einen neuen TOP hinzu"
                . PHP_EOL
                . PHP_EOL . "/termin <Titel>"
                . PHP_EOL . "<Text>"
                . PHP_EOL . "Fügt einen neuen Termin hinzu"
                . PHP_EOL . "(Dies passiert auch automatisch, wenn ich ein Datum (X.X.) in deinem TOP finde.)"
                . PHP_EOL
                . PHP_EOL . "/del <Titel>"
                . PHP_EOL . "Löscht einen TOP und/oder Termin"
                . PHP_EOL
                . PHP_EOL . "/getto"
                . PHP_EOL . "Liefert einen Link zum Download der TO"
                . PHP_EOL
                . PHP_EOL . "/upto"
                . PHP_EOL . "Lädt die TO auf den SDS Server hoch"
                . PHP_EOL
                . PHP_EOL . "/seeto"
                . PHP_EOL . "Liefert einen Link zum Ansehen der TO"
                . PHP_EOL
                . PHP_EOL
                . PHP_EOL . "Ortsgruppe einrichten:"
                . PHP_EOL
                . PHP_EOL . "/init <Ortsgruppe> <Wochentag> <Passwort>"
                . PHP_EOL . "Initialisiert eine neue Ortsgruppe"
                . PHP_EOL . "(Das Passwort kannst du dir aussuchen und später ändern.)"
                . PHP_EOL
                . PHP_EOL . "/init <Ortsgruppe> <Passwort>"
                . PHP_EOL . "Fügt dich einer Ortsgruppe hinzu"
                . PHP_EOL . "(Schrib mir das am besten in deinem Privatchat mit mir)"
                . PHP_EOL
                . PHP_EOL . "/leave <Ortsgruppe>"
                . PHP_EOL . "Verlässt eine Ortsgruppe"
                . PHP_EOL
                . PHP_EOL . "/changepw <Passwort>"
                . PHP_EOL . "Ändert das Passwort einer Ortsgruppe"
                . PHP_EOL
                . PHP_EOL . "/plenum <Tag>"
                . PHP_EOL . "Ändert den Tag des Plenums"
                . PHP_EOL
                . PHP_EOL . "/folder <Dateipfad>"
                . PHP_EOL . "Ändert den Speicherort der TO (Bsp. \"/folder Ortsgruppe Berlin/2023 SoSe/\")"
                . PHP_EOL
                . PHP_EOL . "Wenn ihr eure Ortsgruppe löschen wollt, schreibt mir einfach eine Mail an support@politischdekoriert.de";
            break;
        case "get to":
            $msg = "Klicke hier um die TO zu erhalten: ";
            break;
        case "upload to":
            $msg = "Klicke hier um die TO Hochzuladen: ";
            break;
        case "see to":
            $msg = "Hier ist der Link zur TO: ";
            break;
        case "top saved":
            $msg = "TOP \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event saved":
            $msg = "Termin \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event recognized":
            $msg = "Event am " . $args[0] . " erkannt. Event wurde hinzugefügt.";
            break;
        case "top deleted":
            $msg = "TOP \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "event deleted":
            $msg = "Termin \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "top not found":
            $msg = "TOP \"" . $args[0] . "\" wurde nicht gefunden.";
            break;
        case "event not found":
            $msg = "Termin \"" . $args[0] . "\" wurde nicht gefunden.";
            break;
        case "init":
            $msg = "Ortsgruppe " . $args[0] . " wurde erfolgreich hinzugefügt.";
            break;
        case "plenum changed":
            $msg = "Plenumstag für " . $args[0] . " wurde auf " . $args[1] . " geändert.";
            break;
        case "folder changed":
            $msg = "Speicherort für " . $args[0] . " wurde auf \"" . $args[1] . "\" geändert.";
            break;
        case "joined group":
            $msg = "Du bist der Ortsgruppe " . $args[0] . " beigetreten.";
            break;
        case "left group":
            $msg = "Du hast die Ortsgruppe " . $args[0] . " verlassen.";
            break;
        case "group not found":
            $msg = "Die Ortsgruppe " . $args[0] . " wurde nicht gefunden.";
            break;
        case "not in group":
            $msg = "Du bist nicht in der Ortsgruppe " . $args[0] . ".";
            break;
        case "already in group":
            $msg = "Du bist bereits in der Ortsgruppe " . $args[0] . ".";
            break;
        case "has to be weekday":
            $msg = "Der Tag muss ein Wochentag sein. (z.B. Montag, Dienstag, ...)";
            break;
        case "not correct init":
            $msg = "Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
        case "not correct init private":
            $msg = "Bitte benutze den Befehl /init <Ortsgruppe> <Passwort> um einer Ortsgruppe beizutreten.";
            break;
        case "not correct init characters":
            $msg = "Der Ortsgruppenname darf nur Buchstaben und Zahlen enthalten.";
            break;
        case "password changed":
            $msg = "Passwort für Ortsgruppe " . $args[0] . " wurde geändert.";
            break;
        case "wrong password":
            $msg = "Das Passwort ist falsch.";
            break;
        case "password too short":
            $msg = "Das Passwort muss mindestens 4 Zeichen lang sein.";
            break;
        case "not initialized":
            $msg = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
            break;
        default:
            $msg = "Fehler: Nachricht nicht gefunden.";
            break;
    }
    return $msg;
}

function saveTOP($og, $title, $content)
{
    // enter TOP into TOs/Ortsgruppe/Plenum_to.json
    $to = json_decode(file_get_contents("../TOs/" . $og . "/Plenum_to.json"), true);
    // generate unique id
    $id = uniqid();
    // add top to tops array
    array_push($to['tops'], array("id" => $id, "title" => $title, "content" => $content));
    file_put_contents("../TOs/" . $og . "/Plenum_to.json", json_encode($to, JSON_PRETTY_PRINT));
}

function saveEvent($og, $title, $content, $date)
{
    // enter TOP into TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);
    // generate unique id
    $id = uniqid();
    // add top to tops array
    array_push($events['events'], array("id" => $id, "title" => $title, "content" => $content, "date" => $date));
    file_put_contents("../TOs/" . $og . "/events.json", json_encode($events, JSON_PRETTY_PRINT));
}

function deleteTOP($og, $title)
{
    // load TOs/Ortsgruppe/Plenum_to.json
    $to = json_decode(file_get_contents("../TOs/" . $og . "/Plenum_to.json"), true);
    // search for top with title
    foreach ($to['tops'] as $key => $top) {
        if ($top['title'] == $title) {
            // delete top
            unset($to['tops'][$key]);
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
    foreach ($events['events'] as $key => $event) {
        if ($event['title'] == $title) {
            // delete top
            unset($events['events'][$key]);
            // save file
            file_put_contents("../TOs/" . $og . "/events.json", json_encode($events, JSON_PRETTY_PRINT));
            return true;
        }
    }
    return false;
}

function send_message($token, $chat_id, $response, $deleteCmd = null, $delTime = 5, $deleteAnswer = false, $deleteAtMidnight = false)
{
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($response) . "&disable_notification=true";
    // send message and get message id
    $message = json_decode(file_get_contents($url), true);
    $message_id = $message['result']['message_id'];

    // delete message after delTime seconds
    $url = "https://api.telegram.org/bot" . $token . "/deleteMessage?chat_id=" . $chat_id . "&message_id=" . $message_id;
    $url2 = "https://api.telegram.org/bot" . $token . "/deleteMessage?chat_id=" . $chat_id . "&message_id=" . $deleteCmd;

    // log answer
    logToFile("Answer: " . $response);

    // if deleteAtMidnight is true, add to todelete.json
    if ($deleteAtMidnight) {
        $todelete = json_decode(file_get_contents("todelete.json"), true);
        array_push($todelete, $url);
        file_put_contents("todelete.json", json_encode($todelete, JSON_PRETTY_PRINT));
    }

    // if deleteCmd is not null, delete command message
    if ($deleteCmd != null) {
        file_get_contents($url2);
    }

    // if deleteAnswer is true, delete answer message
    if ($deleteAnswer) {
        sleep($delTime);
        // ! Find a better way to do this
        file_get_contents($url);
    }

    return $message_id;
}

function react($token, $chat_id, $message_id, $reaction)
{
    // react to message
}

function leave_group($token, $chat_id)
{
    // leave group
    $url = "https://api.telegram.org/bot" . $token . "/leaveChat?chat_id=" . $chat_id;
    file_get_contents($url);
}

function createToken($group)
{
    // open tokens.json and create a new token for the group
    $tokens = json_decode(file_get_contents("tokens.json"), true);
    // look if group already has a token
    $found = false;
    foreach ($tokens as $t) {
        if ($t['group'] == $group) {
            $found = true;
            break;
        }
    }
    // if not create a new array
    if (!$found) {
        array_push($tokens, array("group" => $group, "tokens" => array()));
    }
    // create new token
    $mtoken = bin2hex(random_bytes(16));
    //add token to tokens.json
    foreach ($tokens as &$t) {
        if ($t['group'] == $group) {
            array_push($t['tokens'], $mtoken);
            break;
        }
    }
    // save tokens.json
    file_put_contents("tokens.json", json_encode($tokens, JSON_PRETTY_PRINT));

    return $mtoken;
}

function weekdayED($day)
{
    switch ($day) {
        case "monday":
            return "Montag";
        case "tuesday":
            return "Dienstag";
        case "wednesday":
            return "Mittwoch";
        case "thursday":
            return "Donnerstag";
        case "friday":
            return "Freitag";
        case "saturday":
            return "Samstag";
        case "sunday":
            return "Sonntag";
    }
}

function weekdayDE($day)
{
    switch (strtolower($day)) {
        case "montag":
            return "monday";
        case "dienstag":
            return "tuesday";
        case "mittwoch":
            return "wednesday";
        case "donnerstag":
            return "thursday";
        case "freitag":
            return "friday";
        case "samstag":
            return "saturday";
        case "sonntag":
            return "sunday";
        default:
            $weekdays = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
            if (in_array(strtolower($day), $weekdays)) {
                return strtolower($day);
            } else {
                // current weekday
                return date("l");
            }
    }
}

function logToFile($message)
{
    $log = fopen("log.txt", "a");
    // get current time
    $time = date("d.m.Y H:i:s");

    // tab before every line
    $message = str_replace(PHP_EOL, PHP_EOL . "\t", $message);

    fwrite($log, "\t" . $time . " | " . $message . PHP_EOL);
    fclose($log);
}
?>