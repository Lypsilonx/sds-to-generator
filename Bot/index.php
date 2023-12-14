<?php
// load token from token.txt
$token = file_get_contents("token.txt");
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Check if callback is set
if (isset($update['callback_query'])) {
    $chat_id = $update['callback_query']['from']['id'];
    $callback_message = $update['callback_query']['data'];

    getMessage($callback_message)->send($token, $chat_id);
    return;
}

if (!isset($update['message'])) {
    return;
}

$message = $update['message'];
$text = $message['text'];
$chat_id = $message['chat']['id'];

if (!isset($message['text'])) {
    return;
}

// https://api.telegram.org/botXXXX/setWebhook?url=www.politischdekoriert.de/sds-to-generator/Bot/index.php&drop_pending_updates=true

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

// user is in a group
$ingroup = $chat_id < 0;
// Start the bot
if (strpos($text, "/start") === 0) {
    if ($ingroup) {
        getMessage("start group", deleteCmd: $message_id)->send($token, $chat_id);
    } else {
        getMessage("start", deleteCmd: $message_id)->send($token, $chat_id);
    }
}
// Initialize a group
else if (strpos($text, "/init") === 0) {
    // load chats.json
    $chats = json_decode(file_get_contents("chats.json"), true);

    $rest = explode(" ", $text);

    // check if rest of message is valid
    if (count($rest) < 3) {
        getMessage("not correct init", deleteCmd: $message_id)->send($token, $chat_id);
        return;
    }

    // get name
    $name = $rest[1];

    // if folder for Ortsgruppe does not exist
    if (!file_exists("../TOs/" . $name)) {

        // check if name is valid
        if (preg_match("/[^a-zA-Z0-9äüöß]/", $name)) {
            getMessage("not correct init characters", deleteCmd: $message_id)->send($token, $chat_id);
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
        getMessage("init", [$name], deleteCmd: $message_id)->send($token, $chat_id);
    } else {

        // get password
        $password = $rest[2];

        // enter chat id into group members
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $name) {
                // check if user is already in group
                if (in_array($chat_id, $g['members'])) {
                    getMessage("already in group", [$name], deleteCmd: $message_id)->send($token, $chat_id);
                    return;
                }

                // check if message is 3 words long
                if (count($rest) > 3) {
                    getMessage("not correct init private", deleteCmd: $message_id)->send($token, $chat_id);
                    return;
                }

                // check if password is correct
                if (hash("sha256", $password) != $g['password']) {
                    getMessage("wrong password", deleteCmd: $message_id)->send($token, $chat_id);
                    return;
                }
                array_push($g['members'], $chat_id);

                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                getMessage("joined group", [$name], deleteCmd: $message_id)->send($token, $chat_id);
                return;
            }
        }

        // send response
        getMessage("group not found", [$name], deleteCmd: $message_id)->send($token, $chat_id);
    }
}
// Help
else if (strpos(strtolower($text), "/help") === 0) {
    getMessage("help", deleteCmd: $message_id)->send($token, $chat_id);
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
            getMessage("not initialized", deleteCmd: $message_id)->send($token, $chat_id);
            return;
        } else {
            getMessage("not initialized")->send($token, $chat_id);
            return;
        }
    }

    // Get TO
    if (strpos(strtolower($text), "/getto") === 0) {
        getMessage("get to", [$domain . "Actions/downloadto.php?dir=" . $group . "&chatid=" . $chat_id], deleteCmd: $message_id)->send($token, $chat_id);
    }
    // Upload TO
    else if (strpos(strtolower($text), "/upto") === 0) {
        $mtoken = createToken($group);

        getMessage("upload to", [$domain . "Actions/uploadto.php?dir=" . $group . "&token=" . $mtoken], deleteCmd: $message_id)->send($token, $chat_id);
    }
    // Look at TO
    else if (strpos(strtolower($text), "/seeto") === 0) {
        $mtoken = createToken($group);

        getMessage("see to", [$domain . "index.php?dir=" . $group . "/Plenum&token=" . $mtoken], deleteCmd: $message_id)->send($token, $chat_id);
    }
    // Change Password
    else if (strpos(strtolower($text), "/changepw") === 0) {
        // get rest of message
        $password = substr($text, 10);

        if (strlen($password) < 4) {
            getMessage("password too short", deleteCmd: $message_id)->send($token, $chat_id);
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
        getMessage("password changed", [$group], deleteCmd: $message_id)->send($token, $chat_id);
    }
    // Change Weekday
    else if (strpos(strtolower($text), "/plenum") === 0) {
        // get rest of message (lowercase)
        $weekday = weekdayDE(strtolower(substr($text, 8)));

        $weekdays = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

        if (!in_array($weekday, $weekdays)) {
            getMessage("has to be weekday", deleteCmd: $message_id)->send($token, $chat_id);
            return;
        }

        // set new weekday
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $group) {
                $g['weekday'] = $weekday;
                getMessage("plenum changed", [$group, weekdayED($weekday)], deleteCmd: $message_id)->send($token, $chat_id);
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
                getMessage("folder changed", [$group, $folder], deleteCmd: $message_id)->send($token, $chat_id);
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
            $message_id = getMessage("top saved", [$title])->send($token, $chat_id);
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
            getMessage("event recognized", [$date->format("d.m.")])->send($token, $chat_id);
            $message_id = getMessage("top saved", [$title])->send($token, $chat_id);
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
        $message_id = getMessage("event saved", [$title])->send($token, $chat_id);

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
            getMessage("top deleted", [$title], deleteCmd: $message_id)->send($token, $chat_id);
        } else {
            getMessage("top not found", [$title], deleteCmd: $message_id)->send($token, $chat_id);
        }

        // delete event
        if (deleteEvent($group, $title)) {
            // send response
            getMessage("event deleted", [$title], deleteCmd: $message_id)->send($token, $chat_id);
        } else {
            getMessage("event not found", [$title], deleteCmd: $message_id)->send($token, $chat_id);
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
                    $g['members'] = array_values(array_diff($g['members'], array($chat_id)));
                }
            }
            file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

            // send response
            getMessage("left group", [$group], deleteCmd: $message_id)->send($token, $chat_id);
        } else {
            // send response
            getMessage("not in group", [$group], deleteCmd: $message_id)->send($token, $chat_id);
        }
    } else {
        // send response
        getMessage("command not found", deleteCmd: $message_id)->send($token, $chat_id);
    }
}

