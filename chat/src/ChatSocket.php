<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/20/2019
 * Time: 12:25 PM
 */

/**
 * has 9 commands : get_employees, take_dialog, close_chat, redirect_chat, new_chat,
 *                  start_typing, stop_typing, message, mark_messages
 *
 * when open connection, url path should be ws://localhost:8000/?userId=1 (example)
 * (instead of localhost past path to your server and vise versa)
 *
 * to send message
 * query format should be
 *
 * {
 * user_id_from:user_object.id,
 * command:'message',
 * dialog_id:1,
 * message: 'hello how are you'
 * }
 *
 *
 * to start new chat
 *
 * for employee chat
 * {
 * user_id_from:user_object.id,
 * command:'new_chat',
 * dialog_type:'employee_chat',
 * topic: 'natural disasters'
 * }
 *
 * for user chat
 * {
 * user_id_from:user_object.id,
 * command:'new_chat',
 * dialog_type:'user_chat',
 * second_user:1
 * }
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
 * type: 'new_chat',
 * message: "New chat with employee ".$employee_id." was added",
 * second_user: $employee_id,
 * dialog_id: $chat_id,
 * dialog_type: $dialog_type,
 * topic: $topic,
 * is_emp_available: true/false
 * }
 *
 *
 * if there is available employee
 *
 *  {
 * type: 'new_chat',
 * message: "New chat with employee ".$employee_id." was added",
 * second_user: $employee_id,
 * dialog_id: $chat_id,
 * dialog_type: $dialog_type,
 * topic: $topic
 * }
 *
 *
 * for user chat
 *
 *  {
 * type: 'new_chat',
 * message: "New chat with user ".$second_user." was added",
 * second_user: $second_user,
 * dialog_id: $chat_id,
 * dialog_type: $dialog_type
 * }
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
 * {
 * command:'mark_messages',
 * dialog_id:1
 * }
 */

namespace MyApp;

