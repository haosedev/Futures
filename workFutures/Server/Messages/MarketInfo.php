<?php 
/**
 * 发送当前报价信息
 */
namespace Server\Messages;

use Server\Utils;

class MarketInfo{
    
  public $message = null;
  public function __construct($message){
    $this->message = $message ;
  }
  
  public function serialize(){
    return array(TYPES_MARKET_INFO, 
      $this->message
    );
  }
}

