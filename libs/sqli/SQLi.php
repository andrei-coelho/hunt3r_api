<?php

namespace libs\sqli;

use libs\sqli\DataBase as DataBase;
use libs\sqli\SQLiException as SQLiException;
use libs\sqli\Result as Result;

class SQLi {

	private static $lasterror = false;


	private function __construct() {}
	private function __clone() {}


	public static function getLastError(){
		return self::$lasterror;
	}


	public static function query(string $query, array $values = [], string $aliasDB = "default"){
		
		if(($pdo = DataBase::get($aliasDB)) === null) return false;
	
		$st = $pdo->prepare($query);
		if(!$st) throw new SQLiException(0, $pdo->errorInfo()[2]);
			
		if(count($values) > 1)
			self::setBinds($st, $values);
		
		$result = new Result($st);
		if($result->hasError()){
			self::$lasterror = $result->getCode();
			return false;
		}

		return  $result;
		
	}


	public static function exec(string $exec, $insert = false, array $values = [], string $aliasDB = "default"){
		
		if(($pdo = DataBase::get($aliasDB)) === null) return false;
		$st = $pdo->prepare($exec);
		
		if(!$st) throw new SQLiException(0, $pdo->errorInfo()[2]);
			
		if(count($values) > 1)
			self::setBinds($st, $values);
			
		$res = new Result($st);
		if($insert) return $res->hasError() ? false : $pdo->lastInsertId();
		return $res->hasError() ? $res->getCode() : true;
		
	}


	private static function setBinds($st, array $values){

		$binds = array_shift($values);
		
		for($i = 0, $y = 1; $i < strlen($binds); $i++, $y++){
			
			$var = &$values[$i];
			self::bind($st, $var, $y, $binds[$i]);
			
		}
		
	}


	private static function bind($st, &$var, $y, $bind){

		switch ($bind) {
			case 'i':
				$bind = \PDO::PARAM_INT;
				break;
			case 'b':
				$bind = \PDO::PARAM_BOOL;
				break;
			case 'd':
				$var = strval($var);
				$bind = \PDO::PARAM_STR;
				break;
			case 's':
				$bind = \PDO::PARAM_STR;
				break;
			default:
				$bind = false;
				break;
		}

		!$bind ? $st->bindParam($y, $var) : $st->bindParam($y, $var, $bind);
	}

}