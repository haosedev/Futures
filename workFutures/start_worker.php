<?php 
/**
 * 
 *
 */
use \Workerman\Worker;
use \Server\Utils;
use \Server\Player;
use \Server\GameServer;
use \Server\Datebase;

// 自动加载类
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Server/Constants.php';

Worker::$stdoutFile = './log/stdout'.time().'.log';

$global_uid = 0;
// 创建一个文本协议的Worker监听2347接口
$ws_worker = new Worker("Websocket://0.0.0.0:8050");
$ws_worker->name = 'worktestWorker';
// 只启动1个进程，这样方便客户端之间传输数据
$ws_worker->count = 1;

//服务启动时
$ws_worker->onWorkerStart = function($ws_worker){
    // 将db实例存储在全局变量中(也可以存储在某类的静态成员中)
    global $db, $datebase;
    //$db = new \Workerman\MySQL\Connection('localhost', '3306', 'Futures', 'Bushiba520', 'Futures');
    $datebase = new Datebase('Futures', 'Bushiba520', 'Futures');
    
    $gameServer = new GameServer('world1', 1000, $ws_worker);
    $gameServer->run();
    
    $ws_worker->gameServer[] = $gameServer;
    
};


// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
$ws_worker->onConnect = function($connection) use($ws_worker, $global_uid){
    
    $gameServer = Utils::detect($ws_worker->gameServer, function($gameServer)use($ws_worker) {
        return $gameServer->playerCount < 1000;
    });
    if($gameServer && isset($gameServer->connectCallback)) {
        call_user_func($gameServer->connectCallback, new Player($connection, $gameServer));
    }
};

// 当客户端发送消息过来时，转发给所有人 **被Player.php模块覆盖
$ws_worker->onMessage = function($connection, $data) use($ws_worker){
    foreach($ws_worker->connections as $conn) {
       $conn->send("user[{$connection->uid}] said: $data");
    }
};
// 当客户端断开时，广播给所有客户端  **被Player.php模块覆盖
$ws_worker->onClose = function($connection) use($ws_worker){

    foreach($ws_worker->connections as $conn) {
        $conn->send("user[{$connection->uid}] logout");
    }
};

function debuglog($msg){
    echo date('Y.m.d H:i:s',time())." - ".$msg."\n";
}



//**for test echo
function p($item){
    if (isset($item)){
        if(is_array($item)){
          var_dump($item);
        }else if (is_object($item)){
          echo "typeof Class:".get_class($item)."\n";   
        }else{
          echo $item."\n";
        }
    }else{
        echo "Error p():have no item\n";
    }
}