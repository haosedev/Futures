<?php 
/**
 * GameServer
 * 
 * 连接管理
 * 消息队列管理
 */
namespace Server;
use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Server\Events;
use \Server\Messages;


class GameServer {
  
  public $id;
  public $maxPlayers;
  public $server;
  public $ups;
  public $players = array();
  public $EventsQueues = array();
  public $outgoingQueues = array();
  public $itime = null;     //当前时间
  
  public function __construct($id, $maxPlayers, $websocketServer) {
      
    $this->id = $id;
    $this->maxPlayers = $maxPlayers;
    $this->server = $websocketServer;
    $this->ups = 5;
    $this->playerCount = 0;
    $this->players = array();
    $this->EventsQueues = array();
    $this->outgoingQueues = array();
    $this->itime=time();
    
    $self = $this;

    $this->StockServer = new StockData($self);      //数据服务器
    $this->TransServer = new Transaction($self);    //交易服务器
    
    $this->onPlayerConnect(function ($player)use($self) {

    });
      
    $this->onPlayerEnter(

      function($player) use ($self){
        //echo $player->name . " has joined ". $self->id."\n";
        debuglog($player->name ."(".$player->id.") has joined ". $self->id);
    
        if(!$player->hasEnteredGame) {
            $self->incrementPlayerCount();
        }
        // $moveCallback = function($x, $y) use($player, $self){
            // echo $player->name . " is moving to (" . $x . ", " . $y . ")\n";
    
            // $player->forEachAttacker(function($mob) use($player, $self) {
                // $target = $self->getEntityById($mob->target);
                // if($target) 
                // {
                    // $pos = $self->findPositionNextTo($mob, $target);
                    // if($mob->distanceToSpawningPoint($pos['x'], $pos['y']) > 50) 
                    // {
                        // $mob->clearTarget();
                        // $mob->forgetEveryone();
                        // $player->removeAttacker($mob);
                    // } 
                    // else 
                    // {
                        // $self->moveEntity($mob, $pos['x'], $pos['y']);
                    // }
                // }
            // });
        // };
    
        // $player->onMove($moveCallback);
        // $player->onLootMove($moveCallback);
    
        $player->onExit(function() use($self, $player){
          //echo $player->name . " has left the System.\n";
          if ($player->isLogin==true){
            debuglog($player->userInfo['nickname'] ."(".$player->id.") has left the System");
          }else{
            debuglog($player->name ."(".$player->id.") has left the System");
          }
          
          
          $self->removePlayer($player);
          $self->decrementPlayerCount();
  
          if(isset($self->removedCallback)) {
              call_user_func($self->removedCallback);
          }
        });
    
        // if(isset($self->addedCallback)) {
          // call_user_func($self->addedCallback);
        // }
      }
    );
  }  
  //
  public function run(){
    $self = $this;

    Timer::add(1/$this->ups, function() use ($self) {
        //$self->processGroups();
        $self->processEvents();     //任务事件
        $self->processQueues();     //广播事件
    });
    
    debuglog($this->id." Server is created capacity: ".$this->maxPlayers." players");
  }
  
  public function setUpdatesPerSecond($ups) {
    $this->ups = $ups;
  }
  
  public function onInit($callback) {
    $this->initCallback = $callback;
  }

  public function onPlayerConnect($callback) {
    $this->connectCallback = $callback;
  }
  
  public function onPlayerEnter($callback) {
    $this->enterCallback = $callback;
  }
  
  public function onPlayerAdded($callback) {
    $this->addedCallback = $callback;
  }
  
  public function onPlayerRemoved($callback) {
    $this->removedCallback = $callback;
  }
  //新增用户
  public function addPlayer($player) {
    $this->players[$player->id] = $player;
    $this->outgoingQueues[$player->id] = array();
  }
  //移除用户
  public function removePlayer($player) {  
    unset($this->players[$player->id], 
          $this->outgoingQueues[$player->id]);
          
    $player->destroy();     //摧毁
  }
  //查找某个uid的player是否在线
  public function GetPlayerByUid($uid) {
    foreach($this->players as $v){
      if ($v->userInfo['id']==$uid){
        return $v;
      }
    }
    return null;
  }
  //为所有用户重新发送用户数据
  public function reSendPlayerUserInfo() {
    foreach($this->players as $v){
      if ($v->userInfo['id']>0){
        $v->SendUserAllInfo();
      }
    }
  }
  //对某个player发送消息
  public function pushToPlayer($player, $message) {
    if($player && isset($this->outgoingQueues[$player->id])) {
      $this->outgoingQueues[$player->id][] = $message->serialize();
    } else {
      debuglog("pushToPlayer: player was undefined");
    }
  }
  //广播，对所有人发送消息
  public function pushBroadcast($message, $ignoredPlayer = null) {
    foreach($this->outgoingQueues as $id=>$item){
      if($id != $ignoredPlayer){
        $this->outgoingQueues[$id][] = $message->serialize();
      }
    }
  }
  //消息队列处理
  public function processQueues() {
    foreach($this->outgoingQueues as $id=>$item){
      if($this->outgoingQueues[$id]) {
        if (isset($this->server->connections[$id])) {
          $connection = $this->server->connections[$id];
          $connection->send(json_encode($this->outgoingQueues[$id]));
        }else{
          debuglog("connect is closed and can't send message !");
        }
        $this->outgoingQueues[$id] = array();
      }
    }
  }
  //添加事件队列
  public function addEvents($id, $event) {
    $this->EventsQueues[$id][] = $event;
  }
  //**事件队列处理
  public function processEvents() {
    $nowtime=time();
    if ($this->itime<>$nowtime){
      $this->itime=$nowtime;
      foreach($this->EventsQueues as $id=>$item){
        if($this->EventsQueues[$id]) {
          $isChange=false;
          //**这里逐条Event处理
          foreach($this->EventsQueues[$id] as $k=>$v){ 
            //处理event
            if ($this->EventsQueues[$id][$k]->trigger($nowtime)){
              unset($this->EventsQueues[$id][$k]);
              //重新排序
              $isChange=true;
            }
          }
          if ($isChange){
            $this->EventsQueues[$id] = array_values($this->EventsQueues[$id]);
          }
        }
      }
    }
  }
  public function forEachPlayer($callback) {
    foreach($this->players as $player){
      call_user_func($callback, $player);
    }
  }
  
  public function getPlayerCount() {
    $count = 0;
    foreach($this->players as $p => $player){
      if($this->players->hasOwnProperty($p)){
        $count += 1;
      }
    }
    return $count;
  }
  
  public function setPlayerCount($count) {
    $this->playerCount = $count;
  }
  //
  public function incrementPlayerCount() {
    $this->setPlayerCount($this->playerCount + 1);
  }
  //
  public function decrementPlayerCount() {
    if($this->playerCount > 0) {
      $this->setPlayerCount($this->playerCount - 1);
    }
  }
  
  public function onConnect($connection) {
    $connection->onWebSocketConnect = array($this, 'onWebSocketConnect');
  }
  
  public function onWebSocketConnect($connection) {
      
  }
}
