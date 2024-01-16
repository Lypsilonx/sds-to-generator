<?php
// https://api.telegram.org/botXXXX/setWebhook?url=www.politischdekoriert.de/sds-to-generator/Bot/index.php&drop_pending_updates=true
require_once 'bot-core.php';
class TelegramBotApi implements BotApi
{
    private $token;
    private $chat_id;
    private $message_id;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function debug()
    {
        echo "Debug mode enabled\n";
        $this->chat_id = "debug";
        $this->message_id = "debug";
    }

    public function handle_callback($update): ?UserMessage
    {
        $callback_do = false;

        $output = new UserMessage();

        // Check if callback is set
        if (isset($update['callback_query'])) {
            $this->chat_id = $update['callback_query']['from']['id'];
            $callback_message = $update['callback_query']['data'];

            // delete callback message
            $callback_message_id = $update['callback_query']['message']['message_id'];
            $this->delete_message($callback_message_id);

            if (strpos($callback_message, "say:") === 0) {
                $callback_message = substr($callback_message, 4);
                $this->send_message(getMessage($callback_message));
                return null;
            } else if (strpos($callback_message, "do:") === 0) {
                $output->text = substr($callback_message, 3);
                $this->message_id = null;
                $callback_do = true;
                $output->username = $update['callback_query']['from']['username'];

                if (!isset($output->username)) {
                    // use id if no username is set
                    $output->username = $update['callback_query']['from']['id'];
                }
            } else {
                return null;
            }
        }

        if (!$callback_do) {
            if (!isset($update['message'])) {
                return null;
            }

            $message = $update['message'];

            if (!isset($message['text'])) {
                return null;
            }
            $output->text = $message['text'];
            $this->chat_id = $message['chat']['id'];
            $this->message_id = $message['message_id'];
            $output->username = $message['from']['username'];

            if (!isset($username)) {
                // use id if no username is set
                $username = $message['from']['id'];
            }
        }

        return $output;
    }

    public function send_message(BotMessage $response)
    {
        $message = $this->send_bot_api_request(
            "sendMessage",
            array(
                "chat_id" => $this->chat_id,
                "text" => $response->text,
                "disable_notification" => true,
                "parse_mode" => "Markdown",
                "reply_markup" => json_encode(
                    [
                        'inline_keyboard' => $response->buttons
                    ]
                )
            )
        );

        if ($this->chat_id != "debug") {
            $message_id = $message['result']['message_id'];

            // log answer
            logToFile("Answer: " . $response->text);
        } else {
            $message_id = "debug";
        }

        if ($response->deleteAnswer == DeleteAnswerOptions::AT_MIDNIGHT) {
            $url = $this->build_bot_api_link(
                "deleteMessage",
                array(
                    "chat_id" => $this->chat_id,
                    "message_id" => $message_id
                )
            );
            $todelete = json_decode(file_get_contents("todelete.json"), true);
            array_push($todelete, $url);
            file_put_contents("todelete.json", json_encode($todelete, JSON_PRETTY_PRINT));
        }

        // if deleteCmd is not null, delete command message
        if ($response->deleteCommand) {
            $this->send_bot_api_request(
                "deleteMessage",
                array(
                    "chat_id" => $this->chat_id,
                    "message_id" => $this->message_id
                )
            );
        }

        // if deleteAnswer is true, delete answer message
        if (($response->deleteAnswer == DeleteAnswerOptions::YES && $response->buttons == array([])) || $response->deleteAnswer == DeleteAnswerOptions::FORCE) {
            sleep($response->delTime);
            // ! Find a better way to do this
            $this->send_bot_api_request(
                "deleteMessage",
                array(
                    "chat_id" => $this->chat_id,
                    "message_id" => $message_id
                )
            );
        }

        return $message_id;
    }

    public function delete_message($message_id)
    {
        $this->send_bot_api_request(
            "deleteMessage",
            array(
                "chat_id" => $this->chat_id,
                "message_id" => $message_id
            )
        );
    }

    public function debug_log($message)
    {
        $message = $this->send_bot_api_request(
            "sendMessage",
            array(
                "chat_id" => $this->chat_id,
                "text" => $message,
                "disable_notification" => true,
                "parse_mode" => "Markdown"
            )
        );
    }

    public function react($message_id, $reaction)
    {
        // react to message with $reaction
    }

    public function leave_group()
    {
        $this->send_bot_api_request("leaveChat", array("chat_id" => $this->chat_id));
    }

    public function in_group(): bool
    {
        return $this->chat_id < 0;
    }

    public function get_uid(): string
    {
        return $this->chat_id;
    }

    private function send_bot_api_request($method, $params = [])
    {
        if ($this->chat_id == "debug") {
            echo $method;
            echo var_dump($params);
            return $this->message_id;
        }

        $unencoded = $params;
        foreach ($unencoded as $key => $value) {
            $params[$key] = urlencode($value);
        }

        return json_decode(file_get_contents($this->build_bot_api_link($method, $params)), true);
    }

    private function build_bot_api_link($method, $params = [])
    {
        $url = "https://api.telegram.org/bot" . $this->token . "/" . $method . "?";
        foreach ($params as $key => $value) {
            $url .= $key . "=" . $value . "&";
        }
        $url = substr($url, 0, -1);
        return $url;
    }
}