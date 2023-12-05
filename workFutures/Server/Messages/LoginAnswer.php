<?php 
/**
 * 发送Math数据
 */
namespace Server\Messages;

use Server\Utils;

class LoginAnswer{
  public $boo = false;
  public $message = null;
  public function __construct($boo=false,$msg){
    $this->boo = $boo ;
    $this->message = $msg;
  }
  
  public function serialize(){
    return array(TYPES_LOGIN_ANSWER, 
      $this->boo,
      $this->message
    );
  }
}

