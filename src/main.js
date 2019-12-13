// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import vueFilter from './filter/index'      //全局过滤器
//import underscore from 'vue-underscore';  //用于使用 _.isFunction 和 _.each

//Vue.use(underscore);
Vue.config.productionTip = false

for (let key in vueFilter){ 
  Vue.filter(key,vueFilter[key]) 
}

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  components: { App },
  template: '<App/>'
})
