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
 * Cria um objeto para armazenar e manipular configurações em arquivos INI.
 * @todo Métodos para adicionar e excluir configurações.
 * @todo Possibilitar instância sem necessidade de carregar configurações de arquivo INI.
 * 
 */
class Config{
    /**
     *
     * @var array Armazena a configuração
     */
    protected $config = array();
    
    /**
     *
     * @var string O caminho para o arquivo INI.
     */
    protected $source = NULL;

    /**
     * Construtor da classe. Recebe como parâmetro obrigatório o nome do arquivo INI com as configurações. $filename será interpretado com {@link http://php.net/manual/en/function.realpath.php realpath()} e armazenado em {@link Config::source}.
     * 
     * @param string $filename Um nome/caminho de arquivo INI com configurações.
     * @throws Exception
     */
    public function __construct($filename) {
        if(!is_file($filename)){
            throw new Exception("$filename não é um arquivo válido.");
        }else{
            try{
                $config = parse_ini_file($filename, true);
                $this->source = realpath($filename);
            } catch (Exception $ex) {
                throw $ex;
            }
            
            if(!$this->buildConfArray($config)){
                throw new Exception('Ocorreu um erro ao construir a configuração.');
            }
        }
    }
    
    /**
     * Constrói o array com as configurações que será armazenado em {@link Config::config}.
     * 
     * @param array $config O array retornado por {@link http://php.net/manual/en/function.parse-ini-file.php parse_ini_file()}.
     * @return boolean
     */
    protected function buildConfArray($config){
        if(count($config) == 0){
            return false;
        }
        
        foreach ($config as $key => $value){
            if(is_array($value)){
                $this->config[$key] = $this;
                if(count($value) > 0){
                    foreach ($value as $subkey => $subvalue){
                        $this->config[$subkey] = $subvalue;
                    }
                }
            }else{
                $this->config[$key] = $value;
            }
        }
        return true;
    }
    
    /**
     * Converte o conteúdo de {@link Config::config} para uma string.
     * Essa string não pode ser usada para criar um novo arquivo INI.
     * 
     * @return string Retorna uma string com o conteúdo de {@link Config::config}.
     */
    public function __toString() {
        $config = $this->config;
        $section = false;
        $previous_section = NULL;
        $str = '';
        
        foreach ($config as $key => $value){
            if($value === $this){
                if($previous_section === $this){
                    $str .= "\t[vazio]".PHP_EOL;
                }
                $previous_section = $value;
                $str .= "$key :".PHP_EOL;
                $section = true;
            }else{
                $previous_section = NULL;
                if($section){
                    $tab = "\t";
                }else{
                    $tab = '';
                }
                $str .= "$tab$key : $value".PHP_EOL;
            }
        }
        
        return $str;
    }
    
    /**
     * Exibe a string gerada por {@link Config::__toString()}
     */
    public function show(){
        echo $this->__toString();
    }
    
    /**
     * Método mágico usado para retornar as configurações.
     * Para saber mais sobre métodos mágicos no PHP acesse {@link http://php.net/manual/pt_BR/language.oop5.magic.php o manual do PHP}.
     * @param string $name O nome da configuração desejada.
     * @return mixed Retorna o valor da configuração desejada.
     * @throws Exception
     */
    public function __get($name) {
        if(array_key_exists($name, $this->config)){
            return $this->config[$name];
        }else{
            throw new Exception("$name não é uma configuração válida!");
        }
    }
    
    /**
     * Método mágico usado para definir valores nas configurações.
     * Para saber mais sobre métodos mágicos no PHP acesse {@link http://php.net/manual/pt_BR/language.oop5.magic.php o manual do PHP}.
     * 
     * @param string $name A configuração desejada.
     * @param mixed $value O valor da configuração.
     * @return mixed Retorna o valor configurado em caso de sucesso ou FALSE em caso de erro.
     * @throws Exception
     */
    public function __set($name, $value) {
        if(array_key_exists($name, $this->config)){
            if($this->config[$name] === $this){
                throw new Exception("$name é uma seção de configuração e não pode ser modificada!");
            }else{
                $method_name = "validate_$name";
                if(method_exists($this, $method_name)){
                    if($this->$method_name($value)){
                        $this->config[$name] = $value;
                    }else{
                        throw new Exception("O valor \"$value\" é inválido para $name!");
                    }
                }else{
                    $this->config[$name] = $value;
                }
            }
        }else{
            throw new Exception("$name não é uma configuração válida!");
        }
        return $this->config[$name];
    }
    
    /**
     * Salva o conteúdo de {@link Config} em um arquivo INI.
     * 
     * @param string $filename Opcional. O nome/caminho do arquivo de configuração. Se omitido, o armazenado em {@link Config::source} será utilizado.
     * @return integer Retorna o número de bytes escritos em caso de sucesso.
     * @throws Exception
     */
    public function save($filename = NULL){
        if($filename === NULL){
            $filename = $this->source;
        }
        
        $data = $this->prepareToSave();
        
        $save = file_put_contents($filename, $data);
        if($save == false){
            throw new Exception("Não foi possível salvar a configuração em $filename");
        }else{
            return $save;
        }
    }
    
    /**
     * Método para retornar o arquivo INI fonte das configurações, armazenado em {@link Config::source}.
     * @return string Retorna o arquivo INI de onde as configurações foram lidas.
     */
    public function getSource(){
        return $this->source;
    }
    
    /**
     * Cria uma string com o conteúdo de {@link Config::config} que possa ser gravada num arquivo INI.
     * @return string Retorna uma string para ser gravada em arquivos INI.
     */
    protected function prepareToSave(){
        $config = $this->config;
        $previous_section = NULL;
        $str = '';
        
        foreach ($config as $key => $value){
            if($value === $this){
                if($previous_section === $this){
                    //nothing
                }
                $previous_section = $value;
                $str .= "[$key]".PHP_EOL;
            }else{
                $previous_section = NULL;
                $str .= "$key = $value".PHP_EOL;
            }
        }
        
        return $str;
    }
    
}