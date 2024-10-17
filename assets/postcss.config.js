// postcss.config.js
module.exports = {
	plugins: [
		require('tailwindcss'),
		require('autoprefixer'),
		require('postcss-prefix-selector')({
			// Le sélecteur global sous lequel toutes les classes seront encapsulées
			prefix: '.ux-editor',
			
			// Exclure les sélecteurs globaux comme html et body pour éviter de casser le style de base
			exclude: [/^html$/, /^body$/],
		}),
	],
};
