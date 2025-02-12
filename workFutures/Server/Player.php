<?php 
/**
 * 
 */
namespace Server;
use \Workerman\Lib\Timer;
use \Server\Events;

error_reporting(E_ALL^E_NOTICE);

class Player {
  
  public $hasEnteredSystem = FALSE;
  public $disconnectTimeout = 0;
  public $connection;
  public $server;
  public $id=0;
  public $sn_id=null;       //数据序列
  public $isLogin=FALSE;    //登录状态
  public $userInfo;         //用户数据
  public $KeepStock=[];     //持仓数据
  
  public function __construct($connection, $GameServer){
      
    $this->server = $GameServer;
    $this->connection = $connection;
    
    $this->id=$this->connection->id;        //直接使用id
    $this->name="NONAME";
    //$this->hasEnteredSystem = FALSE;
    $this->isDead = FALSE;
    $this->KeepStock = [];
    $this->disconnectTimeout = 0;
    $this->connection->onMessage = array($this, 'onClientMessage');
    $this->connection->onClose = array($this, 'onClientclose');
    $this->connection->onWebSocketConnect = function($con) {
        //$con->send(json_encode(array(TYPES_MESSAGES_HELLO)));
        //$con->send("go");
    };
    //**添加用户（本来应该在认证完成时候添加的）
    //$this->server->addPlayer($this);
  }
  
