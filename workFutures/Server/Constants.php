<?php 
/**
 * 常用常量定义
 */
//SYSTEM
define('__DAYTIME_KEY__', 'YmdH');    //*未完成全部兼容配置，无法改动
define('__BUY_TAX_PRECENT__', 0);  //买方印花税 0%    单向收费更合适，买入不收税
define('__SELL_TAX_PRECENT__', 0.003);  //买方印花税 0.3%

//Message
define('TYPES_MESSAGES_SYSTEM', 0);
define('TYPES_MESSAGES_HELLO', 1);
define('TYPES_MESSAGES_WELCOME', 2);
define('TYPES_MESSAGES_MESSAGE', 3);
//User
define('TYPES_LOGIN', 4);
define('TYPES_LOGIN_ANSWER', 5);
define('TYPES_USER_INFO', 6);
define('TYPES_LOGOUT', 7);
//Market
define('TYPES_PING', 9);
define('TYPES_MARKET_INFO', 10);       //大盘统计
define('TYPES_MARKET_OFFER', 11);      //单品全新数据
define('TYPES_MARKET_CHANGE', 12);     //单品报价改变

//KeepStock
define('TYPES_MARKET_KEEP', 15);          //持仓数据
define('TYPES_MARKET_ORDER', 20);          //挂单数据

//Trade
define('TYPES_TRADE_LIST', 300);        //挂单数据【客户端请求，服务端发送数据】
define('TYPES_TRADE_CANCEL', 301);      //撤单，撤销已挂订单（发送订单号）【客户端】
define('TYPES_TRADE_ENORDER', 310);     //挂单，包含买卖挂单【客户端】

