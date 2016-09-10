<?php

namespace Kara\Model;

use Kara\Plugin\PluginInterface;

class PluginRegistration
{
    public function __construct(PluginInterface $plugin)
    {
        $this->plugin = $plugin;
    }
    
    public function getPlugin()
    {
        return $this->plugin;
    }
}
