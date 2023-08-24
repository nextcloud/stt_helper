/* Copied from https://github.com/nextcloud/integration_openai */

<template>
	<div class="picker-content-wrapper">
		<div class="picker-content">
			<h2>
				{{ t('stt_helper', 'Speech to Text') }}
			</h2>
			<div class="form-wrapper">
				<div class="line justified">
					<div class="radios">
						<NcCheckboxRadioSwitch
							:button-variant="true"
							:checked.sync="mode"
							type="radio"
							value="record"
							button-variant-grouped="horizontal"
							name="mode"
							@update:checked="resetAudioState">
							{{ t('stt_helper', 'Record Audio') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch
							:button-variant="true"
							:checked.sync="mode"
							type="radio"
							value="choose"
							button-variant-grouped="horizontal"
							name="mode"
							@update:checked="resetAudioState">
							{{ t('stt_helper', 'Choose Audio File') }}
						</NcCheckboxRadioSwitch>
					</div>
				</div>
			</div>
			<audio-recorder v-if="mode === 'record'"
				class="recorder"
				:attempts="1"
				:time="120"
				:show-download-button="true"
				:show-upload-button="false"
				:after-recording="onRecordEnd" />
			<div v-else>
				<div class="line">
					{{ audioFilePath == null
						? t('stt_helper', 'No audio file selected (Only MP3 format is supported)')
						: t('stt_helper', 'Selected Audio File:') + " " + audioFilePath.split('/').pop() }}
				</div>
				<div class="line justified">
					<NcButton
						:disabled="loading"
						@click="onChooseButtonClick">
						{{ t('stt_helper', 'Choose Audio File') }}
					</NcButton>
				</div>
			</div>
			<div class="footer">
				<NcButton
					type="primary"
					:disabled="loading || (audioData == null && audioFilePath == null)"
					@click="onInputEnter">
					<template #icon>
						<NcLoadingIcon v-if="loading"
							:size="20" />
						<ArrowRightIcon v-else />
					</template>
					{{ t('stt_helper', 'Transcribe') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import VueAudioRecorder from 'vue2-audio-recorder'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'

import Vue from 'vue'
Vue.use(VueAudioRecorder)

const VALID_MIME_TYPES = ['audio/mpeg']
const picker = getFilePickerBuilder(t('stt_helper', 'Choose Audio File'))
	.setMimeTypeFilter(VALID_MIME_TYPES)
	.setModal(true)
	.setMultiSelect(false)
	.allowDirectories(false)
	.setType(1)
	.build()

export default {
	name: 'SttCustomPickerElement',

	components: {
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		ArrowRightIcon,
	},

	props: {
		providerId: {
			type: String,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			loading: false,
			mode: 'record',
			audioData: null,
			audioFilePath: null,
		}
	},

	methods: {
		resetAudioState() {
			this.audioData = null
			this.audioFilePath = null
		},

		async onChooseButtonClick() {
			this.audioFilePath = await picker.pick()
		},

		async onRecordEnd(e) {
			const readBlob = (blob) => {
				const reader = new FileReader()
				return new Promise((resolve) => {
					reader.addEventListener('load', () => {
						resolve(reader.result)
					})
					reader.readAsDataURL(blob)
				})
			}

			try {
				this.audioData = await readBlob(e.blob)
			} catch (error) {
				console.error('Recording error:', error)
				this.audioData = null
			}
		},

		async onInputEnter() {
			if (this.mode === 'record') {
				const url = generateUrl('/apps/stt_helper/transcribeAudio')
				const params = { audioBase64: this.audioData }
				await this.apiRequest(url, params)
			} else {
				const url = generateUrl('/apps/stt_helper/transcribeFile')
				const params = { path: this.audioFilePath }
				await this.apiRequest(url, params)
			}

			this.resetAudioState()
		},

		async apiRequest(url, params) {
			try {
				this.loading = true
				const response = await axios.post(url, params)
				this.$emit('submit', response.data)
				this.loading = false
			} catch (error) {
				this.loading = false
				console.error('API error:', error)
				showError(
					t('stt_helper', 'Failed to get transcription/translation')
					+ ': ' + (
						error.response?.data
						|| error.message
						|| t('stt_helper', 'Unknown API error')
					)
				)
			}
		},
	},
}
</script>

<style scoped lang="scss">
.picker-content-wrapper {
	width: 100%;
}

.picker-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 12px 16px 16px 16px;

	h2 {
		display: flex;
		align-items: center;
	}

	.form-wrapper {
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 100%;
		margin: 8px 0;
		.radios {
			display: flex;
		}
	}

	.line {
		display: flex;
		align-items: center;
		margin-top: 8px;
		width: 100%;
		&.justified {
			justify-content: center;
		}
	}

	.footer {
		display: flex;
		align-items: center;
		justify-content: end;
		margin-top: 8px;
		width: 100%;
	}

	:deep(.recorder) {
		background-color: var(--color-main-background) !important;
		box-shadow: unset !important;
		.ar-content * {
			color: var(--color-main-text) !important;
		}
		.ar-icon {
			background-color: var(--color-main-background) !important;
			fill: var(--color-main-text) !important;
			border: 1px solid var(--color-border) !important;
		}
		.ar-recorder__time-limit {
			position: unset !important;
		}
		.ar-player {
			&-bar {
				border: 1px solid var(--color-border) !important;
			}
			.ar-line-control {
				background-color: var(--color-background-dark) !important;
				&__head {
					background-color: var(--color-main-text) !important;
				}
			}
			&__time {
				font-size: 14px;
			}
			.ar-volume {
				&__icon {
					background-color: var(--color-main-background) !important;
					fill: var(--color-main-text) !important;
				}
			}
		}
		.ar-records {
			height: unset !important;
			&__record {
				border-bottom: 1px solid var(--color-border) !important;
			}
			&__record--selected {
				background-color: var(--color-background-dark) !important;
				border: 1px solid var(--color-border) !important;
				.ar-icon {
					background-color: var(--color-background-dark) !important;
				}
			}
		}
	}
}
</style>
