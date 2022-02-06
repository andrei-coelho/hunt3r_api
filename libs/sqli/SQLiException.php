<?php

namespace libs\sqli;

class SQLiException extends \Exception {

    public function __construct(int $code, string $msg = null){
        parent::__construct($this -> generateMessage($code, $msg),1);
    }

    private function generateMessage(int $code, string $msg){
        switch ($code) {
            case 0:
                return $msg;
            case 1:
                return "Erro ao gerar o JSON";
            case 2:
                return "Este JSON não contém a(s) chaves(s): ".$msg;
            case 3:
                return "Nenhum banco de dados foi criado";
            case 4:
                return "O Banco de dados ".$msg." não está cadastrado.";
            case 5:
                return "A quantidade de valores necessárias no array não está correta.";
        }
    }

}