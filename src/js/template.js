/**
 * template.js
 */
module.exports = function(main, $elms){
	const $ = main.jQuery;
	const templates = {
		"home": require('../resources/templates/home.html.twig'),
		"setup": require('../resources/templates/setup.html.twig'),
		"detail": require('../resources/templates/detail.html.twig'),
	};
	this.elms = $elms;

	this.bind = function(templateName, data){
		var rtn = templates[templateName](data);
		return rtn;
	}
}
