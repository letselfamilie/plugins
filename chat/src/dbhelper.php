<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/20/2019
 * Time: 6:03 PM
 */

namespace MyApp;

//path to configuration file
//on localhost will be different, change it to your local configuration file path
require_once ('/var/webconfig/config.php');

class DBHelper {
//    private static $port = 3306;
    private static $conn = null;

    static function connect() {
        try{
            self::$conn = new \PDO("mysql:host=".DB_HOST.
                "; dbname=".DB_NAME,
                DB_USER,
                DB_PASS);
            // set the PDO error mode to exception
            self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$conn->exec("SET NAMES 'utf8'");

        }catch(\Exception $e){
            exit;
        }
        return self::$conn;
    }

    public static function disconnect() {
        self::$conn = null;
    }
}