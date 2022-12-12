<?php
// prevent script injection
if (!isset($_GET['dir'])) {
    header('Location: index.php');
    exit();
}
$_GET['dir'] = htmlspecialchars($_GET['dir']);
$_GER['token'] = htmlspecialchars($_GET['token']);

// recieves dir
$folder = $_GET['dir'];
$token = $_GET['token'];
$file = "TOs/" . $folder . "/Plenum_to.json";
$json = file_get_contents($file);

// decode json to array
$json_data = json_decode($json, true);

?>

<head>
    <meta charset="UTF-8">
    <title>Download</title>
    <link rel="stylesheet" href="sds-to-style.css">
</head>

<body>
    <script src=//cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js></script>
    <script src="sds-to-functions.js"></script>
    <script style="display: none;">
        renderMarkDown("<?php echo $folder; ?>" + '/Plenum', download);

        // go to index.php after 1 second
        setTimeout(function () {
            window.location.href = "index.php?dir=<?php echo $folder; ?>/Plenum&token=<?php echo $token; ?>";
        }, 1000);
    </script>
</body>