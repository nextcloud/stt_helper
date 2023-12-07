import Vue from 'vue'

import { linkTo } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'

import ResultPage from './views/ResultPage.vue'

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('stt_helper', 'js/') // eslint-disable-line

Vue.mixin({ methods: { t, n } })

const View = Vue.extend(ResultPage)
new View().$mount('#stt_helper-content')
