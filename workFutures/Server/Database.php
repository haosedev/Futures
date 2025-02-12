<?php

/**
 * This file is class for Database.
 *
 */

namespace Server;

use \Workerman\Worker;
use think\facade\Db;

class Database
{

    public function init()
    {
        $config = require __DIR__ . '/../Config/database.php';
        Db::setConfig($config);
    }

    /*******************
     * 用户登陆
     *******************/
    public function UserLogin($user = '', $pwd = '')
    {
        //return $this->db->select('*')->from('User u')->where('u.username = :user and u.password = :pwd ')->bindValues(array('user' => $user, 'pwd' => $pwd))->row();
        return Db::table('User')->where('username', $user)->where('password', $pwd)->find();
    }
    /*******************
     * 刷新用户数据
     *******************/
    public function refreshUserInfo($uid = 0)
    {
        //return $this->db->select('*')->from('User')->where('id = :id')->bindValues(array('id' => $uid))->row();
        return Db::table('User')->where('id', $uid)->find();
    }
    /*******************
     * 更新用户最后登陆数据
     *******************/
    public function UpdateUserInfo($info)
    {
        //$tmp=$info;
        //unset($tmp['id']);
        //unset($tmp['money']);
        $tmp['update_time'] = time();
        //$this->db->update('User')->cols($tmp)->where('ID=' . $info['id'])->query();
        Db::table('User')->where('id', $info['id'])->update($tmp);
    }
    /*******************
     * 余额变化
     * 使用金额Type = Dec
     * 获得金额Type = Add
     *******************/
    public function ChangeMoney($uid = 0, $money = 0, $type = 'Dec')
    {
        $nowmoney = 0;
        //$oldmoney = $this->db->select('money')->from('User')->where('id = :id')->bindValues(array('id' => $uid))->single();
        $oldmoney = Db::table('User')->where('id', $uid)->value('money');
        if ($type == "Dec") {
            if ($oldmoney >= $money) {
                $nowmoney = $oldmoney - $money;
                //$res = $this->db->update('User')->cols(['money' => $nowmoney])->where('id=' . $uid . " and money=" . $oldmoney)->query();
                $res = Db::table('User')->where('id', $uid)->where('money', $oldmoney)->update(['money' => $nowmoney]);
                //**写入资金变化记录表


                return true;
            }
        } else if ($type == "Add") {
            $nowmoney = $oldmoney + $money;
            //$res = $this->db->update('User')->cols(['money' => $nowmoney])->where('id=' . $uid . " and money=" . $oldmoney)->query();
            $res = Db::table('User')->where('id', $uid)->where('money', $oldmoney)->update(['money' => $nowmoney]);
            //**写入资金变化记录表


            return true;
        }
        return false;
    }
    /*******************
     * 解锁部分余额
     *******************/
    public function UnlockFreezeMoney($uid = 0, $money = 0)
    {
        $nowmoney = 0;
        //$oldmoney = $this->db->select('money_freeze')->from('User')->where('id = :id')->bindValues(array('id' => $uid))->single();
        $oldmoney = Db::table('User')->where('id', $uid)->value('money_freeze');
//**这里
        if ($oldmoney >= $money) {
            $nowmoney = $oldmoney - $money;
            //$res = $this->db->update('User')->cols(['money_freeze' => $nowmoney])->where('id=' . $uid . " and money_freeze=" . $oldmoney)->query();
            $res = Db::table('User')->where('id', $uid)->where('money_freeze', $oldmoney)->update(['money_freeze' => $nowmoney]);
            return true;
        }
        return false;
    }
    /*******************
     * 用户持仓数据
     * 
     *******************/
    public function getUserKeep($uid = 0)
    {
        //return $this->db->select('k.*,s.name')->from('KeepStock k')->innerJoin('Stock s', 'k.code=s.code')->where('k.uid = :uid and k.num>0 ')->bindValues(array('uid' => $uid))->query();
        return Db::table('KeepStock')->alias('k')->join('Stock s', 'k.code=s.code')->where('k.uid', $uid)->where('k.num', '>', 0)->select()->toArray();
    }
    /*******************
     * 尝试挂单售卖（金额不变，股票不变，卖出部分添加锁定）。
     *******************/
    public function KeepWantSell($uid = 0, $code = '', $num = 0)
    {

        //$keep = $this->db->select('*')->from('KeepStock')->where('uid = :uid and code = :code ')->bindValues(array('uid' => $uid, 'code' => $code))->orderByDESC(array('id'))->row();
        $keep = Db::table('KeepStock')->where('uid', $uid)->where('code', $code)->order('id', 'desc')->find();
        //挂单售卖：持仓股票金额不变，股票减少
        if (($keep) && (($keep['num'] - $keep['buy_freeze'] - $keep['sell_freeze']) >= $num)) {
            //有存货，并且余额足够
            $keep['sell_freeze'] += $num;     //卖出锁定
            //$res = $this->db->update('KeepStock')->cols([
            //    'sell_freeze' => $keep['sell_freeze'],
            //])->where('id=' . $keep['id'])->query();
            $res = Db::table('KeepStock')->where('id', $keep['id'])->update([
                'sell_freeze' => $keep['sell_freeze'],
            ]);
            return true;
        }
        return false;
    }
    /*******************
     * 部分售卖成功（金额增加，股票减少，卖出锁定减少）。
     *******************/
    public function KeepSellSuccess($uid = 0, $code = '', $num = 0, $changemoney = 0)
    {
        //部分售卖成功，1持仓变化（数量、买入价格），2用户余额增加
        //$keep = $this->db->select('*')->from('KeepStock')->where('uid = :uid and code = :code ')->bindValues(array('uid' => $uid, 'code' => $code))->orderByDESC(array('id'))->row();
        $keep = Db::table('KeepStock')->where('uid', $uid)->where('code', $code)->order('id', 'desc')->find();
        //
        if ($keep) {
            //有存货，并且余额足够
            $keep['num'] -= $num;
            $keep['sell_freeze'] -= $num;
            $keep['buy_money'] -= $changemoney;
            // $res = $this->db->update('KeepStock')->cols([
            //     'num' => $keep['num'],
            //     'sell_freeze' => $keep['sell_freeze'],
            //     'buy_money' => $keep['buy_money'],
            // ])->where('id=' . $keep['id'])->query();
            $res = Db::table('KeepStock')->where('id', $keep['id'])->update([
                'num' => $keep['num'],
                'sell_freeze' => $keep['sell_freeze'],
                'buy_money' => $keep['buy_money'],
            ]);
            //用户余额变动
            $this->ChangeMoney($uid, $changemoney, "Add");  //用户余额增加
            return true;
        }
        return false;
    }
    /*******************
     * 卖单退回：没卖出的sell_freeze将被解锁，用户余额不发生变化。
     *******************/
    public function KeepSellFail($order)
    {
        // **1检测是否完成的订单，2确定surplus不为0，执行退回surplus数量的sell_freeze
        if ($order['surplus'] > 0) {
            //$keep = $this->db->select('*')->from('KeepStock')->where('uid = :uid and code = :code ')->bindValues(array('uid' => $order['uid'], 'code' => $order['code']))->row();
            $keep = Db::table('KeepStock')->where('uid', $order['uid'])->where('code', $order['code'])->find();
            if ($keep) {
                $keep['sell_freeze'] -= $order['surplus'];    //卖出锁定的-掉没卖完的
                // $res = $this->db->update('KeepStock')->cols([
                //     'sell_freeze' => $keep['sell_freeze'],
                // ])->where('id=' . $keep['id'])->query();
                $res = Db::table('KeepStock')->where('id', $keep['id'])->update([
                    'sell_freeze' => $keep['sell_freeze'],
                ]);
            }
        }
        //close listsell
        //$order['surplus']=0;
        $this->updateListSell($order, true);
    }
    /*******************
     * 尝试挂单求购（余额不变，添加余额添加锁定）。
     *******************/
    public function KeepWantBuy($uid = 0, $money = 0)
    {
        // **购买尝试，先计算余额是否够用
        //$user = $this->db->select('money, money_freeze')->from('User')->where('id = :id')->bindValues(array('id' => $uid))->row();
        $user = Db::table('User')->where('id', $uid)->find();
        if (($user['money'] - $user['money_freeze']) >= $money) {
            $user['money_freeze'] += $money;
            //$res = $this->db->update('User')->cols(['money_freeze' => $user['money_freeze']])->where('id=' . $uid)->query();
            $res = Db::table('User')->where('id', $uid)->update(['money_freeze' => $user['money_freeze']]);
            return true;
        }
        return false;
    }
    /*******************
     * 买入成功部分：股票数量增加、买入成交金额加大
     *******************/
    public function KeepBuySuccess($uid = 0, $code = '', $num = 0, $changemoney = 0, $daytime = "")
    {
        //$keep = $this->db->select('*')->from('KeepStock')->where('uid = :uid and code = :code ')->bindValues(array('uid' => $uid, 'code' => $code))->orderByDESC(array('id'))->row();
        $keep = Db::table('KeepStock')->where('uid', $uid)->where('code', $code)->order('id desc')->find();
        //买入：买入价+新买花费  存货+买入数量，对买入数量进行冻结
        if ($keep) {
            $keep['num'] += $num;
            $keep['buy_money'] += $changemoney;
            $keep['buy_freeze'] += $num;
            $keep['buyday'] = $daytime;
            // $res = $this->db->update('KeepStock')->cols([
            //     'num' => $keep['num'],
            //     'buy_money' => $keep['buy_money'],
            //     'buy_freeze' => $keep['buy_freeze'],
            //     'buyday' => $keep['buyday'],
            // ])->where('id=' . $keep['id'])->query();
            $res = Db::table('KeepStock')->where('id', $keep['id'])->update([
                'num' => $keep['num'],
                'buy_money' => $keep['buy_money'],
                'buy_freeze' => $keep['buy_freeze'],
                'buyday' => $keep['buyday'],
            ]);
        } else {
            //新插入数据
            // $insert_id = $this->db->insert('KeepStock')->cols([
            //     'uid' => $uid,
            //     'code' => $code,
            //     'num' => $num,
            //     'buy_money' => $changemoney,
            //     'buyday' => $daytime,
            //     'buy_freeze' => $num,
            // ])->query();
            $insert_id = Db::table('KeepStock')->insert([
                'uid' => $uid,
                'code' => $code,
                'num' => $num,
                'buy_money' => $changemoney,
                'buyday' => $daytime,
                'buy_freeze' => $num,
            ]);
        }
        return true;
    }
    /*******************
     * 买单退回：未使用的money用来清除User的money_freeze
     *******************/
    public function KeepBuyFail($order)
    {
        // **1检测是否完成的订单，2确定order['money']不为0，执行退回money数量的money_freeze
        if ($order['money'] > 0) {
            $money_freeze = $this->db->select('money_freeze')->from('User')->where('id = :id')->bindValues(array('id' => $order['uid']))->single();
            $money_freeze -= $order['money']; //冻结金额-剩余未使用金额
            //$res = $this->db->update('User')->cols(['money_freeze' => $money_freeze, 'update_time' => time()])->where('id=' . $order['uid'])->query();
            $res = Db::table('User')->where('id', $order['uid'])->update(['money_freeze' => $money_freeze, 'update_time' => time()]);
        }
        //close listbuy
        $this->updateListBuy($order, true);
    }
    /*******************
     * 清除冻结数据
     * 
     *******************/
    public function ClearFreeze($datekey = '')
    {
        if ($datekey == '') {
            $tmp['buyday'] = '';
            $tmp['buy_freeze'] = 0;
            //$tmp['sell_freeze']=0;        //暂时不自动清理，由挂单清理顺带执行，方便排错。
            //$this->db->update('KeepStock')->cols($tmp)->where('1=1')->query();
            Db::table('KeepStock')->update($tmp);
        } else {
            $tmp['buyday'] = '';
            $tmp['buy_freeze'] = 0;
            //$tmp['sell_freeze']=0;
            //$this->db->update('KeepStock')->cols($tmp)->where('buyday=' . $datekey)->query();
            Db::table('KeepStock')->where('buyday', $datekey)->update($tmp);
        }
    }
    /*******************
     * 测试SN是否未使用
     * 未使用时是True
     *******************/
    public function IsEmptySn($sn = '', $table = '')
    {
        //$item = $this->db->select('id')->from($table)->where('sn = :sn')->bindValues(array('sn' => $info['sn']))->single();
        $item = Db::table($table)->where('sn', $sn)->find();
        if ($item) return false;
        else return true;
    }
    /////////////////////////////////////////////////////大盘数据/////////////////////////////////////////////////////
    /*******************
     * 获取最新DataKey
     * 
     *******************/
    public function getDataKey()
    {
        //$date=$db->row("SELECT daytime FROM `PriceList` order by daytime desc");
        //return $this->db->select('daytime')->from('PriceList')->orderByDESC(array('daytime'))->single();
        return Db::table('PriceList')->order('daytime', 'desc')->value('daytime');
    }
    /*******************
     * 获取某DataKey的每股数据
     * 
     *******************/
    public function getTodayData($dateKey)
    {
        //$this->TodayData = $db->query("SELECT p.*,s.name FROM `PriceList` p left join `Stock` s on p.code=s.code where p.daytime='".$dateKey."'");
        //return  $this->db->select('p.*,s.name')->from('PriceList p')->innerJoin('Stock s', 'p.code=s.code')->where('p.daytime = :item')->bindValues(array('item' => $dateKey))->query();
        return Db::table('PriceList')->alias('p')->join('Stock s', 'p.code=s.code')->where('p.daytime', $dateKey)->select()->toArray();
    }
    /*******************
     * 写入单只数据（如果已存在就放弃写入）
     * 
     *******************/
    public function saveDataSingle($now_key, $info)
    {
        //$item = $this->db->select('id')->from('PriceList')->where('code = :code and daytime = :item')->bindValues(array('code' => $info['code'], 'item' => $now_key))->single();
        $item = Db::table('PriceList')->where('code', $info['code'])->where('daytime', $now_key)->find();
        if (!$item) {
            //插入数据
            // $insert_id = $this->db->insert('PriceList')->cols([
            //     'code' => $info['code'],
            //     'daytime' => $now_key,
            //     'yestoday_price' => $info['yestoday_price'],
            //     'start_price' => $info['start_price'],
            //     'now_price' => $info['now_price'],
            //     'ud_price' => $info['ud_price'],
            //     'ud_precent' => $info['ud_precent']
            // ])->query();
            $insert_id = Db::table('PriceList')->insert([
                'code' => $info['code'],
                'daytime' => $now_key,
                'yestoday_price' => $info['yestoday_price'],
                'start_price' => $info['start_price'],
                'now_price' => $info['now_price'],
                'ud_price' => $info['ud_price'],
                'ud_precent' => $info['ud_precent']
            ]);
        }
    }
    /*******************
     * 写入大盘数据（如果存在就放弃写入）
     * 
     *******************/
    public function saveMarketAll($now_key, $MarketAllData)
    {
        //$market = $this->db->select('id')->from('MarketAll')->where('daytime = :item')->bindValues(array('item' => $now_key))->single();
        $market = Db::table('MarketAll')->where('daytime', $now_key)->find();
        if (!$market) {
            //插入数据
            // $insert_id = $this->db->insert('MarketAll')->cols([
            //     'daytime' => $MarketAllData['daytime'],
            //     'yestoday_price' => $MarketAllData['yestoday_price'],
            //     'start_price' => $MarketAllData['start_price'],
            //     'now_price' => $MarketAllData['now_price'],
            //     'ud_price' => $MarketAllData['ud_price'],
            //     'ud_precent' => $MarketAllData['ud_precent']
            // ])->query();
            $insert_id = Db::table('MarketAll')->insert([
                'daytime' => $MarketAllData['daytime'],
                'yestoday_price' => $MarketAllData['yestoday_price'],
                'start_price' => $MarketAllData['start_price'],
                'now_price' => $MarketAllData['now_price'],
                'ud_price' => $MarketAllData['ud_price'],
                'ud_precent' => $MarketAllData['ud_precent']
            ]);
        }
    }

