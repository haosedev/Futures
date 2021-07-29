/* jshint esversion: 6 */
import {_} from 'vue-underscore';
import orderWindow from '../common/view/orderWindow';

export default {
  name : 'Main',
  components:{ orderWindow, },
  data() {
    return {
      websock: null,
      pingTimer:null,
      pingLastTime:0, //延时
      userLogin:false,
      handlers:[],
      datalist:[],
      keeplist:[],
      userInfo:[],
      marketInfo:{},
      username:'',
      password:'',
      OrderWindowVisible: false,
      MenuPopvisible: false,
      top: 0,
      left: 0,
    };
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
        this.userLogin=false;
        self.initWebSocket(); //重连
      }
    },3000);
  },
  destroyed() {
    this.websock.close(); // 离开路由之后断开websocket连接
  },
  watch: {
    MenuPopvisible(value) {
      if (value) {
        document.body.addEventListener('click', this.closePopMenu);
      } else {
        document.body.removeEventListener('click', this.closePopMenu);
      }
    }
  },
  methods: {
    openPopMenu(e) {
      //console.log('openmenu')
      const menuMinWidth = 105;
      const offsetLeft = this.$el.getBoundingClientRect().left; // container margin left
      const offsetWidth = this.$el.offsetWidth; // container width
      const maxLeft = offsetWidth - menuMinWidth; // left boundary
      const left = e.clientX - offsetLeft; // 15: margin right
      if (left > maxLeft) {
        this.left = maxLeft;
      } else {
        this.left = left + 15;
      }
      this.top = e.clientY - 15; // fix 位置bug
      //this.visible = true  //开启右键菜单
    },
    closePopMenu() {
      this.visible = false;
    },
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
      this.handlers[this.cons.Types.User.LOGIN_ANSWER] = this.receiveLoginAnswer;
      this.handlers[this.cons.Types.User.INFO] = this.receiveUserInfo;
      this.handlers[this.cons.Types.Market.INFO] = this.receiveInfo;
      this.handlers[this.cons.Types.Market.OFFER] = this.receiveMOffer;
      this.handlers[this.cons.Types.Market.CHANGE] = this.receiveMChange;
      this.handlers[this.cons.Types.Market.KEEP] = this.receiveKeep;
    },
    websocketonopen(){ // 连接建立之后执行send方法发送数据
      let actions = [this.cons.Types.Messages.HELLO]; 
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
      this.userLogin=false;
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
    receiveLoginAnswer:function(data){
      if (data[1]===true){
        this.userLogin=true;
        console.log(data[2]);
      }else{
        this.userLogin=false;
        alert(data[2]);
      }
    },
    receiveUserInfo:function(data){
      var msg = data[1];
      console.log(msg);
      this.userInfo=msg;
    },
    receiveMOffer: function(data) {
        var msg = data[1];     
        //console.log('OFFER',msg);
        this.ChangeOffer(msg,true);
    },
    receiveMChange: function(data) {
        var msg = data[1];     
        //console.log('OFFER',msg);
        this.ChangeOffer(msg,false);
    },
    receiveInfo: function(data) {
        var msg = data[1];     
        //console.log('INFO',msg);
        if (msg.ud_price>0) msg.color='red';
        else if (msg.ud_price<0) msg.color='green';
        if (msg.isOfferTime) msg.status=1;
        else msg.status=0;
        this.marketInfo=msg;
        //this.ChangeOffer(msg);
    },
    //
    ChangeOffer: function(data,boo){
      //查找列表
      var isFindID=-1;
      this.datalist.forEach(function(v,i,arr){
        if (v.code==data.code){
          isFindID=i;
        }
      })
      if (data.ud_price>0) data.color='text-up';
      else if (data.ud_price<0) data.color='text-down';
      if (isFindID===-1){
        this.datalist.push(data);
      }else{
        //替换（全替换）
        if (boo){
          this.datalist.splice(isFindID,1,data);
        }else{
        //部分替换
         this.datalist[isFindID]['ud_price'] = data['ud_price'];
         this.datalist[isFindID]['ud_precent'] = data['ud_precent'];
         this.datalist[isFindID]['now_price'] = data['now_price'];
         this.datalist[isFindID]['color'] = data['color'];
        }
      }
      this.reFreshKeep(data);
      //***变化的这条需要给个动画
    },
    receiveKeep: function(data) {
        var msg = data[1];     
        console.log('KEEP',msg);
        this.FillKeep(msg);
    },
    FillKeep: function(data){
      //查找列表
      var isFindID=-1;
      this.keeplist.forEach(function(v,i,arr){
        if (v['code']==data['code']){
          isFindID=i;
        }
      })
      if (isFindID===-1){
        if (data['num']>0)
          this.keeplist.push(data);
      }else{
        if (data['num']>0)
          this.keeplist.splice(isFindID,1,data);
        else
          this.keeplist.splice(isFindID,1);
      }
    },
    reFreshKeep:function(data){
      var isFindID=-1;
      this.keeplist.forEach(function(v,i,arr){
        if (v['code']==data['code']){
          v['now_price'] = data['now_price'];
          v['all_price'] = data['now_price']*v['num'];
          v['ud_price'] = (data['now_price']-v['price'])*v['num'];
          if (v['ud_price']>0) v['color']='text-up';
          else if (v['ud_price']<0) v['color']='text-down';
        }
      })
    },
    //**定时发送Ping */
    SendPing:function (){
      let actions = [this.cons.Types.Ping]; 
      this.websocketsend(actions);
    },
    doLogin:function(){
      let actionslogin = [this.cons.Types.User.LOGIN, this.username,this.password]; 
      this.websocketsend(actionslogin);
    },
    doLogout:function(){
      this.userLogin=false;
      let actionslogout = [this.cons.Types.User.LOGOUT]; 
      this.websocketsend(actionslogout);
    },
    makeOrder:function(mode, code, price, num){
      console.log('makeOrder to server', mode, code , price, num);
      let actionsOrder = [this.cons.Types.Trade.ENORDER, mode, code, price, num];
      this.websocketsend(actionsOrder);
    },
  },
  computed:{
    marketStatus: {
      get: function () {
        return this.marketInfo.status==1? '开盘':'收盘';
      }
    }
  },
}