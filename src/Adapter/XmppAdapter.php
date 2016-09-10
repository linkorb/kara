<?php

namespace Kara\Adapter;

use Kara\Model\Message;
use XMPPJid;

class XmppAdapter implements AdapterInterface
{
    protected $client;
    protected $rooms = [];
    
    public function __construct($arguments)
    {
        $jid = $arguments['jid'];
        $password = $arguments['password'];
        
        $_PRIVDIR = getcwd() . DIRECTORY_SEPARATOR . '.kara';
        is_dir($_PRIVDIR) || mkdir($_PRIVDIR);
        
        $this->client = new KaraJaxl(array(
            // (required) credentials
            'jid' => $jid,
            'pass' => $password,
            'log_level' => JAXL_INFO,
            'priv_dir' => $_PRIVDIR,
            'strict' => false
        ));
        
        
        //
        // required XEP's
        //
        $this->client->require_xep(array(
            '0199', // XMPP Ping
            '0045', // MUC
            '0203' // Delayed Delivery
        ));
        
        $this->client->add_cb('on_auth_success', [$this, 'onAuthSuccess']);
        $this->client->add_cb('on_roster_update', [$this, 'onRosterUpdate']);
        $this->client->add_cb('on_auth_failure', [$this, 'onAuthFailure']);
        $this->client->add_cb('on_chat_message', [$this, 'onChatMessage']);
        $this->client->add_cb('on_groupchat_message', [$this, 'onGroupchatMessage']);
        //$this->client->add_cb('on_presence_stanza', [$this, 'onPresenceStanza']);
        $this->client->add_cb('onDisconnect', 'onDisconnect');
    }
    
    
    public function addRoom($room)
    {
        $this->rooms[] = $room;
    }
    
    protected function log($message)
    {
        echo "LOG: " . $message . "\n";
    }
    
    public function onAuthSuccess()
    {
        //$this->log("got on_auth_success cb, jid " . $this->client->full_jid->to_string());

        // fetch roster list
        $this->client->get_roster();
        
        $client = $this->client;
        // fetch vcard
        $vcard = $this->client->get_vcard($client->full_jid->to_string(), function ($stanza) use ($client) {
            //$this->log("YO GOT VCARD");
            // dump($stanza);
        });

        // set status
        $this->client->set_status("Hello world", "chat", 10);
        
        // Join rooms
        foreach ($this->rooms as $room) {
            //echo "Joining " . $room->getJid()->to_string() . "\n";
            $this->client->xeps['0045']->join_room($room->getJid());
        }
    }
    
    public function onAuthFailure($reason)
    {
        $this->client->send_end_stream();
        $this->log("got on_auth_failure cb with reason $reason");
    }
    
    // by default JAXL instance catches incoming roster list results and updates
    // roster list is parsed/cached and an event 'on_roster_update' is emitted
    public function onRosterUpdate()
    {
        //$this->log("Roster update...");
        // dump($this->client->roster);
    }
    
    public function onChatMessage($stanza)
    {
        dump($stanza);
        // echo back incoming chat message stanza
        /*
        $stanza->to = $stanza->from;
        $stanza->from = $this->client->full_jid->to_string();
        $stanza->body = ':) ' . $stanza->body;
        $this->client->send($stanza);
        */
        $m = new Message();
        $m->setBody($stanza->body);
        $m->setFrom($stanza->from);
        $this->bot->onMessage($m);
        
        //$this->client->xeps['0045']->send_groupchat($this->room_full_jid, "Hello world from Kara");
    }
    
