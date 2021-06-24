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
                    <select class="q-inputbox inputfocus" name="tradeMode">
                      <option>买入（做多）</option>
                      <option>卖出（做空）</option>
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
                <div class="q-field__messages flex1"><div></div></div>
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
                <div class="q-field__messages flex1"><div></div></div>
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
                <div class="q-field__messages flex1"><div></div></div>
              </div>
            </div>
            <div class="form-item">
              <div class="form-line">
                <div class="form-line--label flex flex-text-v-center"></div>
                <div class="flex1">
                  <div class="form-line--content flex">
                    <div class="text-caption text-grey-8">最大可用数量：<span class="available-balance">{{maxUse}}</span></div>
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
            <div class="trade-status-icon align-right trade-status-icon--0">
            已开市
            </div>
          </div>
          <div class="form-item">
            <div class="form-line">
              <button class="q-btn q-btn-item non-selectable no-outline full-width btn-close-price q-btn--standard q-btn--rectangle q-btn--actionable q-focusable q-hoverable q-btn--wrap up">
                <span class="q-focus-helper" tabindex="-1"></span>
                <span class="q-btn__wrapper min-hei-wid col row q-anchor--skip">
                  <span class="q-btn__content text-center col items-center q-anchor--skip justify-center row">
                    <span class="block">{{nowPrice|toYuan}}</span>
                  </span>
                </span>
              </button>
            </div>
          </div>
          <button tabindex="0" type="submit" role="button" class="q-btn q-btn-item non-selectable no-outline full-width q-mt-sm q-btn--standard q-btn--rectangle bg-blue text-white q-btn--actionable q-focusable q-hoverable q-btn--wrap">
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
      wantCode: 0,    //选择中的号码
      selectCode: 0,  //选中的股票
      nowPrice: 0,    //当前最新报价
      maxUse: 0,      //最大可操作数量
      wantPrice: 0,   //期望成交价
      wantNum: 0,     //期望成交数量
      //datalist : this.datalist,
    }
  },
  props: {
    datalist: Array,
  }, 
  mounted:function(){
    console.log('orderWindow',this.hero)
  },
  methods:{
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
              self.nowPrice=item.now_price;
              self.wantPrice=item.now_price;
            }
          }
        })
      }
    },
  },
  computed:{
    showWantPrice: {
      get: function () {
        return this.$options.filters['toYuan'] (this.wantPrice );
      },
      set: function (value) {
        this.wantPrice = parseInt(value * 100)
      }
    }
  },
  watch:{
    wantCode:{
　　　　deep: true,//深度监听
　　　　handler: function() {
　　　　　this.findcode(this.wantCode);
　　　　}
    },
    value:{
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