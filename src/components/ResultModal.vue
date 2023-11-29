<template>
	<NcModal
		size="normal"
		:can-close="false"
		class="stt_helper-modal">
		<div class="stt_helper-modal">
			<div ref="modal_content"
				class="stt_helper-modal--wrapper">
				<div class="stt_helper-modal--content">
					<NcButton :aria-label="t('stt_helper', 'Close Modal')"
						:title="t('stt_helper', 'Close')"
						type="tertiary"
						class="close-button"
						@click="onCancel">
						<template #icon>
							<CloseIcon />
						</template>
					</NcButton>
					<ResultStuffing :transcript="transcript" @cancel="onCancel" />
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import CloseIcon from 'vue-material-design-icons/Close.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'

import ResultStuffing from './ResultStuffing.vue'

export default {
	name: 'ResultModal',

	components: {
		CloseIcon,
		NcButton,
		NcModal,
		ResultStuffing,
	},

	props: {
		transcript: {
			type: String,
			required: true,
		},
	},

	methods: {
		onCancel() {
			this.$emit('cancel')
		},
	},
}
</script>

<style lang="scss">
.stt_helper-modal {
	display: flex !important;
}
</style>

<style scoped lang="scss">
.stt_helper-modal {
	display: flex;
	flex-direction: column;

	&--wrapper {
		padding: 16px;
		overflow-y: auto;
	}

	&--content {
		display: flex;
		flex-direction: column;
		overflow-y: auto;

		.close-button {
			position: absolute;
			top: 4px;
			right: 4px;
		}
	}
}
</style>
