<?php

namespace Kara;

class Application
{
    protected $bots = [];
    
    public function addBot(Bot $bot)
    {
        $this->bots[] = $bot;
    }
    
    public function getBots()
    {
        return $this->bots;
    }
}
