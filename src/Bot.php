<?php

namespace Kara;

use Kara\Adapter\AdapterInterface;
use Kara\Model\PluginRegistration;

class Bot
{
    protected $adapter;
    protected $nick;
    protected $pluginRegistrations = [];
    
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $adapter->setBot($this);
    }
    
    public function connect()
    {
        $this->adapter->connect();
    }
    
    public function disconnect()
    {
        $this->adapter->disconnect();
    }
    
    public function onMessage($message)
    {
        $body = $message->getBody();
        if (!$body) {
            return;
        }
        if ($message->getRoom()) {
            if (substr($message->getBody(), 0, strlen($this->nick) +2)!='@' . $this->nick . ' ') {
                return;
            }
            $body = substr($message->getBody(), strlen($this->nick)+2);
            $message->setBody($body);
        }
        foreach ($this->pluginRegistrations as $pluginRegistration) {
            $plugin = $pluginRegistration->getPlugin();
            if (method_exists($plugin, 'onMessage')) {
                $plugin->onMessage($message);
            }
        }
        
        foreach ($this->listeners as $pattern => $callback) {
            $res = preg_match_all($pattern, $body, $matches);
            if ($res) {
                $callback($message, $matches);
            }
        }
    }
    
    public function sendMessage($to, $body)
    {
        $this->adapter->sendMessage($to, $body);
    }
    
    public function sendGroupMessage($room, $body)
    {
        $this->adapter->sendGroupMessage($room, $body);
    }
    
    public function addPluginRegistration(PluginRegistration $pluginRegistration)
    {
        $this->pluginRegistrations[] = $pluginRegistration;
    }
    
    public function getNick()
    {
        return $this->nick;
    }
    
    public function setNick($nick)
    {
        $this->nick = $nick;
        return $this;
    }
    
    protected $listeners = [];
    public function listen($pattern, $callback)
    {
        $this->listeners[$pattern] = $callback;
    }
}