  public function onClientMessage($connection, $data){
      
    $message = json_decode($data, true);
    if (is_array($message)){
      $action = $message[0];
    }else{
      debuglog(" Error Message !");
      return;
    }
    if((!$this->hasEnteredSystem) && ($action !== TYPES_MESSAGES_HELLO)){
      $this->connection->close(json_encode(array(TYPES_MESSAGES_SYSTEM,"Invalid handshake message: ". $data)));
      return;
    }
    $this->resetTimeout();
    
    if($action === TYPES_MESSAGES_HELLO) {
        
      //$name = preg_replace('/^( |\s)*|( |\s)*$/', '', $message[1]);       //头尾去空格
      $this->name =  "ANONYMOUS".Rand(100000,999999) ;
      $this->server->addPlayer($this);
      call_user_func($this->server->enterCallback, $this);
      
      $this->connection->send(json_encode(array(TYPES_MESSAGES_WELCOME, $this->id, $this->name)));
      $this->hasEnteredSystem = true;
      //第一次要发全量数据给用户，向系统请求一个全量数据吧
      $this->server->StockServer->SendAllDataToPlayer($this);
        
    }elseif($action == TYPES_LOGIN){
      global $db, $database;
      if (!$this->isLogin){
        //$name = strtolower(preg_replace('/^( |\s)*|( |\s)*$/', '', $message[1])));       //头尾去空格并转小写
        $name = strtolower(preg_replace('/\s+/', '', $message[1]));            //去掉所有空格并转小写
        //$pwd = preg_replace('/^( |\s)*|( |\s)*$/', '', $message[2]));        //头尾去空格 
        $pwd = preg_replace('/\s+/', '', $message[2]);                         //去掉所有空格 

        if ($name){
          $tmp = $database->UserLogin($name, $pwd);
          if ($tmp){
            $this->isLogin=true;
            $tmp['login_time']=time();
            $this->userInfo=$tmp;
            debuglog("UserLogin:[".$this->name."] Service for [".$tmp['nickname']."]");
            //$this->name=$tmp['nickname'];
            $this->pushToPlayer(new Messages\LoginAnswer(true,"登录成功！"));
          }else{
            // //**用户错误
            $this->pushToPlayer(new Messages\LoginAnswer(false,"登录失败！"));
            return;
          }
        }
      }else{
          $this->pushToPlayer(new Messages\LoginAnswer(true,"已是登录状态！"));
      }
      //更新登录信息
      //$this->UpdateUserInfo();
      $this->sendUserInfo();

      //去读取持仓信息
      $this->reFreshKeepStockInfo();
      $this->sendKeepStock();
      $this->sendMyOrder();

      //**********TEST~~~~~~~~~~~
      //$this->checkKeepStock(10002,10);
      //$this->testorder();
      //$this->TryToUseMoney(1);
    }elseif($action == TYPES_LOGOUT){
      //登出
      debuglog("UserLogout:[".$this->userInfo['nickname']."] Logout from [".$this->name."]");
      $this->userInfo=[];
      $this->KeepStock=[];
      $this->isLogin=false;

    }elseif($action == TYPES_PING){
      //接收 ping 信息
      //debuglog($this->name . " SEND PING !");
    }
    elseif($action === TYPES_TRADE_LIST) {
      //客户请求挂单信息
        
    }
    elseif($action === TYPES_TRADE_CANCEL) {
      //客户请求取消挂单
        
    }
    elseif($action === TYPES_TRADE_ENORDER) {

      if ($this->isLogin && ($this->userInfo['id']>0)){
        //p($message);
        // $type =$message[1];    //买入、卖出
        // $code =$message[2];    //代码
        // $price=$message[3];    //价格
        // $num  =$message[4];    //数量
        //客户请求挂单
        //**如何管理挂单数据，比如写入Redis
        //挂单数据包含 买入/卖出，代码，单价，*剩余数量，*单号，原始数量，所有者
        $order['daytime']=date(__DAYTIME_KEY__); //daytime
        $order['types']=$message[1];              //-1卖出，1买入
        $order['code']=$message[2];               //股票代码
        $order['price']=$message[3];              //交易定价 7.08 **需要检测交易价格是否合理，比如大于或者低于最大值
        $order['amount']=$message[4];             //原始数量
        $order['surplus']=$message[4];
        $order['sn'] = '';                        //Utils::makeSN(8);   //利用工具生成序列号
        $order['uid']=$this->userInfo['id'];      //所有者
        $order['tax']=0;

        $this->makeOrder($order); //调用制作订单接口
          
      }else{
          //用户未登录报错
          $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310001)));
          return;
      }
    }
    // else 
    // {
        // if(isset($this->messageCallback)) 
        // {
            // call_user_func($this->messageCallback, $message);
        // }
    // }
  }
  //客户断开
  public function onClientClose(){
    // Timer::del($this->disconnectTimeout);
    // $this->disconnectTimeout = 0;
    if(isset($this->exitCallback)) {
      call_user_func($this->exitCallback);
    }
  }
  //发送全部用户信息
  public function SendUserAllInfo(){
    //p('send uid:'.$this->userInfo['id']);
    $this->refreshUserInfo(); //重新取用户数据
    $this->sendUserInfo();    //发送用户数据
    $this->reFreshKeepStockInfo(); //重新取持仓数据
    $this->sendKeepStock();       //发送持仓数据    
    $this->sendMyOrder();         //查询并发送挂单数据
  }
  //保存用户信息
  private function UpdateUserInfo(){
    if ($this->isLogin && ($this->userInfo['id']>0)){
      //写入数据库
      global $database;
      $database->UpdateUserInfo($this->userInfo);
      return true;
    }
    return false;
  }
  /*
   * 重新获取用户信息
   */
  public function refreshUserInfo(){
    global $database;
    $tmp = $database->refreshUserInfo($this->userInfo['id']);
    if ($tmp){
        $this->userInfo=$tmp;
    }
  }
  /*
   * 发送用户信息
   */
  public function sendUserInfo(){
    $this->pushToPlayer(new Messages\UserInfo($this->userInfo));
  }
  /*
   * 刷新持仓信息
   */
  public function reFreshKeepStockInfo(){
    //重新读取持仓信息
    global $database;
    $this->KeepStock = $database->getUserKeep($this->userInfo['id']);
    //计算当前均价
    foreach($this->KeepStock as $k=>$v){
      $this->KeepStock[$k]['price']=intval($v['buy_money']/$v['num']);  //取整
    }
    //发送持仓数据**以后考虑是否刷新后都要发送（如果未变化呢）
    //$this->sendKeepStock();
  }
  /*
   * 发送持仓信息
   */
  public function sendKeepStock(){
    //******发送给用户（应该批量，一次刷新）
    // foreach($this->KeepStock as $v){
    //   $this->pushToPlayer(new Messages\MarketKeep($v));
    // }
    //p($this->KeepStock);
    $this->pushToPlayer(new Messages\MarketKeep($this->KeepStock));
  }
  /*
   * 发送我当前的未完成挂单信息
   */
  public function sendMyOrder(){
    global $database;
    //**发送我当前的挂单信息
    //1读取我的买单
    //2读取我的卖单
    //3组合在一起发送
    if ($this->isLogin){
      $buyOrders= $database->fetchListBuyByUser($this->userInfo['id'], $this->server->StockServer->daytime);
      foreach($buyOrders as $k=>$v){
        $buyOrders[$k]['mode']=1; //模式：买
      }
      $sellOrders= $database->fetchListSellByUser($this->userInfo['id'], $this->server->StockServer->daytime);
      foreach($sellOrders as $k=>$v){
        $sellOrders[$k]['mode']=-1; //模式：卖
      }
      $orders=array_merge($buyOrders,$sellOrders);
      if ($orders){
        Utils::sortArrByField($orders, 'create_time', true);
      }
      $this->pushToPlayer(new Messages\MyOrders($orders));
      
    }

  }
  /*
   * 发送我当前所有的订单（各种状态）
   * 以及历史订单
   */
  public function sendAllMyOrder(){
    //几种状态：1读取当前未成交挂单，2读取当前所有订单


  }
  /*
   * 成交历史记录
   */
  public function sendDealOrder(){
    //几种状态：当前成交记录，历史成交记录（年月日，买入/卖出，CODE，NAME，NUM，MONEY）


  }

  /*
   * 检测持仓存货，并使用
   * 用于挂单卖
   */
  public function checkKeepStock($order){
    global $database;
    if ($this->isLogin && ($this->userInfo['id']>0)){
      if ($database->KeepWantSell($this->userInfo['id'], $order['code'], $order['amount'])){
        return true;
      }else{
        return false;   //货不够
      }
    }
    return false;
  }
  /*
   * 余额检测并使用。
   * 用于挂单买
   */
  public function TryToUseMoney($money){
    global $database;
    if ($this->isLogin && ($this->userInfo['id']>0)){
      if ($database->KeepWantBuy($this->userInfo['id'], $money)){
        //$this->refreshUserInfo($this->userInfo['id']);
        //$this->sendUserInfo();    //等订单发起后一起通知用户
        return true;
      }else{
        return false;   //钱不够
      }
    }
    return false;
  }
  /*
  * 尝试交易订单
  */
  public function makeOrder($order){
    //客户请求挂单
    //**如何管理挂单数据，比如写入Redis
    // $order['daytime']=date(__DAYTIME_KEY__); //daytime
    // $order['types']=1;        //-1卖出，1买入
    // $order['code']=10001;     //股票代码
    // $order['price']=708;      //交易定价 7.08 **需要检测交易价格是否合理，比如大于或者低于最大值
    // $order['surplus']=100;    //剩余数量
    // $order['money']= 0;    //计划花费金额    只够购买单有效，卖单起始为0
    // $order['sn'] = ''; //Utils::makeSN(8);   //利用工具生成序列号
    // $order['amount']=100;     //原始数量
    // $order['uid']=1;          //所有者

    //检测股票代码，修正异常价格
    $res = $this->server->StockServer->checkCodePrice($order['code'],$order['price']);
    if ($res===false){
        //找不到
        $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310003)));
        return;
    }
    if ($order['price']!=$res[2]) $order['price']=$res[2];   //修正定价

    if ($order['types']==-1){  //卖出
      //检测股票是否有库存，是否足够量
      if ($this->checkKeepStock($order)){
        $order['money']=0;
        $this->server->TransServer->OrderSell($order);
      }else{
        $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310004)));
        return;
      }
    }else if ($order['types']==1){
      //买入
      //检测余额是否足够购买
      $order['money']=$order['price']*$order['amount'];           //计划购入所需金额
      $order['tax']=$this->server->TransServer->calc_tax('buy', $order['money']);      //计算可能需要的税金
      $order['money']+=$order['tax'];                             //加入税金
      
      if ($this->TryToUseMoney($order['money'])){
        $this->server->TransServer->OrderBuy($order);
      }else{
        $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310005)));
        return;
      }
    }else{
      //用户未登录报错
      $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310002)));
      return;
    }
    //$this->server->addEvents($this->id, new Events\LateSendInfoEvents(time()+5, $this));
    $this->SendUserAllInfo();  //挂单者必须收取通知
  }
  //
  public function destroy() {
    //销毁前保存
    //$this->UpdateUserInfo();  保存可能会导致金额与数据库不同而覆盖成脏数据
    debuglog("destroy player(".$this->id.")");
    Timer::del($this->disconnectTimeout);
  }
  
  public function send($message) {
    $this->connection->send($message);
  }
  
  public function pushToPlayer($message){
    $this->server->pushToPlayer($this, $message);
  }
  
  public function onExit($callback){
    $this->exitCallback = $callback;
  }
  
  public function onMessage($callback) {
    $this->messageCallback = $callback;
  }
  //重新设置，无响应时间（每55秒用户无响应向用户发起一次timeout）
  public function resetTimeout() {
    Timer::del($this->disconnectTimeout);
    // 15分钟
    $this->disconnectTimeout = Timer::add(55, array($this, 'timeout'), false);
  }
  
  public function timeout() {
    //debuglog("Player (".$this->id.") was idle for too long then to close connected!");
    //$this->connection->close(json_encode(array(TYPES_MESSAGES_SYSTEM,"timeout: ")));
    // $this->connection->send('timeout');
    debuglog("Player (".$this->id.") was idle for too long!");
  }

  ///////////////////////////for test
  public function testorder(){
    //客户请求挂单
    //**如何管理挂单数据，比如写入Redis
    //挂单数据包含 买入/卖出，代码，单价，*剩余数量，*单号，原始数量，所有者
    $order['daytime']=date(__DAYTIME_KEY__); //daytime
    $order['types']=1;        //0卖出，1买入
    $order['code']=10001;     //股票代码
    $order['price']=708;      //交易定价 7.08 **需要检测交易价格是否合理，比如大于或者低于最大值
    $order['surplus']=100;    //剩余数量
    $order['money']= 0;    //计划花费金额    只够购买单有效，卖单起始为0
    $order['sn'] = ''; //Utils::makeSN(8);   //利用工具生成序列号
    $order['amount']=100;     //原始数量
    $order['uid']=1;          //所有者

    //检测股票代码，修正异常价格
    $res = $this->server->StockServer->checkCodePrice($order['code'],$order['price']);
    if ($res===false){
      //找不到
      $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310003)));
      return;
    }
    if ($order['price']!=$res[2]) $order['price']=$res[2];   //修正定价

    if ($order['types']==-1){  //卖出
      //检测股票是否有库存，是否足够量
      if ($this->checkKeepStock($order)){
        $this->server->TransServer->OrderSell($order);

      }else{
        $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310004)));
        return;
      }
    }else if ($order['types']==1){
      //买入
      //检测余额是否足够购买
      $order['money']=$order['price']*$order['amount'];           //计划购入所需金额
      $order['money']+=$this->server->TransServer->calc_tax('buy',$order['money']);    //加入可能需要的税金
      if ($this->TryToUseMoney($order['money'])){
        $this->server->TransServer->OrderBuy($order);
      }else{
        $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310005)));
        return;
      }
    }else{
      //用户未登录报错
      $this->pushToPlayer(new Messages\SysMsg(ErrorCodeMaker::getErrCodeMsg(310002)));
      return;
    }
  }

}
