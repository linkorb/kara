<?php

namespace Kara\Plugin;

use Kara\Model\Message;

class JokePlugin implements PluginInterface
{
    protected $bot;
    protected $guzzle;
    
    public function __construct($bot, $arguments)
    {
        $this->bot = $bot;
        $this->bot->listen('/^joke$/', [$this, 'handleJoke']);
        $this->bot->listen('/^joke\s(?<name>\w*)$/', [$this, 'handleJokeName']);
        
        $this->guzzle = new \GuzzleHttp\Client(
            [
                'base_uri' => 'http://api.icndb.com',
                'http_errors' => false
            ]
        );
    }
    
    protected function getJoke()
    {
        try {
            $res = $this->guzzle->request('GET', '/jokes/random');
        } catch(\Exception $e) {
            print_r($e->getMessage());
        } 
        if ($res->getStatusCode()==200) {
            $json = (string)$res->getBody();
            $data =json_decode($json, true);
            $joke = (string)$data['value']['joke'];
        } else {
            $joke = '???';
        }
        return $joke;
    }
    
    public function handleJoke($message, $arguments = [])
    {
        $joke = $this->getJoke();
        
        if ($message->getRoom()) {
            $this->bot->sendGroupMessage($message->getRoom(), $joke);
        } else {
            $this->bot->sendMessage($message->getFrom(), $joke);
        }
    }
    
    public function handleJokeName($message, $arguments = [])
    {
        $joke = $this->getJoke();
        $joke = str_replace('Chuck Norris', $arguments['name'][0], $joke);
        
        if ($message->getRoom()) {
            $this->bot->sendGroupMessage($message->getRoom(), $joke);
        } else {
            $this->bot->sendMessage($message->getFrom(), $joke);
        }
    }

}
