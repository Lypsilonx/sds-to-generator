<?php
// Send a .md file via Telegram bot

// Get the arguments from $_POST
$markdown = $_POST['markdown'];
$filename = $_POST['filename'];
$chatid = $_POST['chatid'];

$token = file_get_contents("../Bot/token.txt");

// create new .md file and save url
$myfile = fopen("../Bot/Files/" . $filename, "w") or die("Unable to open file!");
fwrite($myfile, $markdown);
fclose($myfile);

// Send the file via Telegram bot
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/sendDocument?chat_id=' . $chatid,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => array('document' => new CURLFILE('../Bot/Files/' . $filename)),
)
);

$response = curl_exec($curl);

curl_close($curl);
echo $response;
?>