<?php

class User extends Base { 
  private $table_name = 'users';
  private $primary_key_field = 'id';
  private $has_many = array(
    'photos' => array('model' => 'Photo', 'table_name' => 'photos', 'foreign_key_field' => 'user_id')
  );
  
  public function __construct() {
    $this->prep();
  }
  
  public function prep() {
    self::table_name_set($this->table_name);
    self::primary_key_field_set($this->primary_key_field);
    self::associations_set(array(
      'has_many' => $this->has_many
    ));
    return $this;
  }
  
  /* Your methods go here */  
  
}