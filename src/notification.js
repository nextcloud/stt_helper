/**
 * @param {event} event notification event
 */
export function handleNotification(event) {
	console.debug('stt_helper: handleNotification', event)
	if (event.notification.app !== 'stt_helper' || event.action.type !== 'WEB') {
		return
	}
	console.debug('stt_helper: handleNotification', event)
	event.cancelAction = true
	fetchTranscript(event.notification.objectId)
}

/**
 * @param {string} transcriptId transcript id
 * @return {Promise<void>}
 */
async function fetchTranscript(transcriptId) {
	const { default: axios } = await import(/* webpackChunkName: "axios-lazy" */'@nextcloud/axios')
	const { generateUrl } = await import(/* webpackChunkName: "router-lazy" */'@nextcloud/router')
	const { showError } = await import(/* webpackChunkName: "dialogs-lazy" */'@nextcloud/dialogs')

	const url = generateUrl('/apps/stt_helper/transcript?id={id}', { id: transcriptId })
	axios.get(url).then((response) => {
		if (!response.data) {
			showError(t('stt_helper', 'Error while loading results: {error}', { error: 'No transcript received' }))
			return
		}
		openModal(response.data)
	}).catch((error) => {
		console.error(error)
		showError(t('stt_helper', 'Error while loading results: {error}', { error: error.response?.data ?? '' }))
	})
}

/**
 * @param {string} transcript generated transcript
 * @return {Promise<void>}
 */
async function openModal(transcript) {
	const { default: Vue } = await import(/* webpackChunkName: "vue-lazy" */'vue')
	const { default: ResultModal } = await import(/* webpackChunkName: "stt_helper-modal-lazy" */'./components/ResultModal.vue')
	Vue.mixin({ methods: { t, n } })

	const modalElement = document.createElement('div')
	modalElement.id = 'stt_helper_modal'
	document.body.append(modalElement)

	const View = Vue.extend(ResultModal)
	const view = new View({ propsData: { transcript } }).$mount(modalElement)

	view.$on('cancel', () => {
		view.$destroy()
	})
}
