<?php 
/**
 * 发送当前挂单信息
 */
namespace Server\Messages;

use Server\Utils;

class MyOrders{
    
  public $message = null;
  public function __construct($orders){
    foreach($orders as $k=>$v){
      $msg[$k]['mode']=$v['mode'];
      $msg[$k]['sn']=$v['sn'];
      $msg[$k]['code']=$v['code'];
      $msg[$k]['name']=$v['name'];
      $msg[$k]['surplus']=$v['surplus'];  //待成交
      $msg[$k]['price']=$v['price'];      //成交价
      $msg[$k]['time']=date('Y-m-d H:i:s',$v['create_time']);
    }
    $this->message = $msg;
  }
  
  public function serialize(){
    return array(TYPES_MARKET_ORDER, 
      $this->message
    );
  }
}

