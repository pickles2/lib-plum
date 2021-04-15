/**
 * pages/main.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;
	const it79 = require('iterate79');

	this.run = function( options, callback ){
		let $html;
		let condition = main.getCondition();

		it79.fnc({}, [
			function(it1){
				$html = $( template.bind(
					'home',
					{
						'condition': condition,
					}
				) );

				$html.find('button[data-plum-target-staging-index][data-plum-method=detail]')
					.on('click', function(){
						let index = $(this).attr('data-plum-target-staging-index');
						main.loadPage('detail', {
							'serverIndex': index,
						}, function(){});
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
