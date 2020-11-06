/**
 * pages/main.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;

	this.run = function( callback ){
		let condition = main.getCondition();
		let $html = $( template.bind(
			'main',
			{
				'condition': condition,
			}
		) );
		callback($html);
	}
}
