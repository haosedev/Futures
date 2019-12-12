<template>
  <div class="test">
      <table>
        <tr v-for="(vo, index) in nowlist" :key="index">
          <td>{{vo.code}}</td>
          <td>{{vo.name}}</td>
          <td>{{vo.yestoday_price}}</td>
          <td>{{vo.start_price}}</td>
          <td>{{vo.now_price}}</td>
          <td>{{vo.ud_price}}</td>
          <td>{{vo.ud_precent}}</td>
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
        data1:[],
        data2:[],
        nowlist:[],
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
          console.log('OFFER',msg);
          this.nowlist=msg;
      },
    },
  }
</script>
<style scope>
</style>