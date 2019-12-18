<template>
  <div>
    <div class="board">
        <div class="bar head">
          开盘序列：<span class="td2">{{marketInfo.daytime}}</span> ,盘序：<span class="td2">{{marketInfo.nowHour}}</span>
        </div>
        <table class="table_head">
          <thead>
            <tr>
              <th width="30">▼</th>
              <th width="80">代码</th>
              <th width="100">名称</th>
              <th width="80">涨幅</th>
              <th width="70">现价</th>
              <th width="70">涨跌</th>
              <th width="70">今开</th>
              <th width="70">最高</th>
              <th width="70">最低</th>
              <th width="70">昨收</th>
              <th></th>
            </tr>
          </thead>
        </table>
        <table class="table_body">
            <tbody>
              <tr v-for="(vo, index) in datalist" :key="index">
                <td width="30"  class="td1">{{index+1}}</td>
                <td width="80"  class="td2">{{vo.code}}</td>
                <td width="100" class="td3">{{vo.name}}</td>
                <td width="80"  class="td4" :class="vo.color">{{vo.ud_precent|toYuan}}%</td>
                <td width="70"  class="td5" :class="vo.color">{{vo.now_price|toYuan}}</td>
                <td width="70"  class="td6" :class="vo.color">{{vo.ud_price|toYuan}}</td>
                <td width="70"  class="td7" :class="vo.color">{{vo.start_price|toYuan}}</td>
                <td width="70"  class="td8 red">{{vo.max_up|toYuan}}</td>
                <td width="70"  class="td9 green">{{vo.max_down|toYuan}}</td>
                <td width="70"  class="td10">{{vo.yestoday_price|toYuan}}</td>
                <td></td>
              </tr>
            </tbody>
          </table>
          <div class="bar bottom">
            状态：<span class="td2">{{marketInfo.status}}</span>，大盘指数：<span :class="marketInfo.color">{{marketInfo.now_price|toYuan}}</span>，涨跌：<span :class="marketInfo.color">{{marketInfo.ud_price|toYuan}}</span>，涨幅：<span :class="marketInfo.color">{{marketInfo.ud_precent|toYuan}}%</span>
          </div>
    </div>
  </div>
