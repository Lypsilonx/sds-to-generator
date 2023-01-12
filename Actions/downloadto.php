<?php
// prevent script injection
if (!isset($_GET['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['dir'] = htmlspecialchars($_GET['dir']);
$_GET['token'] = htmlspecialchars($_GET['token']);


// if chatid in post set $chatid
if (isset($_POST['chatid'])) {
    $chatid = $_POST['chatid'];
} else {
    if (isset($_GET['chatid'])) {
        $chatid = $_GET['chatid'];
    } else {
        $chatid = "";
    }
}

// recieves dir
$folder = $_GET['dir'];
$token = $_GET['token'];
$file = "../TOs/" . $folder . "/Plenum_to.json";
$json = file_get_contents($file);

// decode json to array
$json_data = json_decode($json, true);
?>

<head>
    <meta charset="UTF-8">
    <title>Download</title>
    <link rel="stylesheet" href="../sds-to-style.css">
</head>

<body>
    <script src=//cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js></script>
    <script src=../sds-to-functions.js></script>
    <script style="display: none;">
    <?php
    if ($chatid == "") {
        echo "renderMarkDown('" . $folder . "/Plenum', download, '../');";
        echo "setTimeout(function () {";
        echo "window.location.href = '../index.php?dir=" . $folder . "/Plenum&token=" . $token . "';";
        echo "}, 1000);";
    } else {
        echo "renderMarkDown('" . $folder . "/Plenum', download, '../', '" . $chatid . "');";
        // after 2 seconds close the window
        echo "setTimeout(function () {";
        echo "window.close();";
        if ($chatid > 0) {
            echo "window.location.href = 'tg://resolve?domain=sds_to_bot';";
        }

        echo "}, 500);";
    }
    ?>

    </script>
    <?php
    if ($chatid == "") {
        echo "<p>Download started...</p>";
    } else {
        echo "<p>Download started...<br>Check your Telegram chat.</p>";
    }
    ?>
</body>