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
 * for employee chat
 * {
    user_id_from:user_object.id,
    command:'new_chat',
    dialog_type:'employee_chat',
    topic: 'natural disasters'
    }
 *
 * for user chat
 * {
    user_id_from:user_object.id,
    command:'new_chat',
    dialog_type:'user_chat',
    second_user:1
   }
 *
 *
 * data, returned by socket on new_chat request
 * ______________________
 *
 * for employee chat
 *
 * if there is not any available employee
 *
 * {
    type: 'new_chat',
    message: "New chat with employee ".$employee_id." was added",
    second_user: $employee_id,
    dialog_id: $chat_id,
    dialog_type: $dialog_type,
    topic: $topic,
    is_emp_available: true/false
   }
 *
 *
 * if there is available employee
 *
 *  {
      type: 'new_chat',
      message: "New chat with employee ".$employee_id." was added",
      second_user: $employee_id,
      dialog_id: $chat_id,
      dialog_type: $dialog_type,
      topic: $topic
    }
 *
 *
 * for user chat
 *
 *  {
        type: 'new_chat',
        message: "New chat with user ".$second_user." was added",
        second_user: $second_user,
        dialog_id: $chat_id,
        dialog_type: $dialog_type
    }
 *
 *_______________________
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

        if(!array_key_exists($user_id, $this->users_id))
            $this->users_id[$user_id] = array();
        $this->users_id[$user_id][] = $conn->resourceId;

        if ($this->queryObj['consultan'] == '1') {
            if(!array_key_exists($user_id, $this->consultants_id))
                $this->consultants_id[$user_id] = array();

            $this->consultants_id[$user_id][] = $conn->resourceId;
            echo "Consultant connected ".$user_id."\n";
        }

        echo "User connected ".$user_id."\n";
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);

        $conn_id = $from->resourceId;

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

                    $this->sendMessage($conn_id, $user_id_from, $room_id, $message, $time);
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
                                    $first_message = $data->message;
                                    $second_user = $this->getAvailableEmployeeId();
                                    $is_emp_available = true;

                                    if ($second_user < 0) {
                                        $second_user = $this->getEmployeeId();
                                        $is_emp_available = false;
                                    }

                                    if($second_user > -1){
                                        $chat_id = $this->addDialogToDB($user_id_from, $dialog_type, $second_user, $topic);

                                        $time = date("Y-m-d H:i:s");
                        //                $this->sendMessage($conn_id, $user_id_from, $chat_id, $first_message, $time);
                                        $this->saveMessageInDB($user_id_from, $chat_id, $first_message, $time);

                                        $message = array(
                                            'message' => "New chat with employee ".$second_user." was added",
                                            'topic' => $topic,
                                            'is_emp_available'=>$is_emp_available,
                                            'first_message'=> array(
                                                'message' => $first_message,
                                                'time' => $time,
                                                'from'=> $user_id_from
                                            )
                                        );

                                        $userInfo2 = array(
                                            'user_id' => $second_user,
                                            'user_login' => $topic,
                                            'user_photo' => null
                                        );

                                    }else{
                                        $message = array(
                                            'type'=> 'new_chat',
                                            'message' => "No employee in database"
                                        );
                                        $from->send(json_encode($message));
                                        return;
                                    }
                                }
                                break;
                                case "user_chat":{
                                    $second_user = $data->second_user;
                                    $chat_id = $this->addDialogToDB($user_id_from, $dialog_type, $second_user, null);
                                    $message = array(
                                        'message' => "New chat with user ".$second_user." was added"
                                    );

                                    $userInfo2 = $this->getUserInfo($second_user);
                                }
                                break;
                                default:
                                    $message = array(
                                        'type'=> 'new_chat',
                                        'message' => "Unavailable dialog type"
                                    );
                                    $from->send(json_encode($message));
                                    return;
                            }

                            $userInfo1 = $this->getUserInfo($user_id_from);

                            $message['type'] = 'new_chat';
                            $message['second_user'] = $second_user;
                            $message['dialog_id'] = $chat_id;
                            $message['dialog_type'] = $dialog_type;
                            $message['user_info_1'] = $userInfo1;
                            $message['user_info_2'] = $userInfo2;

                            $from->send(json_encode($message));
                            $this->sendDialogToSecondUser($second_user, $message);
                        }
                    }
                    break;
                case 'mark_messages':
                    if(isset($room_id) ){
                        if($this->markMessages($room_id)){
                            //message for user
                            $message = array(
                                'type'=> 'mark_messages',
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
                                'type'=> 'mark_messages',
                                'message' => 'Error happened while marking messages in dialog '.$room_id
                            );
                        }
                    }else{
                        $message = array(
                            'type'=> 'mark_messages',
                            'message' => 'Dialog id or user id is not specified'
                        );
                    }
                    $from->send(json_encode($message));
                    break;
                default:
                    $message = array(
                        'type'=> 'default',
                        'message' => 'default action'
                    );
                    $from->send(json_encode($message));
                    break;
            }
        }
    }


    function getUserInfo($userId){
        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT ID as user_id, user_login
                     FROM wp_users
                     WHERE ID = ".$userId.";";

        $users = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $user){
                $user['user_photo'] = null;
                $users[] = $user;
            }
            DBHelper::disconnect();
            return count($users) > 0 ? $users[0] : null;

        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
            return null;
        }
    }

    function sendDialogToSecondUser($user_id, $packet){
        $this->sendDialogToClients([$user_id], $packet);
    }



    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        $user_id = -1;
        foreach ($this->users_id as $id => $user_conns){
            if(in_array ($conn->resourceId, $user_conns)){
                $key = array_search ($conn->resourceId, $user_conns);
                unset($this->users_id[$id][$key]);
                $user_id = $id;
                break;
            }
        }

        if($user_id > -1 && empty($this->users_id[$user_id]))
            unset($this->users_id[$user_id]);

        $cons_id = -1;
        foreach ($this->consultants_id as $id => $user_conns){
            if(in_array ($conn->resourceId, $user_conns)){
                $key = array_search ($conn->resourceId, $user_conns);
                unset($this->consultants_id[$id][$key]);
                $cons_id = $id;
                break;
            }
        }

        if($cons_id > -1 && empty($this->consultants_id[$cons_id]))
            unset($this->consultants_id[$cons_id]);

