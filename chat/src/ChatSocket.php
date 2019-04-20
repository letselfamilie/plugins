<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/20/2019
 * Time: 12:25 PM
 */

/**
 * has 4 commands : new_chat, start_typing, stop_typing, message
 *
 * when open connection, url path should be ws://localhost:8000/?userId=1 (example)
 * (instead of localhost past path to your server and vise versa)
 *
 * to send message, query format should be
 *
 * {
    user_id_from:user_object.id,
    command:'message',
    dialog_id:1,
    message: 'hello how are you'
    }
 *
 *
 * to start new chat
 *
 * {
    user_id_from:user_object.id,
    dialog_type:'employee_chat' or 'user_chat',
    second_user:1,
    topic: 'natural disasters' (or null)
    }
 *
 *
 *
*/

namespace MyApp;

require_once ('dbhelper.php');

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatSocket implements MessageComponentInterface
{
    protected $clients;
    private $users_id; //connected users ids
    private $users; //connections list
    private $queryObj;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->users_id = array();
        $this->users = array();
        $this->queryObj = array();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->users[$conn->resourceId] = $conn;

        $querystring = $conn->httpRequest->getUri()->getQuery();
        $query_list = explode("&", $querystring);

        foreach ($query_list as $query){
            $queryPair = explode("=", $query);
            $this->queryObj[$queryPair[0]] = $queryPair[1];
        }

        $user_id = trim($this->queryObj['userId']);
        $this->users_id[$user_id] = $conn->resourceId;

//        $dbconn = DBHelper::connect();
//        $sqlQuery = "SELECT *
//                     FROM wp_f_categories;";
//        try {
//            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
//        } catch (\Exception $e) {
//            echo "Error occured: ".$e." \n";
//            DBHelper::disconnect();
//        }
//        DBHelper::disconnect();
        echo "User connected ".$user_id;
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);

        if (isset($data->command)) {
            $room_id = $data->dialog_id;
            $user_id_from = $data->user_id_from;

            switch ($data->command) {
                case "start_typing":
                    $this->sendUserStartedTypingMessage($user_id_from, $room_id);
                    break;

                case "stop_typing":
                    $this->sendUserStoppedTypingMessage($user_id_from, $room_id);
                    break;

                case "message":
                    $message = $data->message;
                    $this->sendMessage($user_id_from, $room_id, $message);
                    $this->sendUserStoppedTypingMessage($user_id_from, $room_id);
                    break;

                case "new_chat":
                    if (isset($user_id_from)) {
                        $dialog_type = $data->dialog_type;
                        $second_user = $data->second_user;
                        $topic = $data->topic;

                        if (isset($dialog_type) && isset($second_user)) {
                            $this->addDialogToDB($user_id_from, $dialog_type, $second_user, $topic);
                        }

                        $message = array('message' => "New chat with ".$second_user." was added");
                        $from->send(json_encode($message));
                    }
                    break;
                default:
                    $message = array('message' => 'default action');
                    $from->send(json_encode($message));
                    break;
            }
        }
//        $numRecv = count($this->clients) - 1;
//        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
//            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
//
//        foreach ($this->clients as $client) {
//            if ($from !== $client) {
//                // The sender is not the receiver, send to each client connected
//                $client->send($msg);
//            }
//        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        if(in_array ($conn->resourceId, $this->users_id)){
            $key = array_search ($conn->resourceId, $this->users_id);
            unset($this->users_id[$key]);
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    function sendUserStartedTypingMessage($userFromId, $roomId)
    {
        $dataResponse = array(
            'type'=> 'start_typing',
            'from'=> $userFromId,
            'timestamp'=> time(),
        );

        $clients = $this->findRoomClients($roomId);
//        unset($clients[$client->getResourceId()]);
        $this->sendDataToClients($clients, $dataResponse);
    }

    function sendUserStoppedTypingMessage($userFromId, $roomId)
    {
        $dataPacket = array(
            'type'=> 'stop_typing',
            'from'=> $userFromId,
            'timestamp'=> time(),
        );

        $clients = $this->findRoomClients($roomId);
//        unset($clients[$client->getResourceId()]);
        $this->sendDataToClients($clients, $dataPacket);
    }

    function sendMessage($clientFromId, $roomId, $message)
    {
        $dataPacket = array(
            'type'=> 'message',
            'from'=> $clientFromId,
            'message'=> $message
        );

        $clients = $this->findRoomClients($roomId);
        $this->sendDataToClients($clients, $dataPacket);

        $this->saveMessageInDB($clientFromId, $roomId, $message);
    }

    function saveMessageInDB($clientFromId, $dialogId, $message){
        $dbconn = DBHelper::connect();

        $sqlQuery = "INSERT INTO wp_c_messages (user_from_id, dialog_id, message_body) 
                     VALUES (
                        '" . $clientFromId . "',
                        '" . $dialogId."',
                        '" . $message."');";
        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
        }
        DBHelper::disconnect();
    }

    function addDialogToDB($userIdFrom, $dialog_type, $second_user, $topic){
//        global $wpdb;
        $dbconn = DBHelper::connect();

        $sqlQuery = "";
        if($dialog_type == 'employee_chat'){
            $sqlQuery = "INSERT INTO wp_c_dialogs (user1_id, employee_id, is_employee_chat, dialog_topic) 
                     VALUES (
                        " . $userIdFrom . ",
                        " . $second_user.",
                        " . '1'.",
                        '" . $topic."');";
        }else{
            $sqlQuery = "INSERT INTO wp_c_dialogs (user1_id, user2_id) 
                     VALUES (
                        " . $userIdFrom . ",
                        " . $second_user.");";
        }

        try {
            $dbconn->query($sqlQuery);

        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
        }
        DBHelper::disconnect();
    }

    function findRoomClients($roomId){
//        global $wpdb;
        echo "ROOMID ".$roomId;

        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT COALESCE(user2_id, employee_id) AS user_id
                     FROM wp_c_dialogs
                     WHERE dialog_id = ".$roomId.";";

        $users = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $user){
                echo "USER ".$user['user_id'];
                $users[] = $user['user_id'];
            }
        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
        }
        DBHelper::disconnect();
        return $users;
    }

    function sendDataToClients(array $clients, array $packet)
    {
        foreach ($clients AS $client) {
            if(array_key_exists($client, $this->users_id)) {
                echo $client;
                $conn = $this->users[$this->users_id[$client]];
                $this->sendData($conn, $packet);
            }
        }
    }

    function sendData(ConnectionInterface $client, array $packet)
    {
        $client->send(json_encode($packet));
    }
}