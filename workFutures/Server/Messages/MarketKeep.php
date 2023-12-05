<?php 
/**
 * 发送当前报价信息
 */
namespace Server\Messages;

use Server\Utils;

class MarketKeep{
    
  public $message = null;
  public function __construct($keeps){
    //$this->message = $message ;
    foreach($keeps as $k=>$v){
      $msg[$k]['code']=$v['code'];
      $msg[$k]['name']=$v['name'];
      $msg[$k]['num']=$v['num'];                  //持仓
      $msg[$k]['price']=$v['price'];              //平均买入单价
      $msg[$k]['buy_money']=$v['buy_money'];      //买入成本
      $msg[$k]['freeze']=$v['sell_freeze']+$v['buy_freeze'];  //冻结中
    }
    $this->message = $msg;
  }
  
  public function serialize(){
    return array(TYPES_MARKET_KEEP, 
      $this->message
    );
  }
}

