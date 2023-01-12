<?php
// prevent script injection
if (!isset($_GET['dir'])) {
    header('Location: ../index.php');
    exit();
}
$_GET['dir'] = htmlspecialchars($_GET['dir']);
$_GET['token'] = htmlspecialchars($_GET['token']);

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
    <title>Upload</title>
    <link rel="stylesheet" href="../sds-to-style.css">
</head>

<body>
    <script src=//cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js></script>
    <script src="../sds-to-functions.js"></script>
    <script style="display: none;">
        var dir = "<?php echo $folder; ?>";
        renderMarkDown(dir + '/Plenum', upload, "../");

        fetch("../Bot/chats.json").then(function (response) {
            return response.json();
        })
            .then(function (chats) {
                //go to index.php after 1 second
                setTimeout(function () {
                    //find chat where name is dir
                    for (var i = 0; i < chats["groups"].length; i++) {
                        if (chats["groups"][i]["name"] == dir) {
                            dir = chats["groups"][i]["dir"];
                            // html encode dir
                            dir = encodeURIComponent(dir);
                            console.log(dir);
                            break;
                        }
                    }

                    window.location.href = "https://cloud.linke-sds.org/apps/files/?dir=/" + dir;
                }, 1000);
            });
    </script>
</body>