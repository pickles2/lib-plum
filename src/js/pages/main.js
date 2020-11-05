/**
 * pages/main.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;

	this.run = function( callback ){
		let $html = $( template.bind('main', {}) );
		callback($html);
	}
}
