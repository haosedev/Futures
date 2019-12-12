import Vue from 'vue'
import Router from 'vue-router'
import cons from '@/constants/constants'
import Main from '@/components/Main'

Vue.prototype.cons = cons //引用常量
Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Main',
      component: Main
    }
  ]
})
