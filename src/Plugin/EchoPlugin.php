<?php

namespace Kara\Plugin;

use Kara\Model\Message;

class EchoPlugin implements PluginInterface
{
    protected $bot;
    protected $prefix;
    
    public function __construct($bot, $arguments)
    {
        $this->prefix = $arguments['prefix'];
        $this->bot = $bot;
    }
    
    public function onMessage(Message $message)
    {
        if ($message->getRoom()) {
            $this->bot->sendGroupMessage($message->getRoom(), $this->prefix . $message->getBody());
        } else {
            $this->bot->sendMessage($message->getFrom(), $this->prefix . $message->getBody());
        }
    }
}