    /////////////////////////////////////////////////////大盘数据/////////////////////////////////////////////////////
    /*******************
     * 读取买方挂单数据
     *******************/
    public function fetchListBuy()
    {
        //直接读取一单
        //return $this->db->select('*')->from('Listbuy')->where('status=0 and surplus>0')->orderByASC(array('id'))->row();
        return Db::table('Listbuy')->where('status', 0)->where('surplus', '>', 0)->order('id', 'asc')->find();
    }
    /*******************
     * 按要求读取买方挂单数据
     *******************/
    public function fetchListBuyByOrder($order)
    {
        //必须符合条件 status为0，surplus>0（剩余购入，剩余卖出），uid非自己
        //return $this->db->select('*')->from('Listbuy')->where('status=0 and surplus>0 and price>=:price and uid<>:uid and daytime=:daytime')->bindValues(array('price' => $order['price'], 'uid' => $order['uid'], 'daytime' => $order['daytime']))->orderByASC(array('id'))->row();
        return Db::table('Listbuy')->where('status', 0)->where('surplus', '>', 0)->where('price', '>=', $order['price'])->where('uid', '<>', $order['uid'])->where('daytime', $order['daytime'])->order('id', 'asc')->find();
    }
    /*******************
     * 读取某用户买单列表
     * @uid  $daytime  $all（是否展示已完成的）
     *******************/
    public function fetchListBuyByUser($uid = 0, $daytime = '', $all = false)
    {
        if ($all) {
            //return $this->db->select('lb.*,s.name')->from('Listbuy lb')->innerJoin('Stock s', 'lb.code=s.code')->where('lb.uid=:uid and lb.daytime=:daytime')->bindValues(array('uid' => $uid, 'daytime' => $daytime))->orderByASC(array('id'))->query();
            return Db::table('Listbuy')->alias('lb')->join('Stock s', 'lb.code=s.code')->where('lb.uid', $uid)->where('lb.daytime', $daytime)->select()->toArray();
        } else {
            //return $this->db->select('lb.*,s.name')->from('Listbuy lb')->innerJoin('Stock s', 'lb.code=s.code')->where('lb.status=0 and lb.uid=:uid and lb.daytime=:daytime')->bindValues(array('uid' => $uid, 'daytime' => $daytime))->orderByASC(array('id'))->query();
            return Db::table('Listbuy')->alias('lb')->join('Stock s', 'lb.code=s.code')->where('lb.status', 0)->where('lb.uid', $uid)->where('lb.daytime', $daytime)->select()->toArray();
        }
    }
    /*******************
     * 读取卖方挂单数据
     *******************/
    public function fetchListSell()
    {
        //return $this->db->select('*')->from('Listsell')->where('status=0 and surplus>0')->orderByASC(array('id'))->row();
        return Db::table('Listsell')->where('status', 0)->where('surplus', '>', 0)->order('id', 'asc')->find();
    }
    /*******************
     * 按要求读取卖方挂单数据
     *******************/
    public function fetchListSellByOrder($order)
    {
        //必须符合条件 status为0，surplus>0（剩余购入，剩余卖出），uid非自己
        //return $this->db->select('*')->from('Listsell')->where('status=0 and surplus>0 and price<=:price and uid<>:uid and daytime=:daytime')->bindValues(array('price' => $order['price'], 'uid' => $order['uid'], 'daytime' => $order['daytime']))->orderByASC(array('id'))->row();
        return Db::table('Listsell')->where('status', 0)->where('surplus', '>', 0)->where('price', '<=', $order['price'])->where('uid', '<>', $order['uid'])->where('daytime', $order['daytime'])->order('id', 'asc')->find();
    }
    /*******************
     * 读取某用户卖单列表
     * @uid  $daytime  $all（是否展示已完成的）
     *******************/
    public function fetchListSellByUser($uid = 0, $daytime = '', $all = false)
    {
        if ($all) {
            //return $this->db->select('ls.*, s.name')->from('Listsell ls')->innerJoin('Stock s', 'ls.code=s.code')->where('ls.uid=:uid and ls.daytime=:daytime')->bindValues(array('uid' => $uid, 'daytime' => $daytime))->orderByASC(array('id'))->query();
            return Db::table('Listsell')->alias('ls')->join('Stock s', 'ls.code=s.code')->where('ls.uid', $uid)->where('ls.daytime', $daytime)->select()->toArray();
        } else {
            //return $this->db->select('ls.*, s.name')->from('Listsell ls')->innerJoin('Stock s', 'ls.code=s.code')->where('ls.status=0 and ls.uid=:uid and ls.daytime=:daytime')->bindValues(array('uid' => $uid, 'daytime' => $daytime))->orderByASC(array('id'))->query();
            return Db::table('Listsell')->alias('ls')->join('Stock s', 'ls.code=s.code')->where('ls.status', 0)->where('ls.uid', $uid)->where('ls.daytime', $daytime)->select()->toArray();
        }
    }

