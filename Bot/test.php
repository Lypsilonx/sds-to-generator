<?php
require_once '../sds-to-functions.php';
require_once "tg-api.php";

$debugapi = new TelegramBotApi(file_get_contents("token.txt"));
$debugapi->debug();

// load token from token.txt
$bot = new Bot($debugapi);
$bot->handle_message(new UserMessage("__"));
?>