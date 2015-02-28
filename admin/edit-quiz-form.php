<?php
/**
 * 퀴즈 생성 및 수정
 **/
?>
<form action="" method="post">
	<div id="poststuff">
		<div id="post-body" class="has-sidebar has-right-sidebar">
			<!-- 사이드바 -->
			<div class="inner-sidebar" id="side-info-column">
				<div id="submitdiv" class="postbox">
					저장영역
				</div>
			</div>
			<!-- //사이드바 -->
			<!-- 본문영역 -->
			<div id="post-body-content" class="has-sidebar-content">
				<div id="titlediv">
					<div id="titlewrap">
						<label class="" id="title-prompt-text" for="title"></label>
						<input type="text" name="question" size="30" value="" id="title" autocomplete="off" placeholder="<?php _e('Enter question here','quizpress');?>">
					</div>
					<div class="inside">
						<div id="edit-slug-box" class="hide-if-no-js"></div>
					</div>
					<!-- <input type="hidden" id="samplepermalinknonce" name="samplepermalinknonce" value="8ef67c56c8">-->
				</div>
				<div id="answersdiv" class="postbox">
					<h3><?php _e('Answers');?></h3>
				</div>
			</div>
			<!-- //본문영역 -->
		</div>
	</div>
</form>