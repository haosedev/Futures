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
                  <div class="btn pull-right btnScale" >挂单</div>
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
      <el-dialog
        title="新订单"
        :visible.sync="OrderWindowVisible"
        width="30%"
        :before-close="handleClose">
        <span>这里会加入内容</span>
        <span slot="footer" class="dialog-footer">
          <el-button @click="dialogVisible = false">取 消</el-button>
          <el-button type="primary" @click="dialogVisible = false">确 定</el-button>
        </span>
      </el-dialog>
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
  @import '../css/common.css';
</style>