function saveTOP(string $og, string $title, string $content)
{
    // enter TOP into TOs/Ortsgruppe/Plenum_to.json
    $to = json_decode(file_get_contents("../TOs/" . $og . "/Plenum_to.json"), true);
    // generate unique id
    $id = uniqid();
    // add top to tops array
    array_push($to['tops'], array("id" => $id, "title" => $title, "content" => $content));
    file_put_contents("../TOs/" . $og . "/Plenum_to.json", json_encode($to, JSON_PRETTY_PRINT));
}

function saveEvent(string $og, string $title, string $content, string $date)
{
    // enter TOP into TOs/Ortsgruppe/events.json
    $events = json_decode(file_get_contents("../TOs/" . $og . "/events.json"), true);
    // generate unique id
    $id = uniqid();
    // add event to events array
    array_push($events['events'], array("id" => $id, "title" => $title, "content" => $content, "date" => $date));
    file_put_contents("../TOs/" . $og . "/events.json", json_encode($events, JSON_PRETTY_PRINT));
}

function deleteTOP(string $og, string $title)
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

function deleteEvent(string $og, string $title)
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

class Response
{
    function __construct(string $text, string $deleteCmd = null, float $delTime = 5, bool $deleteAnswer = false, bool $deleteAtMidnight = false, $buttons)
    {
        $this->text = $text;
        $this->deleteCmd = $deleteCmd;
        $this->delTime = $delTime;
        $this->deleteAnswer = $deleteAnswer;
        $this->deleteAtMidnight = $deleteAtMidnight;
        $this->buttons = $buttons;
    }

