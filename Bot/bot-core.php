<?php
require_once "response.php";
function getMessage($id, $args = [])
{
    $response = new Response();

    switch ($id) {
        case "start":
            $response->text = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und deshalb manchmal etwas buggy."
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
            $response->text = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und deshalb manchmal etwas buggy."
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
            $response->text = "Hier ist eine Liste aller Befehle:"
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
            $response->deleteAnswer = true;
            $response->text = "Hier ist die TO";
            break;
        case "upload to":
            $response->deleteAnswer = true;
            $response->text = "Die TO wurde erfolgreich hochgeladen.";
            break;
        case "see to":
            $response->deleteAtMidnight = true;
            $response->text = "Hier ist der Link zur TO";
            $response->buttons = [
                ["text" => "TO Anschauen", "url" => $args[0]]
            ];
            break;
        case "top saved":
            $response->deleteAnswer = true;
            $response->text = "TOP \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event saved":
            $response->deleteAnswer = true;
            $response->text = "Termin \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event recognized":
            $response->text = "Termin am " . $args[0] . " erkannt. Hinzufügen?";
            $response->buttons = [
                ["text" => "Ja", "callback_data" => "do:/termin " . $args[1]],
                ["text" => "Nein", "callback_data" => "say:event recognized/no"]
            ];
            break;
        case "event recognized/no":
            $response->deleteAnswer = true;
            $response->text = "Termin wurde nicht hinzugefügt.";
            break;
        case "top deleted":
            $response->deleteAnswer = true;
            $response->text = "TOP \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "event deleted":
            $response->deleteAnswer = true;
            $response->text = "Termin \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "top not found":
            $response->deleteAnswer = true;
            $response->text = "TOP \"" . $args[0] . "\" wurde nicht gefunden.";
            break;
        case "event not found":
            $response->deleteAnswer = true;
            $response->text = "Termin \"" . $args[0] . "\" wurde nicht gefunden.";
            break;
        case "init":
            $response->deleteAnswer = true;
            $response->text = "Ortsgruppe " . $args[0] . " wurde erfolgreich hinzugefügt.";
            break;
        case "plenum changed":
            $response->deleteAnswer = true;
            $response->text = "Plenumstag für " . $args[0] . " wurde auf " . $args[1] . " geändert.";
            break;
        case "folder changed":
            $response->deleteAnswer = true;
            $response->text = "Speicherort für " . $args[0] . " wurde auf \"" . $args[1] . "\" geändert.";
            break;
        case "joined group":
            $response->deleteAnswer = true;
            $response->text = "Du bist der Ortsgruppe " . $args[0] . " beigetreten.";
            break;
        case "leave group":
            $response->text = "Willst du die Ortsgruppe " . $args[0] . " wirklich verlassen?";
            $response->buttons = [
                ["text" => "Ja", "callback_data" => "do:/leave " . $args[0] . " confirm"],
                ["text" => "Nein", "callback_data" => "none"]
            ];
            break;
        case "left group":
            $response->deleteAnswer = true;
            $response->text = "Du hast die Ortsgruppe " . $args[0] . " verlassen.";
            break;
        case "group not found":
            $response->deleteAnswer = true;
            $response->text = "Die Ortsgruppe " . $args[0] . " wurde nicht gefunden.";
            break;
        case "not in group":
            $response->deleteAnswer = true;
            $response->text = "Du bist nicht in der Ortsgruppe " . $args[0] . ".";
            break;
        case "already in group":
            $response->deleteAnswer = true;
            $response->text = "Du bist bereits in der Ortsgruppe " . $args[0] . ".";
            break;
        case "has to be weekday":
            $response->deleteAnswer = true;
            $response->text = "Der Tag muss ein Wochentag sein.";
            $response->buttons = [
                ["text" => "Montag", "callback_data" => "do:/plenum monday"],
                ["text" => "Dienstag", "callback_data" => "do:/plenum tuesday"],
                ["text" => "Mittwoch", "callback_data" => "do:/plenum wednesday"],
                ["text" => "Donnerstag", "callback_data" => "do:/plenum thursday"],
                ["text" => "Freitag", "callback_data" => "do:/plenum friday"],
                ["text" => "Samstag", "callback_data" => "do:/plenum saturday"],
                ["text" => "Sonntag", "callback_data" => "do:/plenum sunday"]
            ];
            break;
        case "not correct init":
            $response->deleteAnswer = true;
            $response->text = "Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
        case "not correct init private":
            $response->deleteAnswer = true;
            $response->text = "Bitte benutze den Befehl /init <Ortsgruppe> <Passwort> um einer Ortsgruppe beizutreten.";
            break;
        case "not correct init characters":
            $response->deleteAnswer = true;
            $response->text = "Der Ortsgruppenname darf nur Buchstaben und Zahlen enthalten.";
            break;
        case "password changed":
            $response->deleteAnswer = true;
            $response->text = "Passwort für Ortsgruppe " . $args[0] . " wurde geändert.";
            break;
        case "wrong password":
            $response->deleteAnswer = true;
            $response->text = "Das Passwort ist falsch.";
            break;
        case "password too short":
            $response->deleteAnswer = true;
            $response->text = "Das Passwort muss mindestens 4 Zeichen lang sein.";
            break;
        case "not initialized":
            $response->deleteAnswer = true;
            $response->text = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
            break;
        case "command not found":
            $response->deleteAnswer = true;
            $response->text = "Dieser Befehl wurde nicht gefunden. Gib /help ein um eine Liste aller Befehle zu erhalten.";
            break;
        default:
            $response->text = "Fehler: Nachricht nicht gefunden.";
            break;
    }

    return $response;
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
    switch (strtolower($day)) {
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