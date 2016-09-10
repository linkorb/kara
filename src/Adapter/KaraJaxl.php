<?php

namespace Kara\Adapter;

use JAXL;

// Simple extension of standard JAXL in order to get access to protected properties and methods
class KaraJaxl extends JAXL
{
    public function emit($ev, $data = [])
    {
        $this->ev->emit($ev, $data);
    }
    
    public function customConnect()
    {
        $this->add_cb('on_connect', array($this, 'start_stream'));
        
        if ($this->connect($this->get_socket_path())) {
            $this->emit('on_connect');
        } else {
            $this->emit(
                'on_connect_error',
                [
                    $this->trans->errno,
                    $this->trans->errstr
                ]
            );
        }
    }
}
