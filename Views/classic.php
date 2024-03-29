<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SDS TO Generator</title>
    <link rel="icon" type="image/x-icon" href="../data/favicon.ico">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet" />
    <link rel="stylesheet" href="Styles/old-sds-to-style.css">
</head>

<body>
    <header>
        <h1>SDS TO Generator</h1>
        <div id="headerbuttons" action="Actions/signin.php" method="post">
            <?php
            if (!$signedin) {
                echo '<form action="Actions/signin.php" method="post">';
                echo '<input type="hidden" name="dir" value="' . $serverPath . '">';
                echo '<input type="password" name="password" placeholder="Passwort" id="passwordfield" title="Das Passwort, dass deine Gruppe festgelegt hat." required>';
                echo '<a type="submit" class="unlockbutton" onclick="this.parentNode.submit();"><span class="material-symbols-outlined"></span></a>';
                echo '</form>';
            }
            if ($signedin_somewhere) {
                echo '<form action="Actions/signout.php" method="post">';
                echo '<input type="hidden" name="dir" value="' . $serverPath . '">';
                echo '<a type="submit" class="lockbutton" onclick="this.parentNode.submit();"><span class="material-symbols-outlined"></span></a>';
                echo '</form>';
            }
            ?>
            <div class="autocomplete">
                <input type="text" name="dir" placeholder="Ortsgruppe/Plenum" value="<?php
                if ($serverPath != "fallback") {
                    echo $serverPath;
                } ?>" id="searchfield">
            </div>
            <a class="searchbutton">
                <span class="material-symbols-outlined">search</span>
            </a>
            <?php
            if ($signedin) {
                echo '<a class="editbutton" toptitle="' . $json_data['title'] . '" topdate="' . $json_data['date'] . '">';
                echo '<span class="material-symbols-outlined">edit</span>';
                echo '</a>';
            }
            ?>
        </div>
    </header>
    <div id="rbody">
        <div id="sidebar">
            <a id="menubutton" onmouseover="this.classList.add('hover')" onmouseout="this.classList.remove('hover')">
                <span class="material-symbols-outlined"></span>
            </a>
            <h2>
                <?php
                // prevent script injection
                $title = preg_replace('/[^a-zA-Z0-9äüöß!?.\- ]/', '', $title);

                if ($serverPath != "fallback" && $addto == false) {
                    echo $title;
                } else {
                    echo "Ortsgruppen";
                }
                ?>
            </h2>
            <ul>
                <div class="placeholder"></div>
                <?php
                if ($serverPath != "fallback" && $addto == false) {
                    echo '<li><a href="#wrb">Wochenrückblick</a></li>';
                    echo '<li><a href="#wfs">Wochenvorschau</a></li>';

                    if ($tops != null) {
                        echo '<hr>';
                    }

                    $i = 1;
                    foreach ($tops as $top) {
                        // prevent script injection
                        $top['title'] = preg_replace('/[^a-zA-Z0-9äüöß!?.\- ]/', '', $top['title']);
                        // tab before title
                        echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                        $i++;
                    }

                    if ($topsP != null) {
                        echo '<hr>';
                        echo '<li><a href="#permanent">Laufende Arbeitsaufträge</a></li>';
                    }

                    foreach ($topsP as $top) {
                        // prevent script injection
                        $top['title'] = preg_replace('/[^a-zA-Z0-9äüöß!?.\- ]/', '', $top['title']);
                        echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                        $i++;
                    }
                } else {
                    // list all groups
                    $groups = scandir('TOs');
                    foreach ($groups as $group) {
                        if ($group != '.' && $group != '..' && $group != 'fallback_to.json') {
                            echo '<li><a href="?dir=' . $group . '/Plenum">' . $group . '</a></li>';
                        }
                    }
                }
                ?>
                <div class="placeholder"></div>
                <div id=actionbuttons>
                    <?php
                    if ($serverPath != "fallback" && $addto == false) {
                        echo '<a class="downloadb button" href="Actions/downloadto.php?dir=' . $serverPath . '">';
                        echo '<span class="material-symbols-outlined">file_download</span>';
                        echo '</a>';

                        if ($signedin) {
                            echo '<a class="uploadb button" href="Actions/uploadto.php?dir=' . $serverPath . '">';
                            echo '<span class="material-symbols-outlined">cloud_upload</span>';
                            echo '</a>';
                        }

                        echo '<a class="shareb button">';
                        echo '<span class="material-symbols-outlined">share</span>';
                        echo '</a>';

                        echo '<a class="botb button" href="https://t.me/sds_to_bot">';
                        echo '<span class="material-symbols-outlined">smart_toy</span>';
                        echo '</a>';

                        echo '<a class="styleb button" href="?dir=' . $serverPath . '&view=default">';
                        echo '<span class="material-symbols-outlined">tv_gen</span>';
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
                if ($serverPath != "fallback" && $addto == false) {
                    echo '<a href="Actions/ics.php?date=' . $date . '&time=18Uhr&title=' . $title . '">';
                    echo "<h3>";
                    echo format_date($date);
                    echo "</h3>";
                    echo '</a>';
                }
                ?>
                </h3>
            </div>
            <?php

            if ($serverPath != "fallback" && $addto == false) {
                echo '<div class="catrow" id="wrb">';
                echo '<hr>';
                echo '<h3>Wochenrückblick</h3>';

                foreach ($events as $event) {
                    // if event was within the last 7 days of $date
                    if (strtotime($event['date']) >= strtotime('-7 days', strtotime($date)) && strtotime($event['date']) < strtotime($date)) {
                        echo '<div class="toprow">';
                        echo '<div class="top">';
                        // prevent script injection
                        $event['title'] = str_replace('"', '&quot;', $event['title']);
                        $event['content'] = str_replace('"', '&quot;', $event['content']);
                        $event['date'] = str_replace('"', '&quot;', $event['date']);
                        echo '<h4>' . $event['title'] . '</h4>';
                        echo '<div class="eventdate">';
                        echo '<a href="Actions/ics.php?date=' . $event['date'] . '&time=&title=' . $event['title'] . '">';
                        echo '<h5>' . format_date($event['date']) . '</h5>';
                        echo '</a>';
                        echo '</div>';
                        echo formatMD(preg_match('/^\(?s(\.|iehe) TOP\)?$/i', $event['content']) == 1 ? "" : $event['content']);
                        echo '</div>';
                        if ($signedin) {
                            echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
                            echo '<span class="material-symbols-outlined">edit</span>';
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
                    if (strtotime($event['date']) >= strtotime($date) && strtotime($event['date']) < strtotime('+7 days', strtotime($date))) {
                        echo '<div class="toprow">';
                        echo '<div class="top">';
                        // prevent script injection
                        $event['title'] = str_replace('"', '&quot;', $event['title']);
                        $event['content'] = str_replace('"', '&quot;', $event['content']);
                        $event['date'] = str_replace('"', '&quot;', $event['date']);
                        echo '<h4>' . $event['title'] . '</h4>';
                        echo '<div class="eventdate">';
                        echo '<a href="Actions/ics.php?date=' . $event['date'] . '&time=&title=' . $event['title'] . '">';
                        echo '<h5>' . format_date($event['date']) . '</h5>';
                        echo '</a>';
                        echo '</div>';
                        echo formatMD(linkEventContent($event, $tops));
                        echo '</div>';
                        if ($signedin) {
                            echo '<a class="editbutton event" eventid="' . $event['id'] . '" eventtitle="' . $event['title'] . '" eventcontent="' . $event['content'] . '" eventdate="' . $event['date'] . '">';
                            echo '<span class="material-symbols-outlined">edit</span>';
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                }

                echo '</div>';

                if ($signedin) {
                    echo '<a class="addeventb button">';
                    echo '<span class="material-symbols-outlined">add</span>';
                    echo '</a>';
                }

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
                    echo formatMD($top['content']);
                    echo '</div>';
                    if ($signedin) {
                        echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="false">';
                        echo '<span class="material-symbols-outlined">edit</span>';
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
                    echo formatMD($top['content']);
                    echo '</div>';
                    if ($signedin) {
                        echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="true">';
                        echo '<span class="material-symbols-outlined">edit</span>';
                        echo '</a>';
                    }
                    echo '</div>';
                }

                if ($signedin) {
                    echo '<a class="addtopb button">';
                    echo '<span class="material-symbols-outlined">add</span>';
                    echo '</a>';
                }
            }

            echo '<div class="placeholder"></div>';

            echo '</div>';
            ?>

        </div>
    </div>

    <div class="addtop menu hidden">
        <form action="Actions/addtop.php" method="post" id="addtopform">
            <h2>TOP hinzufügen</h2>
            <input type="hidden" name="dir" value="<?php
            // prevent script injection
            $serverPath = str_replace('"', '&quot;', $serverPath);
            echo $serverPath;
            ?>">
            <input type="hidden" name="id" value="<?php
            // generate random id
            echo uniqid();
            ?>">
            <input type="hidden" name="edit" value="" id="editfield">
            <input type="hidden" name="delete" value="false" id="deletefield">
            <input type="text" name="title" placeholder="Titel" id="titlefield" value="" required>
            <textarea name="content" placeholder="Bechreibung" id="contentfield"></textarea>
            <div class="atmbuttons">
                <a class="cancelbutton">Cancel</a>
                <a class="deletebutton hidden">Delete</a>
                <div id="pfield">
                    Lauf. Arbeitsauftrag
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
            $serverPath = str_replace('"', '&quot;', $serverPath);
            echo $serverPath;
            ?>">
            <input type="hidden" name="edit" value="" id="editfield">
            <input type="hidden" name="delete" value="false" id="deletefield">
            <input type="text" name="title" placeholder="TO Title" id="titlefield" value="" required>
            <input type="date" name="date" placeholder="TO Date" id="datefield" value="" required>
            <div class="atmbuttons">
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
            $serverPath = str_replace('"', '&quot;', $serverPath);
            echo $serverPath;
            ?>">
            <input type="hidden" name="id" value="<?php
            // generate random id
            echo uniqid();
            ?>">
            <input type="hidden" name="edit" value="" id="editfield">
            <input type="hidden" name="delete" value="false" id="deletefield">
            <input type="text" name="title" placeholder="Event Title" id="titlefield" value="" required>
            <input type="date" name="date" placeholder="Event Date" id="datefield" value="" required>
            <textarea name="content" placeholder="Event Description" id="contentfield"></textarea>
            <div class="atmbuttons">
                <a class="cancelbutton">Cancel</a>
                <a class="deletebutton hidden">Delete</a>
                <input type="submit" value="Add" class="submitbutton">
            </div>
        </form>
    </div>

    <script src="sds-to-main.js"></script>

</body>

</html>