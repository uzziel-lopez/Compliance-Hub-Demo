<?php
  class Connector {
    const MODE = TRUE;
    private $production_mode;
    private $DBhost;
    private $DBname;
    private $DBuser;
    private $DBpass;
    private $connector;

    private function env($key, $default = '') {
      $value = getenv($key);
      if ($value === false || $value === null || $value === '') return $default;
      return $value;
    }
    
    public function __construct(){
      $this->production_mode = self::MODE;
      $this->DBhost = $this->env('APP_DB_HOST', '127.0.0.1');
      $this->DBname = $this->env('APP_DB_NAME', 'compliance_hub_demo');
      $this->DBuser = $this->env('APP_DB_USER', 'root');
      $this->DBpass = $this->env('APP_DB_PASSWORD', '');
      $this->connector = new PDO("mysql:host=$this->DBhost;dbname=$this->DBname;charset=utf8",
      $this->DBuser,
      $this->DBpass,
      array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
      $this->connector->query("SET lc_time_names = 'es_ES';");
    }
    
    function __destruct(){
      $this->connector = null;
    }
    
    public function connection(){
      return $this->connector;
    }
    
    public function consult($query, $params = null, $single = NULL){
      $response = array();
      $sentencia = $this->connector->prepare($query);
      if ($sentencia->execute($params)) {
        while ($fila = $sentencia->fetch(PDO::FETCH_ASSOC)) {
          $response[] = $fila;
        }
      } 
      else {
        if (!$this->production_mode) {
          echo "-- ERROR LIST: ";
          print_r($sentencia->errorInfo());
        }
        $response = null;
      }
      if ($single) return reset($response);
      else return $response;
    }
    
    public function execute($query, $params){
      $response = null;
      $sentencia = $this->connector->prepare($query);
      if ($sentencia->execute($params)) {
        if ($this->connector->lastInsertId()) $response = $this->connector->lastInsertId();
        else $response = true;
      }
      else {
        if (!$this->production_mode) {
          echo "-- ERROR LIST: ";
          print_r($sentencia->errorInfo());
        }
      }
      return $response;
    }
  }
?>
