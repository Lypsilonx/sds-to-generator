<?php
require_once '../sds-to-functions.php';
require_once "bot-core.php";
require_once "tg-api.php";

// load token from token.txt
$token = file_get_contents("token.txt");

$input = file_get_contents('php://input');
$update = json_decode($input, true);

$callback_do = false;

// Check if callback is set
if (isset($update['callback_query'])) {
    $chat_id = $update['callback_query']['from']['id'];
    $callback_message = $update['callback_query']['data'];

    // delete callback message
    $callback_message_id = $update['callback_query']['message']['message_id'];
    send_bot_api_request(
        $token,
        "deleteMessage",
        array(
            "chat_id" => $chat_id,
            "message_id" => $callback_message_id
        )
    );

    if (strpos($callback_message, "say:") === 0) {
        $callback_message = substr($callback_message, 4);
        getMessage($callback_message)->send($token, $chat_id);
        return;
    } else if (strpos($callback_message, "do:") === 0) {
        $text = substr($callback_message, 3);
        $message_id = null;
        $callback_do = true;
        $username = $update['callback_query']['from']['username'];

        if (!isset($username)) {
            // use id if no username is set
            $username = $update['callback_query']['from']['id'];
        }
    } else {
        return;
    }
}


// https://api.telegram.org/botXXXX/setWebhook?url=www.politischdekoriert.de/sds-to-generator/Bot/index.php&drop_pending_updates=true

$domain = "https://www.politischdekoriert.de/sds-to-generator/";