require_once('dbhelper.php');

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
        $this->consultants_id = array();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->users[$conn->resourceId] = $conn;

        $querystring = $conn->httpRequest->getUri()->getQuery();
        $query_list = explode("&", $querystring);
        echo $querystring;

        foreach ($query_list as $query) {
            $queryPair = explode("=", $query);
            $this->queryObj[$queryPair[0]] = $queryPair[1];
        }

        if (isset($this->queryObj['userId'])) {
            $user_id = trim($this->queryObj['userId']);

            if (!array_key_exists($user_id, $this->users_id))
                $this->users_id[$user_id] = array();

            $this->users_id[$user_id][] = $conn->resourceId;

            if ($this->queryObj['consultan'] == '1') {
                if (!array_key_exists($user_id, $this->consultants_id))
                    $this->consultants_id[$user_id] = array();

                $this->consultants_id[$user_id][] = $conn->resourceId;
                echo "Consultant connected " . $user_id . "\n";
            }

            echo "User connected " . $user_id . "\n";
        }
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
                case 'notification':
                    $type = $data->type;
                    $messageForUser = array();

                    if ($type == 'bad_word' || $type = 'report') {
                        if(isset($data->user_login))
                            $user_login = $data->user_login;
                        else{
                            $user_info = $this->getUserInfo($user_id_from);
                            $user_login = $user_info['user_login'];
                        }
                        $message_text = $data->message_text;

                        $messageForUser['command'] = 'notification_user_resp';
                        $messageForUser['type'] = 'bad_word';

                        $message = array(
                            'command' => 'notification',
                            'type' => $type,
                            'user_id_from' => $user_id_from,
                            'user_login_from' => $user_login,
                            'message_text' => $message_text
                        );
                        $this->sendToAllEmployees($message);
                    } else {
                        $message = array(
                            'command' => 'notification',
                            'type' => 'smth_else'
                        );

                        $messageForUser['command'] = 'notification_user_resp';
                        $messageForUser['type'] = 'smth_else';
                    }
                    $from->send(json_encode($messageForUser));
                    break;

                case 'take_dialog':
                    $message = array(
                        'type' => 'take_dialog',
                        'employee' => $user_id_from,
                        'dialog_id' => $room_id
                    );

                    $messageToEmp = array(
                        'type' => 'take_dialog_user_resp',
                        'employee' => $user_id_from,
                        'dialog_id' => $room_id,
                    );
                    if ($this->takeDialog($room_id, $user_id_from)) {
                        $message['state'] = 'success';
                        $messageToEmp['state'] = 'success';
                    } else {
                        $message['state'] = 'error';
                        $messageToEmp['state'] = 'error';
                    }

                    $this->sendToAllEmployeesExcept($message, [$user_id_from]);
                    $from->send(json_encode($messageToEmp));
                    break;

                case "close_chat":
                    if ($this->closeChat($room_id)) {
                        $message = array(
                            'type' => 'close_chat',
                            'state' => 'success'
                        );
                    } else {
                        $message = array(
                            'type' => 'close_chat',
                            'state' => 'error'
                        );
                    }
                    $from->send(json_encode($message));
                    break;

                case "get_employees":
                    $emp_ids = array_keys($this->consultants_id);
                    unset($emp_ids[$user_id_from]);
                    $emp_inf = $this->getEmployeesInf($emp_ids);
                    $message = array(
                        'type' => 'get_employees',
                        'employees_information' => $emp_inf
                    );
                    $from->send(json_encode($message));
                    break;

                case "redirect_chat":
                    $new_employee_id = $data->new_employee;
                    // $new_employee_id = $this->getAvailableEmployeeId($user_id_from);
                    if (isset($new_employee_id) && isset($user_id_from) && isset($room_id)) {
                        if ($this->redirectDialog($room_id, $new_employee_id, $user_id_from)) {
                            $dialog_inf = $this->getDialog($room_id);
                            $message = array(
                                'type' => 'redirect_chat',
                                'state' => 'success',
                                'message' => "Dialog was redirected to user {$new_employee_id}",
                                'new_employee_id' => $new_employee_id,
                                'dialog_id' => $room_id,
                                'dialog_info' => $dialog_inf
                            );
                            $this->sendDialogToSecondUser($new_employee_id, $message);
                        } else {
                            $message = array(
                                'type' => 'redirect_chat',
                                'state' => 'error',
                                'message' => "Error occurred while redirecting",
                                'dialog_id' => $room_id
                            );
                            $from->send(json_encode($message));
                        }
                    } else {
                        $message = array(
                            'type' => 'redirect_chat',
                            'state' => 'no_employees',
                            'message' => "No employee available. Try to redirect later",
                            'dialog_id' => $room_id
                        );
                        $from->send(json_encode($message));
                    }
                    break;

                case "start_typing":
                    $this->sendUserStartedTypingMessage($user_id_from, $room_id);
                    break;

                case "stop_typing":
                    $this->sendUserStoppedTypingMessage($user_id_from, $room_id);
                    break;

                case "message":
                    $time = date("Y-m-d H:i:s");
                    $message = $data->message;

                    $this->sendMessage($conn_id, $user_id_from, $room_id, $message, $time, $data->photo, $data->from_login);
                    $this->sendUserStoppedTypingMessage($user_id_from, $room_id);
                    break;

                case "new_chat":
                    $this->createNewChat($from, $data);
                    break;
                case 'mark_messages':
                    if (isset($room_id)) {
                        if ($this->markMessages($room_id)) {
                            //message for user
                            $message = array(
                                'type' => 'mark_messages',
                                'message' => 'Messages marked as read in dialog ' . $room_id
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
                        } else {
                            $message = array(
                                'type' => 'mark_messages',
                                'message' => 'Error happened while marking messages in dialog ' . $room_id
                            );
                        }
                    } else {
                        $message = array(
                            'type' => 'mark_messages',
                            'message' => 'Dialog id or user id is not specified'
                        );
                    }
                    $from->send(json_encode($message));
                    break;
                default:
                    $message = array(
                        'type' => 'default',
                        'message' => 'default action'
                    );
                    $from->send(json_encode($message));
                    break;
            }
        } else {
            $message = array(
                'type' => 'default',
                'message' => 'default action'
            );
            $from->send(json_encode($message));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        $user_id = -1;
        foreach ($this->users_id as $id => $user_conns) {
            if (in_array($conn->resourceId, $user_conns)) {
                $key = array_search($conn->resourceId, $user_conns);
                unset($this->users_id[$id][$key]);
                $user_id = $id;
                break;
            }
        }

        if ($user_id > -1 && empty($this->users_id[$user_id]))
            unset($this->users_id[$user_id]);

        $cons_id = -1;
        foreach ($this->consultants_id as $id => $user_conns) {
            if (in_array($conn->resourceId, $user_conns)) {
                $key = array_search($conn->resourceId, $user_conns);
                unset($this->consultants_id[$id][$key]);
                $cons_id = $id;
                break;
            }
        }

        if ($cons_id > -1 && empty($this->consultants_id[$cons_id]))
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

    function createNewChat($from, $data)
    {
        $user_id_from = $data->user_id_from;
        if (isset($user_id_from)) {
            $dialog_type = $data->dialog_type;
            if (isset($dialog_type)) {
                switch ($dialog_type) {
                    case "employee_chat":
                        {
                            $topic = $data->topic;
                            $first_message = $data->message;
                            $second_user = $this->getAvailableEmployeeId();
                            $is_emp_available = true;

                            if ($second_user < 0) {
                                $second_user = null;
                                $is_emp_available = false;
                            }

                            $chat_id = $this->addDialogToDB($user_id_from, $dialog_type, $second_user, $topic);
                            $time = date("Y-m-d H:i:s");
                            $this->saveMessageInDB($user_id_from, $chat_id, $first_message, $time);

                            $message = array(
                                'topic' => $topic,
                                'is_emp_available' => $is_emp_available,
                                'first_message' => array(
                                    'message' => $first_message,
                                    'time' => $time,
                                    'from' => $user_id_from
                                )
                            );

                            if ($second_user > -1) {
                                $message['message'] = "New chat between employee {$second_user} and " . $user_id_from . " was added";
                                $userInfo2 = array(
                                    'user_id' => $second_user,
                                    'user_login' => $topic,
                                    'user_photo' => null
                                );
                            } else {
                                $message['message'] = "New chat without employee was added";
                                $userInfo2 = array();
                            }
                        }
                        break;
                    case "user_chat":
                        {
                            $chat_id = $data->dialog_id;
                            $second_user = $this->getSecondUser($chat_id, $user_id_from);

                            echo "First user " . $user_id_from;
                            echo "Second user " . $second_user;

                            $message = array(
                                'message' => "New chat between users {$user_id_from} and " . $second_user . " was added"
                            );
                            $userInfo2 = $this->getUserInfo($second_user);
                        }
                        break;
                    default:
                        $message = array(
                            'type' => 'new_chat',
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

                $message['user_info_1'] = $userInfo2;
                $message['user_info_2'] = $userInfo1;
                $message['second_user'] = $user_id_from;

                if ($second_user != null) {
                    $this->sendDialogToSecondUser($second_user, $message);
                } else {
                    $this->sendToAllEmployees($message);
                }
            }
        }
    }

    function getEmployeesInf(array $emp_ids)
    {
        $message = array();

        foreach ($emp_ids AS $emp_id) {
            $emp_info = $this->getUserInfo($emp_id);
            $message[] = $emp_info;
        }
        return $message;
    }

//    GET EMPLOYEE
    function getEmployeeId()
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT ID, meta_value AS wp_capabilities
                     FROM wp_users INNER JOIN wp_usermeta ON wp_users.ID = wp_usermeta.user_id
                     WHERE meta_key = 'wp_capabilities';";

        $employees = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $user) {
                $roles = unserialize($user['wp_capabilities']);

                echo $user['wp_capabilities'];
                var_dump($roles);

                if (array_key_exists('adviser', $roles)) {
                    $employees[] = $user['ID'];
                }
            }
            $randIndex = array_rand($employees);
            DBHelper::disconnect();
            return $employees[$randIndex];

        } catch (\Exception $e) {
            echo "Error occured: " . $e . " \n";
            DBHelper::disconnect();
            return -1;
        }
    }


    function getAvailableEmployeeId($except_val = null)
    {
        $emp_ids = $this->consultants_id;
        if (isset($except_val)) {
            unset($emp_ids[$except_val]);
        }
        $randIndex = array_rand($emp_ids);
        return isset($randIndex) ? $randIndex : -1;
    }

