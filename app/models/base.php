<?php

class Base {
  public static function establish_connection($db_settings_name) {
    global $BASE_DATABASE;
    $BASE_DATABASE = load('database', $db_settings_name);
  }
  
  protected static function table_name_set($name) {
    global $BASE_CURRENT_TABLE_NAME;
    $BASE_CURRENT_TABLE_NAME = $name;
  }
  
  protected static function table_name() {
    global $BASE_CURRENT_TABLE_NAME;
    return $BASE_CURRENT_TABLE_NAME;
  }
  
  protected static function primary_key_field_set($field) {
    global $BASE_CURRENT_TABLE_PRIMARY_KEY_FIELD;
    $BASE_CURRENT_TABLE_PRIMARY_KEY_FIELD = $field;
  }
  
  protected static function primary_key_field() {
    global $BASE_CURRENT_TABLE_PRIMARY_KEY_FIELD;
    return $BASE_CURRENT_TABLE_PRIMARY_KEY_FIELD;
  }
  
  protected static function database() {
    global $BASE_DATABASE;
    return $BASE_DATABASE;
  }
  
  public static function find($options = array()) {
    $sql = 'SELECT * from ' . self::table_name() .  
      (isset($options['conditions'])  ? ' WHERE ' . $options['conditions']  : '') . 
      (isset($options['limit'])       ? ' LIMIT ' . $options['limit']       : '') . ';';
    
    $statement = self::database()->prepare($sql);
    $statement->execute();
    
    return self::to_objects($statement->fetchAll(PDO::FETCH_ASSOC));
  }
  
  
  private static function to_objects($result_set_array) {
    $object_list = array();
    foreach($result_set_array as $result_set) {
      $object_list[] = self::to_object($result_set);
    }
    return $object_list;
  }
  
  private static function to_object($result_set) {
    $object = new self;
    $object->from_array($result_set);
    return $object;
  }
  
  /* Instance Methods */
  protected $row;
  
  public function __construct() {
    
  }
  
  public function from_array($result_set) {
    $this->row = $result_set;
  }
  
  public function save() {    
    if(!isset($this->row) || 0 == count($this->row)) {
      throw new Exception("Can't save empty record.");
    }
    
    $sql_fields = '';
    foreach($this->row as $key => $value) {
      $value = self::database()->quote($value);
      $sql_fields .= $key . ' = ' . $value . ', ';
    }
    $sql_fields = substr($sql_fields, 0, strlen($sql_fields) - 2);
    if(!isset($this->row[self::primary_key_field()])) {
      throw new Exception("Primary key not set for row, cannot save.");
    }
    $primary_key_value = $this->row[self::primary_key_field()];
    
    $sql = 'UPDATE ' . self::table_name() . ' SET ' . $sql_fields . 
      ' WHERE ' . self::primary_key_field() . ' = ' . $primary_key_value;
      
    return $this->database()->exec($sql); 
  }
  
  public function __call($method, $arguments) {
    if(isset($this->row[$method])) {
      return $this->row[$method];
    } elseif("_set" == substr($method, -4)) {
      if((1 != count($arguments)) || !is_scalar($arguments[0])) {
        throw new Exception("Must have one (and just one) scalar value to set.");
      }
      $property = substr($method, 0, strlen($method) - 4);
      $this->row[$property] = $arguments[0];
    } else {
      throw new Exception("Property not found in record.");
    }
  }
}