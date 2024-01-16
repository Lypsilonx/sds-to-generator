<?php
class BotMessage
{
    function __construct($text = "", $delTime = 3, $deleteAnswer = true, $deleteCommand = true, $deleteAtMidnight = false, $buttons = [])
    {
        $this->text = $text;
        $this->delTime = $delTime;
        $this->deleteAnswer = $deleteAnswer;
        $this->deleteCommand = $deleteCommand;
        $this->deleteAtMidnight = $deleteAtMidnight;
        $this->buttons = $buttons;
    }

    public $text;
    public $delTime;
    public $deleteAnswer;
    public $deleteCommand;
    public $deleteAtMidnight;
    public $buttons;
}
?>