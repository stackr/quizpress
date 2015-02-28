var QuizPress = function(){
	var QuizPress = this;
	this.quiz = '';
	this.idx = 0;
	this.correct_count = 0;
	this.wrong_count = 0;
	this.init = function(){
		jQuery(document).ready(function($){
			$('a.answer_link').live('click',function(){
				var quiz_id = $(this).parent().parent().parent().find('.quiz_question').attr('id').replace('quiz-','');
				var quiz_view = $(this).parent().parent().parent().find('.quiz');
				var quiz_count = $(this).parent().parent().parent().find('.count');
				var answer = $(this);
				$.post(
						QuizPress.ajax_url,
						{
							action:'submit_answer',
							quiz_id:quiz_id,
							answer:$(this).html()
						},
						function(res){

							if(res.success){
								answer.parent().addClass('correct');
								QuizPress.correct_count++;
							}else{
								answer.parent().addClass('wrong');
								QuizPress.wrong_count++;
							}
							if(QuizPress.type == 'category'){
								setTimeout(function(){
									html = QuizPress.createQuiz(QuizPress.idx);
									quiz_view.html(html);
									quiz_count.find('em').text(QuizPress.idx);
								},1000);

							}
						},
						'json'
					);
				return false;
			});
			$('a.submit_link').live('click',function(){

				var quiz_id = $(this).parent().parent().parent().find('.quiz_question').attr('id').replace('quiz-','');
				var quiz_view = $(this).parent().parent().parent().find('.quiz');
				var submit = $(this);
				var answer = $(this).parent().parent().parent().find('input[name="answer"]').val();

				$.post(
						QuizPress.ajax_url,
						{
							action:'submit_answer',
							quiz_id:quiz_id,
							answer:answer
						},
						function(res){

							if(res.success){
								submit.parent().removeClass('wrong');
								submit.parent().addClass('correct');
								QuizPress.correct_count++;
							}else{
								submit.parent().removeClass('correct');
								submit.parent().addClass('wrong');
								QuizPress.wrong_count++;
							}
							if(QuizPress.type == 'category'){
								setTimeout(function(){
									html = QuizPress.createQuiz(QuizPress.idx);
									quiz_view.html(html);},1000);
							}
						},
						'json'
					);
				return false;
			});
		});
		this.createHtml();

	},
	this.createHtml = function(){

		jQuery.post(
				QuizPress.ajax_url,
				{
					action:'get_quiz_html',
					id:QuizPress.id,
					type:QuizPress.type
				},
				function(res){
					if(res.success){
						var data = res.data;
						QuizPress.quiz = data.quiz;
						var html = '';
						html += '<div id="header">';
						html += '<h2>'+data.title+'</h2>';
						html += '</div>';
						html += '<div id="content">';
						html += '<div class="quizProcCon" id="quizProcCon">';
						if(QuizPress.type == 'category'){
							html += '<div class="count"><em>1</em> / '+data.quiz.length+'</div>';
						}
						html += '<dl class="quiz">';
						html += QuizPress.createQuiz(0);
						
						html += '</dl>';
						html += '</div>';
						html += '</div>';
						jQuery('div#quizpress-'+QuizPress.type+'-'+QuizPress.id).html(html);
						
					}else{
						alert(res.msg);
					}
				},
				'json'
			);

	},
	this.createQuiz = function(idx){
		html = '';
		data = QuizPress.quiz;
		if(!data[idx]){
			return this.resultView();
		}
		html += '<dt><dt class="quiz_question" id="quiz-'+data[idx].quiz_id+'"><span>Q. </span>'+data[idx].question+'</dt></dt>';
						
		if(data[idx].answer_type == 'short'){
			html += '<dd class="answer">';
			html += '<input type="text" name="answer" value=""/>';
			html += '<a class="submit_link">제출</a>';
			html += '</dd>';
		}else{
			for(i=0; i<data[idx].answers.length; i++){

				html += '<dd class="answer">';
				html += '<a class="answer_link" href="#">'+data[idx].answers[i]+'</a>';
				html += '</dd>';
			}
		}

		QuizPress.idx = idx + 1;
		return html;
	},
	this.resultView = function(){
		data = QuizPress.quiz;
		html = '';
		html += '<div class="displayResult">';
		html += '	<div class="quizScore">';
		html += '		<em class="number">'+data.length+'</em>';
		html += '		<em class="got">'+this.correct_count+'</em>';
		html += '		<em class="missed">'+this.wrong_count+'</em>';
		html += '	</div>';
		
		html += '</div>';
		return html;
	}
}
