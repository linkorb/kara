<?php

namespace Kara\Adapter;

use XMPPJid;

class XmppRoom
{
    protected $name;
    protected $roomId;
    protected $nick;
    
    public function __construct($name, $arguments)
    {
        $this->name = $name;
        $this->roomId = $arguments['room_id'];
        $this->nick = $arguments['nick'];
    }
    
    public function getId()
    {
        return $this->roomId;
    }
    
    public function getNick()
    {
        return $this->nick;
    }
    
    public function getJid()
    {
        return new XMPPJid($this->getFullId());
    }
    
    public function getFullId()
    {
        return sprintf('%s/%s', $this->getId(), $this->getNick());
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
}
