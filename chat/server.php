<?php
/**
 * Created by PhpStorm.
 * User: Polina Mahur
 * Date: 4/19/2019
 * Time: 8:06 PM
 */

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__FILE__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new \MyApp\ChatSocket()
        )
    ),
    8000, '178.128.202.94' //if running on localhost no address needed
);

//$app = new Ratchet\App("localhost", 8090, '0.0.0.0');
//$app->route('/chat', new WebSocketController, array('*'));

$server->run();