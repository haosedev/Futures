<template>
  <div>
    <div class="mask flex-center" @click.self="maskAni">
      <div class="dialog dialog-order" >
        <div class="q-bar row no-wrap items-center q-bar--standard">
          <div>新订单</div>
          <div class="q-space"></div>
          <button type="button" role="button" class="q-btn q-btn-item btn-close q-focusable q-hoverable" @click="closeWindow"><span class="q-focus-helper"></span><span class="q-btn__wrapper col row q-anchor--skip"><span class="text-center items-center justify-center row"><i class="q-icon iconfont icon-close"></i></span></span></button>
        </div>
        <hr aria-orientation="horizontal" class="q-separator q-separator q-separator--horizontal">
        <section class="ordersection">
          <div class="q-form flex-item">
            <div class="form-item">
              <div class="form-line">
                <div class="form-line--label flex flex-text-v-center">交易方式</div>
                <div class="flex1 q-field__control relative">
                  <div class="form-item--content flex">
                    <select class="q-inputbox inputfocus" name="tradeMode" v-model="tradeMode">
                      <option value="1">买入（做多）</option>
                      <option value="0">卖出（做空）</option>
                    </select>
                  </div>
                </div>                  
              </div>
              <div class="form-line-bottom">
                <div class="form-line--dt"></div>
                <div class="q-field__messages flex1"><div></div></div>
              </div>
            </div>
            <div class="form-item">
              <div class="form-line">
                <div class="form-line--label flex flex-text-v-center">股票代码</div>
                <div class="flex1 q-field__control relative">
                  <div class="form-line--content flex">
                    <input type="text" name="code" v-model="wantCode" autocomplete="off" class="q-inputbox inputfocus">
                  </div>
                </div>                  
              </div>
              <div class="form-line-bottom">
                <div class="form-line--dt"></div>
                <div class="q-field__messages flex1"><div><span v-show="wantCodeErr.status">{{wantCodeErr.msg}}</span></div></div>
              </div>
            </div>
            <div class="form-item">
              <div class="form-line">
                <div class="form-line--label flex flex-text-v-center">成交单价</div>
                <div class="flex1 q-field__control relative">
                  <div class="form-line--content flex">
                    <input type="text" name="price" autocomplete="off" v-model="showWantPrice" class="q-inputbox inputfocus">
                  </div>
                </div>                  
              </div>
              <div class="form-line-bottom">
                <div class="form-line--dt"></div>
                <div class="q-field__messages flex1"><div><span v-show="wantPriceErr.status">{{wantPriceErr.msg}}</span></div></div>
              </div>
            </div>
            <div class="form-item">
              <div class="form-line">
                <div class="form-line--label flex flex-text-v-center">订单数量</div>
                <div class="flex1 q-field__control relative">
                  <div class="form-line--content flex">
                    <input type="text" name="num" autocomplete="off" v-model="wantNum" class="q-inputbox inputfocus">
                  </div>
                </div>                  
              </div>
              <div class="form-line-bottom">
                <div class="form-line--dt"></div>
                <div class="q-field__messages flex1"><div><span v-show="wantNumErr.status">{{wantNumErr.msg}}</span></div></div>
              </div>
            </div>
            <div class="form-item">
              <div class="form-line">
                <div class="form-line--label flex flex-text-v-center"></div>
                <div class="flex1">
                  <div class="form-line--content flex">
                    <div class="text-caption text-grey-8">最大可用数量：<span class="available-balance">{{showmaxUse}}</span></div>
                  </div>
                </div>                  
              </div>
              <div class="form-line-bottom">
                <div class="form-line--dt"></div>
                <div class="q-field__messages flex1"><div></div></div>
              </div>
            </div>
          </div>
          <div class="trade-status-row">
            <div class="close-price">
                <i class="iconfont icon-data-view statue--0"></i>
                实时价格
            </div>
            <div v-show="marketStatus == 1" class="trade-status-icon align-right trade-status-icon--1">已开市</div>
            <div v-show="marketStatus == 0" class="trade-status-icon align-right trade-status-icon--0">休市中</div>
          </div>
          <div class="form-item">
            <div class="form-line">
              <button class="q-btn q-btn-item non-selectable no-outline full-width btn-close-price q-btn--standard q-btn--rectangle q-btn--actionable q-focusable q-hoverable q-btn--wrap" :class="color">
                <span class="q-focus-helper" tabindex="-1"></span>
                <span class="q-btn__wrapper min-hei-wid col row q-anchor--skip">
                  <span class="q-btn__content text-center col items-center q-anchor--skip justify-center row">
                    <span class="block">{{nowPrice|toYuan}}</span>
                  </span>
                </span>
              </button>
            </div>
          </div>
          <button tabindex="0" type="submit" @click="sumbitBtn" class="q-btn q-btn-item non-selectable no-outline full-width q-mt-sm q-btn--standard q-btn--rectangle bg-blue text-white q-btn--actionable q-focusable q-hoverable q-btn--wrap">
            <span class="q-focus-helper" tabindex="-1"></span>
            <span class="q-btn__wrapper min-hei-wid col row q-anchor--skip">
            <span class="q-btn__content text-center col items-center q-anchor--skip justify-center row">
              <span class="block">提交</span>
            </span>
            </span>
          </button>
        </section>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name : 'orderWindow',
  data () {
    return {
      tradeMode:1,
      wantCode: 0,    //选择中的号码
      selectCode: 0,  //选中的股票
      nowPrice: 0,    //当前最新报价
      maxUse: 0,      //最大可操作数量
      wantPrice: 0,   //期望成交价
      wantNum: 0,     //期望成交数量
      wantCodeErr:{status:false, msg:'股票代码异常！'},
      wantPriceErr:{status:false, msg:'价格设置异常！'},
      wantNumErr:{status:false, msg:'订单数量异常！'},
      //datalist : this.datalist,
      color: 'up',
    }
  },
  props: {
    datalist: Array,
    keeplist: Array,
    marketStatus: Number,
    myMoney: Number,
  }, 
  mounted:function(){
    console.log('orderWindow',this.hero)
  },
  methods:{
    reDraw: function(){
      let self=this
      if (this.selectCode.length==5){
        this.datalist.forEach(function(item, index){
          if (item.code == self.selectCode){
            self.nowPrice=item.now_price;
            if (item.ud_price<0) self.color='down';
            else self.color='up';
          }
        })
      }
    },
    maskAni: function(){
      console.log('maskAni');
    },
    closeWindow: function(){
      console.log('closeWindow');
      this.$emit("closeDialog",);
    },
    findcode: function(code){
      if (code.length==5){
        let self=this
        this.datalist.forEach(function(item, index){
          if (item.code == code){
            if (self.selectCode!=code){
              self.selectCode=code;
              self.wantPrice=item.now_price;
              self.reDraw();
            }
          }
        })
      }
    },
    sumbitBtn: function (){
      //提交数据，先判断数据是否完整
      var ret = true;
      if ((this.wantCode.length!=5)||(this.selectCode!=this.wantCode)){
        console.log('股票代码错误！')
        this.wantCodeErr.status=true;
        ret=false;
      }
      if ((this.wantPrice.length==0)||(this.wantPrice<=0)){
        console.log('成交单价异常!')
        this.wantPriceErr.status=true;
        ret=false;
      }
      if ((this.wantNum.length==0)||(this.wantNum<=0)||(this.wantNum>this.maxUse)){
        console.log('订单数量异常！')
        this.wantNumErr.status=true;
        ret=false;
      }
      if (ret){
        console.log('数据正常，可以提交！')
      }
    },
  },
  computed:{
    showWantPrice: {
      get: function () {
        return this.$options.filters['toYuanNumber'] (this.wantPrice );
      },
      set: function (value) {
        this.wantPrice = parseInt(value * 100)
        if (this.wantPrice>0){
          this.wantPriceErr.status=false;
        }else{
          this.wantPriceErr.status=true;
        }
      }
    },
    showmaxUse:{
      get: function () {
        this.maxUse=0;
        if (this.tradeMode==1){
          //买入，计算金额可以买入多少
          if (this.wantPrice>0)
            this.maxUse = parseInt(this.myMoney/this.wantPrice)
        }else if (this.tradeMode==0){
          //卖出，计算持仓有多少库存
          if ((this.wantCode.length==5)&&(this.selectCode==this.wantCode)){
            let self= this
            this.keeplist.forEach(function(item, index){
              if (item.code == self.wantCode){
                self.maxUse=item.num
              }
            })
          }
        }
        return this.maxUse;
      },
    }
  },
  watch:{
    wantCode(val){
      this.findcode(val);
      this.wantCodeErr.status=false;
    },
    wantNum(){
      this.wantNumErr.status=false;
    },  
    datalist:{
　　　　deep: true,//深度监听
　　　　handler: function() {
　　　　　　this.reDraw() //********数据变化时， 部分需要跟着重新计算 */
　　　　}
    },
  },
}
</script>

<style scoped>

</style>