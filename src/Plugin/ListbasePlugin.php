<?php

namespace Kara\Plugin;

use Kara\Model\Message;
use RuntimeException;

class ListbasePlugin implements PluginInterface
{
    protected $bot;
    protected $guzzle;
    
    public function __construct($bot, $arguments)
    {
        $this->bot = $bot;
        $this->bot->listen('/lb#(?<cardId>\w*)/', [$this, 'handleCard']);
        
        $baseUrl = 'https://listbase.io';
        if (isset($arguments['base_url'])) {
            $this->baseUrl = $arguments['base_url'];
        }
        
        if (!isset($arguments['username'])) {
            throw new RuntimeException('Missing argument: username');
        }
        $username = $arguments['username'];
        
        if (!isset($arguments['password'])) {
            throw new RuntimeException('Missing argument: password');
        }
        $password = $arguments['password'];
        
        
        $this->guzzle = new \GuzzleHttp\Client(
            [
                'base_uri' => $baseUrl,
                'auth' => [
                    $username,
                    $password
                ],
                'http_errors' => false
            ]
        );
    }
    
    public function handleCard($message, $arguments = [])
    {
        $body = '';
        foreach ($arguments['cardId'] as $cardId) {
            $res = $this->guzzle->request('GET', '/api/v1/cards/' . $cardId);
            print_r($res);
            if ($res->getStatusCode()==200) {
                $json = (string)$res->getBody();
                $data =json_decode($json, true);
                $name = $data['name'];
            } else {
                $name = '???';
            }
            
            
            //$body = '<a href="https://listbase.io/cards/' . $cardId . '">' . $cardId . '</a>';
            $body .= '#' . $cardId . ': ' . $name;
            $body .= ' https://listbase.io/cards/' . $cardId  . "\n";
        }    
        if ($message->getRoom()) {
            $this->bot->sendGroupMessage($message->getRoom(), $body);
        } else {
            $this->bot->sendMessage($message->getFrom(), $body);
        }
    }

}
