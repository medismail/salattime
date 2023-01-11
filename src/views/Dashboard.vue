<template>
	<div id="salattime-widget">
		<span v-if="loading" class="icon icon-loading" />
		<VueMarkdown v-else-if="content"
			class="markdown-content">
			{{ content }}
		</VueMarkdown>
		<EmptyContent
			v-else
			:icon="emptyContentIcon">
			<template #desc>
				{{ emptyContentMessage }}
			</template>
		</EmptyContent>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import VueMarkdown from 'vue-markdown'
export default {
	name: 'Dashboard',
	components: {
		// DashboardWidget,
		EmptyContent,
		VueMarkdown,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			content: '',
		}
	},
	computed: {
		emptyContentMessage() {
			return t('salattime', 'Salat time content is unavailable')
		},
		emptyContentIcon() {
			return 'icon-close'
		},
	},
	beforeMount() {
		this.getContent()
	},
	mounted() {
	},
	methods: {
		getContent() {
			const url = generateUrl('/apps/salattime/widget-content')
			axios.get(url).then((response) => {
				this.content = response.data.ocs.data.content
				// console.debug('"' + this.content + '"')
			}).catch((error) => {
				console.debug(error)
			}).then(() => {
				this.loading = false
			})
		},
	},
}
</script>

<style scoped lang="scss">
:deep(.markdown-content) {
	h1, h2, h3, h4, h5 {
		font-weight: bold;
		margin: 12px 0 12px 0;
	}
	h1 {
		font-size: 30px;
		line-height: 30px;
	}
	h2 {
		font-size: 20px;
		line-height: 20px;
	}
	h3 {
		font-size: 16px;
		line-height: 20px;
	}
	h4 {
		font-size: 14px;
		line-height: 20px;
	}
	h5 {
		font-size: 12px;
		line-height: 20px;
	}
	ul, ol {
		list-style-type: none;
		li {
			list-style-type: none;
		}
		li:before {
			content: '•';
			padding-right: 8px;
		}
		ul, ol {
			margin-left: 20px;
			li:before {
				content: '∘';
			}
			ul, ol {
				li:before {
					content: '⁃';
				}
			}
		}
	}
	a {
		color: var(--color-text-light);
		text-decoration: underline;
	}
	td, th {
		border: 1px solid #ddd;
		padding: 6px;
	}
	tr:nth-child(even) {
		color: black;
		background-color: #f2f2f2;
	}
	th {
		padding-top: 6px;
		padding-bottom: 6px;
		text-align: left;
		background-color: #04AA6D;
		color: white;
	}
	> p {
		img {
			display: block;
			margin: 0 auto 0 auto;
			height: 100px;
			width: auto;
		}
	}
}

#salattime-widget {
	height: 100%;
	padding: 0 10px 0 10px;
	.icon-loading {
		display: block;
		margin-top: 50%;
	}
}
</style>
