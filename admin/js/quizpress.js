jQuery(document).ready(function($){
	$('.quizpress_tabs a').bind('click',function(){
		var selectId = $(this).parent().attr('id');
		$('input[name="answer_type"]').val(selectId);
		$('.quizpress_tabs li').attr('class','');
		$('.quizpress_tabs #'+selectId).attr('class','selected');
		$('#quizpress_answers .quizpress_panel').css('display','none');
		$('#quizpress_answers #'+selectId+'_panel').css('display','block');
		return false;
	});
	$('#add-answer-holder button').bind('click',function(){
		var liLength = $('#quizpress_answers #answers li').length;
		liLength += 1;
		if(liLength > 5){
			alert(quizpress.answer_add_max);
			return false;
		}
		var newLi = '<li>';
		newLi += '<input type="text" autocomplete="off" placeholder="Enter an wrong answer here" value="" tabindex="2" size="30" name="answers[new'+liLength+']">';
		//newLi += ' <label for="thisanswer'+liLength+'">정답</label>';
		//newLi += '<input type="radio" name="thisanswer" id="thisanswer'+liLength+'" value="new'+liLength+'"/>';
		newLi += '</li>';
		$('#quizpress_answers #answers').append(newLi);
		return false;
	});
	$('#publishing-action input[type="submit"]').bind('click',function(){
		var this_answer = true;
		var answer_type = $('input[name="answer_type"]').val();
		if(answer_type == 'optional'){
			$('#quizpress_answers #answers li').each(function(){
				if($(this).find('input[type="text"]').val() == ''){
					alert('보기를 입력해주세요.');
					$(this).find('input[type="text"]').focus();
					this_answer = false;
					return false;
				}
			});
			
			/*if(this_answer && $('#quizpress_answers #answers li input:checked').val() == undefined){
				alert('정답을 선택해주세요.');
				return false;
			}*/
		}
		if(answer_type == 'short'){
			if($('div#short_panel input[type="text"]').val() == ''){
				alert('답안을 입력해주세요.');
				$('div#short_panel input[type="text"]').focus();
				return false;
			}
		}
		return true;
	});
});