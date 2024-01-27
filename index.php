<?php
session_start();
require_once 'sds-to-functions.php';

// load json file from directory in url
$addto = false;
$serverPath;

if (!isset($_GET['dir'])) {
    $serverPath = 'fallback';
} else {
    // sanitize input
    $serverPath = preg_replace('/[^a-zA-Z0-9äüöß\/_-]/', '', $_GET['dir']);

    // check if directory is valid (exactly one folder deep) and at least one character long (before and after /)
    if (preg_match('/^[a-zA-Z0-9äüöß_-]{1,}\/[a-zA-Z0-9äüöß_-]{1,}$/', $serverPath) == 0) {
        $serverPath = 'fallback';
    }
}

$json;
// try getting json file
if (file_exists('TOs/' . $serverPath . '_to.json')) {
    $json = file_get_contents("TOs/" . $serverPath . "_to.json");
} else {
    $addto = true;
    $json = file_get_contents("TOs/fallback_to.json");
}

// decode json to array
$json_data = json_decode($json, true);
$title = $json_data['title'];
$date = $json_data['date'];
$tops;
$topsP = array();
$topsE = array();
$signedin = false;
$signedin_somewhere = false;

if ($serverPath != "fallback") {
    // try getting json file (permanent)
    $jsonP;
    $folder = explode('/', $serverPath)[0];
    if (file_exists("TOs/" . $folder . "/permanent.json")) {
        $jsonP = file_get_contents("TOs/" . $folder . "/permanent.json");
    } else {
        // create new json file
        $jsonP = array(
            'tops' => array()
        );
        file_put_contents("TOs/" . $folder . "/permanent.json", json_encode($jsonP));
    }

    $json_dataP = json_decode($jsonP, true);

    // try getting json file (events)
    $jsonE;
    if (file_exists("TOs/" . $folder . "/events.json")) {
        $jsonE = file_get_contents("TOs/" . $folder . "/events.json");
    } else {
        // create new json file
        $jsonE = array(
            'events' => array()
        );
        file_put_contents("TOs/" . $folder . "/events.json", json_encode($jsonE));
    }

    $json_dataE = json_decode($jsonE, true);

    $tops = array();
    if ($json_data['tops'] != null) {
        $tops = $json_data['tops'];
    }

    $topsP = array();
    if ($json_dataP['tops'] != null) {
        $topsP = $json_dataP['tops'];
    }

    $events = array();
    if ($json_dataE['events'] != null) {
        $events = $json_dataE['events'];
    }

    // sort events by date
    usort($events, function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });


    // check if logged in
    if (isset($_SESSION['signedin'])) {
        if ($_SESSION['signedin'] == $folder) {
            $signedin = true;
        } else {
            $signedin = false;
        }
        $signedin_somewhere = true;
    }

    // check if token is set
    if (isset($_GET['token'])) {
        // check if token is valid
        // load Bot/tokens.json
        $json = file_get_contents("Bot/tokens.json");
        $tokens = json_decode($json, true);
        // check if folder is in tokens.json as "group"
        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i]["group"] == $folder) {
                // check if token is in tokens.json
                if (in_array($_GET['token'], $tokens[$i]["tokens"])) {
                    // set session variable
                    $_SESSION['signedin'] = $folder;
                    $signedin = true;
                    $signedin_somewhere = true;
                }
            }
        }
    }
} else {
    $tops = $json_data['tops'];
}

// get "view" from session (if not set, set to "default")
$view = "default";
if (isset($_SESSION['view']) && $_SESSION['view'] != "" && file_exists('Views/' . $_SESSION['view'] . '.php')) {
    $view = $_SESSION['view'];
} else {
    $_SESSION['view'] = "default";
}

// get "view" from url (if set)
if (isset($_GET['view'])) {
    // sanitize input
    $view = preg_replace('/[^a-zA-Z0-9äüöß_-]/', '', $_GET['view']);
    // check if view is valid
    if (file_exists('Views/' . $view . '.php')) {
        $_SESSION['view'] = $view;
    } else {
        $view = "default";
    }
}

function linkEventContent($event, array $tops)
{
    // check if "content" is "(Siehe TOP)" or "siehe TOP" or "s. TOP" ("(" opional, has to contain "s" and "TOP")
    if (preg_match('/^\(?s(\.|iehe) TOP\)?$/i', $event['content']) == 1) {
        $topId = getTopId($event['title'], $tops);

        if ($topId == "") {
            return $event['content'];
        } else {
            $topTitle = getTopTitle($topId, $tops);
            $eventContent = "[" . $topTitle . "](#" . $topId . ")";
        }
    } else {
        $eventContent = $event['content'];
    }

    return $eventContent;
}

