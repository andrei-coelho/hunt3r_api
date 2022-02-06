<?php 

use libs\sqli\SQLi as sqli; 

function get(){

    $strQuery = "SELECT * FROM clientes WHERE status = 1";

    if(isset($_GET['cliente']) && $_GET['cliente'] != "all"){
        $clients = explode(";", $_GET['cliente']);
        $strQuery .= " AND (";
        foreach ($clients as $clientSlug) {
            $strQuery .= " slug = '".trim($clientSlug)."' OR ";
        }
        $strQuery = substr($strQuery, 0, -3)." )";
    }
    
    $query = sqli::query($strQuery);

    return _response($query->fetchAllAssoc());

}


function getAccounts(){
    
    if(!$_GET['cliente']) _error(401, "É necessário a variável cliente para recuperar as contas");
    $cliente = trim($_GET['cliente']);

    $res = sqli::query(
        "SELECT 
            contas_rede_social.id, 
            contas_rede_social.email, 
            contas_rede_social.senha,
            perfis.slug
         
         FROM  
                 contas_rede_social 
            JOIN perfis_cliente ON contas_rede_social.perfil_cliente_id = perfis_cliente.id 
            JOIN perfis ON perfis_cliente.perfil_id = perfis.id
            JOIN clientes ON clientes.id = perfis_cliente.cliente_id

        WHERE 
            contas_rede_social.status = 1 AND clientes.slug = '$cliente'
    ");

    if(!$res) _error();

    $accunts = $res->fetchAllAssoc();
    $ids = join(',', array_column($accunts, 'id'));
    $map = _service('actions', 'getLimitDay', [$ids]);

    foreach ($accunts as $key => $acc) {
        if(isset($map[$acc['id']])){
            foreach ($map[$acc['id']] as $k => $v) $accunts[$key]['actions'][$k] = $v;
            continue;
        }
        foreach ($map[0] as $k => $v) $accunts[$key]['actions'][$k] = $v;
    }

    return _response($accunts);

}


function getProfiles(){

    if(!$_GET['cliente'] || !$_GET['status'] || !$_GET['max']) 
        _error(401, "É necessário as variáveis 'cliente', 'status', 'max' para recuperar os perfis do cliente");
    
    $cliente = trim($_GET['cliente']);
    $status  = trim($_GET['status']);
    $max     = trim($_GET['max']);

    $res = sqli::query(
        "SELECT 
            perfis.id, perfis.slug, perfis.nome, perfis_cliente.status
         
         FROM    perfis_cliente 

            JOIN perfis   ON perfis.id = perfis_cliente.perfil_id 
            JOIN clientes ON clientes.id = perfis_cliente.cliente_id

        WHERE 
            perfis_cliente.status = $status AND clientes.slug = '$cliente'
        
        ORDER BY perfis_cliente.id ASC LIMIT $max
    ");

    return $res ? (function($res){
        return _response($res->fetchAllAssoc());
    })($res) : _error();

}


function getAncors(){

    if(!$_GET['cliente']) _error(401, "É necessário a variável cliente para recuperar as contas");
    $cliente = trim($_GET['cliente']);

    $res = sqli::query(
        "SELECT 
            perfis.slug, perfis.nome
         
         FROM    perfis_ancoras 
         
            JOIN perfis   ON perfis_ancoras.perfil_id = perfis.id 
            JOIN clientes ON clientes.id = perfis_ancoras.cliente_id

        WHERE 
            perfis.status = 1 AND clientes.slug = '$cliente'
    ");

    return $res ? (function($res){
        return _response($res->fetchAllAssoc());
    })($res) : _error();

}