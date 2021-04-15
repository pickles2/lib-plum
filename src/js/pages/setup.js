/**
 * pages/setup.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;
	const it79 = require('iterate79');

	this.run = function( options, callback ){
		let $html;

		it79.fnc({}, [
			function(it1){
				$html = $( template.bind('setup', {}) );
				$html.find('button')
					.on('click', function(){
						px2style.loading();
						$html.find('button,input,select,textarea,a').attr({'disabled': 'disabled'});
						main.gpiBridge({'api': 'init_staging_env'}, function(result){
							// console.log(result);
							px2style.closeLoading();
							main.init();
						});
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
