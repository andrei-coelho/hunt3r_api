<?php 

header('Content-Type: application/json');

include "autoload.php";
include "source/response.php";
include "source/helper.php";

libs\sqli\DataBase::open_links();

$service  = isset($_GET['service']) ? $_GET['service'] : false;
$function = isset($_GET['function'])  ? $_GET['function']  : false;

if(!$service || !$function) _error(401, "Unauthorized");

$respObj = _service($service, $function);

if($respObj instanceof \libs\app\Response) echo $respObj->response();
else _error(404, "Este serviço não está disponível");