//    DATA PACKETS
    function sendUserStartedTypingMessage($userFromId, $roomId)
    {
        $dataPacket = array(
            'type' => 'start_typing',
            'from' => $userFromId,
            'timestamp' => time(),
        );

        $clients = ($this->getDialogInfo($roomId))['users'];
//        unset($clients[$client->getResourceId()]);
//        $room_inf = $this->findRoomInf($roomId);
//        $clientToSend = $room_inf['first_user'] == $userFromId ? $room_inf['second_user'] : $room_inf['first_user'];
        //     $second_user = getSecondUser($roomId, $userFromId);
        //     $this -> sendDataToClients($userFromId, [$second_user], $dataPacket);
        $this->sendDataToClients($userFromId, $clients, $dataPacket);
    }

    function sendUserStoppedTypingMessage($userFromId, $roomId)
    {
        $dataPacket = array(
            'type' => 'stop_typing',
            'from' => $userFromId,
            'timestamp' => time(),
        );

        $clients = ($this->getDialogInfo($roomId))['users'];
//        $second_user = getSecondUser($roomId, $userFromId);
//        $room_inf = $this->findRoomInf($roomId);
//        $clientToSend = $room_inf['first_user'] == $userFromId ? $room_inf['second_user'] : $room_inf['first_user'];
//        $this -> sendDataToClients($userFromId, [$second_user], $dataPacket);

        //       unset($clients[$client->getResourceId()]);
        $this->sendDataToClients($userFromId, $clients, $dataPacket);
    }

    function sendMessage($conn_id, $clientFromId, $roomId, $message, $time, $photo, $from_username)
    {
        echo "ROOMID " . $roomId;
        $dialog_inf = $this->getDialogInfo($roomId);

        $dataPacket = array(
            'type' => 'message',
            'dialog_id' => $roomId,
            'from' => $clientFromId,
            'message' => $message,
            'time' => $time,
            'is_employee_chat' => $dialog_inf['is_employee_chat'],
            'photo' => $photo,
            'from_username' => $from_username
        );


        $clients = $dialog_inf['users'];
        $this->sendMessageToClients($conn_id, $clientFromId, $clients, $dataPacket);
        $this->saveMessageInDB($clientFromId, $roomId, $message, $time);
    }

