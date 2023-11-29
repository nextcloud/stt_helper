import Vue from 'vue'
import ResultPage from './views/ResultPage.vue'
Vue.mixin({ methods: { t, n } })

const View = Vue.extend(ResultPage)
new View().$mount('#stt_helper-content')