</template>
<script>
  import {_} from 'vue-underscore';
  export default {
    name : 'Main',
    data() {
      return {
        websock: null,
        pingTimer:null,
        pingLastTime:0, //延时
        handlers:[],
        datalist:[],
        marketInfo:{},
      }
    },
    created() {
      var self = this;
      this.initWebSocket();
      this.pingTimer = setInterval(function(){
        if (self.websock.readyState===1) {
          self.pingLastTime+=3;
          if (self.pingLastTime>40){
            self.pingLastTime=0;
            self.SendPing();
          }
        }else if (self.websock.readyState===3){
          self.initWebSocket(); //重连
        }
      },3000)
    },
    destroyed() {
      this.websock.close() // 离开路由之后断开websocket连接
    },
    methods: {
      initWebSocket(){ // 初始化weosocket
        const wsuri = "ws://47.99.245.128:8050";
        this.websock = new WebSocket(wsuri);
        this.websock.onmessage = this.websocketonmessage;
        this.websock.onopen = this.websocketonopen;
        this.websock.onerror = this.websocketonerror;
        this.websock.onclose = this.websocketclose;
        //
        this.handlers[this.cons.Types.Messages.SYSTEM] = this.receiveSystem;
        this.handlers[this.cons.Types.Messages.WELCOME] = this.receiveWelcome;
        this.handlers[this.cons.Types.Messages.MESSAGE] = this.receiveMsg;
        this.handlers[this.cons.Types.Market.INFO] = this.receiveInfo;
        this.handlers[this.cons.Types.Market.OFFER] = this.receiveOffer;
      },
      websocketonopen(){ // 连接建立之后执行send方法发送数据
        let actions = [this.cons.Types.Messages.HELLO,'Lin']; 
        this.websocketsend(actions);
      },
      websocketonerror(){ // 连接建立失败重连
        this.initWebSocket();
      },
      websocketonmessage(e){ // 数据接收
        //const redata = JSON.parse(e.data);
        this.receiveMessage(e.data);
      },
      websocketsend(Data){ // 数据发送
        this.websock.send(JSON.stringify(Data));
      },
      websocketclose(e){  // 关闭
        console.log('断开连接',e);
      },
      //自动处理
      receiveMessage: function(message) {
          var data, action;
          //console.log("data: " + message);
          data = JSON.parse(message);
          if(data instanceof Array) {
              if(data[0] instanceof Array) {
                  // Multiple actions received
                  this.receiveActionBatch(data);
              } else {
                  // Only one action received
                  this.receiveAction(data);
              }
          }
      },
      receiveAction: function(data) {
          var action = data[0];
          if(this.handlers[action] && _.isFunction(this.handlers[action])) {
              this.handlers[action].call(this, data);
          } else {
              console.log("Unknown action : " + action);
          }
      },
      receiveActionBatch: function(actions) {
          var self = this;
          _.each(actions, function(action) {
             self.receiveAction(action);
          });
      },
      //自定义处理
      receiveSystem: function(data) {
          var msg = data[1];     
          console.log('SYSTEM',data);
      },
      receiveWelcome: function(data) {
          var id = data[1],
              name = data[2];     
          console.log('WELCOME',data);
      },
      receiveMsg: function(data) {
          var msg = data[1];     
          console.log('MESSAGE',data);
      },
      receiveOffer: function(data) {
          var msg = data[1];     
          //console.log('OFFER',msg);
          this.ChangeOffer(msg);
      },
      receiveInfo: function(data) {
          var msg = data[1];     
          console.log('INFO',msg);
          if (msg['ud_price']>0) msg['color']='red';
          else if (msg['ud_price']<0) msg['color']='green';
          if (msg['isOfferTime']) msg['status']="开盘";
          else msg['status']="收盘";
          this.marketInfo=msg;
          //this.ChangeOffer(msg);
      },
      //
      ChangeOffer: function(data){
        //查找列表
        var isFindID=-1;
        this.datalist.forEach(function(v,i,arr){
          if (v['code']==data['code']){
            isFindID=i;
          }
        })
        if (data['ud_price']>0) data['color']='red';
        else if (data['ud_price']<0) data['color']='green';
        if (isFindID===-1){
          this.datalist.push(data);
        }else{
          //替换
          this.datalist.splice(isFindID,1,data);
        }
        //***变化的这条需要给个动画
      },
      //**定时发送Ping */
      SendPing:function (){
        let actions = [this.cons.Types.Ping]; 
        this.websocketsend(actions);
      },
    },
  }
</script>
<style scope>
  html{
    background-color: #000;
  }
  .board{
    border:1px solid #ccc;
    width: 780px;
  }
  .board .bar{
    color:#ccc;
    padding:3px 5px;
    text-align: left;
    font-size:14px;
  }
  .board .head{
    border-bottom:1px solid #ccc;
  }
  .board .bottom{
    border-top:1px solid #ccc;
  }
  table{
    width:100%;
    margin:0 auto;
  }
  .table_head{
    border-bottom:1px solid #ccc;
    padding:3px 0;
  }
  .table_head th{
    vertical-align: baseline;
    color:#ccc;
    font-size:16px;
  }
  .table_body{
    padding:5px 0;
    height:500px;
    display:block;
    overflow-y:scroll;
  }
  .table_body::-webkit-scrollbar {
    /*滚动条整体样式*/
    width : 10px;  /*高宽分别对应横竖滚动条的尺寸*/
    height: 1px;
  }
  .table_body::-webkit-scrollbar-thumb {
    /*滚动条里面小方块*/
    box-shadow   : inset 0 0 5px rgba(0, 0, 0, 0.2);
    background   : #535353;
  }
  .table_body::-webkit-scrollbar-track {
    /*滚动条里面轨道*/
    box-shadow   : inset 0 0 5px rgba(0, 0, 0, 0.2);
    background   : #b1afaf;
  }
  .table_body td{
    vertical-align: baseline;
  }
  .td2,.td3{
    color:#fffd81;
  }
  .td1,.td4,.td5,.td6,.td7,.td8,.td9,.td10 {
    color:#ccc;
  }
  .red{
    color:#ff5c53;
  }
  .green{
    color:#63fd52;
  }
</style>