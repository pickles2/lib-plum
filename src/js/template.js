/**
 * template.js
 */
module.exports = function(main, $elms){
	const $ = main.jQuery;
	const templates = {
		"main": require('../resources/templates/main.html.twig'),
		"setup": require('../resources/templates/setup.html.twig'),
	};
	this.elms = $elms;

	this.bind = function(templateName, data){
		var rtn = templates[templateName](data);
		return rtn;
	}
}
