<?php 
/**
 * 发送当前报价信息
 */
namespace Server\Messages;

use Server\Utils;

class MarketChange{
    
    public $message = null;
    public function __construct($message){
      $msg['code']=$message['code'];
      $msg['ud_price']=$message['ud_price'];
      $msg['ud_precent']=$message['ud_precent'];
      $msg['now_price']=$message['now_price'];
      $this->message = $msg ;
    }
    
    public function serialize(){
      return array(TYPES_MARKET_CHANGE, 
          $this->message
      );
    }
}

