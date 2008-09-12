<?php
class WelcomeController { 
  public function index()
  {
    view('welcome', $template_variables);
  } 
}
