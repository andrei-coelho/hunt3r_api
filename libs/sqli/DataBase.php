<?php 

namespace libs\sqli;

use libs\app\Config as Config;

class DataBase {
    
    private $pdo;
    private $alias, $driver, $host, $dbname, $user, $pass, $port, $charset;
    private $open = false;

    private static $instances;
	
	private $callback;

    private function __construct($alias, $driver, $host, $dbname, $user, $pass, $charset, $port, $callback){
        
        $this->alias    = $alias;
        $this->driver   = $driver;
        $this->host     = $host;
        $this->dbname   = $dbname;
        $this->user     = $user;
        $this->pass     = $pass;
        $this->charset  = $charset;
        $this->port     = $port;
        $this->callback = $callback;
    
    }

    
    public static function open_links(){

        $databaseArray = Config::get()['database'];
        foreach ($databaseArray as $alias => $db) {
            if(self::hasKeys(array_keys($db))){
                self::$instances[$alias] = new self(
                    $alias,
                    $db['driver'],
                    $db['host'],
                    $db['dbname'],
                    $db['user'],
                    $db['pass'],
                    $db['charset'],
                    $db['port'],
                    function($e, $db){
                        // se estiver em desenvolvimento mostre o erro
                        echo "erro ao se connectar";
                    }
                );
            } else {
                echo "ta faltando info";
                // se estiver em desenvolvimento mostre o erro
            }
        }

    }


    public static function get(string $alias = "default"){

        return self::$instances[$alias]->connect();
    }


    private function connect(){
        
        if(!$this->open){
        
            $strConn = $this->driver.":host=".$this->host.";";
            if($this->port) $strConn .= "port=".$this->port;
            $strConn .= "dbname=".$this->dbname.";";
            $strConn .= "charset=".$this->charset;

            try {
                $this->pdo = new \PDO($strConn, $this->user, $this->pass);
            } catch (\PDOException $e){
                if(($call = $this->callback) !== null){
                    $call($e, $this);
                }
            }

            $this->open = true;

        }
        return $this->pdo;
    }

    private static function hasKeys(array $keys){
		
		$ks = ['driver', 'host', 'dbname', 'user', 'pass'];
		$list = [];

        foreach ($ks as $value) if(!in_array($value, $keys)) $list[] = $value;
		return count($list) == 0;

	}


	
    public function close(){
        $this->pdo = null;
    }

    public static function close_all(){
        foreach (self::$instances as $instance) {
            $instance->close();
        }
    }

    // getters

    public function host(){
        return $this -> host;
    }

    public function dbname(){
        return $this -> dbname;
    }

    public function alias(){
        return $this -> alias;
    }

}