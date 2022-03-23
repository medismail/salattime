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
			userId: null,
			userName: null,
		}
	},
	computed: {
		emptyContentMessage() {
			return t('welcome', 'No welcome content')
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
				this.content = response.data.content
				// eslint-disable-next-line
				this.content = this.content.replaceAll(/\!\[(.*)\]\(.*\?fileId=(\d+).*/g, (match, p1, p2) => {
					return '![' + p1 + '](' + generateUrl('/core/preview?fileId=' + p2 + '&x=200&y=200&a=true') + ')'
				})
				console.debug('"' + this.content + '"')
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
::v-deep .markdown-content {
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
	overflow: scroll;
	height: 100%;
	padding: 0 10px 0 10px;
	.icon-loading {
		display: block;
		margin-top: 50%;
	}
}
</style>
