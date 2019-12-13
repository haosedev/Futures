<template>
  <div class="board">
      <table>
          <tr class="title">
            <td>▼</td>
            <td>代码</td>
            <td>名称</td>
            <td>涨幅</td>
            <td>现价</td>
            <td>涨跌</td>
            <td>今开</td>
            <td>最高</td>
            <td>最低</td>
            <td>昨收</td>
          </tr>
        <tr v-for="(vo, index) in datalist" :key="index">
          <td class="td1">{{index+1}}</td>
          <td class="td2">{{vo.code}}</td>
          <td class="td3">{{vo.name}}</td>
          <td class="td4" :class="vo.color">{{vo.ud_precent|toYuan}}%</td>
          <td class="td5" :class="vo.color">{{vo.now_price|toYuan}}</td>
          <td class="td6" :class="vo.color">{{vo.ud_price|toYuan}}</td>
          <td class="td7" :class="vo.color">{{vo.start_price|toYuan}}</td>
          <td class="td8" :class="vo.color">{{vo.max_up|toYuan}}</td>
          <td class="td9" :class="vo.color">{{vo.max_down|toYuan}}</td>
          <td class="td10">{{vo.yestoday_price|toYuan}}</td>
        </tr>
      </table>
  </div>
</template>
<script>
  import {_} from 'vue-underscore';
  export default {
    name : 'Main',
    data() {
      return {
        websock: null,
        handlers:[],
        datalist:[],
      }
    },
    created() {
      this.initWebSocket();
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
        this.handlers[this.cons.Types.Messages.OFFER] = this.receiveOffer;
      },
      websocketonopen(){ // 连接建立之后执行send方法发送数据
        let actions = [1,'Lin']; 
        this.websocketsend(JSON.stringify(actions));
      },
      websocketonerror(){ // 连接建立失败重连
        this.initWebSocket();
      },
      websocketonmessage(e){ // 数据接收
        //const redata = JSON.parse(e.data);
        this.receiveMessage(e.data);
      },
      websocketsend(Data){ // 数据发送
        this.websock.send(Data);
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
      }
    },
  }
</script>
<style scope>
  html{
    background-color: #000;
  }
  table{
    width:90%;
    margin:0 auto;
  }
  .title{
    color:#ccc;
    font-size:17px;
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