//

    function markMessages($dialog_id)
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "UPDATE wp_c_messages
                     SET is_read = 1
                     WHERE dialog_id=" . $dialog_id . " AND is_read = 0;";

        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            echo "Error occured: " . $e . " \n";
            DBHelper::disconnect();
            return false;
        }
        DBHelper::disconnect();
        return true;
    }

//    SAVE IN DB
    function saveMessageInDB($clientFromId, $dialogId, $message, $time)
    {
        $dbconn = DBHelper::connect();

        $message = str_replace("'", "\'", $message);

        $sqlQuery = "INSERT INTO wp_c_messages (user_from_id, dialog_id, message_body, create_timestamp) 
                     VALUES (
                        '" . $clientFromId . "',
                        '" . $dialogId . "',
                        '" . $message . "',
                        '" . $time . "');";
        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            echo "Error occured: " . $e . " \n";
            DBHelper::disconnect();
        }
        DBHelper::disconnect();
    }

    function addDialogToDB($userIdFrom, $dialog_type, $second_user, $topic)
    {
//        global $wpdb;
        $dbconn = DBHelper::connect();

        $second_user = $second_user == null ? 'NULL' : $second_user;

        $sqlQuery = "";
        if ($dialog_type == 'employee_chat') {
            $topic = str_replace("'", "\'", $topic);

            $sqlQuery = "INSERT INTO wp_c_dialogs (user1_id, employee_id, is_employee_chat, dialog_topic) 
                     VALUES (
                        " . $userIdFrom . ",
                        " . $second_user . ",
                        " . '1' . ",
                        '" . $topic . "');";
        } else {
            $sqlQuery = "INSERT INTO wp_c_dialogs (user1_id, user2_id) 
                     VALUES (
                        " . $userIdFrom . ",
                        " . $second_user . ");";
        }

        try {
            $dbconn->query($sqlQuery);
            $last_id = $dbconn->lastInsertId();
            DBHelper::disconnect();
            return $last_id;

        } catch (\Exception $e) {
            echo "Error occured: " . $e . " \n";
            DBHelper::disconnect();
            return null;
        }
    }

    function takeDialog($room_id, $new_employee_id)
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "UPDATE  wp_c_dialogs 
                     SET employee_id = " . $new_employee_id . "
                     WHERE dialog_id = " . $room_id . ";";

        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
            DBHelper::disconnect();
            return true;
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
            DBHelper::disconnect();
            return false;
        }
    }

    function redirectDialog($room_id, $new_employee_id, $old_employee_id)
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "UPDATE  wp_c_dialogs 
                     SET employee_id = " . $new_employee_id . "
                     WHERE dialog_id = " . $room_id . ";";

        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);

            $sqlQueryMessages = "UPDATE  wp_c_messages 
                                 SET user_from_id = " . $new_employee_id . "
                                 WHERE dialog_id = " . $room_id . " AND user_from_id = " . $old_employee_id . ";";

            $dbconn->query($sqlQueryMessages, \PDO::FETCH_ASSOC);

            DBHelper::disconnect();
            return true;
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
            DBHelper::disconnect();
            return false;
        }
    }

    function closeChat($room_id)
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "UPDATE  wp_c_dialogs 
                     SET is_closed = 1
                     WHERE dialog_id = " . $room_id . ";";

        try {
            $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
            DBHelper::disconnect();
            return true;
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
            DBHelper::disconnect();
            return false;
        }
    }

