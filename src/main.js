import { handleNotification } from './notification.js'

import { subscribe } from '@nextcloud/event-bus'

(function() {
	subscribe('notifications:action:execute', handleNotification)
})()
