<?php
require_once '../sds-to-functions.php';
require_once "bot-core.php";
require_once "tg-api.php";

// load token from token.txt
$bot = new Bot(new TelegramBotApi(file_get_contents("token.txt")));
$bot->handle_input(file_get_contents('php://input'));
?>