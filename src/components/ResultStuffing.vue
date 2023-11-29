<template>
	<div class="stt_helper-stuffing">
		<div class="stt_helper-stuffing--header">
			<SttIcon :size="24" class="icon" />
			<span>{{ t('stt_helper', 'Speech to Text') }}</span>
		</div>
		<div class="transcript-box">
			<label for="stt_helper-result" class="transcript-label">
				{{ t('stt_helper', 'Transcript') }}
			</label>
			<NcRichText
				id="stt_helper-result"
				class="plain-text"
				:text="transcript" />
		</div>
		<div class="line copy-box">
			<NcButton
				type="primary"
				:disabled="copied"
				:title="t('stt_helper', 'Copy')"
				:aria-label="t('stt_helper', 'Copy Transcript to Clipboard')"
				@click="onCopy">
				{{ copied
					? t('stt_helper', 'Copied')
					: t('stt_helper', 'Copy to Clipboard') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import SttIcon from './icons/SttIcon.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcRichText from '@nextcloud/vue/dist/Components/NcRichText.js'

import { showError } from '@nextcloud/dialogs'

export default {
	name: 'ResultStuffing',

	components: {
		NcButton,
		NcRichText,
		SttIcon,
	},

	props: {
		transcript: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			copied: false,
		}
	},

	methods: {
		onCancel() {
			this.$emit('cancel')
		},
		async onCopy() {
			try {
				await navigator.clipboard.writeText(this.transcript)
				this.copied = true
				setTimeout(() => {
					this.copied = false
					this.onCancel()
				}, 1000)
			} catch (error) {
				console.error(error)
				showError(t('stt_helper', 'Result could not be copied to the clipboard'))
			}
		},
	},
}
</script>

<style scoped lang="scss">
.stt_helper-stuffing {
	display: flex;
	flex-direction: column;

	&--header {
		align-self: start;
		display: flex;
		gap: 8px;
		margin: 12px 0;
		font-size: 1.2rem;
		font-weight: bold;

		.icon {
			color: var(--color-primary);
		}
	}

	.transcript-box {
		margin-bottom: 12px;

		.transcript-label {
			font-size: 1.05rem;
			font-weight: bold;
			margin-bottom: 12px;
		}
		.plain-text {
			padding: 4px;
			padding-left: 8px;
			padding-right: 8px;
			font-size: 1.0rem;
			border: 2px solid var(--color-primary-element-light);
			border-radius: var(--border-radius-large);
		}
	}

	.copy-box {
		display: flex;
		justify-content: end;
	}
}
</style>