    /*******************
     * 更新买家挂单
     * 
     *******************/
    public function updateListBuy($row, $close = false)
    {
        $tmp = $row;
        unset($tmp['id']);
        //
        if ($close) {
            $tmp['status'] = 2;   //被取消
        } else {
            if ($tmp['surplus'] > 0) $tmp['status'] = 0;
            else $tmp['status'] = 1;
        }
        $tmp['update_time'] = time();
        //$this->db->update('Listbuy')->cols($tmp)->where('id=' . $row['id'])->query();
        Db::table('Listbuy')->cols($tmp)->where('id', $row['id'])->update();
    }
    /*******************
     * 更新卖家挂单
     * 
     *******************/
    public function updateListSell($row, $close = false)
    {
        $tmp = $row;
        unset($tmp['id']);
        //
        if ($close) {
            $tmp['status'] = 2;   //被取消
        } else {
            if ($tmp['surplus'] > 0) $tmp['status'] = 0;
            else $tmp['status'] = 1;
        }
        $tmp['update_time'] = time();
        //$this->db->update('Listsell')->cols($tmp)->where('id=' . $row['id'])->query();
        Db::table('Listsell')->cols($tmp)->where('id', $row['id'])->update();
    }
    /*******************
     * 写入未成交的买方挂单数据
     * 
     *******************/
    public function saveListBuy($order)
    {

        //id	daytime 开盘序列  create_time 挂单时间	update_time 更新时间	sn 订单序列号	code 股票代码	price 交易定价（购入定价）	surplus 剩余购入数量	amount 计划购入数量	money 计划花费金额	uid 会员代码	status 0未完成，1已完成
        if ($order) {
            //插入数据
            $status = 1;
            if ($order['surplus'] > 0) $status = 0;
            $time = time();
            // $this->db->insert('Listbuy')->cols([
            //     'daytime'     => $order['daytime'],
            //     'create_time' => $time,
            //     'update_time' => $time,
            //     'sn'          => $order['sn'],
            //     'code'        => $order['code'],
            //     'price'       => $order['price'],
            //     'surplus'     => $order['surplus'],
            //     'amount'      => $order['amount'],
            //     'money'       => $order['money'],
            //     'tax'         => $order['tax'],
            //     'uid'         => $order['uid'],
            //     'status'      => $status,
            // ])->query();
            Db::table('Listbuy')->insert([
                'daytime'     => $order['daytime'],
                'create_time' => $time,
                'update_time' => $time,
                'sn'          => $order['sn'],
                'code'        => $order['code'],
                'price'       => $order['price'],
                'surplus'     => $order['surplus'],
                'amount'      => $order['amount'],
                'money'       => $order['money'],
                'tax'         => $order['tax'],
                'uid'         => $order['uid'],
                'status'      => $status,
            ]);
        }
    }