    public function onGroupchatMessage($stanza)
    {
        // dump($stanza);
        $from = new \XMPPJid($stanza->from);
        $delay = $stanza->exists('delay', \NS_DELAYED_DELIVERY);
        if ($from->resource) {
            /*
            echo sprintf(
                "message stanza rcvd from %s saying... %s, %s".PHP_EOL,
                $from->resource,
                $stanza->body,
                $delay ? "delay timestamp ".$delay->attrs['stamp'] : "timestamp ".gmdate("Y-m-dTH:i:sZ")
            );
            */
        } else {
            $subject = $stanza->exists('subject');
            /*
            if ($subject) {
                echo "room subject: ".$subject->text.($delay ? ", delay timestamp ".
                    $delay->attrs['stamp'] : ", timestamp ".gmdate("Y-m-dTH:i:sZ")).PHP_EOL;
            }
            */
        }
        $process = true;
        if ($delay) {
            //echo "Delayed...\n";
            $process = false;
        }
        foreach ($this->rooms as $room) {
            if ($stanza->from == $room->getId()) {
                //$process = false;
            }
            if ($stanza->from == $room->getFullId()) {
                //echo "Not processing this one... " . $stanza->from . "\n";
                $process = false;
            }
        }
        if ($process) {
            //echo $this->bot->getNick() . " ... processing\n";
            //$this->client->xeps['0045']->send_groupchat($this->room_full_jid, "Hello world from Kara");
            /*
            if ($stanza->body) {
                dump($stanza);
                dump($from);
                echo "room_jid: " . $from->bare . "\n";
                exit();
            }
            */
            $m = new Message();
            $m->setBody($stanza->body);
            $m->setFrom($stanza->from);
            $m->setRoom($from->bare);
            $this->bot->onMessage($m);
            
            /*
            $a = [
                'to' => '28688_182239@chat.hipchat.com',
                'from' => $this->client->full_jid->to_string(),
                'type' => 'chat'
            ];
            print_r($from);
            $msg = new \XMPPMsg($a, 'Ohi, ' . $from->resource . ' just said: ' . $stanza->body);
            $this->client->send($msg);
            */
        }
    }
    
    public function onPresenceStanza($stanza)
    {
        // dump($stanza);
        $from = new XMPPJid($stanza->from);
        // self-stanza received, we now have complete room roster
        if (strtolower($from->to_string()) == strtolower($this->room_full_jid->to_string())) {
            if (($x = $stanza->exists('x', NS_MUC.'#user')) !== false) {
                if (($status = $x->exists('status', null, array('code' => '110'))) !== false) {
                    $item = $x->exists('item');
                    $this->log("xmlns #user exists with x ".$x->ns." status ".$status->attrs['code'].
                        ", affiliation:".$item->attrs['affiliation'].", role:".$item->attrs['role']);
                } else {
                    $this->log("xmlns #user have no x child element");
                }
            } else {
                $this->log("=======> odd case 1");
            }
        } elseif (strtolower($from->bare) == strtolower($this->room_full_jid->bare)) {
            // stanza from other users received
            if (($x = $stanza->exists('x', NS_MUC.'#user')) !== false) {
                $item = $x->exists('item');
                $this->log("presence stanza of type ".($stanza->type ? $stanza->type : "available")." received from ".
                    $from->resource.", affiliation:".$item->attrs['affiliation'].", role:".$item->attrs['role']);
            } else {
                $this->log("=======> odd case 2");
            }
        } else {
            $this->log("=======> odd case 3");
        }
    }
    
    public function onDisconnect()
    {
        $this->log("got on_disconnect cb");
    }
    
    public function connect()
    {
        $this->client->customConnect();
    }
    
    public function disconnect()
    {
        $this->client->emit('on_disconnect');
    }
    
    public function sendMessage($to, $body)
    {
        $a = [
            'to' => $to,
            'from' => $this->client->full_jid->to_string(),
            'type' => 'chat'
        ];
        $msg = new \XMPPMsg($a, $body);
        $this->client->send($msg);
    }
    
    public function sendGroupMessage($jid, $body)
    {
        $this->client->xeps['0045']->send_groupchat($jid, $body);
    }
    
    protected $bot;
    
    public function setBot($bot)
    {
        $this->bot = $bot;
    }
}
