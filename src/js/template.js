/**
 * template.js
 */
module.exports = function(main){
	const templates = {
		"mainframe": require('../resources/templates/mainframe.html.twig'),
		"before_initialize": require('../resources/templates/before_initialize.html.twig'),
	};

    this.bind = function(templateName, data){
        var rtn = templates[templateName](data);
        return rtn;
    }
}
