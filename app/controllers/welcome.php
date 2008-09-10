<?php
class WelcomeController { 
  public function index()
  {
    load('model', "User");
    $template_variables = array('users' => User::find());
    view('welcome', $template_variables);
  } 
}
