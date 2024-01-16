<?php
class BotMessage
{
    function __construct($text = "", $delTime = 3, DeleteAnswerOptions $deleteAnswer = DeleteAnswerOptions::YES, $deleteCommand = true, $buttons = array([]))
    {
        $this->text = $text;
        $this->delTime = $delTime;
        $this->deleteAnswer = $deleteAnswer;
        $this->deleteCommand = $deleteCommand;
        $this->buttons = $buttons;
    }

    public $text;
    public $delTime;
    public $deleteAnswer;
    public $deleteCommand;
    public $buttons;
}
enum DeleteAnswerOptions
{
    case NO;
    case YES;
    case AT_MIDNIGHT;
    case FORCE;
}