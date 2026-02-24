<?php
require_once "../sds-to-functions.php";
require_once "tg-api.php";

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$debugapi = new TelegramBotApi(file_get_contents("token.txt"));
$debugapi->debug();

// load token from token.txt
$bot = new Bot($debugapi);
$bot->handle_message(new UserMessage("__"));