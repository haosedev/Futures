<?php 
/**
 * 数据管理器、发生器
 * 
 */
namespace Server;
use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Server\Messages;


class StockData {  
  public $server;     //GameServer接口
  public $ups;        //更新频率(每秒几次)
  public $daytime;      //当时的daykey
  public $TodayData;      //当日盘中数据
  public $MarketAllData;    //大盘统计
  public $lastMorningTime;  //上次开盘时间，开盘后保存时间，防止重复操作
  public $lastCloseTime;    //上次收盘时间，收盘后保存时间，防止重复操作
  
  public function __construct($GameServer) {
    
    $this->server = $GameServer;
    $this->isOfferTime=false;   //是否开盘
    $this->ups=2;         
    
    $self = $this;

    Timer::add(1/$this->ups, function() use ($self) {
      $self->TimeControl();           //时间事件切换（开盘，收盘）
      $self->processVirtualOffer();   //虚拟交易事件
    });
    
    //服务器启动时，把数据库数据读取到缓存中。
    global $db, $datebase;
    $dateKey = $datebase->getDataKey();
    $this->TodayData = $datebase->getTodayData($dateKey);

    //
    $this->resetMarket();   //大盘重新统计
    //判断当前数据是否处于
    $now_key=date(__DAYTIME_KEY__);
    $now_min=date("i");
    //状态：-1刚启动系统，0收盘状态，1开盘中准备中，2交易状态，3收盘准备中。
    if ($this->isInStartHour()){
      if (($now_key===$dateKey)&&($now_min<55)){
        //当前还处于报价状态，继续当前开盘状态
        $this->doMorning(); //开盘
      }else if($now_key>$dateKey){
        //这里有两种状态。1是否开盘时间。
        if ($now_min<55){
          //新一日开盘
          $this->doMorning(true); //重新开盘
        }
      }else{
        //开盘时间内时间大于55分
        debuglog("Market is Time to rest !!");
        $this->status = 0;
        $this->isOfferTime=false;
      }
    }else{
      //非开盘时间
      debuglog("Market is Time to rest !!");
      $this->status = 0;
      $this->isOfferTime=false;
    }
  }

  public function setUpdatesPerSecond($ups) {
    $this->ups = $ups;
  }
  //开盘
  public function doMorning($isNewDay=false){
    $now_key=date(__DAYTIME_KEY__);
    $this->daytime=$now_key;    //开盘后记录daytime
    if ($this->lastMorningTime!=$now_key){
      $this->status = 1;
      if ($isNewDay){ //新一日重新开盘
        foreach($this->TodayData as $k=>$v){
          $this->TodayData[$k]['yestoday_price']=$v['now_price'];
          $this->TodayData[$k]['start_price']=$v['now_price'];
          $this->TodayData[$k]['daytime']=$now_key;
          $this->TodayData[$k]['ud_price']=0;
          $this->TodayData[$k]['ud_precent']=0;
          $this->TodayData[$k]['max_up']=intval($v['now_price']*1.1);     //涨停
          $this->TodayData[$k]['max_down']=intval($v['now_price']*0.9);   //跌停
        }
      }
      $this->lastMorningTime=$now_key;
      $this->status = 2;
      $this->isOfferTime=true;
      //**当前这波的开盘时间，收盘时间，这个是当前日的第几小时的数据。
      $this->resetMarket();   //大盘统计

      debuglog("Market Offer starting !!");
      
      ////TEST
      //$this->checkCodePrice(10002,1);
    }
  }
  //收盘
  public function doClose(){
    $this->isOfferTime=false;
    $now_key=date(__DAYTIME_KEY__);
    if ($this->lastCloseTime!=$now_key){  //不是保存状态时才操作
      $this->status = 3;
      
      global $db, $datebase;
      //收盘写入数据库，当前数据保留当明日开盘
      foreach($this->TodayData as $k=>$v){
        $datebase->saveDataSingle($now_key,$v);
      }
      //写入大盘数据
      $datebase->saveMarketAll($now_key,$this->MarketAllData);
      
      //清理所有未完成的挂单
      $this->server->TransServer->clearOrder($order);   
      //每次收盘的时候，还要对KeepStock表的freeze进行解冻，清除buyday标记和freeze数量
      $this->ClearFreeze();

      //所有登陆用户重新执行
      $this->server->reSendPlayerUserInfo();

      $this->lastCloseTime=$now_key;
      $this->status = 0;
      debuglog("Market Offer closing !!");
    }
  }
  //群发当前盘面数据给某人(Player模块有使用到)
  public function SendAllDataToPlayer($player){
    foreach($this->TodayData as $v){
      $this->server->pushToPlayer($player, new Messages\MarketOffer($v));
    }
    //**附带发送大盘数据
    $this->server->pushToPlayer($player, new Messages\MarketInfo($this->getMarketInfo()));
  }
  //开盘群发所有数据给所有人
  private function BroadcastAllData(){
    foreach($this->TodayData as $v){
      $this->server->pushBroadcast(new Messages\MarketOffer($v));
    }
    //附带发送大盘数据
    $this->server->pushBroadcast(new Messages\MarketInfo($this->getMarketInfo()));
  }  
  //广播单条变化
  private function BroadcastSingle($data){
    //$this->server->pushBroadcast(new Messages\MarketOffer($data));
    $this->server->pushBroadcast(new Messages\MarketChange($data));
    //**附带发送大盘数据
    $this->server->pushBroadcast(new Messages\MarketInfo($this->getMarketInfo()));
  }
  
