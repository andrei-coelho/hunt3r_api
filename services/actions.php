<?php 

use libs\sqli\SQLi as sqli; 

# retorna o limite diário de cada conta
# só pode ser invocada por outras funções e serviços
function getLimitDay($vars){

    # $_GET -> clientes (1) | redesocial (1)
    $respCli = _service('cliente', 'get')->getData();
    $cliente = count($respCli) == 1 ? $respCli[0] : false;
    
    if(!$cliente) _error();
    if(!isset($_GET['redesocial'])) _error(401, "É necessário a variável redesocial para recuperar o mapa de actions");
    $redesocial = trim($_GET['redesocial']);

    $id = $cliente['id'];

    $actionsClientQ = sqli::query(
        "SELECT
             actions.slug, actions.id, actions_cliente.limite
        FROM actions_cliente 
        JOIN actions ON actions_cliente.action_id = actions.id 
        JOIN rede_social ON actions.rede_social_id = rede_social.id
        JOIN clientes ON clientes.id = actions_cliente.cliente_id

        WHERE clientes.id = $id;
    ");

    $actionsCliente = $actionsClientQ->fetchAllAssoc();

    $ids = $vars[0];
    // $hoje = date('2021-12-05');
    $hoje = date('Y-m-d');

    $actionsMapAccsDayQ = sqli::query(
        "SELECT 
            map_actions_day.count, map_actions_day.action_id, map_actions_day.conta_rede_social_id as id
        FROM map_actions_day
        WHERE map_actions_day.conta_rede_social_id IN ($ids)
        AND data = '$hoje'
    ");

    $actionsAcc = $actionsMapAccsDayQ->fetchAllAssoc();
    $res = [];

    foreach ($actionsAcc as $actionAcc) {
        foreach ($actionsCliente as $accCli) {
            if($accCli['id'] == $actionAcc['action_id']){
                $res[$actionAcc['id']][$accCli['slug']] = $accCli['limite'] - $actionAcc['count'];
            }
        }
    }
    
    foreach ($actionsCliente as $action) {
        $res[0][$action['slug']] = $action['limite'];
        foreach ($res as $id => $act) {
            if(!isset($act[$action['slug']])){
                $res[$id][$action['slug']] = $action['limite'];
            }
        }
    }
    
    return $res;

}


function getActiveActions(){

    if(!isset($_GET['action_slug'])) 
        _error(401, "É necessário a variável 'action_slug' para usar essa function");

    $not = "";
    if(isset($_GET['not'])) 
        $not = " AND active_actions.id NOT IN (".join(",", explode(";", $_GET['not'])).")";

    $respCli = _service('cliente', 'get')->getData();
    if(count($respCli) == 0) _error();
    
    $cliId = $respCli[0]['id'];
    $hoje  = date('Y-m-d');

    $res = sqli::query(
        "SELECT 
            active_actions.id, active_actions.link, actions.slug
            FROM active_actions 
                JOIN actions ON actions.id = active_actions.action_id 
                JOIN clientes ON clientes.id = active_actions.cliente_id
            WHERE 
                data = '$hoje' 
                AND clientes.id = '$cliId' 
                
                $not
        "); // AND status = 1
        
    $data = false;
    $stts = false;
    if($res && $res->rowCount() > 0){
        $data  = $res->fetchAllAssoc();
        $inIds = "(".join(",", array_column($data, "id")).")";
        $stts  = sqli::exec("UPDATE active_actions SET status = 2 WHERE id IN $inIds");
    }

    return $data && $stts ? _response($data) : _error();

}


function get_programmed_action_follow(){
    
    $respCli = _service('cliente', 'get')->getData();
    if(count($respCli) == 0) _error();

    $id  = $respCli[0]['id'];
    $res = sqli::query(
        "SELECT hour_start, hour_end
            FROM actions_cliente_programmed
            WHERE cliente_id = $id AND action_id = 2
    ");

    if($res->rowCount() != 1) _error();
    return _response($res->fetchAllAssoc());

}


function follow(){
    
    if(!$_GET['perfil_id'] || !$_GET['conta_id'] || !$_GET['status']) 
        _error(401, "É necessário as variáveis 'perfil_id' e 'conta_id' para usar essa function");

    $perfilId = trim($_GET['perfil_id']);
    $contaId  = trim($_GET['conta_id']);
    $statusI  = (int)trim($_GET['status']);
    $respCli  = _service('cliente', 'get')->getData();
    $cliente  = count($respCli) == 1 ? $respCli[0] : false;
    
    if(!$cliente) _error();
    $id = $cliente['id'];

    if(!sqli::exec(
        "UPDATE perfis_cliente 
         SET status = $statusI, data_att = NOW() 
         WHERE cliente_id = $id AND perfil_id = $perfilId"
        ))
    _error(500, "Não foi possível altualizar o dado solicitado");

    $hoje = date('Y-m-d');
    $quer = sqli::query(
        "SELECT map_actions_day.id 
         FROM   map_actions_day 
           JOIN actions ON actions.id = map_actions_day.action_id 
           JOIN contas_rede_social ON contas_rede_social.id = map_actions_day.conta_rede_social_id 
         WHERE  
                data = '$hoje' 
            AND map_actions_day.action_id = 2 
            AND map_actions_day.conta_rede_social_id = $contaId
    ");

    $res = $quer->rowCount() == 0 ? 
        sqli::exec(
            "INSERT INTO map_actions_day 
                (data, action_id, conta_rede_social_id, count)
             VALUES
                (NOW(), 2, $contaId, 1)
            "
        ) :
        sqli::exec(
            "UPDATE map_actions_day 
             SET count = count + 1
             WHERE 
                data = '$hoje' 
                AND action_id = 2 
                AND conta_rede_social_id = $contaId
            "
        ) ;

    if(!$res)
        _error(500, "Não foi possível altualizar o dado solicitado");

    return _response([]);
}