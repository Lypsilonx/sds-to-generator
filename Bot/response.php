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
}
?>