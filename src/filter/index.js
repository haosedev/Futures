//公共过滤器
const vFliter={
  toYuan:function(value){
    var num = Number(value)
    if (typeof num === 'number' && !isNaN(num)){
      num=num/100
      return num.toFixed(2)
    }else{
      return ''
    }
  },
  toYuanNumber:function(value){
    var num = Number(value)
    if (typeof num === 'number' && !isNaN(num)){
      num=num/100
      return parseFloat(num.toFixed(2))   //Number转换回来的会被识别为String，再次转换成浮点数解决问题
    }else{
      return 0
    }
  },
  mode2str:function(value){
    if (value==1) return '买入'
    else if(value==-1) return '卖出'
    else return '未知'
  }
}

export default vFliter