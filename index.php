<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SDS TO Generator</title>
    <link rel="icon" type="image/x-icon" href="../data/favicon.ico">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="sds-to-style.css">
</head>

<body>
    <script src=//cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js></script>
    <?php
    // load json file from directory in url
    $addto = false;
    $dir;
    if (!isset($_GET['dir'])) {
        $dir = 'fallback';
    } else {
        // sanitize input
        $dir = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $_GET['dir']);

        // check if directory is valid (exactly one folder deep) and at least one character long (before and after /)
        if (preg_match('/^[a-zA-Z0-9_-]{1,}\/[a-zA-Z0-9_-]{1,}$/', $dir) == 0) {
            $dir = 'fallback';
        }
    }

    $json;
    // try getting json file
    if (file_exists('TOs/' . $dir . '_to.json')) {
        $json = file_get_contents("TOs/" . $dir . "_to.json");
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

    if ($dir != "fallback") {
        // try getting json file (permanent)
        $jsonP;
        $folder = explode('/', $dir)[0];
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

        $tops = $json_data['tops'];
        $topsP = $json_dataP['tops'];
        $events = $json_dataE['events'];

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
    ?>
    <header>
        <h1>SDS TO Generator</h1>
        <div id="headerbuttons" action="Actions/signin.php" method="post">
            <?php
            if (!$signedin) {
                echo '<form action="Actions/signin.php" method="post">';
                echo '<input type="hidden" name="dir" value="' . $dir . '">';
                echo '<input type="password" name="password" placeholder="Passwort" id="passwordfield" title="Das Passwort, dass deine Gruppe festgelegt hat." required>';
                echo '<a type="submit" class="unlockbutton" onclick="this.parentNode.submit();"><i class="material-icons"></i></a>';
                echo '</form>';
            }
            if ($signedin_somewhere) {
                echo '<form action="Actions/signout.php" method="post">';
                echo '<input type="hidden" name="dir" value="' . $dir . '">';
                echo '<a type="submit" class="lockbutton" onclick="this.parentNode.submit();"><i class="material-icons"></i></a>';
                echo '</form>';
            }
            ?>
            <input type="text" name="dir" placeholder="Directory" value="<?php
            if ($dir != "fallback") {
                echo $dir;
            } ?>" id="searchfield">
            <a class="searchbutton">
                <i class="material-icons">search</i>
            </a>
            <?php
            if ($signedin) {
                echo '<a class="editbutton" toptitle="<?php echo $title; ?>" topdate="<?php echo $date; ?>">';
                echo '<i class="material-icons">edit</i>';
                echo '</a>';
            }
            ?>
        </div>
    </header>
    <a id="menubutton">
        <i class="material-icons">menu</i>
    </a>
    <div id="rbody">
        <div id="sidebar">
            <h2>
                <?php
                // prevent script injection
                $title = preg_replace('/[^a-zA-Z0-9äüöß ]/', '', $title);
                echo $title;
                ?>
            </h2>
            <ul>
                <div class="placeholder"></div>
                <?php
                if ($dir != "fallback" && $addto == false) {
                    echo '<li><a href="#wrb">Wochenrückblick</a></li>';
                    echo '<li><a href="#wfs">Wochenvorschau</a></li>';
                    echo '<hr>';

                    $i = 1;
                    foreach ($tops as $top) {
                        // prevent script injection
                        $top['title'] = preg_replace('/[^a-zA-Z0-9äüöß ]/', '', $top['title']);
                        echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                        $i++;
                    }

                    if ($topsP != null) {
                        echo '<hr>';
                        echo '<li><a href="#permanent">Laufende Arbeitsaufträge</a></li>';
                    }

                    foreach ($topsP as $top) {
                        // prevent script injection
                        $top['title'] = preg_replace('/[^a-zA-Z0-9äüöß ]/', '', $top['title']);
                        echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                        $i++;
                    }
                }
                ?>
                <div class="placeholder"></div>
                <div id=sidebarbottomgradient>
                    <?php
                    if ($dir != "fallback" && $addto == false) {
                        echo '<a class="downloadb button">';
                        echo '<i class="material-icons">file_download</i>';
                        echo '</a>';

                        echo '<a class="shareb button">';
                        echo '<i class="material-icons">share</i>';
                        echo '</a>';
                    }
                    ?>
                </div>
            </ul>
        </div>
        <div id="main">
            <div id="titleholder">
                <h2>
                    <?php echo $title; ?>
                </h2>
                <?php
                if ($dir != "fallback" && $addto == false) {
                    echo '<a href="Actions/ics.php?date=' . $date . '&time=18Uhr&title=' . $title . '">';
                    echo "<h3>";
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
                    echo $day . ', den ' . date_format($datec, 'd.m.Y');
                    echo "</h3>";
                    echo '</a>';
                }
                ?>
                </h3>
            </div>
            <?php

            if ($dir != "fallback" && $addto == false) {
                echo '<div class="catrow" id="wrb">';
                echo '<hr>';
                echo '<h3>Wochenrückblick</h3>';

                foreach ($events as $event) {
                    // if event was within the last 7 days of $date
                    if (strtotime($event['date']) >= strtotime('-7 days', strtotime($date)) && strtotime($event['date']) <= strtotime($date)) {
                        echo '<div class="toprow">';
                        echo '<div class="top">';
                        // prevent script injection
                        $event['title'] = str_replace('"', '&quot;', $event['title']);
                        $event['content'] = str_replace('"', '&quot;', $event['content']);
                        $event['date'] = str_replace('"', '&quot;', $event['date']);
                        echo '<h4>' . $event['title'] . '</h4>';
                        echo '<a href="Actions/ics.php?date=' . $event['date'] . '&time=&title=' . $event['title'] . '">';
                        echo '<h5>' . $event['date'] . '</h5>';
                        echo '</a>';
                        echo '<p>' . formatMD($event['content']) . '</p>';
                        echo '</div>';
                        if ($signedin) {
                            echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
                            echo '<i class="material-icons">edit</i>';
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                }

                echo '</div>';

                echo '<div class="catrow" id="wfs">';
                echo '<hr>';
                echo '<h3>Wochenvorschau</h3>';

                foreach ($events as $event) {
                    // if event is within the next 7 days of $date
                    if (strtotime($event['date']) >= strtotime($date) && strtotime($event['date']) <= strtotime('+7 days', strtotime($date))) {
                        echo '<div class="toprow">';
                        echo '<div class="top">';
                        // prevent script injection
                        $event['title'] = str_replace('"', '&quot;', $event['title']);
                        $event['content'] = str_replace('"', '&quot;', $event['content']);
                        $event['date'] = str_replace('"', '&quot;', $event['date']);
                        echo '<h4>' . $event['title'] . '</h4>';
                        echo '<h5>' . $event['date'] . '</h5>';
                        echo '<p>' . formatMD($event['content']) . '</p>';
                        echo '</div>';
                        if ($signedin) {
                            echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
                            echo '<i class="material-icons">edit</i>';
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                }

                if ($signedin) {
                    echo '<a class="addeventb button">';
                    echo '<i class="material-icons">add</i>';
                    echo '</a>';
                }
                echo '</div>';

                echo '<div class="catrow" id="wfs">';
                echo '<hr>';
                echo '<h3>TOPS</h3>';
                echo '</div>';

                $i = 1;
                foreach ($tops as $top) {
                    echo '<div class="toprow">';
                    echo '<div class="top">';
                    // prevent script injection
                    $top['title'] = str_replace('"', '&quot;', $top['title']);
                    $top['content'] = str_replace('"', '&quot;', $top['content']);
                    echo '<h4 id="' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</h4>';
                    $i++;
                    echo '<p>' . formatMD($top['content']) . '</p>';
                    echo '</div>';
                    if ($signedin) {
                        echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="false">';
                        echo '<i class="material-icons">edit</i>';
                        echo '</a>';
                    }
                    echo '</div>';
                }

                if ($topsP != null) {
                    echo '<div class="catrow" id="permanent">';
                    echo '<hr>';
                    echo '<h3>Laufende Arbeitsaufträge</h3>';
                    echo '</div>';
                }

                foreach ($topsP as $top) {
                    echo '<div class="toprow">';
                    echo '<div class="top">';
                    // prevent script injection
                    $top['title'] = str_replace('"', '&quot;', $top['title']);
                    $top['content'] = str_replace('"', '&quot;', $top['content']);
                    echo '<h4 id="' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</h4>';
                    $i++;
                    echo '<p>' . formatMD($top['content']) . '</p>';
                    echo '</div>';
                    if ($signedin) {
                        echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="true">';
                        echo '<i class="material-icons">edit</i>';
                        echo '</a>';
                    }
                    echo '</div>';
                }

                if ($signedin) {
                    echo '<a class="addtopb button">';
                    echo '<i class="material-icons">add</i>';
                    echo '</a>';
                }
            }

            echo '<div class="placeholder"></div>';

            echo '<div id="mainbottomgradient">';
            echo '</div>';
            ?>

        </div>
    </div>

    <div class="addtop menu hidden">
        <form action="Actions/addtop.php" method="post" id="addtopform">
            <h2>TOP hinzufügen</h2>
            <input type="hidden" name="dir" value="<?php
            // prevent script injection
            $dir = str_replace('"', '&quot;', $dir);
            echo $dir;
            ?>">
            <input type="hidden" name="id" value="<?php
            // generate random id
            echo uniqid();
            ?>">
            <input type="hidden" name="edit" value="" id="editfield">
            <input type="hidden" name="delete" value="false" id="deletefield">
            <input type="text" name="title" placeholder="Titel" id="titlefield" value="" required>
            <textarea name="content" placeholder="Bechreibung" id="contentfield" required></textarea>
            <div id="atmbuttons">
                <a class="cancelbutton">Cancel</a>
                <a class="deletebutton hidden">Delete</a>
                <div id="pfield">
                    <label for="permanentfield">Laufender Arbeitsauftrag</label>
                    <input type="checkbox" name="permanent" id="permanentfield">
                </div>
                <input type="submit" value="Add" class="submitbutton">
            </div>
        </form>
    </div>

    <div class="addto menu<?php if (!$addto) {
        echo ' hidden';
    } ?>">
        <form action="Actions/addto.php" method="post" id="addtoform">
            <h2>TO hinzufügen</h2>
            <input type="hidden" name="dir" value="<?php
            // prevent script injection
            $dir = str_replace('"', '&quot;', $dir);
            echo $dir;
            ?>">
            <input type="hidden" name="edit" value="" id="editfield">
            <input type="hidden" name="delete" value="false" id="deletefield">
            <input type="text" name="title" placeholder="TO Title" id="titlefield" value="" required>
            <input type="date" name="date" placeholder="TO Date" id="datefield" value="" required>
            <div id="atmbuttons">
                <a class="cancelbutton">Cancel</a>
                <a class="deletebutton hidden">Delete</a>
                <input type="submit" value="Add" class="submitbutton">
            </div>
        </form>
    </div>

    <div class="addevent menu hidden">
        <form action="Actions/addevent.php" method="post" id="addeventform">
            <h2>Event hinzufügen</h2>
            <input type="hidden" name="dir" value="<?php
            // prevent script injection
            $dir = str_replace('"', '&quot;', $dir);
            echo $dir;
            ?>">
            <input type="hidden" name="id" value="<?php
            // generate random id
            echo uniqid();
            ?>">
            <input type="hidden" name="edit" value="" id="editfield">
            <input type="hidden" name="delete" value="false" id="deletefield">
            <input type="text" name="title" placeholder="Event Title" id="titlefield" value="" required>
            <input type="date" name="date" placeholder="Event Date" id="datefield" value="" required>
            <textarea name="content" placeholder="Event Description" id="contentfield" required></textarea>
            <div id="atmbuttons">
                <a class="cancelbutton">Cancel</a>
                <a class="deletebutton hidden">Delete</a>
                <input type="submit" value="Add" class="submitbutton">
            </div>
        </form>
    </div>

    <script src="sds-to-functions.js"></script>
    <script src="sds-to-main.js"></script>

    <?php

    function formatMD($text)
    {
        $out = $text;

        // use parsedown
        require_once 'Plugins/Parsedown.php';
        $Parsedown = new Parsedown();
        $out = $Parsedown->text($out);


        // recognize dates (M.D. or M.D.Y) and link .ics files (do not break the line)
        $out = preg_replace('/((&quot;[a-zA-Z0-9äüöß ]*&quot; )|([a-zA-Z0-9äüöß]* ))?(am )?(\d{1,2}\.\d{1,2}\.(\d{2,4})?)( )?(um )?(\d{1,2}(:\d{1,2}|( )?Uhr))?/', '<a href="Actions/ics.php?date=$5&time=$9&title=$2$3">$2$3$4$5$6$7$8$9</a>', $out);

        // replace -> with arrow
        $out = str_replace('-&gt;', '→', $out);
        // replace <- with arrow
        $out = str_replace('&lt;-', '←', $out);
        // replace => with arrow
        $out = str_replace('=&gt;', '⇒', $out);
        // replace <= with arrow
        $out = str_replace('&lt;=', '⇐', $out);

        return $out;
    }
    ?>
</body>

</html>