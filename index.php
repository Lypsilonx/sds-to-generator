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
        $tops = $json_data['tops'];
        $topsP = $json_dataP['tops'];
    } else {
        $tops = $json_data['tops'];
    }
    ?>
    <header>
        <h1>SDS TO Generator</h1>
        <?php
        if ($dir != "fallback") {
            echo '<a id="downloadbutton">';
            echo '<i class="material-icons">file_download</i>';
            echo '</a>';
        }
        ?>
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
                <?php echo $title; ?>
            </h2>
            <ul>
                <div class="placeholder"></div>
                <?php
                $i = 1;
                foreach ($tops as $top) {
                    echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                    $i++;
                }

                if ($topsP != null) {
                    echo '</br>';
                    echo '<hr>';
                    echo '<li><a href="#permanent">Laufende Arbeitsaufträge</a></li>';
                }

                foreach ($topsP as $top) {
                    echo '<li><a href="#' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</a></li>';
                    $i++;
                }
                ?>
                <div class="placeholder"></div>
                <div id=sidebarbottomgradient>
                    <?php
                    if ($dir != "fallback" && $addto == false) {
                        echo '<a class="addtopbutton">';
                        echo '<i class="material-icons">add</i>';
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
                    if ($dir != "fallback") {
                        // datum formatieren nach dd.mm.yyyy
                        $date = date_create($date);
                        $day = date_format($date, 'l');
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
                        echo $day . ', den ' . date_format($date, 'd.m.Y');
                    }
                    ?>
                </h3>
            </div>
            <?php
            $i = 1;
            foreach ($tops as $top) {
                echo '<div class="toprow">';
                echo '<div class="top">';
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
                echo '<h4 id="' . $top['id'] . '">TOP ' . $i . ': ' . $top['title'] . '</h4>';
                $i++;
                echo '<p>' . $top['content'] . '</p>';
                echo '</div>';
                echo '<a class="editbutton" topid="' . $top['id'] . '" topcontent="' . $top['content'] . '" toptitle="' . $top['title'] . '" toppermanent="true">';
                echo '<i class="material-icons">edit</i>';
                echo '</a>';
                echo '</div>';
            }

            echo '<div class="placeholder"></div>';

            if ($dir != "fallback" && $addto == false) {
                echo '<div id="mainbottomgradient">';
                echo '<a class="addtopbutton">';
                echo '<i class="material-icons">add</i>';
                echo '</a>';
                echo '</div>';
            }
            ?>

        </div>
    </div>

    <div class="addtopmenu hidden">
        <form action="addtop.php" method="post" id="addtopform">
            <h2>TOP hinzufügen</h2>
            <input type="hidden" name="dir" value="<?php echo $dir; ?>">
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

    <div class="addtomenu<?php if (!$addto) {
        echo ' hidden';
    } ?>">
        <form action="addto.php" method="post" id="addtoform">
            <h2>TO hinzufügen</h2>
            <input type="hidden" name="dir" value="<?php echo $dir; ?>">
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

    <script src="sds-to-main.js"></script>
</body>

</html>