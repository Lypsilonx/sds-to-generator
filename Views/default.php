<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SDS TO Generator</title>
    <link rel="icon" type="image/x-icon" href="../data/favicon.ico">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet" />
    <link rel="stylesheet" href="Styles/sds-to-style.css">
    <?php
    $isHex = preg_match('/^[0-9a-fA-F]{6}$/', $color);

    // set color
    echo '<style>';
    echo ':root{' . PHP_EOL;
    if ($isHex) {
        echo '--color-accent: #' . $color . ';' . PHP_EOL;
    } else {
        echo '--color-accent: var(--color-accent-' . $color . ');' . PHP_EOL;
    }
    echo '}';
    echo '</style>';
    ?>
</head>

<body>
    <header>
        <div class="logo">
            <h1>SDS</h1>
            <h3>TO Generator</h3>
        </div>
        <div id="headerbuttons" class="buttondrawer" action="Actions/signin.php" method="post">
            <?php
            if (!$signedin) {
                echo '<form action="Actions/signin.php" method="post">';
                echo '<input type="password" name="password" placeholder="Passwort" id="passwordfield" title="Das Passwort, dass deine Gruppe festgelegt hat." required>';
                echo '<input type="hidden" name="dir" value="' . $serverPath . '">';
                echo '<a type="submit" class="unlockbutton" onclick="this.parentNode.submit();" title="Anmelden"><span class="material-symbols-outlined"></span></a>';
                echo '</form>';
            }
            if ($signedin_somewhere) {
                echo '<form action="Actions/signout.php" method="post">';
                echo '<a type="submit" class="lockbutton" onclick="this.parentNode.submit();" title="Abmelden"><span class="material-symbols-outlined"></span></a>';
                echo '<input type="hidden" name="dir" value="' . $serverPath . '">';
                echo '</form>';
            }
            ?>
            <div class="autocomplete">
                <input type="text" name="dir" placeholder="Ortsgruppe/Plenum" value="<?php
                if ($serverPath != "fallback") {
                    echo $serverPath;
                } ?>" id="searchfield">
            </div>
            <a class="searchbutton" title="Suchen">
                <span class="material-symbols-outlined">search</span>
            </a>
            <?php
            if ($signedin) {
                echo '<a class="editbutton" toptitle="' . $json_data['title'] . '" topdate="' . $json_data['date'] . '" title="TO Bearbeiten">';
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
            <div id="scrollcontainer">
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
                            echo '<li><a href="#' . $top['id'] . '" topnumber="TOP ' . $i . ': ">' . $top['title'] . '</a></li>';
                            $i++;
                        }

                        if ($topsP != null) {
                            echo '<hr>';
                            echo '<li><a href="#permanent">Laufende Arbeitsaufträge</a></li>';
                        }

                        foreach ($topsP as $top) {
                            // prevent script injection
                            $top['title'] = preg_replace('/[^a-zA-Z0-9äüöß!?.\- ]/', '', $top['title']);
                            echo '<li><a href="#' . $top['id'] . '" topnumber="TOP ' . $i . ': ">' . $top['title'] . '</a></li>';
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
                    <div id="actionbuttons" class="buttondrawer">
                        <?php
                        if ($serverPath != "fallback" && $addto == false) {
                            echo '<a class="downloadb button" href="Actions/downloadto.php?dir=' . $serverPath . '" title="TO Heunterladen">';
                            echo '<span class="material-symbols-outlined">file_download</span>';
                            echo '</a>';

                            if ($signedin) {
                                echo '<a class="uploadb button" href="Actions/uploadto.php?dir=' . $serverPath . '" title="TO in die Cloud hochladen">';
                                echo '<span class="material-symbols-outlined">cloud_upload</span>';
                                echo '</a>';
                            }

                            echo '<a class="shareb button" title="TO Teilen">';
                            echo '<span class="material-symbols-outlined">share</span>';
                            echo '</a>';

                            echo '<a class="botb button" href="https://t.me/sds_to_bot" title="TO Bot">';
                            echo '<span class="material-symbols-outlined">smart_toy</span>';
                            echo '</a>';

                            echo '<div class="buttondrawer expandable_down">';

                            // color picker
                            echo '<a class="button">';
                            echo '<input type="color" id="colorpicker" onchange="window.location.href=\'?dir=' . $serverPath . '&color=\' + this.value.substring(1);" value="' . ($isHex ? '#' . $color : '#666666') . '">';
                            echo '<span class="material-symbols-outlined">colorize</span>';
                            echo '</a>';

                            for ($i = 0; $i < $num_colors; $i++) {
                                // on click set parameter in url
                                echo '<a class="button" style="font-variation-settings: \'FILL\' 100;" href="?dir=' . $serverPath . '&color=' . ($i + 1) . '">';
                                echo '<span class="material-symbols-outlined" style="color: var(--color-accent-' . ($i + 1) . ');">circle</span>';
                                echo '</a>';
                            }

                            echo '<a class="colorb button">';
                            echo '<span class="material-symbols-outlined">color_lens</span>';
                            echo '</a>';
                            echo '</div>';

                            echo '<div class="buttondrawer expandable_down">';
                            // classic view
                            echo '<a class="button" href="?dir=' . $serverPath . '&view=classic" title="Klassische Ansicht">';
                            echo '<span class="material-symbols-outlined">radio</span>';
                            echo '</a>';
                            // modern (default) view
                            echo '<a class="button" href="?dir=' . $serverPath . '&view=default" title="Moderne Ansicht">';
                            echo '<span class="material-symbols-outlined">tv_gen</span>';
                            echo '</a>';
                            echo '<a class="styleb button">';
                            echo '<span class="material-symbols-outlined">view_quilt</span>';
                            echo '</a>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </ul>
            </div>
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
                echo '</div>';

                foreach ($events as $event) {
                    // if event was within the last 7 days of $date
                    if (strtotime($event['date']) >= strtotime('-7 days', strtotime($date)) && strtotime($event['date']) < strtotime($date)) {
                        echo '<div class="toprow">';
                        echo '<h4>' . $event['title'] . '</h4>';
                        echo '<div class="eventdate">';
                        echo '<a href="Actions/ics.php?date=' . $event['date'] . '&time=&title=' . $event['title'] . '">';
                        echo '<h5>' . format_date($event['date']) . '</h5>';
                        echo '</a>';
                        echo '</div>';
                        echo '<div class="top">';
                        // prevent script injection
                        $event['title'] = str_replace('"', '&quot;', $event['title']);
                        $event['content'] = str_replace('"', '&quot;', $event['content']);
                        $event['date'] = str_replace('"', '&quot;', $event['date']);
                        echo formatMD(preg_match('/^\(?s(\.|iehe) TOP\)?$/i', $event['content']) == 1 ? "" : $event['content']);
                        echo '</div>';
                        generateButtons($event, $signedin, false);
                        echo '</div>';
                    }
                }

                echo '<div class="catrow" id="wfs">';
                echo '<hr>';
                echo '<h3>Wochenvorschau</h3>';
                echo '</div>';

                foreach ($events as $event) {
                    // if event is within the next 7 days of $date
                    if (strtotime($event['date']) >= strtotime($date) && strtotime($event['date']) < strtotime('+7 days', strtotime($date))) {
                        echo '<div class="toprow">';
                        echo '<h4>' . $event['title'] . '</h4>';
                        echo '<div class="eventdate">';
                        echo '<a href="Actions/ics.php?date=' . $event['date'] . '&time=&title=' . $event['title'] . '">';
                        echo '<h5>' . format_date($event['date']) . '</h5>';
                        echo '</a>';
                        echo '</div>';
                        echo '<div class="top">';
                        // prevent script injection
                        $event['title'] = str_replace('"', '&quot;', $event['title']);
                        $event['content'] = str_replace('"', '&quot;', $event['content']);
                        $event['date'] = str_replace('"', '&quot;', $event['date']);
                        echo formatMD(linkEventContent($event, $tops));
                        echo '</div>';
                        generateButtons($event, $signedin, false);
                        echo '</div>';
                    }
                }

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
                    echo '<h4 id="' . $top['id'] . '">' . $top['title'] . '</h4>';
                    echo '<div class="topnumber">';
                    echo '<a>';
                    echo '<h5>TOP ' . $i . '</h5>';
                    echo '</a>';
                    echo '</div>';
                    echo '<div class="top">';
                    // prevent script injection
                    $top['title'] = str_replace('"', '&quot;', $top['title']);
                    $top['content'] = str_replace('"', '&quot;', $top['content']);
                    $i++;
                    echo formatMD($top['content']);
                    echo '</div>';
                    generateButtons($top, $signedin, true);
                    echo '</div>';
                }

                if ($signedin) {
                    echo '<a class="addtopb button">';
                    echo '<span class="material-symbols-outlined">add</span>';
                    echo '</a>';
                }

                if ($topsP != null) {
                    echo '<div class="catrow" id="permanent">';
                    echo '<hr>';
                    echo '<h3>Laufende Arbeitsaufträge</h3>';
                    echo '</div>';
                }

                foreach ($topsP as $top) {
                    echo '<div class="toprow">';
                    echo '<h4 id="' . $top['id'] . '">' . $top['title'] . '</h4>';
                    echo '<div class="topnumber">';
                    echo '<a>';
                    echo '<h5>TOP ' . $i . '</h5>';
                    echo '</a>';
                    echo '</div>';
                    echo '<div class="top">';
                    // prevent script injection
                    $top['title'] = str_replace('"', '&quot;', $top['title']);
                    $top['content'] = str_replace('"', '&quot;', $top['content']);
                    $i++;
                    echo formatMD($top['content']);
                    echo '</div>';
                    generateButtons($top, $signedin, true, true);
                    echo '</div>';
                }

                if ($signedin && $topsP != null) {
                    echo '<a class="addtopb button">';
                    echo '<span class="material-symbols-outlined">add</span>';
                    echo '</a>';
                }
            }
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