    public string $text;
    public string $deleteCmd;
    public float $delTime;
    public bool $deleteAnswer;
    public bool $deleteAtMidnight;
    public array $buttons;

    function send(string $token, string $chat_id)
    {
        $keyboard = [
            'inline_keyboard' => [
                $this->buttons
            ]
        ];
        $encodedKeyboard = json_encode($keyboard);

        $message = send_bot_api_request($token, "sendMessage", array(
            "chat_id" => $chat_id,
            "text" => $this->text,
            "disable_notification" => true,
            "parse_mode" => "Markdown",
            "reply_markup" => $encodedKeyboard
        )
        );
        $message_id = $message['result']['message_id'];

        // log answer
        logToFile("Answer: " . $this->text);

        // if deleteAtMidnight is true, add to todelete.json
        if ($this->deleteAtMidnight) {
            $url = build_bot_api_link($token, "deleteMessage", array(
                "chat_id" => $chat_id,
                "message_id" => $message_id
            )
            );
            $todelete = json_decode(file_get_contents("todelete.json"), true);
            array_push($todelete, $url);
            file_put_contents("todelete.json", json_encode($todelete, JSON_PRETTY_PRINT));
        }

        // if deleteCmd is not null, delete command message
        if ($this->deleteCmd != null) {
            send_bot_api_request($token, "deleteMessage", array(
                "chat_id" => $chat_id,
                "message_id" => $this->deleteCmd
            )
            );
        }

        // if deleteAnswer is true, delete answer message
        if ($this->deleteAnswer) {
            sleep($this->delTime);
            // ! Find a better way to do this
            send_bot_api_request($token, "deleteMessage", array(
                "chat_id" => $chat_id,
                "message_id" => $message_id
            )
            );
        }

        return $message_id;
    }
}

