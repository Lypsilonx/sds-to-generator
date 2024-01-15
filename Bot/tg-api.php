<?php
function tg_debug_log($token, $chat_id, $message)
{
    $message = send_bot_api_request(
        $token,
        "sendMessage",
        array(
            "chat_id" => $chat_id,
            "text" => $message,
            "disable_notification" => true,
            "parse_mode" => "Markdown"
        )
    );
}

function tg_react($token, $chat_id, $message_id, $reaction)
{
    // react to message with $reaction
}

function tg_bot_leave_group($token, $chat_id)
{
    send_bot_api_request($token, "leaveChat", array("chat_id" => $chat_id));
}

function send_bot_api_request($token, $method, $params = [])
{
    $unencoded = $params;
    foreach ($unencoded as $key => $value) {
        $params[$key] = urlencode($value);
    }

    return json_decode(file_get_contents(build_bot_api_link($token, $method, $params)), true);
}

function build_bot_api_link($token, $method, $params = [])
{
    $url = "https://api.telegram.org/bot" . $token . "/" . $method . "?";
    foreach ($params as $key => $value) {
        $url .= $key . "=" . $value . "&";
    }
    $url = substr($url, 0, -1);
    return $url;
}

?>