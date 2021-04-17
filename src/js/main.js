/**
 * main.js
 */
module.exports = function($elm, options){
	const _this = this;
	const main = this;
	const $ = require('jquery');
	this.$ = this.jQuery = $;
	const Px2style = require('px2style'),
		px2style = new Px2style();
	this.px2style = px2style;
	this.px2style.setConfig('additionalClassName', 'plum');
	const it79 = require('iterate79');

	const $elms = {
		'main': $( $elm ),
	};
	const pages = {
		home: require('./pages/home.js'),
		setup: require('./pages/setup.js'),
		detail: require('./pages/detail.js'),
	};
	const template = new (require('./template.js'))(this, $elms);

	let condition = {};

	options = options || {};
	options.gpiBridge = options.gpiBridge || function(){};



	/**
	 * 画面を初期化する
	 */
	this.init = function(){

		it79.fnc({}, [
			function(it1){
				$elms.main.removeClass('plum').addClass('plum');
				it1.next();
			},
			function(it1){
				// 状態情報を更新
				main.updateCondition(function(result){
					// console.log(result);
					it1.next();
				});
			},
			function(it1){
				if( !condition.is_local_master_available ){
					main.loadPage('setup', {}, function(){
						it1.next();
					});
				}else{
					main.loadPage('home', {}, function(){
						it1.next();
					});
				}
			},
			function(it1){
				it1.next();
			},
			function(it1){
				console.log('Standby');
				it1.next();
			}
		]);

	}

	/**
	 * 状態情報を取得する
	 *
	 * 最後に取得した状態情報のキャッシュを返します。
	 * この関数では、状態情報の更新は行いません。
	 */
	this.getCondition = function(){
		return condition;
	}

	/**
	 * 状態情報を更新する
	 */
	this.updateCondition = function( callback ){
		callback = callback || function(){};
		main.gpiBridge({'api': 'get_condition'}, function(result){
			// console.log(result);
			condition = result;
			callback( condition );
		});
		return;
	}

	/**
	 * GPIを呼び出す
	 */
	this.gpiBridge = function( gpiOptions, callback ){
		options.gpiBridge(gpiOptions, function(result){
			// console.log(result);
			callback(result);
		});
		return;
	}

	/**
	 * ブロードキャストメッセージを受信する
	 */
	this.broadcastMessage = function( message ){
		console.info('--- broadcast message:', message);
	}

	/**
	 * ページをロードする
	 */
	this.loadPage = function(pageName, options, callback){
		const page = new pages[pageName](this, template);
		px2style.loading();
		page.run(options, function($dom){
			$elms.main.html('');
			$elms.main.append( $dom );
			px2style.closeLoading();
			callback();
		});
		return;
	}

};
