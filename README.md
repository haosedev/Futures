# futures

> workerman make futures

## Build Setup

``` bash
# install dependencies
npm install

# serve with hot reload at localhost:8080
npm run dev

# build for production with minification
npm run build

# build for production and view the bundle analyzer report
npm run build --report
```

#存在问题待解决
1.组件弹框的动画问题。弹出框时的动画，点击其他背景时的动画，关闭时的动画。

OK 2.关于关闭组件是否要提交到父组件来设置visibale属性。（解决：利用sync双向绑定变量，在子组件内通过 this.$emit('update:panelShow', false)来修改父组件的变量）