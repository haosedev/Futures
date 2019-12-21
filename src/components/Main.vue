<template>
  <div>
    <div class="board" @contextmenu.prevent="">
        <div class="card_header">
          <div class="clearfix card-title">
            开盘序列：<span class="td2">{{marketInfo.daytime}}</span> ,盘序：<span class="td2">{{marketInfo.nowHour}}</span>
          </div>
        </div>
        <table cellspacing="0" cellpadding="0" class="table_head">
          <colgroup>
            <col width="30">
            <col width="80">
            <col width="100">
            <col width="80">
            <col width="80">
            <col width="80">
            <col width="80">
            <col width="80">
            <col width="80">
            <col width="80">
            <col />
          </colgroup>
          <thead>
            <tr>
              <th class="cell-index is-leaf"><div class="cell">▼</div></th>
              <th class="cell-name is-leaf"><div class="cell">代码</div></th>
              <th class="cell-name is-leaf"><div class="cell">名称</div></th>
              <th class="is-leaf"><div class="cell">涨跌幅</div></th>
              <th class="is-leaf"><div class="cell">涨跌值</div></th>
              <th class="is-leaf"><div class="cell">现价</div></th>
              <th class="is-leaf"><div class="cell">开盘</div></th>
              <th class="is-leaf"><div class="cell">最高</div></th>
              <th class="is-leaf"><div class="cell">最低</div></th>
              <th class="is-leaf"><div class="cell">昨收</div></th>
              <th class="gutter" style="border-right: 0px;background-color:#1d1d23;"></th>
            </tr>
          </thead>
        </table>
        <table cellspacing="0" cellpadding="0" class="table_body">
            <colgroup>
              <col width="30">
              <col width="80">
              <col width="100">
              <col width="80">
              <col width="80">
              <col width="80">
              <col width="80">
              <col width="80">
              <col width="80">
              <col width="80">
            </colgroup>
            <tbody>
              <tr v-for="(vo, index) in datalist" :key="index">
                <td class="cell-index td1"><div class="cell">{{index+1}}</div></td>
                <td class="cell-name td2"><div class="cell">{{vo.code}}</div></td>
                <td class="cell-name td3"><div class="cell">{{vo.name}}</div></td>
                <td class="td4"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.ud_precent|toYuan}}%</span></div></td>
                <td class="td5"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.ud_price|toYuan}}</span></div></td>
                <td class="td6"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.now_price|toYuan}}</span></div></td>
                <td class="td7"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.start_price|toYuan}}</span></div></td>
                <td class="td8 text-up"><div class="cell">{{vo.max_up|toYuan}}</div></td>
                <td class="td9 text-down"><div class="cell">{{vo.max_down|toYuan}}</div></td>
                <td class="td10"><div class="cell">{{vo.yestoday_price|toYuan}}</div></td>
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
        if (data['ud_price']>0) data['color']='text-up';
        else if (data['ud_price']<0) data['color']='text-down';
        if (isFindID===-1){
          this.datalist.push(data);
        }else{
          //替换
          this.datalist.splice(isFindID,1,data);
        }
        console.log(this.datalist);
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
    background-color: #1d1d23;
  }
  body{
    font-family: Avenir,-apple-system,BlinkMacSystemFont,Segoe UI,PingFang SC,Hiragino Sans GB,Microsoft YaHei,Helvetica Neue,Helvetica,Arial,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,sans-serif;
    font-size: .75rem;
    margin:0;
  }
  *, :after, :before {
    box-sizing: border-box;
  }
  .board{
    width: 780px;
    background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQYAAACyAgMAAADdd4l4AAAACVBMV…BoEoWTKMKGz69BIrwOCX47XeElEi/R5Yorrrjiiiuu+K/xG7/P5ne1wRTpAAAAAElFTkSuQmCC);
  }
  .board .bar{
    color:#ccc;
    padding:3px 5px;
    text-align: left;
    font-size:14px;
  }
  .card_header {
    padding: 0 .5rem;
    border-bottom: none;
    line-height: 28px;
    text-align: left;
  }
  .card-title {
    color: #4a8ce2;
    font-size: .88rem;
    font-weight: 700;
  }
  table{
    width:100%;
    margin:0 auto;
    font-size: 14px;
    color: #dde0e4;
  }
  table td {
    background-color: #1d1d23;
  }
  table thead {
    color: #909399;
    font-weight: 500;
  }
  .table td, .table th.is-leaf {
    border-bottom: 1px solid #26262b;
  }
  table td, .table th {
    padding: 12px 0;
    min-width: 0;
    box-sizing: border-box;
    text-overflow: ellipsis;
    vertical-align: middle;
    position: relative;
    text-align: left;
  }
  table th,table td {
    padding: 4px 0;
    border-right: 1px solid hsla(0,0%,100%,.05);
    border-bottom: 1px solid hsla(0,0%,100%,.05);
  }
  .table td, .table th {
    border-bottom: 1px solid hsla(0,0%,100%,.05);
  }
  table th {
    background-color: #15151b;
    padding: 0;
    height: 24px;
    line-height: 24px;
    border-right: 1px solid hsla(0,0%,100%,.05);
  }
  table .cell {
    box-sizing: border-box;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    word-break: break-all;
    line-height: 23px;
    padding-left: 10px;
    padding-right: 10px;
  }
  table .cell {
    padding-left: 8px;
    padding-right: 8px;
    text-align: right;
  }
  .table_head th>.cell {
    position: relative;
    word-wrap: normal;
    vertical-align: middle;
    width: 100%;
  }
  .table_head th>.cell {
    padding-left: 8px;
    padding-right: 8px;
    white-space: nowrap;
    text-overflow: clip;
    font-size: .75rem;
  }
  .cell-index .cell {
    text-align: left;
  }
  .cell-name .cell {
    white-space: normal;
    text-align: left;
  }
  .table_head th.is-leaf {
    border-bottom: 1px solid transparent;
  }

  .table_body{
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
  .transform-value {
    transition: color .1s;
  }
  .color-up, .text-down {
    color: #02a263!important;
  }
  .text-up {
    color: #dd2d2f!important;
  }
  .color-down{
    color:#57d26d!important
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
  .loveColorBlueInBlack{
    color:#4a8ce2;
  }
  .loveColorGrayInBlack{
    color:#b3b3b7;
  }
</style>