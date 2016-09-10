<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Kara\Adapter\XmppAdapter;
use Kara\Adapter\XmppRoom;
use Kara\Loader\YamlLoader;
use Kara\Bot;

$loader = new YamlLoader();
$app = $loader->loadFile(__DIR__ . '/../kara.yml');

\JAXLLoop::run();
