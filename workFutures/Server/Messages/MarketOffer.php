<?php 
/**
 * 发送当前报价信息
 */
namespace Server\Messages;

use Server\Utils;

class MarketOffer{
  public $message = null;
  public function __construct($message){
    $msg['id']          =$message['id'];
    $msg['code']        =$message['code'];
    //$msg['daytime']     =$message['daytime'];
    $msg['yestoday_price']=$message['yestoday_price'];
    $msg['start_price'] =$message['start_price'];
    $msg['ud_precent'] =$message['ud_precent'];
    $msg['now_price']   =$message['now_price'];
    $msg['ud_price']    =$message['ud_price'];
    $msg['name']        =$message['name'];
    $msg['max_up']      =$message['max_up'];
    $msg['max_down']    =$message['max_down'];
    $this->message = $msg ;
    //$this->message = $message ;
  }
  
  public function serialize(){
    return array(TYPES_MARKET_OFFER, 
      $this->message
    );
  }
}

