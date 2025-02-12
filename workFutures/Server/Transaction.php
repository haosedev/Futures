<?php 
/**
 * Transaction
 * 
 * 交易管理
 */
namespace Server;

class Transaction {

  public $server;         //Server接口
  private $lock=0;            //**考虑建立互斥锁

  
  public function __construct($GameServer) {
      $this->server = $GameServer;

  }
  /***********************
   * 发起购买单
   * 返回结果：成功，失败！
   */ 
  public function OrderBuy($order_buy){

    global $database;
    //**查询卖单，把符合的卖单按时间先后顺序逐条取出处理。直到无可用订单。
    do{
      $sn = Utils::makeSN(8);
    }while(!$database->IsEmptySn($sn,'Listbuy'));
    $order_buy['sn']=$sn;
    //$database->beginTrans();  //事务开始
    $end=false;
    do{
      //1读取
      //2处理
      //3生成成交订单
      //未完成继续读取1
      //4直到读取1返回false，或者处理完成。
      $order_sell=$database->fetchListSellByOrder($order_buy);
      if ($order_sell){
        if ($order_sell['surplus'] >= $order_buy['surplus']){
          //挂单方有剩余
          $deal_num = $order_buy['surplus'];
          $deal_money = $deal_num * $order_buy['price'];  //按买家金额计算
          $order_sell['surplus'] = $order_sell['surplus']-$order_buy['surplus'];
          $order_buy['surplus'] = 0;        //发起家为0
          $end=true;                        //发起单结束
        }else{
          //挂单方不足
          $deal_num = $order_sell['surplus'];
          $deal_money = $deal_num * $order_buy['price'];  //按买家金额计算
          $order_buy['surplus']=$order_buy['surplus']-$order_sell['surplus'];
          $order_sell['surplus']=0;    //挂单方为0
          $end=false;                  //发起单未结束
        }
        //印花税计算
        $order_buy['tax']=$this->calc_tax('buy',$deal_money);       //计算买家印花税
        $order_sell['tax']= $this->calc_tax('sell',$deal_money);    //计算卖家印花税
        $order_buy['money'] -= ($deal_money+$order_buy['tax']);     //买家剩余-(成交金额+买家税收)
        $order_sell['money'] += ($deal_money-$order_sell['tax']);   //卖家获得+(成交金额-卖家税收)(一般卖方收益，所以需要支付税金)
        //重写卖家挂单
        $database->updateListSell($order_sell);
        //****通知卖方（如果在线）更新用户信息 ---------这里有信息延迟，数据库写入未执行完毕，就算发送了还是有数据的
        $sellplayer=$this->server->GetPlayerByUid($order_sell['uid']);
        if ($sellplayer){
          $this->server->addEvents($sellplayer->id, new Events\LateSendInfoEvents(time()+5, $sellplayer));
          //$sellplayer->SendUserAllInfo();
          //**增加延时发送
        };
        //生成交易流水单
        //id	daytime	type 0:卖方发起，1：买方发起	code 股票代码	amount 成交数量	money 成交金额	create_time 成交时间	buy_uid	buy_sn	sell_uid	sell_sn
        $database->saveListDeal([
          'daytime'=>$order_buy['daytime'],
          'type'=>1,
          'code'=>$order_buy['code'],
          'amount'=>$deal_num,
          'money'=>$deal_money,
          'buy_uid'=>$order_buy['uid'],
          'buy_sn'=>$order_buy['sn'],
          'buy_tax'=>$order_buy['tax'],
          'sell_uid'=>$order_sell['uid'],
          'sell_sn'=>$order_sell['sn'],
          'sell_tax'=>$order_sell['tax'],
        ]);
        //修改卖家金额
        $database->ChangeMoney($order_sell['uid'], $deal_money-$order_sell['tax'], 'Add');
        $database->UnlockFreezeMoney($order_buy['uid'], $deal_money);
        //累计需要更新双方 KeepStore
        $database->KeepBuySuccess($order_buy['uid'], $order_buy['code'], $deal_num, $deal_money, $order_buy['daytime']);   //买方的库存变化是 股票数增加，买入价格累加
        $database->KeepSellSuccess($order_sell['uid'], $order_buy['code'], $deal_num, $deal_money);                //卖方库存变化是股票减少，买入价格降低
      }else{
        $end=true ;
      }
    }while(!$end);
    //写入买订单，并提示标记status和surplus。
    $database->saveListBuy($order_buy);
    
    //*****向系统挂消息，刷新买方卖方的数据。

    //$database->commitTrans(); //提交
    return true;
  }
  /***********************
   * 挂单卖出
   * 返回结果：成功，失败！
   */ 
  public function OrderSell($order_sell){

    global $database;
    do{
      $sn=Utils::makeSN(8);
    }while(!$database->IsEmptySn($sn,'Listsell'));
    $order_sell['sn']=$sn;
    //$database->beginTrans();  //事务开始
    $end=false;
    do{
      //1读取
      //2处理
      //3生成成交订单
      //未完成继续读取1
      //4直到读取1返回false，或者处理完成。
      $order_buy=$database->fetchListBuyByOrder($order_sell);
      if ($order_buy){
        if ($order_buy['surplus'] >= $order_sell['surplus']){
          //挂单方有剩余
          $deal_num = $order_sell['surplus'];
          $deal_money = $deal_num * $order_buy['price'];  //按买家金额计算
          $order_buy['surplus'] = $order_buy['surplus']-$order_sell['surplus'];
          $order_sell['surplus'] = 0;       //发起家为0
          $end=true;                        //发起单结束
        }else{
          //挂单方不足
          $deal_num = $order_buy['surplus'];
          $deal_money = $deal_num * $order_buy['price'];
          $order_sell['surplus']=$order_sell['surplus']-$order_buy['surplus'];
          $order_buy['surplus']=0;    //挂单方为0
          $end=false;                 //发起单未结束
        }
        //印花税计算
        $order_sell['tax']=$this->calc_tax('sell',$deal_money);      //计算卖家印花税
        $order_buy['tax']= $this->calc_tax('buy',$deal_money);       //计算买家印花税
        $order_buy['money'] -= ($deal_money+$order_buy['tax']);      //买家剩余-(成交金额+买家税收)
        $order_sell['money'] += ($deal_money-$order_sell['tax']);    //卖家获得+(成交金额-卖家税收)
        //重写卖家挂单
        $database->updateListBuy($order_buy);
        //****通知买方（如果在线）更新用户信息---------这里有信息延迟，数据库写入未执行完毕，就算发送了还是有数据的
        $buyplayer=$this->server->GetPlayerByUid($order_buy['uid']);
        if ($buyplayer){
          $this->server->addEvents($buyplayer->id, new Events\LateSendInfoEvents(time()+5, $buyplayer));
        };

        //生成交易流水单
        //id	daytime	type 0:卖方发起，1：买方发起	code 股票代码	amount 成交数量	money 成交金额	create_time 成交时间	buy_uid	buy_sn	sell_uid	sell_sn
        $database->saveListDeal([
          'daytime'=>$order_sell['daytime'],
          'type'=>1,
          'code'=>$order_sell['code'],
          'amount'=>$deal_num,
          'money'=>$deal_money,
          'buy_uid'=>$order_buy['uid'],
          'buy_sn'=>$order_buy['sn'],
          'buy_tax'=>$order_buy['tax'],
          'sell_uid'=>$order_sell['uid'],
          'sell_sn'=>$order_sell['sn'],
          'sell_tax'=>$order_sell['tax'],
        ]);
        //修改卖家金额
        $database->ChangeMoney($order_sell['uid'], $deal_money-$order_sell['tax'], 'Add');
        $database->UnlockFreezeMoney($order_buy['uid'], $deal_money);
        //累计需要更新双方 KeepStore，售卖成功
        $database->KeepBuySuccess($order_buy['uid'], $order_sell['code'], $deal_num, $deal_money, $order_sell['daytime']);   //买方的库存变化是 股票数增加，买入价格累加
        $database->KeepSellSuccess($order_sell['uid'], $order_sell['code'], $deal_num, $deal_money);                 //卖方库存变化是股票减少，买入价格降低
      }else{
        $end=true ;
      }
    }while(!$end);
    //写入买订单，并提示标记status和surplus。
    $database->saveListSell($order_sell);
    
    //*****向系统挂消息，刷新买方卖方的数据。

    //$database->commitTrans(); //提交
    return true;
  }
  /***********************
   * 计算交易税费
   * 1.印花税
   * 2.佣金
   * 3.手续费
   */ 
  public function calc_tax($type='buy',$deal_money=0){
    $tax=0;
    if ($type=="buy"){
      //印花税
      if (__BUY_TAX_PRECENT__){
        $tax+= intval($deal_money * __BUY_TAX_PRECENT__);    //计算买家印花税
      }else{
        $tax+= 0;
      }
      //其他税叠加
      


    }elseif ($type=="sell"){
      //印花税
      if (__SELL_TAX_PRECENT__){
        $tax+= intval($deal_money * __SELL_TAX_PRECENT__);    //计算卖家印花税
      }else{
        $tax+=0;
      }
      //其他税叠加



    }
    return $tax;
  }

  /***********************
   * 清算未成交订单
   */
  public function clearOrder(){
    global $database;
    ////////////////清理买单
    $end=false;
    do{
      //1按顺序读取一单
      //2执行相应处理
      //3直到读取不到订单
      $order_buy=$database->fetchListBuy();
      if ($order_buy){
        $database->KeepBuyFail($order_buy);
      }else{
        $end=true ;
      }
    }while(!$end);
    ////////////////////清理卖单
    $end=false;
    do{
      //1按顺序读取一单
      //2执行相应处理
      //3直到读取不到订单
      $order_sell=$database->fetchListSell();
      if ($order_sell){
        $database->KeepSellFail($order_sell);
      }else{
        $end=true ;
      }
    }while(!$end);

  }
  /***********************
   * 单独清退买单
   */
  public function closeBuyOrder($order){
    global $database;
    
  }
  /***********************
   * 单独清退卖单
   */
  public function closeSellOrder($order){
    global $database;
    
  }


}