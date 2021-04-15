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

				$html.find('button[data-plum-target-staging-index][data-plum-method=deploy]')
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
