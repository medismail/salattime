import Vue from 'vue'
import './bootstrap'
import Dashboard from './views/Dashboard'

document.addEventListener('DOMContentLoaded', function() {

        OCA.Dashboard.register('welcome', (el, { widget }) => {
                const View = Vue.extend(Dashboard)
                new View({
                        propsData: { title: widget.title },
                }).$mount(el)
        })

})
