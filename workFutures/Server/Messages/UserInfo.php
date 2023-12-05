<?php 
/**
 * 发送当前报价信息
 */
namespace Server\Messages;

use Server\Utils;

class UserInfo{
    
  public $message = null;
  public function __construct($message){
    $msg['username']=$message['username'];
    $msg['nickname']=$message['nickname'];
    $msg['money']=$message['money'];
    $msg['money_freeze']=$message['money_freeze'];
    $msg['login_time']=date('Y-m-d H:i:s',$message['login_time']);
    $this->message = $msg;
  }
  
  public function serialize(){
    return array(TYPES_USER_INFO, 
      $this->message
    );
  }
}