function formatMD($text)
{
    $out = $text;

    // dates within links ([... 12.12. ...](xy) -> [... 12p12p ...](xy))
    preg_match('/\[.*\d{1,2}\.\d{1,2}\.(\d{2,4})?.*\]\(.*\)/', $out, $matches);
    foreach ($matches as $match) {
        // replace only the dots in the date
        $replace_with = preg_replace('/(\d{1,2})\.(\d{1,2})\.(\d{2,4})?/', '$1p$2p$3', $match);
        $out = str_replace($match, $replace_with, $out);
    }

    // recognize dates (M.D. or M.D.Y) and link .ics files (do not break the line)
    $out = preg_replace('/((&quot;[a-zA-Z0-9äüöß\- ]*&quot; )|([a-zA-Z0-9äüöß\-]* ))?(am )?(\d{1,2}\.\d{1,2}\.(\d{2,4})?)( )?(um )?(\d{1,2}(:\d{1,2}|( )?Uhr))?/', '[$2$3$4$5$6$7$8$9](Actions/ics.php?date=$5&time=$9&title=$2$3)', $out);


    // change p back to .
    preg_match('/\[.*\d{1,2}p\d{1,2}p(\d{2,4})?.*\]\(.*\)/', $out, $matches);
    foreach ($matches as $match) {
        $replace_with = preg_replace('/(\d{1,2})p(\d{1,2})p(\d{2,4})?/', '$1.$2.$3', $match);
        $out = str_replace($match, $replace_with, $out);
    }


    // use parsedown
    require_once 'Plugins/Parsedown.php';
    $Parsedown = new Parsedown();
    $Parsedown->setBreaksEnabled(true);
    $out = $Parsedown->text($out);

    // replace -> with arrow
    $out = str_replace('-&gt;', '→', $out);
    // replace <- with arrow
    $out = str_replace('&lt;-', '←', $out);
    // replace => with arrow
    $out = str_replace('=&gt;', '⇒', $out);
    // replace <= with arrow
    $out = str_replace('&lt;=', '⇐', $out);

    // replace [book-list:single:<type>|<title>] with book list iframe
    $out = preg_replace('/\[book-list:single:([a-zA-Z0-9äüöß\-\'\’\´: ]*)\|([a-zA-Z0-9äüöß\-\'\’\´: ]*)\]/', '<iframe src="https://www.politischdekoriert.de/book-list/actions/single-view.php?type=$1&title=$2" width="120px" height="120px" frameborder="0" scrolling="no" allowtransparency="true"></iframe>', $out);

    // replace [book-list:...] with book list iframe
    $out = preg_replace('/\[book-list:([a-zA-Z0-9äüöß\-\'\’\´: ]*)\]/', '<iframe src="https://www.politischdekoriert.de/book-list/actions/to-view.php?dir=$1" width="100%x" height="120px" frameborder="0" scrolling="horizontal" allowtransparency="true"></iframe>', $out);

    return $out;
}

function format_date($date)
{
    // datum formatieren nach dd.mm.yyyy
    $datec = date_create($date);
    $day = date_format($datec, 'l');
    // auf deutsch uübersetzen
    switch ($day) {
        case 'Monday':
            $day = 'Montag';
            break;
        case 'Tuesday':
            $day = 'Dienstag';
            break;
        case 'Wednesday':
            $day = 'Mittwoch';
            break;
        case 'Thursday':
            $day = 'Donnerstag';
            break;
        case 'Friday':
            $day = 'Freitag';
            break;
        case 'Saturday':
            $day = 'Samstag';
            break;
        case 'Sunday':
            $day = 'Sonntag';
            break;
    }
    return $day . ', den ' . date_format($datec, 'd.m.Y');
}

function generateButtons($event, $signedin, $top, $permanent = false)
{
    if ($signedin) {
        echo '<div class="buttondrawer editbuttons">';
        if ($top) {
            echo '<a class="editbutton" topid="' . $event['id'] . '" topcontent="' . $event['content'] . '" toptitle="' . $event['title'] . '" toppermanent="' . ($permanent ? 'true' : 'false') . '">';
            echo '<span class="material-symbols-outlined">edit</span>';
            echo '</a>';
            echo '<a href="Actions/deletetop.php?id=' . $event['id'] . '&dir=' . $_GET['dir'] . '&permanent=' . ($permanent ? 'true' : 'false') . '">';
            echo '<span class="material-symbols-outlined">delete</span>';
            echo '</a>';
        } else {
            echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
            echo '<span class="material-symbols-outlined">edit</span>';
            echo '</a>';
            echo '<a href="Actions/deleteevent.php?id=' . $event['id'] . '&dir=' . $_GET['dir'] . '">';
            echo '<span class="material-symbols-outlined">delete</span>';
            echo '</a>';
        }
        echo '</div>';
    }
}

// load the .php file for the view
require_once 'Views/' . $view . '.php';