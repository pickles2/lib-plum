/**
 * pages/main.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;
	const it79 = require('iterate79');

	this.run = function( callback ){
		let $html;
		let condition = main.getCondition();

		it79.fnc({}, [
			function(it1){
				$html = $( template.bind(
					'main',
					{
						'condition': condition,
					}
				) );

				$html.find('button[data-plum-target-staging-index]')
					.on('click', function(){
						let index = $(this).attr('data-plum-target-staging-index');
						let selected_branch_name = $html.find('select[id=plum__branch-list-'+index+'] option:selected').val();
						px2style.loading();
						$html.find('button,input,select,textarea,a').attr({'disabled': 'disabled'});
						main.gpiBridge(
							{
								'api': 'init_staging_env',
								'index': index,
								'branch_name': selected_branch_name
							},
							function(result){
								// console.log(result);
								px2style.closeLoading();
								main.init();
							}
						);
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
