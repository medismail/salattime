import { createApp } from 'vue'
import Dashboard from './views/Dashboard.vue'

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('salattime', (element) => {
		createApp(Dashboard).mount(element)
	})
})
