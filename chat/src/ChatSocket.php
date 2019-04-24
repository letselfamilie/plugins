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
 * to send message
 * query format should be
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
    command:'new_chat',
    dialog_type:'employee_chat' or 'user_chat',
    second_user:1 (shouldn't be specified if employee_chat),
    topic: 'natural disasters' (shouldn't be specified if user_chat)
    }
 *
 *
 * to send user start typing event
 *
 * {
 * user_id_from:user_object.id,
 * command:'start_typing',
 * dialog_id:1
 * }
 *
 *
 * to send user stop typing event
 *
 * {
 *  user_id_from:user_object.id,
 *  command:'stop_typing',
 *  dialog_id:1
 * }
 *
 *
 * to mark messages as read
 *
{
command:'mark_messages',
dialog_id:1
}
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

    private $consultants_id;

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
        echo $querystring;

        foreach ($query_list as $query){
            $queryPair = explode("=", $query);
            $this->queryObj[$queryPair[0]] = $queryPair[1];
        }


        $user_id = trim($this->queryObj['userId']);
        $this->users_id[$user_id] = $conn->resourceId;

        if ($this->queryObj['consultan'] == '1') {
            $this->consultants_id[$user_id] = $conn->resourceId;
            echo "Consultant connected ".$user_id."\n";
        }

  //      echo $cookiesArr;
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
        echo "User connected ".$user_id."\n";
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
                    $time = date("Y-m-d H:i:s");
                    $message = $data->message;

                    $this->sendMessage($user_id_from, $room_id, $message, $time);
                    $this->sendUserStoppedTypingMessage($user_id_from, $room_id);
                    break;

                case "new_chat":
                    if (isset($user_id_from)) {
                        $dialog_type = $data->dialog_type;

                        $chat_id = null;
                        $second_user = null;
                        $topic = null;

                        if(isset($dialog_type)) {
                            switch ($dialog_type){

                                case "employee_chat": {
                                    $topic = $data->topic;
                                    $employee_id = $this->getEmployeeId();

                                    if ($employee_id < 0) {
                                        $message = array(
                                            'message' => "Sorry, all employees are unavailable now. Please, try to ask your question later"
                                        );
                                    } else {
                                        $chat_id = $this->addDialogToDB($user_id_from, $dialog_type, $employee_id, $topic);

                                        $message = array(
                                            'message' => "New chat with employee ".$employee_id." was added",
                                            'second_user' => $employee_id,
                                            'dialog_id' => $chat_id,
                                            'dialog_type' => $dialog_type,
                                            'topic' => $topic
                                        );
                                    }
                                }
                                break;
                                case "user_chat":{
                                    $second_user = $data->second_user;
                                    $chat_id = $this->addDialogToDB($user_id_from, $dialog_type, $second_user, null);

                                    $message = array(
                                        'message' => "New chat with user ".$second_user." was added",
                                        'second_user' => $second_user,
                                        'dialog_id' => $chat_id,
                                        'dialog_type' => $dialog_type
                                    );
                                }
                                break;
                                default:
                                    $message = array(
                                        'message' => "Unavailable dialog type"
                                    );
                            }
                            $from->send(json_encode($message));
                        }
                    }
                    break;
                case 'mark_messages':
                    if(isset($room_id) ){
                        if($this->markMessages($room_id)){
                            //message for user
                            $message = array(
                                'message' => 'Messages marked as read in dialog '.$room_id
                            );
//                            //message for other users in chat
//                            $dataPacket = array(
//                                'type'=> 'mark_messages',
//                                'dialog_id' => $room_id,
//                                'from'=> $user_id_from,
//                                'message'=> 'Messages was read by '.$user_id_from.' in dialog '.$room_id
//                            );
//                            $dialog_inf = $this->findRoomInf($room_id);
//                            $clients = $dialog_inf['users'];
//                            $this->sendDataToClients($user_id_from, $clients, $dataPacket);
                        }else{
                            $message = array(
                                'message' => 'Error happened while marking messages in dialog '.$room_id
                            );
                        }
                    }else{
                        $message = array(
                            'message' => 'Dialog id or user id is not specified'
                        );
                    }
                    $from->send(json_encode($message));
                    break;
                default:
                    $message = array('message' => 'default action');
                    $from->send(json_encode($message));
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        if(in_array ($conn->resourceId, $this->users_id)){
            $key = array_search ($conn->resourceId, $this->users_id);
            unset($this->users_id[$key]);
            unset($this->consultants_id[$key]);
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    function getEmployeeId(){
        $randIndex = array_rand($this->consultants_id);
        return isset($randIndex) ? $randIndex : -1;
    }

    function sendUserStartedTypingMessage($userFromId, $roomId)
    {
        $dataResponse = array(
            'type'=> 'start_typing',
            'from'=> $userFromId,
            'timestamp'=> time(),
        );

        $clients = ($this->findRoomInf($roomId))['users'];
//        unset($clients[$client->getResourceId()]);
        $this->sendDataToClients($userFromId, $clients, $dataResponse);
    }

    function sendUserStoppedTypingMessage($userFromId, $roomId)
    {
        $dataPacket = array(
            'type'=> 'stop_typing',
            'from'=> $userFromId,
            'timestamp'=> time(),
        );

        $clients = ($this->findRoomInf($roomId))['users'];
//        unset($clients[$client->getResourceId()]);
        $this->sendDataToClients($userFromId, $clients, $dataPacket);
    }

    function sendMessage($clientFromId, $roomId, $message, $time)
    {
        $dialog_inf = $this->findRoomInf($roomId);

        $dataPacket = array(
            'type'=> 'message',
            'dialog_id' => $roomId,
            'from'=> $clientFromId,
            'message'=> $message,
            'time' => $time,
            'is_employee_chat' => $dialog_inf['is_employee_chat']
        );

        $clients = $dialog_inf['users'];
        $this->sendDataToClients($clientFromId, $clients, $dataPacket);
        $this->saveMessageInDB($clientFromId, $roomId, $message, $time);
    }

    function markMessages($dialog_id){
        $dbconn = DBHelper::connect();

        $sqlQuery = "UPDATE wp_c_messages
                     SET is_read = 1
                     WHERE dialog_id=".$dialog_id." AND is_read = 0;";

        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
            return false;
        }
        DBHelper::disconnect();
        return true;
    }

    function saveMessageInDB($clientFromId, $dialogId, $message, $time){
        $dbconn = DBHelper::connect();

        $sqlQuery = "INSERT INTO wp_c_messages (user_from_id, dialog_id, message_body, create_timestamp) 
                     VALUES (
                        '" . $clientFromId . "',
                        '" . $dialogId."',
                        '" . $message."',
                        '" . $time. "');";
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
            $last_id = $dbconn->lastInsertId();
            DBHelper::disconnect();
            return $last_id;

        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
            return null;
        }
    }

    function findRoomInf($roomId){
//        global $wpdb;
        echo "ROOMID ".$roomId;

        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT user1_id, COALESCE(user2_id, employee_id) AS user_id, is_employee_chat
                     FROM wp_c_dialogs
                     WHERE dialog_id = ".$roomId.";";

        $dialog_inf = array();
        $users = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $dialog){
                echo "USER ".$dialog['user_id'].'\n';
                $users[] = $dialog['user1_id'];
                $users[] = $dialog['user_id'];

                $dialog_inf['is_employee_chat'] = $dialog['is_employee_chat'];
            }
            $dialog_inf['users'] = $users;
        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
        }
        DBHelper::disconnect();

        return $dialog_inf;
    }

    function sendDataToClients($clientFromId, array $clients, array $packet)
    {
        foreach ($clients AS $client) {
            if(array_key_exists($client, $this->users_id) && $client != $clientFromId) {
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