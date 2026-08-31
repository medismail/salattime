module.exports = {
	root: true,
	extends: [
		'@nextcloud/eslint-config/vue3',
	],
	parserOptions: {
		requireConfigFile: false,
	},
	rules: {
		'jsdoc/require-jsdoc': 'off',
		'jsdoc/tag-lines': 'off',
	},
}

