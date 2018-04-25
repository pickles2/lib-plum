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
			

		});

		$('#close_btn').on('click', function() {
			var $dialog = $('.dialog');
			$dialog.remove();
		});
	})
});
