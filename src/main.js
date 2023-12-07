import { handleNotification } from './notification.js'

import { subscribe } from '@nextcloud/event-bus'
import { linkTo } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('stt_helper', 'js/') // eslint-disable-line

;(function() {
	subscribe('notifications:action:execute', handleNotification)
})()