//

// INFO
    function getDialog($roomId, $from = null, $to = null)
    {
        $from_message = isset($from) ? $from : 0;
        $to_message = isset($to) ? $to : 19;

        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT dialog_id, dialog_topic, is_employee_chat, is_closed,
                        (SELECT MAX(create_timestamp)
                         FROM wp_c_messages
                         WHERE dialog_id = D.dialog_id) AS last_message_timestamp,
                      COALESCE (user1_id, user2_id) AS user_id 
                     FROM wp_c_dialogs D
                     WHERE dialog_id = " . $roomId . ";";

        $dialog = array();
        try {
            $stmt = $dbconn->prepare($sqlQuery);
            $stmt->execute();
            $dialog = $stmt->fetch(\PDO::FETCH_ASSOC);

            $dialog['messages'] = array();

            $sqlQueryMessages = "SELECT *
                              FROM wp_c_messages
                              WHERE dialog_id = '" . $roomId . "'
                              ORDER BY create_timestamp DESC
                              LIMIT " . ($to_message - $from_message + 1) . " 
                              OFFSET $from_message;";

            $stmt = $dbconn->prepare($sqlQueryMessages);
            $stmt->execute();
            $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $dialog['messages'] = array_reverse($messages);
            //      foreach (array_reverse($dbconn->query($sqlQueryMessages, \PDO::ARRAY_A)) as $message)
            //       }

        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        DBHelper::disconnect();
        return $dialog;
    }

    function getDialogInfo($roomId)
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT user1_id, COALESCE(user2_id, employee_id) AS user2_id, is_employee_chat
                     FROM wp_c_dialogs
                     WHERE dialog_id = " . $roomId . ";";

        $dialog_inf = array();
        $users = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $dialog) {
                echo "USER " . $dialog['user2_id'] . '\n';
                $users[] = $dialog['user1_id'];
                $users[] = $dialog['user2_id'];

//                $dialog_inf['first_user'] = $dialog['user1_id'];
//                $dialog_inf['second_user'] = $dialog['user2_id'];
                $dialog_inf['is_employee_chat'] = $dialog['is_employee_chat'];
            }
            $dialog_inf['users'] = $users;
        } catch (\Exception $e) {
            echo "Error occured: " . $e . " \n";
            DBHelper::disconnect();
        }
        DBHelper::disconnect();

        return $dialog_inf;
    }

    function getUserInfo($userId)
    {
        $dbconn = DBHelper::connect();

        $sqlQuery = "SELECT ID as user_id, user_login
                     FROM wp_users
                     WHERE ID = " . $userId . ";";

        $users = array();
        try {
            foreach ($dbconn->query($sqlQuery, \PDO::FETCH_ASSOC) as $user) {
                $user['user_photo'] = null;
                $users[] = $user;
            }
            DBHelper::disconnect();
            return count($users) > 0 ? $users[0] : null;

        } catch (\Exception $e) {
            echo "Error occured: " . $e . " \n";
            DBHelper::disconnect();
            return null;
        }
    }

