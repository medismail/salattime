import Vue from 'vue'
import './bootstrap'
import App from './views/App'

document.addEventListener('DOMContentLoaded', () => {
	const el = document.getElementById('salattime-app')
	if (!el) {
		return
	}

	let initialState = {}
	try {
		initialState = JSON.parse(el.dataset.state || '{}')
	} catch (error) {
		console.debug('Could not parse Salat Time initial state', error)
	}

	const View = Vue.extend(App)
	new View({
		propsData: {
			initialView: el.dataset.view || 'overview',
			initialState,
		},
	}).$mount(el)
})
