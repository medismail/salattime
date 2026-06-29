import { createApp } from 'vue'
import App from './views/App.vue'

document.addEventListener('DOMContentLoaded', () => {
	const element = document.getElementById('salattime-app')
	if (!element) {
		return
	}

	let initialState = {}
	try {
		initialState = JSON.parse(element.dataset.state || '{}')
	} catch (error) {
		console.error('Could not parse Salat Time initial state', error)
	}

	createApp(App, {
		initialView: element.dataset.view || 'overview',
		initialState,
	}).mount(element)
})
