<?php

namespace Kara\Plugin;

use Kara\Model\Message;

class PingPlugin implements PluginInterface
{
    protected $bot;
    protected $guzzle;
    
    public function __construct($bot, $arguments)
    {
        $this->bot = $bot;
        $this->bot->listen('/^ping/', [$this, 'handlePing']);
    }
    
    public function handlePing($message, $arguments = [])
    {
        
        if ($message->getRoom()) {
            $this->bot->sendGroupMessage($message->getRoom(), 'pong');
        } else {
            $this->bot->sendMessage($message->getFrom(), 'pong');
        }
    }
}
