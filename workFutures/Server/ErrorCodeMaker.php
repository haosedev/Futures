<?php 
/**
 * 错误代码管理器
 * 为 ErrMessage准备
 */
namespace Server;

error_reporting(E_ALL^E_NOTICE);


class ErrorCodeMaker {
  public static $errCode=[
    '310001'=>'订单提交失败，原因：登陆检测发生错误！',
    '310002'=>'订单提交失败，原因：交易标记出错！',
    '310003'=>'订单提交失败，原因：股票代码检测失败或不在交易时段！',
    '310004'=>'订单提交失败，原因：卖出库存不足！',
    '310005'=>'订单提交失败，原因：余额不足！',
  ];
  
  public static function getErrCodeMsg($code){
    return self::$errCode[$code];
  }

}