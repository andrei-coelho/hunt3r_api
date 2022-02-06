<?php 

function _service($service, $function, $vars = []){

    $serv = "services/".str_replace(".", "/", $service).".php";

    if(!file_exists($serv)) _error(404, "Not Found - O service '$service' nao existe");

    require_once $serv;

    return function_exists($function) ? $function($vars) : _error(404, "Not Found - A function '$function' nao existe");

}