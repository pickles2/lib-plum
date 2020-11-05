/**
 * pages/setup.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;
	const it79 = require('iterate79');

	this.run = function( callback ){
		let $html;

		it79.fnc({}, [
			function(it1){
				$html = $( template.bind('setup', {}) );
				$html.find('button')
					.on('click', function(){
						alert('開発中です。');
					});
				it1.next();
			},
			function(it1){
				callback($html);
				it1.next();
			},
		]);
	}
}
