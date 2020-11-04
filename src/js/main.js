/**
 * main.js
 */
module.exports = function($elm, options){
	const $ = require('jquery');
	const Px2style = require('px2style'),
		px2style = new Px2style();
	this.px2style = px2style;
	this.px2style.setConfig('additionalClassName', 'plum');
	const template = new (require('./template.js'));
	const it79 = require('iterate79');

	const $elms = {
		'main': $( $elm ),
	};

	options = options || {};
	options.gpiBridge = options.gpiBridge || function(){};



	/**
	 * 画面を初期化する
	 */
	this.init = function(){

		it79.fnc({}, [
			function(it1){
				$elms.main.addClass('plum');
				$elms.main.html( template.bind('mainframe', {}) );
				it1.next();
			},
			function(it1){
				options.gpiBridge({'command': 'initialize'}, function(result){
					it1.next();
				});
			},
			function(it1){
				console.log('Standby');
				it1.next();
			}
		]);




		/**
		 * initializeボタン
		 */
		var el_init_btn = document.getElementById("init_btn");
		if (el_init_btn != null) {
			el_init_btn.addEventListener("click", function() {

				// 画面ロック
				display_lock();

				// form作成
				var form = document.createElement('form');
				form.setAttribute('action', '');
				form.setAttribute('method', 'post');

				// formをbodyに追加
				var body = document.getElementsByTagName('body');
				body.appendChild(form);

				// inputを作成
				var input = document.createElement('input');
				input.setAttribute('type', 'hidden');
				input.setAttribute('name', 'initialize');
				input.setAttribute('value', 'value');

				// inputをformに追加
				form.appendChild(input);		
				
				// formをsubmit
				form.submit();

			} , false);
		}


		/**
		 * 反映ボタン
		 */
		var el_reflect_btn = document.getElementsByClassName("reflect");
		if (el_reflect_btn != null) {
			for (var i = 0; i < el_reflect_btn.length; i++) {
				el_reflect_btn[i].addEventListener("click", function() {
					// 画面ロック
					display_lock();
				} , false);	
			}
		}


		/**
		 * 状態ダイアログ[閉じる]ボタン
		 */
		var el_close_btn = document.getElementById("close_btn");
		if (el_close_btn != null) {
			el_close_btn.addEventListener("click", function() {

				var dialog = document.getElementById('status_dialog');
				dialog.remove();

			} , false);
		}
	}

	/**
	 * 画面ロック
	 */
	function display_lock() {
		px2style.loading();
	}

};