    /*******************
     * 写入未成交的卖方挂单数据
     * 
     *******************/
    public function saveListSell($order)
    {

        //id	daytime 开盘序列  create_time 挂单时间	update_time 更新时间	sn 订单序列号	code 股票代码	price 交易定价（购入定价）	surplus 剩余购入数量	amount 计划购入数量	money 计划花费金额	uid 会员代码	status 0未完成，1已完成
        if ($order) {
            //插入数据
            $status = 1;
            if ($order['surplus'] > 0) $status = 0;
            $time = time();
            // $this->db->insert('Listsell')->cols([
            //     'daytime'     => $order['daytime'],
            //     'create_time' => $time,
            //     'update_time' => $time,
            //     'sn'          => $order['sn'],
            //     'code'        => $order['code'],
            //     'price'       => $order['price'],
            //     'surplus'     => $order['surplus'],
            //     'amount'      => $order['amount'],
            //     'money'       => $order['money'],
            //     'tax'         => $order['tax'],
            //     'uid'         => $order['uid'],
            //     'status'      => $status,
            // ])->query();
            Db::table('Listsell')->insert([
                'daytime'     => $order['daytime'],
                'create_time' => $time,
                'update_time' => $time,
                'sn'          => $order['sn'],
                'code'        => $order['code'],
                'price'       => $order['price'],
                'surplus'     => $order['surplus'],
                'amount'      => $order['amount'],
                'money'       => $order['money'],
                'tax'         => $order['tax'],
                'uid'         => $order['uid'],
                'status'      => $status,
            ]);
        }
    }
    /*******************
     * 写入成交流水单
     * 
     *******************/
    public function saveListDeal($arr)
    {
        //id  daytime	type 0:卖方发起，1：买方发起	code 股票代码	amount 成交数量	money 成交金额	create_time 成交时间	buy_uid	buy_sn	sell_uid	sell_sn
        if ($arr) {
            $time = time();
            // $this->db->insert('Listdeal')->cols([
            //     'daytime'    => $arr['daytime'],
            //     'type'       => $arr['type'],
            //     'code'       => $arr['code'],
            //     'amount'     => $arr['amount'],
            //     'money'      => $arr['money'],
            //     'create_time' => $time,
            //     'buy_uid'    => $arr['buy_uid'],
            //     'buy_sn'     => $arr['buy_sn'],
            //     'buy_tax'    => $arr['buy_tax'],
            //     'sell_uid'   => $arr['sell_uid'],
            //     'sell_sn'    => $arr['sell_sn'],
            //     'sell_tax'   => $arr['sell_tax'],
            // ])->query();
            Db::table('Listdeal')->insert([
                'daytime'    => $arr['daytime'],
                'type'       => $arr['type'],
                'code'       => $arr['code'],
                'amount'     => $arr['amount'],
                'money'      => $arr['money'],
                'create_time' => $time,
                'buy_uid'    => $arr['buy_uid'],
                'buy_sn'     => $arr['buy_sn'],
                'buy_tax'    => $arr['buy_tax'],
                'sell_uid'   => $arr['sell_uid'],
                'sell_sn'    => $arr['sell_sn'],
                'sell_tax'   => $arr['sell_tax'],
            ]);

        }
    }
}
