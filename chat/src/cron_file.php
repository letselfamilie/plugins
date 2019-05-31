<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 5/31/2019
 * Time: 7:08 PM
 */
require_once('dbhelper.php');

$dbconn = DBHelper::connect();
$sqlQuery = "DELETE FROM 'wp_c_dialogs' WHERE 'creation_timestamp' <= DATE_SUB(NOW(), INTERVAL 3 MONTH);";

try {
    $dbconn->query($sqlQuery, \PDO::FETCH_ASSOC);
    echo "success"."\n\n";
    DBHelper::disconnect();
} catch (\Exception $e) {
    echo "Error occured: " . $e . " \n\n";
    DBHelper::disconnect();
}