  //开收盘切换
  private function TimeControl(){
    if ($this->isInStartHour()){
      //假设每小时开盘一次，收盘一次，5分钟修正，整点开盘，55分收盘
      $nowTime=date('is');//分钟秒  0000  0分0秒
      if ($nowTime==="0000"){   //每小时头开盘0000
        //开盘
        $this->doMorning(true);  //准点触发新开盘
        $this->BroadcastAllData();
      }else if ($nowTime==="5500"){   //每小时尾收盘5500
        //收盘
        $this->doClose();  //准点触发收盘
      }
    }
  }
  //自动事件队列处理
  private function processVirtualOffer() {
    //
    if ($this->isOfferTime){    //交易时间
      $tick=1;  //超过1秒可以更新
      //**随机选择一只做调整
      //**随机根据股票数量产生轮空时间计算公式
      //**每只股票自我计算概率是否更新，更新范围大小，如果更新范围是0也就不更新
      //**
      foreach($this->TodayData as $k=>$v){
        
        if (empty($v['lastChange'])){
          $this->TodayData[$k]['lastChange']=time();
          continue;
        }
        $chance=time()-$v['lastChange'];
        //
        $canDoChange=($chance>Rand(0,3))?true:false;
        if ($canDoChange){
          $this->TodayData[$k]['lastChange']=time();    //更新时间
          //单次涨跌幅度 0% - 0.5%   -50 ~ 50
          $cPrecent=(Rand(0,100)-50)/10000;   //
          $price_change=$v['start_price']*$cPrecent;     //计算并自动取整（小于
          if ($price_change!=0){
            if ($price_change>0)
              $price_change=ceil($price_change);    //向上取整，小数位稳进
            else
              $price_change=floor($price_change);    //向下取整，小数位稳进
            $new_price=$v['now_price']+$price_change;
            
            //还要判断涨跌停判断
            if ($new_price>$v['max_up']) $new_price=$v['max_up'];
            else if ($new_price<$v['max_down']) $new_price=$v['max_down'];
            
            if ($new_price!=$this->TodayData[$k]['now_price']){
              
              $this->TodayData[$k]['now_price']=$new_price;
              //$this->TodayData[$k]['ud_price']+=$price_change;   //这里算法有bug
              $this->TodayData[$k]['ud_price']=$new_price-$v['start_price'];
              
              $ud_precent=($this->TodayData[$k]['ud_price']/$v['start_price'])*10000;
              //涨跌百分比
              if ($ud_precent>0)
                $ud_precent=ceil($ud_precent);    //向上取整，小数位稳进
              else
                $ud_precent=floor($ud_precent);    //向下取整，小数位稳进
              
              $this->TodayData[$k]['ud_precent']=$ud_precent;
              //重新计算大盘数据
              $this->calcMarket();  //大盘统计

              //SendChangetoAll **这里可能要放到最后广播单品
              $this->BroadcastSingle($this->TodayData[$k]);
              

              //**计算单品交易数据

              

              
            }
          }
        }
      }
    }
  }
  //大盘开盘统计
  private function resetMarket(){
    //$this->MarketAllData
    //大盘统计内容  全量昨日收盘，全量开盘价格，全量当前价，时间，涨跌，涨幅，
    $this->MarketAllData['yestoday_price']=array_sum(array_column($this->TodayData, 'yestoday_price'));
    $this->MarketAllData['start_price']=array_sum(array_column($this->TodayData, 'start_price'));
    $this->MarketAllData['now_price']=array_sum(array_column($this->TodayData, 'now_price'));
    $this->MarketAllData['daytime']=date(__DAYTIME_KEY__);
    $this->MarketAllData['ud_price']=0;
    $this->MarketAllData['ud_precent']=0;
  }
  //大盘瞬时统计
  private function calcMarket(){
    //$this->MarketAllData
    //大盘统计内容  全量昨日收盘，全量开盘价格，全量当前价，时间，涨跌，涨幅，
    //$this->MarketAllData['yestoday_price']=array_sum(array_column($this->TodayData, 'yestoday_price'));
    //$this->MarketAllData['start_price']=array_sum(array_column($this->TodayData, 'start_price'));
    $this->MarketAllData['now_price']=array_sum(array_column($this->TodayData, 'now_price'));
    $this->MarketAllData['ud_price']=$this->MarketAllData['now_price']-$this->MarketAllData['start_price'];
    
    $ud_precent=($this->MarketAllData['ud_price']/$this->MarketAllData['start_price'])*10000;
    //涨跌百分比
    if ($ud_precent>0)
      $ud_precent=ceil($ud_precent);    //向上取整，小数位稳进
    else
      $ud_precent=floor($ud_precent);    //向下取整，小数位稳进
    $this->MarketAllData['ud_precent']=$ud_precent;
  }
  //大盘统计输出
  public function getMarketInfo(){
    $arr = $this->MarketAllData;
    $arr['nowHour']=date('H');   //当前开盘时段
    $arr['isOfferTime']=$this->isOfferTime;
    //******************这里新加还未加入设置
    if ($this->isOfferTime)
      $arr['status']=1;        //当前盘口状态：开市1，休市0
    else
      $arr['status']=0;
    return $arr;
  }
  /*
   * **收盘时对未完成订单进行撤销和资金等解冻。
   * 
   */ 
  public function ClearFreeze(){
    global $db, $datebase;
    $datebase->ClearFreeze();
  }
  /*
   * 检测股票代码和价格是否符合当前规范
   * 自动进行涨停和跌停修正
   * return [1,$code,$price]   or false
   */ 
  public function checkCodePrice($code, $price){
    if ($this->isOfferTime==false) return false;
    //
    $arr = array_column($this->TodayData, 'code');
    $found_key = array_search($code, $arr);

    if ($found_key!==false){
      if ($this->TodayData[$found_key]['code']==$code){
        //涨停跌停修正
        if ($price > $this->TodayData[$found_key]['max_up']) $price = $this->TodayData[$found_key]['max_up'];     //当价格大于当日封停最大值时直接取值最大值。
        if ($price < $this->TodayData[$found_key]['max_down']) $price = $this->TodayData[$found_key]['max_down'];   //当价格小于当日跌停最小值时直接取值最小值。
        return [1,$code,$price];
      }
    }
    //
    return false; //返回错误0，表示股票不存在
  }
  /*
   * 判断当前是否属于开盘规则时间（小时）
   * 
   */
  private function isInStartHour() {
    $nowHour=date('H');
    $start_hour=['09','10','11','13','14','15','16','18','19','20']; //每日内允许开盘的小时标记
    if (in_array($nowHour, $start_hour)){
      //假设每小时开盘一次，收盘一次，5分钟修正，整点开盘，55分收盘
      return true;
    }
    return false;
  }
}
