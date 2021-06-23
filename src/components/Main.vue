<template>
  <div @contextmenu.prevent="">
        <div class="topbar">
          <div class="title">
            神经病股票系统
          </div>
        </div>
      <div class="screen">
        <div class="board">
          <div class="card_header bline">
            <div class="card-title">
              开盘序列：<span class="td2">{{marketInfo.daytime}}</span> ,盘序：<span class="td2">{{marketInfo.nowHour}}</span>
            </div>
          </div>
          <div class="card_header noline">
            <div class="card-title">
            状态：<span class="td2">{{marketInfo.status}}</span>，大盘指数：<span :class="marketInfo.color">{{marketInfo.now_price|toYuan}}</span>，涨跌：<span :class="marketInfo.color">{{marketInfo.ud_price|toYuan}}</span>，涨幅：<span :class="marketInfo.color">{{marketInfo.ud_precent|toYuan}}%</span>
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
          <table cellspacing="0" cellpadding="0" class="table_body" style="height:700px;" @contextmenu.prevent="openPopMenu($event)">
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
        </div>
        <div class="sellbox body-bg">
          <div v-show="!userLogin">
            <div class="LoginArea">
              <h1>SIGN UP</h1>
              <input type="text" class="input-text" placeholder="账号" v-model="username">
              <input type="text" class="input-text" placeholder="密码" v-model="password">
              <div class="LoginBtn btnScale" @click="doLogin">点我登录</div>
            </div>
          </div>
          <div class="contentBox" v-show="userLogin">
            <div class="userinfo">
              <div class="card_header noline">
                <div class="card-title">
                  账户信息
                </div>
              </div>
              <div>
                <table cellspacing="0" cellpadding="0" class="table_head">
                  <tr>
                    <th width="25%"><div class="cell">账号</div></th>
                    <th width="25%"><div class="cell">昵称</div></th>
                    <th width="25%"><div class="cell">余额</div></th>
                    <th width="25%"><div class="cell">最后登录时间</div></th>
                  </tr>
                  <tr>
                    <td class=""><div class="cell">{{userInfo.username}}</div></td>
                    <td class=""><div class="cell">{{userInfo.nickname}}</div></td>
                    <td class=""><div class="cell">{{userInfo.money|toYuan}}</div></td>
                    <td class=""><div class="cell">{{userInfo.login_time}}</div></td>
                  </tr>
                </table>
              </div>
            </div> 
            <div class="keep">
              <div class="card_header noline">
                <div class="card-title">
                持仓
                </div>
              </div>
              <table cellspacing="0" cellpadding="0" class="table_head">
                <colgroup>
                  <col width="80">
                  <col width="100">
                  <col width="80">
                  <col width="80">
                  <col width="100">
                  <col width="100">
                  <col width="100">
                  <col />
                </colgroup>
                <thead>
                  <tr>
                    <th class="cell-name is-leaf"><div class="cell">代码</div></th>
                    <th class="cell-name is-leaf"><div class="cell">名称</div></th>
                    <th class="is-leaf"><div class="cell">买入价</div></th>
                    <th class="is-leaf"><div class="cell">持仓</div></th>
                    <th class="is-leaf"><div class="cell">当前价</div></th>
                    <th class="is-leaf"><div class="cell">总价值</div></th>
                    <th class="is-leaf"><div class="cell">盈亏</div></th>
                    <th class="gutter" style="border-right: 0px;background-color:#1d1d23;"></th>
                  </tr>
                </thead>
              </table>
              <table cellspacing="0" cellpadding="0" class="table_body">
                <colgroup>
                  <col width="80">
                  <col width="100">
                  <col width="80">
                  <col width="80">
                  <col width="100">
                  <col width="100">
                  <col width="100">
                </colgroup>
                <tbody>
                  <tr v-for="(vo, index) in keeplist" :key="index">
                    <td class="cell-name td4"><div class="cell">{{vo.code}}</div></td>
                    <td class="cell-name td4"><div class="cell">{{vo.name}}</div></td>
                    <td class="td7"><div class="cell">{{vo.price|toYuan}}</div></td>
                    <td class="td7"><div class="cell">{{vo.num}}</div></td>
                    <td class="td5"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.now_price|toYuan}}</span></div></td>
                    <td class="td6"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.all_price|toYuan}}</span></div></td>
                    <td class="td7"><div class="cell"><span class="transform-value" :class="vo.color">{{vo.ud_price|toYuan}}</span></div></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="are">
              <div class="card_header noline">
                <div class="card-title">
                  挂单信息
                  <div class="btn pull-right btnScale" @click="OrderWindowVisible = true">挂单</div>
                </div>
              </div>
              <div>
                <table cellspacing="0" cellpadding="0" class="table_head">
                  <tr>
                    <th width="25%"><div class="cell">代码</div></th>
                    <th width="25%"><div class="cell">名称</div></th>
                    <th width="25%"><div class="cell">买入价</div></th>
                    <th width="25%"><div class="cell">买入数量</div></th>
                  </tr>
                  <tr>
                    <td class=""><div class="cell">&nbsp;</div></td>
                    <td class=""><div class="cell"></div></td>
                    <td class=""><div class="cell"></div></td>
                    <td class=""><div class="cell"></div></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-show="OrderWindowVisible" class="mask flex-center" @click="maskAni">
        <div class="dialog dialog-order">
          <div class="q-bar row no-wrap items-center q-bar--standard">
            <div>新订单</div>
            <div class="q-space"></div>
            <button type="button" role="button" class="q-btn q-btn-item btn-close q-focusable q-hoverable"><span class="q-focus-helper"></span><span class="q-btn__wrapper col row q-anchor--skip"><span class="text-center items-center justify-center row"><i class="q-icon iconfont icon-close"> </i></span></span></button>
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
                      <input type="text" name="code" value="" autocomplete="off" class="q-inputbox inputfocus">
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
                      <input type="text" name="price" autocomplete="off" value="0.00" class="q-inputbox inputfocus">
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
                      <input type="text" name="num" autocomplete="off" value="0" class="q-inputbox inputfocus">
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
                      <div class="text-caption text-grey-8">最大可用数量：<span class="available-balance">100</span></div>
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
                      <span class="block">34177.46</span>
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
      <ul v-show="MenuPopvisible" :style="{left:left+'px',top:top+'px'}" class="contextmenu">
        <li>历史记录</li>
      </ul>
  </div>
</template>
<script>
  import Main from '@/js/Main.js'
  export default Main
</script>
<style scope>
  @import '../css/base.css';
  @import '../css/common.css';
</style>