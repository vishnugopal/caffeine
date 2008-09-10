<?php

class Photo extends Base { 
  private $table_name = 'photos';
  private $primary_key_field = 'id';
  
  public function __construct() {
    $this->prep();
  }
  
  public function prep() {
    self::table_name_set($this->table_name);
    self::primary_key_field_set($this->primary_key_field);
    return $this;
  }
  
  /* Your methods go here */  
  
}