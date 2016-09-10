<?php

namespace Kara\Loader;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Kara\Application;
use Kara\Model\PluginRegistration;
use Kara\Bot;
use RuntimeException;

class YamlLoader
{
    public function loadFile($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: " . $filename);
        }
        
        $parser = new YamlParser();
        $data = null;
        try {
            $data = $parser->parse(file_get_contents($filename));
            if (! is_array($data)) {
                throw new RuntimeException("Parsing yaml failed");
            }
        } catch (ParseException $e) {
            throw new RuntimeException(
                sprintf(
                    'Failed to parse yaml content from file "%s": %s',
                    $filename,
                    $e->getMessage()
                )
            );
        }

        $app = new Application();
        foreach ($data['bots'] as $nick => $botData) {
            $adapterData = $botData['adapter'];
            $adapterType = $adapterData['type'];
            $adapterClass = '\\Kara\\Adapter\\' . ucfirst($adapterType) . 'Adapter';
            // Todo: strip 'type';
            $adapter = new $adapterClass($adapterData);
            $bot = new Bot($adapter);
            $bot->setNick($nick);
            
            foreach ($botData['rooms'] as $name => $roomData) {
                $adapterData = $roomData['adapter'];
                $adapterClass = '\\Kara\\Adapter\\' . ucfirst($adapterType) . 'Room';
                $room = new $adapterClass($name, $adapterData);
                $adapter->addRoom($room);
            }
            
            foreach ($botData['plugins'] as $pluginData) {
                $pluginName = $pluginData['plugin'];
                if (isset($pluginData['arguments'])) {
                    $pluginArguments = $pluginData['arguments'];
                } else {
                    $pluginArguments = [];
                }
                $pluginClass = '\\Kara\\Plugin\\' . ucfirst($pluginName) . 'Plugin';
                $plugin = new $pluginClass($bot, $pluginArguments);
                $registration = new PluginRegistration($plugin);
                $bot->addPluginRegistration($registration);
                
                $room = new $adapterClass($name, $adapterData);
                $adapter->addRoom($room);
            }
            
            $app->addBot($bot);
            $bot->connect();
        }
        return $app;
    }
}
