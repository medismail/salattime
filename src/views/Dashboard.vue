<template>
	<div class="salattime-widget">
		<NcLoadingIcon v-if="loading" :size="36" />
		<div v-else-if="error" class="salattime-widget__empty" role="status">
			{{ t('Salat time content is unavailable') }}
		</div>
		<template v-else>
			<header class="salattime-widget__header">
				<h3>{{ widgetData.hijri }}</h3>
				<span v-if="widgetData.city">{{ widgetData.city }}</span>
			</header>
			<ul class="salattime-widget__prayers">
				<li v-for="prayer in widgetData.prayers"
					:key="prayer.key"
					:class="{ 'salattime-widget__prayer--next': prayer.isNext }">
					<span>{{ prayer.label }}</span>
					<strong>{{ prayer.time }}</strong>
				</li>
			</ul>
			<footer v-if="widgetData.nextPrayer" class="salattime-widget__next">
				<span>{{ t('Next') }}: {{ widgetData.nextPrayer.label }}</span>
				<strong>{{ widgetData.remaining }}</strong>
			</footer>
		</template>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { translate } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

export default {
	name: 'SalatTimeDashboard',
	components: {
		NcLoadingIcon,
	},
	data() {
		return {
			loading: true,
			error: false,
			widgetData: {
				hijri: '',
				city: '',
				prayers: [],
				nextPrayer: null,
				remaining: '',
			},
		}
	},
	async mounted() {
		try {
			const response = await axios.get(generateUrl('/apps/salattime/api/v1/widget'))
			this.widgetData = response.data.ocs.data
		} catch (error) {
			this.error = true
			console.error('Could not load the Salat Time widget', error)
		} finally {
			this.loading = false
		}
	},
	methods: {
		t(text) {
			return translate('salattime', text)
		},
	},
}
</script>

<style scoped lang="scss">
.salattime-widget {
	display: flex;
	flex-direction: column;
	height: 100%;
	padding: 0 12px 12px;

	&__header {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		padding: 4px 8px 10px;
		border-bottom: 1px solid var(--color-border);

		h3 {
			margin: 0;
			font-size: 16px;
		}

		span {
			color: var(--color-text-maxcontrast);
		}
	}

	&__prayers {
		flex: 1;
		margin: 0;
		padding: 6px 0;
		list-style: none;
	}

	&__prayers li {
		display: flex;
		justify-content: space-between;
		padding: 7px 10px;
		border-radius: var(--border-radius);
	}

	&__prayer--next {
		color: var(--color-primary-element-text);
		background: var(--color-primary-element);
	}

	&__next {
		display: flex;
		justify-content: space-between;
		padding: 10px;
		border-top: 1px solid var(--color-border);
	}

	&__empty {
		display: grid;
		place-items: center;
		min-height: 180px;
		color: var(--color-text-maxcontrast);
		text-align: center;
	}

	:deep(.loading-icon) {
		margin: auto;
	}
}
</style>
