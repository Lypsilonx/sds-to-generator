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
    <?php
    // load json file from directory in url
    $addto = false;
    $dir;
    if (!isset($_GET['dir'])) {
        $dir = 'fallback';
    } else {
        // sanitize input
        $dir = preg_replace('/[^a-zA-Z0-9\/]/', '', $_GET['dir']);

        // check if directory is valid (exactly one folder deep) and at least one character long (before and after /)
        if (preg_match('/^[a-zA-Z0-9]{1,}\/[a-zA-Z0-9]{1,}$/', $dir) == 0) {
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
    } else {
        $tops = $json_data['tops'];
    }
    ?>
    <header>
        <h1>SDS TO Generator</h1>
        <div id="headerbuttons">
            <input type="text" name="dir" placeholder="Directory" value="<?php
            if ($dir != "fallback") {
                echo $dir;
            } ?>" id="searchfield">
            <a class="searchbutton">
                <i class="material-icons">search</i>
            </a>
            <a class="editbutton" toptitle="<?php echo $title; ?>" topdate="<?php echo $date; ?>">
                <i class="material-icons">edit</i>
            </a>
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
                $title = preg_replace('/[^a-zA-Z0-9 ]/', '', $title);
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
                        $top['title'] = preg_replace('/[^a-zA-Z0-9 ]/', '', $top['title']);
                        echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                        $i++;
                    }

                    if ($topsP != null) {
                        echo '<hr>';
                        echo '<li><a href="#permanent">Laufende Arbeitsaufträge</a></li>';
                    }

                    foreach ($topsP as $top) {
                        // prevent script injection
                        $top['title'] = preg_replace('/[^a-zA-Z0-9 ]/', '', $top['title']);
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
                <h3>
                    <?php
                    if ($dir != "fallback" && $addto == false) {
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
                        echo '<h5>' . $event['date'] . '</h5>';
                        echo '<p>' . $event['content'] . '</p>';
                        echo '</div>';
                        echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
                        echo '<i class="material-icons">edit</i>';
                        echo '</a>';
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
                        echo '<p>' . $event['content'] . '</p>';
                        echo '</div>';
                        echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
                        echo '<i class="material-icons">edit</i>';
                        echo '</a>';
                        echo '</div>';
                    }
                }

                echo '<a class="addeventb button">';
                echo '<i class="material-icons">add</i>';
                echo '</a>';

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
                    echo '<p>' . $top['content'] . '</p>';
                    echo '</div>';
                    echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="false">';
                    echo '<i class="material-icons">edit</i>';
                    echo '</a>';
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
                    echo '<p>' . $top['content'] . '</p>';
                    echo '</div>';
                    echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="true">';
                    echo '<i class="material-icons">edit</i>';
                    echo '</a>';
                    echo '</div>';
                }

                echo '<a class="addtopb button">';
                echo '<i class="material-icons">add</i>';
                echo '</a>';
            }

            echo '<div class="placeholder"></div>';

            echo '<div id="mainbottomgradient">';
            echo '</div>';
            ?>

        </div>
    </div>

    <div class="addtop menu hidden">
        <form action="addtop.php" method="post" id="addtopform">
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
        <form action="addto.php" method="post" id="addtoform">
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
        <form action="addevent.php" method="post" id="addeventform">
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
</body>

</html>