if (!$callback_do) {
    if (!isset($update['message'])) {
        return;
    }

    $message = $update['message'];

    if (!isset($message['text'])) {
        return;
    }
    $text = $message['text'];
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    $username = $message['from']['username'];

    if (!isset($username)) {
        // use id if no username is set
        $username = $message['from']['id'];
    }
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
        getMessage("start group")->send($token, $chat_id, deleteCmd: $message_id);
    } else {
        getMessage("start")->send($token, $chat_id, deleteCmd: $message_id);
    }
}
// Initialize a group
else if (strpos($text, "/init") === 0) {
    // load chats.json
    $chats = json_decode(file_get_contents("chats.json"), true);

    $rest = explode(" ", $text);

    // check if rest of message is valid
    if (count($rest) < 3) {
        getMessage("not correct init")->send($token, $chat_id, deleteCmd: $message_id);
        return;
    }

    // get name
    $name = $rest[1];

    // if folder for Ortsgruppe does not exist
    if (!file_exists("../TOs/" . $name)) {

        // check if name is valid
        if (preg_match("/[^a-zA-Z0-9äüöß]/", $name)) {
            getMessage("not correct init characters")->send($token, $chat_id, deleteCmd: $message_id);
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
        getMessage("init", [$name])->send($token, $chat_id, deleteCmd: $message_id);
    } else {

        // get password
        $password = $rest[2];

        // enter chat id into group members
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $name) {
                // check if user is already in group
                if (in_array($chat_id, $g['members'])) {
                    getMessage("already in group", [$name])->send($token, $chat_id, deleteCmd: $message_id);
                    return;
                }

                // check if message is 3 words long
                if (count($rest) > 3) {
                    getMessage("not correct init private")->send($token, $chat_id, deleteCmd: $message_id);
                    return;
                }

                // check if password is correct
                if (hash("sha256", $password) != $g['password']) {
                    getMessage("wrong password")->send($token, $chat_id, deleteCmd: $message_id);
                    return;
                }
                array_push($g['members'], $chat_id);

                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));

                getMessage("joined group", [$name])->send($token, $chat_id, deleteCmd: $message_id);
                return;
            }
        }

        // send response
        getMessage("group not found", [$name])->send($token, $chat_id, deleteCmd: $message_id);
    }
}
// Help
else if (strpos(strtolower($text), "/help") === 0) {
    getMessage("help")->send($token, $chat_id, deleteCmd: $message_id);
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
            getMessage("not initialized")->send($token, $chat_id, deleteCmd: $message_id);
            return;
        } else {
            getMessage("not initialized")->send($token, $chat_id);
            return;
        }
    }

    // Get TO
    if (strpos(strtolower($text), "/getto") === 0) {
        $result = renderMarkDown($group . "/Plenum");
        download($result['markdown'], $result['filename'], $chat_id);
        getMessage("get to")->send($token, $chat_id, deleteCmd: $message_id);
    }
    // Upload TO
    else if (strpos(strtolower($text), "/upto") === 0) {
        $mtoken = createToken($group);
        $result = renderMarkDown($group . "/Plenum");
        upload($result['markdown'], $result['filename'], $group . "/Plenum");
        getMessage("upload to")->send($token, $chat_id, deleteCmd: $message_id);
    }
    // Look at TO
    else if (strpos(strtolower($text), "/seeto") === 0) {
        $mtoken = createToken($group);

        getMessage("see to", [$domain . "index.php?dir=" . $group . "/Plenum&token=" . $mtoken])->send($token, $chat_id, deleteCmd: $message_id);
    }
    // Change Password
    else if (strpos(strtolower($text), "/changepw") === 0) {
        // get rest of message
        $password = substr($text, 10);

        if (strlen($password) < 4) {
            getMessage("password too short")->send($token, $chat_id, deleteCmd: $message_id);
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
        getMessage("password changed", [$group])->send($token, $chat_id, deleteCmd: $message_id);
    }
    // Change Weekday
    else if (strpos(strtolower($text), "/plenum") === 0) {
        // get rest of message (lowercase)
        $weekday = weekdayDE(substr($text, 8));

        $weekdays = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

        if (!in_array($weekday, $weekdays)) {
            getMessage("has to be weekday")->send($token, $chat_id, deleteCmd: $message_id);
            return;
        }

        // set new weekday
        foreach ($chats['groups'] as &$g) {
            if ($g['name'] == $group) {
                $g['weekday'] = $weekday;
                getMessage("plenum changed", [$group, weekdayED($weekday)])->send($token, $chat_id, deleteCmd: $message_id);
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
                getMessage("folder changed", [$group, $folder])->send($token, $chat_id, deleteCmd: $message_id);
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

            saveTOP($group, $title, $content);

            // send response
            $message_id = getMessage("top saved", [$title])->send($token, $chat_id);
            getMessage("event recognized", [$date->format("d.m."), $title . PHP_EOL . $content])->send($token, $chat_id);
        }

        // react to message with tick
        tg_react($token, $chat_id, $message_id, "✅");
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
        tg_react($token, $chat_id, $message_id, "✅");
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
            getMessage("top deleted", [$title])->send($token, $chat_id, deleteCmd: $message_id);
        } else {
            getMessage("top not found", [$title])->send($token, $chat_id, deleteCmd: $message_id);
        }

        // delete event
        if (deleteEvent($group, $title)) {
            // send response
            getMessage("event deleted", [$title])->send($token, $chat_id, deleteCmd: $message_id);
        } else {
            getMessage("event not found", [$title])->send($token, $chat_id, deleteCmd: $message_id);
        }
    }
    // Leave Group
    else if (strpos(strtolower($text), "/leave") === 0) {
        if (strlen($text) > 7) {
            $rest = explode(" ", substr($text, 7));
            $group = $rest[0];

            if (count($rest) > 1 && $rest[1] === "confirm") {
                // remove chat_id from group members of group in chats.jsons groups array
                foreach ($chats['groups'] as &$g) {
                    if ($g['name'] == $group) {
                        $g['members'] = array_values(array_diff($g['members'], array($chat_id)));
                    }
                }
                file_put_contents("chats.json", json_encode($chats, JSON_PRETTY_PRINT));
                getMessage("left group", [$group])->send($token, $chat_id, deleteCmd: $message_id);
                return;
            }
        }
        // if group name in groups
        if (in_array($group, $groups)) {
            // send response
            getMessage("leave group", [$group])->send($token, $chat_id, deleteCmd: $message_id);
        } else {
            // send response
            getMessage("not in group", [$group])->send($token, $chat_id, deleteCmd: $message_id);
        }
    } else {
        // send response
        getMessage("command not found")->send($token, $chat_id, deleteCmd: $message_id);
    }
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
    // add event to events array
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
?>