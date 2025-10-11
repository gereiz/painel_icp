<?php
require_once("FloodProtection.php");
use frdl\security\floodprotection\FloodProtection;

class ICPConnect {
    protected static $db_game;
    protected static $db_login;

    private function __construct($connection_type, $db_type, $db_host, $db_name, $db_user, $db_pass, $db_port = 3307) {
        $this->host = $db_host;
        $this->name = $db_name;
        $this->user = $db_user;
        $this->pass = $db_pass;
        $this->type = $connection_type;
        $this->port = $db_port ?? 3307;

        $db_driver = $db_type ? "mysql:host" : "sqlsrv:Server";
        $db_database = $db_type ? "dbname" : "Database";

        $dsn = $db_driver . "=" . $this->host . ";port=" . $this->port . ";" . $db_database . "=" . $this->name;

        try {
            $pdo = new \PDO($dsn, $this->user, $this->pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec('SET NAMES utf8');

            if ($this->type == "login") {
                self::$db_login = $pdo;
            } elseif ($this->type == "game") {
                self::$db_game = $pdo;
            }
        } catch (\PDOException $e) {
            return false;
        }
    }

    static function get_client_ip() {
        $v4mapped_prefix_hex = '00000000000000000000ffff';
        $v4mapped_prefix_bin = hex2bin($v4mapped_prefix_hex);
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';

        $addr_bin = inet_pton($ipaddress);
        if (substr($addr_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin) {
            $addr_bin = substr($addr_bin, strlen($v4mapped_prefix_bin));
        }
        return inet_ntop($addr_bin);
    }

    public static function connect($connection_type, $db_type, $dbhost, $dbname, $dbuser, $dbpass, $db_port = 3307) {
        // Aqui você pode manter todo o bloco de flood protection/ips
        $mercadoPagoIps = [
            "209.225.49.0/255","216.33.197.0/255","216.33.196.0/255",
            "63.128.82.0/255","63.128.83.0/255","63.128.94.0/255"
        ];
        $rangeMercadoPagoIps = [];
        foreach ($mercadoPagoIps as $mp) {
            $mpParts = explode("/", $mp);
            $mpIp = explode(".", $mpParts[0]);
            for ($i = 0; $i <= $mpParts[1]; $i++) {
                $rangeMercadoPagoIps[] = $mpIp[0] . "." . $mpIp[1] . "." . $mpIp[2] . "." . ($mpIp[3] + $i);
            }
        }

        $AllowedIps = [
            '186.234.16.8','186.234.16.9','186.234.48.8','186.234.48.9',
            '186.234.144.17','186.234.144.18','200.147.112.136','200.147.112.137',
            '200.221.19.20','64.4.248.8','64.4.249.8','66.211.169.17','173.0.84.40',
            '173.0.84.8','173.0.88.40','173.0.88.8','173.0.92.8','173.0.93.8',
            '54.88.218.97','18.215.140.160','18.213.114.129','18.206.34.84',
            '23.20.84.99','34.236.9.110','34.235.173.218','34.236.26.249','100.24.202.16'
        ];

        if (!in_array(self::get_client_ip(), array_merge($AllowedIps, $rangeMercadoPagoIps)) ||
            strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'googlebot') === false) {
            $FloodProtection = new FloodProtection('ICPNetworks', 45, 60, null, false);
            if ($FloodProtection->check(self::get_client_ip())) {
                header("HTTP/1.1 429 Too Many Requests");
                exit("Access blocked by ICPNetworks Flood Protection.");
            }
        }

        if ($connection_type == "login") {
            if (!self::$db_login) {
                new ICPConnect($connection_type, $db_type, $dbhost, $dbname, $dbuser, $dbpass, $db_port);
            }
            return self::$db_login;
        } elseif ($connection_type == "game") {
            if (!self::$db_game) {
                new ICPConnect($connection_type, $db_type, $dbhost, $dbname, $dbuser, $dbpass, $db_port);
            }
            return self::$db_game;
        }

        return false;
    }
}
