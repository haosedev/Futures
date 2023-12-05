<?php 
/**
 * 发送Math数据
 */
namespace Server\Messages;

use Server\Utils;

class SysMsg{
    
  public $message = null;
  public function __construct($message){
    $this->message = $message ;
  }
  
  public function serialize(){
    return array(TYPES_MESSAGES_SYSTEM, 
      $this->message
    );
  }
}

