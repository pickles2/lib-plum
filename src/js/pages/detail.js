/**
 * pages/main.js
 */
module.exports = function(main, template){
	const $ = main.jQuery;
	const px2style = main.px2style;
	const it79 = require('iterate79');

	this.run = function( options, callback ){
		options = options || {};
		let serverIndex = options.serverIndex;
		let $html;
		let condition = main.getCondition();

		it79.fnc({}, [
			function(it1){
				main.updateCondition( function(result){
					condition = result;
					it1.next();
				} );
			},
			function(it1){
				$html = $( template.bind(
					'detail',
					{
						'condition': condition,
						'server_info': condition.staging_server[serverIndex],
					}
				) );

				$html.find('button[data-plum-target-staging-index][data-plum-method="update-staging"]')
					.on('click', function(){
						let index = $(this).attr('data-plum-target-staging-index');
						modalUpdateStaging(index, condition, condition.staging_server[serverIndex]);
					});
				$html.find('button[data-plum-target-staging-index][data-plum-method="update-htpasswd"]')
					.on('click', function(){
						let index = $(this).attr('data-plum-target-staging-index');
						modalUpdateHtpasswd(index, condition, condition.staging_server[serverIndex]);
					});
				$html.find('button[data-plum-method="goto-home"]')
					.on('click', function(){
                        main.loadPage('home', {}, function(){});
					});
				it1.next();
			},
			function(it1){
				callback($html);
				it1.next();
			},
		]);
	}

	/**
	 * ステージング更新モーダルダイアログ
	 */
	function modalUpdateStaging(index, condition, server_info){
		var $body = $( template.bind(
			'detail-update-staging-modal',
			{
				'condition': condition,
				'server_info': server_info,
			}
		) );
		var modalObj;
		px2style.modal({
			"title": "ステージング "+(server_info.name)+" を更新",
			"body": $body,
			"buttons": [
				$('<button>')
					.text('更新する')
					.addClass('px2-btn')
					.addClass('px2-btn--primary')
					.on('click', function(){
						let selected_branch_name = $body.find('select[id=plum__branch-list-'+index+'] option:selected').val();
						let broadcast_callback_id = 'update_staging_'+index;

						function finish(result){
							if( !result.status ){
								console.error( result );
								alert( result.message );
								modalObj.unlock();
								modalObj.closable(true);
								px2style.closeLoading();
								return;
							}

							main.loadPage('detail', {
								'serverIndex': index,
							}, function(){
								px2style.flashMessage('ステージング '+(server_info.name)+' を更新しました。');
								modalObj.closable(true);
								px2style.closeLoading();
								px2style.closeModal();
							});
						}

						if( condition.is_async_available ){
							main.registerBroadcastCallback(broadcast_callback_id, function(message){
								result = message;
								main.removeBroadcastCallback(broadcast_callback_id);
								finish(result);
							});
						}

						px2style.loading();
						modalObj.lock();
						modalObj.closable(false);
						main.gpiBridge(
							{
								'api': 'init_staging_env',
								'index': index,
								'branch_name': selected_branch_name,
								'broadcast_callback_id': broadcast_callback_id,
							},
							function(result){
								// console.log(result);
								if( !condition.is_async_available ){
									finish(result);
									return;
								}
							}
						);
					})
			],
			"buttonsSecondary": [
				$('<button>')
					.text('キャンセル')
					.addClass('px2-btn')
					.on('click', function(){
						px2style.closeModal();
					})
			]
		}, function(obj){
			modalObj = obj;
		});
	}

	/**
	 * パスワード設定モーダルダイアログ
	 */
	function modalUpdateHtpasswd(index, condition, server_info){
		var $body = $( template.bind(
			'detail-update-htpasswd-modal',
			{
				'condition': condition,
				'server_info': server_info,
			}
		) );
		var modalObj;
		px2style.modal({
			"title": "ステージング "+(server_info.name)+" のパスワードを設定",
			"body": $body,
			"buttons": [
				$('<button>')
					.text('更新する')
					.addClass('px2-btn')
					.addClass('px2-btn--primary')
					.on('click', function(){
						let userName = $body.find('input[id=plum__ht-user-name]').val();
						let userPassWord = $body.find('input[id=plum__ht-password]').val();

						px2style.loading();
						modalObj.lock();
						modalObj.closable(false);
						main.gpiBridge(
							{
								'api': 'update_htpassword',
								'index': index,
								'user_name': userName,
								'user_password': userPassWord
							},
							function(result){
								// console.log(result);
								if( !result.status ){
									console.error( result );
									alert( result.message );
									modalObj.closable(true);
									modalObj.unlock();
									px2style.closeLoading();
									return;
								}

								main.loadPage('detail', {
									'serverIndex': index,
								}, function(){
									px2style.flashMessage('ステージング '+(server_info.name)+' のパスワードを設定しました。');
									modalObj.closable(true);
									px2style.closeLoading();
									px2style.closeModal();
								});
							}
						);
					})
			],
			"buttonsSecondary": [
				$('<button>')
					.text('キャンセル')
					.addClass('px2-btn')
					.on('click', function(){
						px2style.closeModal();
					})
			]
		}, function(obj){
			modalObj = obj;
		});
	}

}
