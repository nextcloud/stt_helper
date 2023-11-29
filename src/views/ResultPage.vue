<template>
	<NcContent app-name="stt_helper">
		<NcAppContent class="wrapper">
			<div class="toffee">
				<NcNoteCard v-if="result.status !== 'success'"
					type="error"
					heading="Error in Speech to Text">
					<p>{{ result.message }}</p>
				</NcNoteCard>
				<ResultStuffing
					v-else
					class="result-stuffing"
					:transcript="result.transcript" />
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import ResultStuffing from '../components/ResultStuffing.vue'

import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'ResultPage',

	components: {
		NcContent,
		NcAppContent,
		NcNoteCard,
		ResultStuffing,
	},

	data() {
		return {
			result: loadState('stt_helper', 'result'),
		}
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.wrapper {
	display: flex;
	justify-content: center;
	margin: 24px 16px 16px 16px;

	.toffee {
		width: 550px;
	}
}
</style>
