<?php
/**
 * 工具
 */
namespace Server;

class Utils{
    /**
     * 订单序列号
     */
    public static function makeSN($len='4'){
        if ($len<4) $len=4; //不允许小于4位
        if ($len>8) $len=8;
        $max=pow(10,$len)-1;
        return date("YmdHis").str_pad(rand(0,$max),4,"0",STR_PAD_LEFT); //自动0
    }
    /**
     * 批量检测寻找返回真
     */
    public static function detect(array $list, $callback){
        foreach($list as $item)
        {
            if(call_user_func($callback, $item))
            {
                return $item;
            }
        }
    }
    /**
     * 以数组array的某field为标记进行排序
     */
    public static function sortArrByField(&$array, $field, $desc = false){
      $fieldArr = array();
      foreach ($array as $k => $v) {
        $fieldArr[$k] = $v[$field];
      }
      $sort = $desc == false ? SORT_ASC : SORT_DESC;
      array_multisort($fieldArr, $sort, $array);
    }
}
