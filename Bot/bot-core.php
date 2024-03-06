<?php
require_once "BotMessage.php";
class UserMessage
{
    public function __construct($text = "", $username = null)
    {
        $this->text = $text;
        $this->username = $username;
    }

    public $text;
    public $username;
}

interface BotApi
{
    public function handle_callback($update): ?UserMessage;
    public function send_message(BotMessage $response, string $chat_id = null);
    public function send_file($path);
    public function delete_message($message_id);
    public function debug_log($message);
    public function react($reaction, $message_id = null);
    public function leave_group();
    public function in_group(string $chat_id = null): bool;
    public function get_uid(): string;
}
class Bot
{
    public BotApi $api;

    public function __construct(BotApi $api)
    {
        $this->api = $api;
    }

    public function handle_input($input)
    {
        $update = json_decode($input, true);

        $callback_result = $this->api->handle_callback($update);

        if ($callback_result == null) {
            return;
        }

        $this->handle_message($callback_result);
    }

    public function handle_message(UserMessage $callback_result)
    {
        $text = $callback_result->text;
        $username = $callback_result->username;

        $this->log_message($text, $username);

        if ($text[0] != "/" && $text[0] != "#") {
            return;
        }

        // Start the bot
        if (strpos($text, "/start") === 0) {
            if ($this->api->in_group()) {
                $this->api->send_message(getMessage("start group"));
            } else {
                $this->api->send_message(getMessage("start"));
            }
        }
        // Initialize a group
        else if (strpos($text, "/init") === 0) {
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);

            $rest = explode(" ", $text);

            // check if rest of message is valid
            if (count($rest) < 3) {
                $this->api->send_message(getMessage("not correct init"));
                return;
            }

            // get name
            $name = $rest[1];

            // if folder for Ortsgruppe does not exist
            if (!file_exists("../TOs/" . $name)) {

                // check if name is valid
                if (preg_match("/[^a-zA-Z0-9äüöß]/", $name)) {
                    $this->api->send_message(getMessage("not correct init characters"));
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
                $salt = file_get_contents("../salt.txt");
                array_push($chats['groups'], array("name" => $name, "dir" => "Ortsgruppe" . $name . "/", "password" => hash("sha256", $password . $salt), "weekday" => $weekday, "members" => array($this->api->get_uid())));
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
                $this->api->send_message(getMessage("init", [$name]));
            } else {

                // get password
                $password = $rest[2];

                // enter chat id into group members
                foreach ($chats['groups'] as &$g) {
                    if ($g['name'] == $name) {
                        // check if user is already in group
                        if (in_array($this->api->get_uid(), $g['members'])) {
                            $this->api->send_message(getMessage("already in group", [$name]));
                            return;
                        }

                        // check if message is 3 words long
                        if (count($rest) > 3) {
                            $this->api->send_message(getMessage("not correct init private"));
                            return;
                        }

                        // check if password is correct
                        $salt = file_get_contents("../salt.txt");
                        if (hash("sha256", $password . $salt) != $g['password']) {
                            $this->api->send_message(getMessage("wrong password"));
                            return;
                        }
                        array_push($g['members'], $this->api->get_uid());

                        file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                        $this->api->send_message(getMessage("joined group", [$name]));
                        return;
                    }
                }

                // send response
                $this->api->send_message(getMessage("group not found", [$name]));
            }
        }
        // Help
        else if (strpos(strtolower($text), "/help") === 0) {
            $this->api->send_message(getMessage("help"));
        } else {
            // load chats.json
            $chats = json_decode(file_get_contents("chats.json"), true);
            // check if chat id is in any group in chats.json
            $found = false;
            $groups = array();
            foreach ($chats['groups'] as $g) {
                if (in_array($this->api->get_uid(), $g['members'])) {
                    $found = true;
                    array_push($groups, $g['name']);
                } else if ($this->api->get_uid() == "debug") {
                    $found = true;
                    array_push($groups, "debug");
                }
            }

            if (!$found) {
                if (count(explode(" ", $text)) > 1) {
                    $this->api->send_message(getMessage("not initialized"));
                    return;
                } else {
                    $this->api->send_message(getMessage("not initialized"));
                    return;
                }
            }

            $group = $groups[0];

            // Get TO
            if (strpos(strtolower($text), "/getto") === 0) {
                $result = renderMarkDown($group . "/Plenum");
                // add .md
                $path = "Files/" . $result['filename'] . ".md";

                // create new .md file and save url
                $myfile = fopen($path, "w") or die("Unable to open file!");
                fwrite($myfile, $result['markdown']);
                fclose($myfile);

                $this->api->send_file($path);
                $this->api->send_message(getMessage("get to"));
            }
            // Upload TO
            else if (strpos(strtolower($text), "/upto") === 0) {
                $rest = array();

                if (strlen($text) > 6) {
                    $rest = explode(" ", substr($text, 6));
                }

                $force = false;

                if (count($rest) > 0) {
                    $force = $rest[0] == "force";
                }

                $result = renderMarkDown($group . "/Plenum");
                $link_to_file = upload($result['markdown'], $result['filename'], $group . "/Plenum", true, $force);

                if ($link_to_file == false) {
                    $this->api->send_message(getMessage("file already exists"));
                    return;
                }

                $this->api->send_message(getMessage("upload to" . ($force ? " force" : ""), [$link_to_file]));

                if (!$this->api->in_group()) {
                    // get group chat id
                    $chat_id = "";
                    foreach ($chats['groups'] as $g) {
                        if ($g['name'] == $group) {
                            foreach ($g['members'] as $member) {
                                if ($this->api->in_group($member)) {
                                    $chat_id = $member;
                                    break;
                                }
                            }
                            break;
                        }
                    }

                    if ($chat_id != "") {
                        $this->api->send_message(getMessage("upload to group", [$link_to_file]), $chat_id);
                    }
                }
            }
            // Look at TO
            else if (strpos(strtolower($text), "/seeto") === 0) {
                // get date from TOs/group/Plenum_to.json
                $to = json_decode(file_get_contents("../TOs/" . $group . "/Plenum_to.json"), true);
                $date = new DateTime($to['date']);
                $filename = str_replace("-", "_", $date->format("Y_m_d")) . ".md";
                $cloudPath = getCloudPath($group . "/Plenum", $date->getTimestamp());

                $sdsCloud = new WebdavApi("../webdavuser.config");

                if ($sdsCloud->fileExists($filename, $cloudPath)) {
                    $cloudLink = $sdsCloud->getFileLink($filename, $cloudPath);
                }

                $mtoken = createToken($group);
                $actual_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
                $args = [$actual_link . "/sds-to-generator/index.php?dir=" . $group . "/Plenum&token=" . $mtoken];
                if (isset($cloudLink)) {
                    array_push($args, $cloudLink);
                }
                $this->api->send_message(getMessage("see to", $args));
            }
            // Change Password
            else if (strpos(strtolower($text), "/changepw") === 0) {
                // get rest of message
                $password = substr($text, 10);

                if (strlen($password) < 4) {
                    $this->api->send_message(getMessage("password too short"));
                    return;
                }
                // set new password
                $salt = file_get_contents("../salt.txt");
                foreach ($chats['groups'] as &$g) {
                    if ($g['name'] == $group) {
                        $g['password'] = hash("sha256", $password . $salt);
                        break;
                    }
                }
                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));
                $this->api->send_message(getMessage("password changed", [$group]));
            }
            // Change Weekday
            else if (strpos(strtolower($text), "/plenum") === 0) {
                // get rest of message (lowercase)
                $weekday = weekdayDE(substr($text, 8));

                $weekdays = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

                if (!in_array($weekday, $weekdays)) {
                    $this->api->send_message(getMessage("has to be weekday"));
                    return;
                }

                // set new weekday
                foreach ($chats['groups'] as &$g) {
                    if ($g['name'] == $group) {
                        $g['weekday'] = $weekday;
                        $this->api->send_message(getMessage("plenum changed", [$group, weekdayED($weekday)]));
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
                foreach ($chats['groups'] as &$g) {
                    if ($g['name'] == $group) {
                        $g['dir'] = $folder;
                        $this->api->send_message(getMessage("folder changed", [$group, $folder]));
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
                preg_match("/\d{4}-\d{2}-\d{2}|\d{1,2}\.\d{1,2}\.\d{4}|\d{1,2}\.\d{1,2}\.\d{1,2}|\d{1,2}\.\d{1,2}\./", $text, $matches);
                echo $text;
                echo var_dump($matches);

                // if no date is found, set date to today
                if (count($matches) == 0) {
                    saveTOP($group, $title, $content);

                    // send response
                    $this->api->send_message(getMessage("top saved", [$title]));
                } else {
                    // bring date to format yyyy-mm-dd
                    $date = $matches[0];
                    if (preg_match("/\d{1,2}\.\d{1,2}\.\d{4}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.Y", $date);
                    } else if (preg_match("/\d{1,2}\.\d{1,2}\.\d{2}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.y", $date);
                    } else if (preg_match("/\d{1,2}\.\d{1,2}\./", $date)) {
                        $date = DateTime::createFromFormat("d.m.", $date);
                    }

                    saveTOP($group, $title, $content);

                    // send response
                    $this->api->send_message(getMessage("top saved", [$title]));
                    $this->api->send_message(getMessage("event recognized", [$date->format("d.m."), $title . PHP_EOL . "(Siehe TOP)"]));
                }

                // react to message with tick
                $this->api->react("✅");
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
                preg_match("/\d{4}-\d{2}-\d{2}|\d{1,2}\.\d{1,2}\.\d{4}|\d{1,2}\.\d{1,2}\.\d{1,2}|\d{1,2}\.\d{1,2}\./", $content, $matches);

                // if no date is found, set date to today
                if (count($matches) == 0) {
                    $date = new DateTime();
                    $date = $date->format('Y-m-d');
                } else {
                    // bring date to format yyyy-mm-dd
                    $date = $matches[0];
                    if (preg_match("/\d{1,2}\.\d{1,2}\.\d{4}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.Y", $date);
                        $date = $date->format("Y-m-d");
                    } else if (preg_match("/\d{1,2}\.\d{1,2}\.\d{1,2}/", $date)) {
                        $date = DateTime::createFromFormat("d.m.y", $date);
                        $date = $date->format("Y-m-d");
                    } else if (preg_match("/\d{1,2}\.\d{1,2}\./", $date)) {
                        $date = DateTime::createFromFormat("d.m.", $date);
                        $date = $date->format("Y-m-d");
                    }
                }

                saveEvent($group, $title, $content, $date);

                // send response
                $this->api->send_message(getMessage("event saved", [$title]));

                // react to message with tick
                $this->api->react("✅");
            }
            // /del or #del (not regarding capitalization)
            else if (strpos(strtolower($text), "#del") === 0 || strpos(strtolower($text), "/del") === 0) {
                // get rest of message
                // set title to first line
                $lines = explode(PHP_EOL, $text);
                // first line without first 4 characters
                $title = substr($lines[0], 5);

                $found_top = false;

                // delete top
                if (deleteTOP($group, $title)) {
                    // send response
                    $this->api->send_message(getMessage("top deleted", [$title]));
                    $found_top = true;
                }
                // delete event
                if (deleteEvent($group, $title)) {
                    // send response
                    $this->api->send_message(getMessage("event deleted", [$title]));
                    $found_top = true;
                }

                if (!$found_top) {
                    $buttons = array();

                    foreach (getTopsAndEvents($group) as $top) {
                        array_push($buttons, array(["text" => $top, "callback_data" => "do:/del " . $top]));
                    }

                    array_push($buttons, array(["text" => "Abbrechen", "callback_data" => "none"]));

                    // react to message with tick
                    $this->api->send_message(getMessage("find top or event", [$title, $buttons]));
                }
            }
            // Leave Group
            else if (strpos(strtolower($text), "/leave") === 0) {
                $rest = array();
                if (strlen($text) > 7) {
                    $rest = explode(" ", substr($text, 7));
                }

                $confirm = "";
                if (count($rest) > 1) {
                    $group = $rest[0];
                    $confirm = $rest[1];
                } else if (count($rest) > 0) {
                    $group = $rest[0];
                }


                if ($confirm === "confirm") {
                    // remove chat_id from group members of group in chats.jsons groups array
                    foreach ($chats['groups'] as &$g) {
                        if ($g['name'] == $group) {
                            $g['members'] = array_values(array_diff($g['members'], array($this->api->get_uid())));
                        }
                    }
                    file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));
                    $this->api->send_message(getMessage("left group", [$group]));
                    return;
                }

                // if group name in groups
                if (in_array($group, $groups)) {
                    // send response
                    $this->api->send_message(getMessage("leave group", [$group]));
                } else {
                    // send response
                    $this->api->send_message(getMessage("not in group", [$group]));
                }
            } else {
                // send response
                $this->api->send_message(getMessage("command not found"));
            }
        }
    }

