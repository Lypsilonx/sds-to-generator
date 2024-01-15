<?php


class Response
{
    function __construct($text = "", $delTime = 5, $deleteAnswer = false, $deleteAtMidnight = false, $buttons = [])
    {
        $this->text = $text;
        $this->delTime = $delTime;
        $this->deleteAnswer = $deleteAnswer;
        $this->deleteAtMidnight = $deleteAtMidnight;
        $this->buttons = $buttons;
    }

    public $text;
    public $delTime;
    public $deleteAnswer;
    public $deleteAtMidnight;
    public $buttons;

    function send($token, $chat_id, $deleteCmd = null)
    {
        $message = send_bot_api_request(
            $token,
            "sendMessage",
            array(
                "chat_id" => $chat_id,
                "text" => $this->text,
                "disable_notification" => true,
                "parse_mode" => "Markdown",
                "reply_markup" => json_encode(
                    [
                        'inline_keyboard' => [
                            $this->buttons
                        ]
                    ]
                )
            )
        );
        $message_id = $message['result']['message_id'];

        // log answer
        logToFile("Answer: " . $this->text);

        // if deleteAtMidnight is true, add to todelete.json
        if ($this->deleteAtMidnight) {
            $url = build_bot_api_link(
                $token,
                "deleteMessage",
                array(
                    "chat_id" => $chat_id,
                    "message_id" => $message_id
                )
            );
            $todelete = json_decode(file_get_contents("todelete.json"), true);
            array_push($todelete, $url);
            file_put_contents("todelete.json", json_encode($todelete, JSON_PRETTY_PRINT));
        }

        // if deleteCmd is not null, delete command message
        if ($deleteCmd != null) {
            send_bot_api_request(
                $token,
                "deleteMessage",
                array(
                    "chat_id" => $chat_id,
                    "message_id" => $deleteCmd
                )
            );
        }

        // if deleteAnswer is true, delete answer message
        if ($this->deleteAnswer) {
            sleep($this->delTime);
            // ! Find a better way to do this
            send_bot_api_request(
                $token,
                "deleteMessage",
                array(
                    "chat_id" => $chat_id,
                    "message_id" => $message_id
                )
            );
        }

        return $message_id;
    }
}
?>