$(function($){
	$(window).load(function(){

		$('#init_btn').on('click', function() {

			// 画面ロック
			var h = $(window).height();
			$('#loader-bg ,#loader').height(h).css('display','block');

			var $form = $('<form>').attr({
				action : '',
				method: 'post'
			});
			$('body').append($form);

			var $input = $('<input>').attr({
				type : 'hidden',
				name : 'initialize',
				value: 'value'
			});
			
			$form.append($input);
			$form.submit();
		});

		$('.reflect').on('click', function() {
			
			// // 画面ロック
			// var h = $(window).height();
			// $('#loader-bg ,#loader').height(h).css('display','block');

			// var val = this.id;
			// var preview = val.replace('reflect_', '');
			// var select_branch = $('#branch_list_' + preview).val();

			// var $form = $('<form>').attr({
			// 	action : '',
			// 	method: 'post'
			// });
			// $('body').append($form);

			// // 反映ボタン押下イベント
			// var $input_reflect = $('<input>').attr({
			// 	type : 'hidden',
			// 	name : 'reflect',
			// 	value: 'value'
			// });

			// // previewサーバ
			// var $input_preview = $('<input>').attr({
			// 	type : 'hidden',
			// 	name : 'preview',
			// 	value: preview
			// });

			// // 選択したブランチ名
			// var $input_branch = $('<input>').attr({
			// 	type : 'hidden',
			// 	name : 'select_branch',
			// 	value: select_branch
			// });
			
			// $form.append($input_reflect);
			// $form.append($input_preview);
			// $form.append($input_branch);
			// $form.submit();
		});
	})
});