    private function log_message($text, $username)
    {
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
    }
}
function getMessage($id, $args = [])
{
    $response = new BotMessage();

    switch ($id) {
        case "start":
            $response->deleteAnswer = DeleteAnswerOptions::NO;
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
            $response->deleteAnswer = DeleteAnswerOptions::NO;
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
            $response->deleteAnswer = DeleteAnswerOptions::NO;
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
                . PHP_EOL . "Schickt dir die TO als .md Datei"
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
            $response->text = "Hier ist die TO";
            break;
        case "file already exists":
            $response->text = "Die TO existiert bereits. Willst du sie überschreiben?";
            $response->buttons = [
                array(
                    ["text" => "Ja", "callback_data" => "do:/upto force"],
                    ["text" => "Nein", "callback_data" => "none"]
                )
            ];
            break;
        case "upload to":
            $response->text = "Die TO wurde erfolgreich hochgeladen.";
            $response->deleteAnswer = DeleteAnswerOptions::AT_MIDNIGHT;
            $response->buttons = [
                array(["text" => "In Cloud öffnen", "url" => $args[0]])
            ];
            break;
        case "upload to force":
            $response->text = "Die TO wurde erfolgreich überschrieben.";
            $response->deleteAnswer = DeleteAnswerOptions::AT_MIDNIGHT;
            $response->buttons = [
                array(["text" => "In Cloud öffnen", "url" => $args[0]])
            ];
            break;
        case "upload to group":
            $response->text = "Hier ist die TO für heute";
            $response->deleteAnswer = DeleteAnswerOptions::AT_MIDNIGHT;
            $response->buttons = [
                array(["text" => "In Cloud öffnen", "url" => $args[0]])
            ];
            break;
        case "see to":
            $response->deleteAnswer = DeleteAnswerOptions::AT_MIDNIGHT;
            $response->text = "Hier ist der Link zur TO";
            $buttons = [
                array(["text" => "TO Anschauen", "url" => $args[0]])
            ];
            if (count($args) > 1) {
                array_push($buttons, array(["text" => "In Cloud öffnen", "url" => $args[1]]));
            }
            $response->buttons = $buttons;
            break;
        case "top saved":
            $response->deleteCommand = false;
            $response->text = "TOP \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event saved":
            $response->deleteCommand = false;
            $response->text = "Termin \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event recognized":
            $response->deleteCommand = false;
            $response->text = "Termin am " . $args[0] . " erkannt. Hinzufügen?";
            $response->buttons = [
                array(
                    ["text" => "Ja", "callback_data" => "do:/termin " . $args[1]],
                    ["text" => "Nein", "callback_data" => "say:event recognized/no"]
                )
            ];
            break;
        case "event recognized/no":
            $response->text = "Termin wurde nicht hinzugefügt.";
            break;
        case "top deleted":
            $response->text = "TOP \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "event deleted":
            $response->text = "Termin \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "find top or event":
            if ($args[0] != "") {
                $response->text = "Ich habe keinen TOP oder Termin mit dem Titel \"" . $args[0] . "\" gefunden.";
            }

            if (count($args) > 1) {
                $response->text = join(PHP_EOL, [$response->text, "Hier ist eine Liste aller TOPs und Termine. Klick auf einen um ihn zu löschen."]);
                $response->buttons = $args[1];
            } else {
                $response->text = "Es gibt keinen TOP oder Termin auf eurer aktullen TO";
            }
            break;
        case "init":
            $response->text = "Ortsgruppe " . $args[0] . " wurde erfolgreich hinzugefügt.";
            break;
        case "plenum changed":
            $response->text = "Plenumstag für " . $args[0] . " wurde auf " . $args[1] . " geändert.";
            break;
        case "folder changed":
            $response->text = "Speicherort für " . $args[0] . " wurde auf \"" . $args[1] . "\" geändert.";
            break;
        case "joined group":
            $response->text = "Du bist der Ortsgruppe " . $args[0] . " beigetreten.";
            break;
        case "leave group":
            $response->text = "Willst du die Ortsgruppe " . $args[0] . " wirklich verlassen?";
            $response->buttons = [
                array(
                    ["text" => "Ja", "callback_data" => "do:/leave " . $args[0] . " confirm"],
                    ["text" => "Nein", "callback_data" => "none"]
                )
            ];
            break;
        case "left group":
            $response->text = "Du hast die Ortsgruppe " . $args[0] . " verlassen.";
            break;
        case "group not found":
            $response->text = "Die Ortsgruppe " . $args[0] . " wurde nicht gefunden.";
            break;
        case "not in group":
            $response->text = "Du bist nicht in der Ortsgruppe " . $args[0] . ".";
            break;
        case "already in group":
            $response->text = "Du bist bereits in der Ortsgruppe " . $args[0] . ".";
            break;
        case "has to be weekday":
            $response->text = "Der Tag muss ein Wochentag sein.";
            $response->buttons = array(
                array(
                    ["text" => "Montag", "callback_data" => "do:/plenum monday"],
                    ["text" => "Dienstag", "callback_data" => "do:/plenum tuesday"],
                ),
                array(
                    ["text" => "Mittwoch", "callback_data" => "do:/plenum wednesday"],
                    ["text" => "Donnerstag", "callback_data" => "do:/plenum thursday"],
                ),
                array(
                    ["text" => "Freitag", "callback_data" => "do:/plenum friday"],
                    ["text" => "Samstag", "callback_data" => "do:/plenum saturday"],
                ),
                array(
                    ["text" => "Sonntag", "callback_data" => "do:/plenum sunday"]
                )
            );
            break;
        case "not correct init":
            $response->deleteCommand = false;
            $response->text = "Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
        case "not correct init private":
            $response->deleteCommand = false;
            $response->text = "Bitte benutze den Befehl /init <Ortsgruppe> <Passwort> um einer Ortsgruppe beizutreten.";
            break;
        case "not correct init characters":
            $response->deleteCommand = false;
            $response->text = "Der Ortsgruppenname darf nur Buchstaben und Zahlen enthalten.";
            break;
        case "password changed":
            $response->text = "Passwort für Ortsgruppe " . $args[0] . " wurde geändert.";
            break;
        case "wrong password":
            $response->text = "Das Passwort ist falsch.";
            break;
        case "password too short":
            $response->text = "Das Passwort muss mindestens 4 Zeichen lang sein.";
            break;
        case "not initialized":
            $response->text = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
            break;
        case "command not found":
            $response->deleteCommand = false;
            $response->text = "Dieser Befehl wurde nicht gefunden. Gib /help ein um eine Liste aller Befehle zu erhalten.";
            break;
        default:
            $response->deleteCommand = false;
            $response->text = "Fehler: Nachricht \"" . $id . "\" nicht gefunden.";
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

function saveTOP($og, $title, $content)
{
    if ($og == "debug") {
        return;
    }

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
    if ($og == "debug") {
        return;
    }

    // enter TOP into TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);
    // generate unique id
    $id = uniqid();
    // add event to events array
    array_push($events['events'], array("id" => $id, "title" => $title, "content" => $content, "date" => $date));
    file_put_contents("../TOs/" . $og . "/events.json", json_encode($events, JSON_PRETTY_PRINT));
}

function getTopsAndEvents($og)
{
    if ($og == "debug") {
        return;
    }

    // load TOs/Ortsgruppe/Plenum_to.json
    $to = json_decode(file_get_contents("../TOs/" . $og . "/Plenum_to.json"), true);
    // load TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);

    $names = array();

    // add tops to buttons array
    foreach ($to['tops'] as $top) {
        array_push($names, $top['title']);

        echo $top['title'];
    }

    // add events to buttons array
    foreach ($events['events'] as $event) {
        // check if the event is within the next or last 7 days
        $date = DateTime::createFromFormat("Y-m-d", $event['date']);
        $now = new DateTime();

        $diff = $date->diff($now)->format("%a");
        if ($diff > 7 || $diff < -7) {
            continue;
        }

        array_push($names, $event['title']);
    }

    return $names;
}

function deleteTOP($og, $title)
{
    if ($og == "debug") {
        return;
    }

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
    if ($og == "debug") {
        return;
    }

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