//        if(in_array ($conn->resourceId, $this->users_id)){
//            $key = array_search ($conn->resourceId, $this->users_id);
//
//            unset($this->users_id[$key]);
//            unset($this->consultants_id[$key]);
//        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }


    function getEmployeeId(){
        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT ID, meta_value AS wp_capabilities
                     FROM wp_users INNER JOIN wp_usermeta ON wp_users.ID = wp_usermeta.user_id
                     WHERE meta_key = 'wp_capabilities';";

        $employees = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $user){
                $roles = unserialize($user['wp_capabilities']);

                echo $user['wp_capabilities'];
                var_dump ($roles);

                if(array_key_exists('adviser', $roles)){
                    $employees[] = $user['ID'];
                }
            }
            $randIndex = array_rand($employees);
            DBHelper::disconnect();
            return $employees[$randIndex];

        } catch (\Exception $e) {
            echo "Error occured: ".$e." \n";
            DBHelper::disconnect();
            return -1;
        }
    }

    function getAvailableEmployeeId(){
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


    function sendMessage($conn_id, $clientFromId, $roomId, $message, $time)
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
        $this->sendMessageToClients($conn_id, $clientFromId, $clients, $dataPacket);
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


    function sendDialogToClients(array $clients, array $dataPacket){
        foreach ($clients AS $client) {
            if(array_key_exists($client, $this->users_id)) {
                echo $client;
                $conn_arr = $this->users_id[$client];
                foreach ($conn_arr as $conn_id){
                        $conn = $this->users[$conn_id];
                        $this->sendData($conn, $dataPacket);
                }
            }
        }
    }

    function sendMessageToClients($from_conn_id, $clientFromId, array $clients, array $dataPacket){
        foreach ($clients AS $client) {
            if(array_key_exists($client, $this->users_id)) {
                echo $client;
                $conn_arr = $this->users_id[$client];
                foreach ($conn_arr as $conn_id){

                    if($conn_id != $from_conn_id){
                        $conn = $this->users[$conn_id];
                        $this->sendData($conn, $dataPacket);
                    }
                }
            }
        }
    }

    function sendDataToClients($clientFromId, array $clients, array $packet)
    {
        foreach ($clients AS $client) {
            if(array_key_exists($client, $this->users_id) && $client != $clientFromId) {
                echo $client;
                $conn_arr = $this->users_id[$client];
                foreach ($conn_arr as $conn_id){
                    $conn = $this->users[$conn_id];
                    $this->sendData($conn, $packet);
                }
            }
        }
    }

    function sendData(ConnectionInterface $client, array $packet)
    {
        $client->send(json_encode($packet));
    }
}