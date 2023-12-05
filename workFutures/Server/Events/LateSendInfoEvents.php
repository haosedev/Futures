<?php
/**
 * 延迟发送用户数据事件
 * 
 */
namespace Server\Events;
use \Server;

class LateSendInfoEvents extends Events{
  public $player=null;
  
  public function __construct($time, $player){
      
    parent::__construct($time);     //继承使用基类的构造
    $this->player = $player;
  }
  
  public function doTrigger($time){   //必须在子类中实现
    $this->player->SendUserAllInfo();
      
  }      
}