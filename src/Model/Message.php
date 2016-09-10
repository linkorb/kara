<?php

namespace Kara\Model;

class Message
{
    protected $body;
    public function getBody()
    {
        return $this->body;
    }
    
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    
    protected $from;
    
    public function getFrom()
    {
        return $this->from;
    }
    
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }
    
    protected $room;
    
    public function getRoom()
    {
        return $this->room;
    }
    
    public function setRoom($room)
    {
        $this->room = $room;
        return $this;
    }
}
