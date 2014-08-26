<?php

/* 
 * Copyright (C) 2014 Everton
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Este arquivo demonstra o uso da classe Config.
 * 
 */
require 'config.class.php'; //inclui a classe

$conf = new Config('exemplo.ini');//crianto um objeto de configuração

echo $conf->conf0.PHP_EOL;//acessando uma configuração que não está dentro de uma seção

echo $conf->section1->conf1.PHP_EOL;//acessando configuração dentro de seção

echo $conf->conf0 = 'Esta configuração foi modificada';//alterando uma configuração
echo PHP_EOL;

require 'extend_config.example.php';;//incluindo uma extensão de Config

$xconf = new ConfigExample('exemplo.ini');//criando uma instância de ConfigExample

try{//definindo um valor inválido para conf0 para demonstrar a função de validação
    echo $xconf->conf0 = 13131313;
    echo PHP_EOL;
} catch (Exception $ex) {
    echo $ex->getMessage().PHP_EOL;
    echo "O valor de conf0 continua {$xconf->conf0}".PHP_EOL;
}

echo 'Exibindo as configurações'.PHP_EOL;
$conf->show();//Exibe o conteúdo da configuração. Interessante para depuração.

//salva a configuração (com alterações num novo arquivo). Também pode ser usado para update no arquivo original
//Atente para o fato de que os comentários presentes no arquivo INI original não serão salvos.
$filename = 'exemplo_modificado.ini';
$save = $conf->save($filename);
echo "A configuração foi salva em $filename ($save bites salvos)".PHP_EOL;