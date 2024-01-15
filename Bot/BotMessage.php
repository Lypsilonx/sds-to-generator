<?php
class BotMessage
{
    function __construct($text = "", $delTime = 5, $deleteAnswer = true, $deleteCommand = true, $deleteAtMidnight = false, $buttons = [])
    {
        $this->text = $text;
        $this->delTime = $delTime;
        $this->deleteAnswer = $deleteAnswer;
        $this->deleteCommand = $deleteCommand;
        $this->deleteAtMidnight = $deleteAtMidnight;
        $this->buttons = $buttons;

        if ($this->buttons != []) {
            $this->deleteAnswer = false;
        }
    }

    public $text;
    public $delTime;
    public $deleteAnswer;
    public $deleteCommand;
    public $deleteAtMidnight;
    public $buttons;
}
?>