//

    function getSecondUser($chat_id, $user_from_id)
    {
        $dialog_inf = $this->getDialogInfo($chat_id);
        $users = $dialog_inf['users'];
//        $from_id = array_search($user_from_id, $users);
        return $users[0] == $user_from_id ? $users[1] : $users[0];
//        if(!empty($from_id)){
//            unset($users[$from_id]);
//            return $users[0];
//        }
//        return -1;
    }

//    SEND DATA
    function sendDialogToSecondUser($user_id, $packet)
    {
        $this->sendDialogToClients([$user_id], $packet);
    }

    function sendDialogToClients(array $clients, array $dataPacket)
    {
        foreach ($clients AS $client) {
            if (array_key_exists($client, $this->users_id)) {
                echo $client;
                $conn_arr = $this->users_id[$client];
                foreach ($conn_arr as $conn_id) {
                    echo "CLIENTID " . $client . "\n";
                    $conn = $this->users[$conn_id];
                    $this->sendData($conn, $dataPacket);
                }
            }
        }
    }

    function sendMessageToClients($from_conn_id, $clientFromId, array $clients, array $dataPacket)
    {
        foreach ($clients AS $client) {
            if (array_key_exists($client, $this->users_id)) {
                echo $client;
                $conn_arr = $this->users_id[$client];
                foreach ($conn_arr as $conn_id) {

                    if ($conn_id != $from_conn_id) {
                        echo "CLIENTID " . $client . "\n";
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
            if (array_key_exists($client, $this->users_id) && $client != $clientFromId) {
                echo $client;
                $conn_arr = $this->users_id[$client];
                foreach ($conn_arr as $conn_id) {
                    $conn = $this->users[$conn_id];
                    $this->sendData($conn, $packet);
                }
            }
        }
    }

    function sendToAllEmployeesExcept($packet, array $except_ids)
    {
        foreach ($this->consultants_id AS $emp_id => $emp) {
            if (!in_array($emp_id, $except_ids)) {
                foreach ($emp AS $emp_resource_id) {
                    if (array_key_exists($emp_resource_id, $this->users)) {
                        $conn = $this->users[$emp_resource_id];
                        $this->sendData($conn, $packet);
                    }
                }
            }
        }
    }

    function sendToAllEmployees($packet)
    {
        foreach ($this->consultants_id AS $emp) {
            foreach ($emp AS $emp_id => $emp_resource_id) {
                if (array_key_exists($emp_resource_id, $this->users)) {
                    $conn = $this->users[$emp_resource_id];
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