function getMessage(string $id, array $args = [], $deleteCmd = null)
{
    $response = new Response("");
    $response->deleteCmd = $deleteCmd;

    switch ($id) {
        case "start":
            $response->msg = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und deshalb manchmal etwas buggy."
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
            $response->msg = "Hallo, ich bin der neue SDS Telegram Bot. Ich werde in Zukunft eure TOPs verwalten. Ich bin noch in der Entwicklung und deshalb manchmal etwas buggy."
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
            $response->msg = "Hier ist eine Liste aller Befehle:"
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
            $response->deleteAtMidnight = true;
            $response->msg = "Klicke hier um die TO zu erhalten.";
            $response->buttons = [
                ['text' => 'TO Herunterladen', 'url' => $args[0]]
            ];
            break;
        case "upload to":
            $response->deleteAtMidnight = true;
            $response->msg = "Klicke hier um die TO hochzuladen.";
            $response->buttons = [
                ['text' => 'TO Hochladen', 'url' => $args[0]]
            ];
            break;
        case "see to":
            $response->deleteAtMidnight = true;
            $response->msg = "Hier ist der Link zur TO";
            $response->buttons = [
                ["text" => "TO Anschauen", "url" => $args[0]]
            ];
            break;
        case "top saved":
            $response->deleteAnswer = true;
            $response->msg = "TOP \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event saved":
            $response->deleteAnswer = true;
            $response->msg = "Termin \"" . $args[0] . "\" wurde erfolgreich hinzugefügt.";
            break;
        case "event recognized":
            $response->deleteAnswer = true;
            $response->msg = "Event am " . $args[0] . " erkannt. Event wurde hinzugefügt.";
            break;
        case "top deleted":
            $response->deleteAnswer = true;
            $response->msg = "TOP \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "event deleted":
            $response->deleteAnswer = true;
            $response->msg = "Termin \"" . $args[0] . "\" wurde erfolgreich gelöscht.";
            break;
        case "top not found":
            $response->deleteAnswer = true;
            $response->msg = "TOP \"" . $args[0] . "\" wurde nicht gefunden.";
            break;
        case "event not found":
            $response->deleteAnswer = true;
            $response->msg = "Termin \"" . $args[0] . "\" wurde nicht gefunden.";
            break;
        case "init":
            $response->deleteAnswer = true;
            $response->msg = "Ortsgruppe " . $args[0] . " wurde erfolgreich hinzugefügt.";
            break;
        case "plenum changed":
            $response->deleteAnswer = true;
            $response->msg = "Plenumstag für " . $args[0] . " wurde auf " . $args[1] . " geändert.";
            break;
        case "folder changed":
            $response->deleteAnswer = true;
            $response->msg = "Speicherort für " . $args[0] . " wurde auf \"" . $args[1] . "\" geändert.";
            break;
        case "joined group":
            $response->deleteAnswer = true;
            $response->msg = "Du bist der Ortsgruppe " . $args[0] . " beigetreten.";
            break;
        case "left group":
            $response->deleteAnswer = true;
            $response->msg = "Du hast die Ortsgruppe " . $args[0] . " verlassen.";
            break;
        case "group not found":
            $response->deleteAnswer = true;
            $response->msg = "Die Ortsgruppe " . $args[0] . " wurde nicht gefunden.";
            break;
        case "not in group":
            $response->deleteAnswer = true;
            $response->msg = "Du bist nicht in der Ortsgruppe " . $args[0] . ".";
            break;
        case "already in group":
            $response->deleteAnswer = true;
            $response->msg = "Du bist bereits in der Ortsgruppe " . $args[0] . ".";
            break;
        case "has to be weekday":
            $response->deleteAnswer = true;
            $response->msg = "Der Tag muss ein Wochentag sein. (z.B. Montag, Dienstag, ...)";
            break;
        case "not correct init":
            $response->deleteAnswer = true;
            $response->msg = "Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
        case "not correct init private":
            $response->deleteAnswer = true;
            $response->msg = "Bitte benutze den Befehl /init <Ortsgruppe> <Passwort> um einer Ortsgruppe beizutreten.";
            break;
        case "not correct init characters":
            $response->deleteAnswer = true;
            $response->msg = "Der Ortsgruppenname darf nur Buchstaben und Zahlen enthalten.";
            break;
        case "password changed":
            $response->deleteAnswer = true;
            $response->msg = "Passwort für Ortsgruppe " . $args[0] . " wurde geändert.";
            break;
        case "wrong password":
            $response->deleteAnswer = true;
            $response->msg = "Das Passwort ist falsch.";
            break;
        case "password too short":
            $response->deleteAnswer = true;
            $response->msg = "Das Passwort muss mindestens 4 Zeichen lang sein.";
            break;
        case "not initialized":
            $response->deleteAnswer = true;
            $response->msg = "Diese Ortsgruppe ist noch nicht initialisiert. Bitte benutze den Befehl /init <Ortsgruppe> <Wochentag> <Passwort> um eine neue Ortsgruppe hinzuzufügen.";
            break;
        case "command not found":
            $response->deleteAnswer = true;
            $response->msg = "Dieser Befehl wurde nicht gefunden. Gib /help ein um eine Liste aller Befehle zu erhalten.";
            break;
        default:
            $response->msg = "Fehler: Nachricht nicht gefunden.";
            break;
    }

    return $response;
}

function react(string $token, string $chat_id, string $message_id, string $reaction)
{
    // react to message with $reaction
}

function leave_group(string $token, string $chat_id)
{
    send_bot_api_request($token, "leaveChat", array("chat_id" => $chat_id));
}

function send_bot_api_request(string $token, string $method, array $params = [])
{
    $unencoded = $params;
    foreach ($unencoded as $key => $value) {
        $params[$key] = urlencode($value);
    }

    return json_decode(file_get_contents(build_bot_api_link($token, $method, $params)), true);
}

function build_bot_api_link(string $token, string $method, array $params = [])
{
    $url = "https://api.telegram.org/bot" . $token . "/" . $method . "?";
    foreach ($params as $key => $value) {
        $url .= $key . "=" . $value . "&";
    }
    $url = substr($url, 0, -1);
    return $url;
}

function createToken(string $group)
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

function weekdayED(string $day)
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

function weekdayDE(string $day)
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

function logToFile(string $message)
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