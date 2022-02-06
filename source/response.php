<?php 

use libs\app\Response as Response;

function _error($code = 404, $message = "Not Found"){
    $resp = new Response([], $code, $message);
    echo $resp->response();
    die();
}

function _response(array $data){
    return new Response($data);
}