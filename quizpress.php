<?php
/*
 * Plugin Name: QuizPress
 * Plugin URI: http://wordpress.org/extend/plugins/quizpress/
 * Description: Create quiz in WordPress
 * Author: Stackr Inc.
 * Author URL: http://www.stackr.co.kr
 * Version: 1.0
 * Text Domain: quizpress
 * Domain Path: /languages/
 */
define('QUIZPRESS_ADMINPATH',dirname(__FILE__).'/admin/');
class WP_QuizPress {
	var $version;
	var $is_author;
	var $action;
	function __construct() {
		if ( did_action( 'plugins_loaded' ) )
			self::plugin_textdomain();
		else
			add_action( 'plugins_loaded', array( __CLASS__, 'plugin_textdomain' ) );

		$this->register_post_type();
		$this->version                = '1.0';
		$this->is_author              = (bool) current_user_can( 'edit_posts' );
		$this->action = empty($_GET['action']) ? '' : $_GET['action'];
		if ( !$this->is_author && in_array( $this->action, array( 'create' ) ) ) {
			$this->action = '';
		}

		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ));
		add_action( 'admin_head', array( &$this, 'quizpress_style' ) );
		add_action('save_post',array(&$this,'save_post'));
		add_shortcode( 'quizpress', array(&$this, 'quizpress') );
		add_action('wp_ajax_nopriv_get_quiz_html', array(&$this, 'get_quiz_html'));
		add_action('wp_ajax_get_quiz_html',array(&$this, 'get_quiz_html'));
		add_action('wp_ajax_nopriv_submit_answer', array(&$this, 'submit_answer'));
		add_action('wp_ajax_submit_answer',array(&$this, 'submit_answer'));
		add_filter('title_save_pre',array(&$this, 'title_save_pre'));
		add_action( 'media_buttons', array( &$this, 'media_buttons' ) ,99);

	}
	public static function plugin_textdomain() {
		load_plugin_textdomain( 'quizpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	function wp_enqueue_scripts(){
		wp_enqueue_script( 'quizpress', plugins_url( 'quizpress.js', __FILE__ ), array( 'jquery' ), $this->version );
		wp_enqueue_style( 'quizpress', plugins_url( 'quizpress.css', __FILE__ ));
	}
	function quizpress_style(){
		wp_register_style( 'quizpress-admin', plugins_url( 'admin/css/quizpress.css', __FILE__ ) );
		wp_enqueue_style( 'quizpress-admin' );
	}
	function register_post_type(){
		$labels = array(
			'name'               => __( 'Quizzes', 'quizpress' ),
			'singular_name'      => __( 'Quiz', 'quizpress' ),
			'menu_name'          => __( 'Quizzes', 'quizpress' ),
			'name_admin_bar'     => __( 'Quiz', 'quizpress' ),
			'add_new'            => __( 'Add New', 'quiz', 'quizpress' ),
			'add_new_item'       => __( 'Add New Quiz', 'quizpress' ),
			'new_item'           => __( 'New Quiz', 'quizpress' ),
			'edit_item'          => __( 'Edit Quiz', 'quizpress' ),
			'view_item'          => __( 'View Quiz', 'quizpress' ),
			'all_items'          => __( 'All Quizzes', 'quizpress' ),
			'search_items'       => __( 'Search Quizzes', 'quizpress' ),
			'parent_item_colon'  => __( 'Parent Quizzes:', 'quizpress' ),
			'not_found'          => __( 'No quizzes found.', 'quizpress' ),
			'not_found_in_trash' => __( 'No quizzes found in Trash.', 'quizpress' ),
		);
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'			 => false,
			//'rewrite'            => array( 'slug' => 'quiz' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'thumbnail' )
		);
		register_post_type( 'quiz', $args );
		register_taxonomy('quiz_tag','quiz',	array( 
			'hierarchical' => false, 
			'public'		=> false,
			'label' => __('Quiz Tag','quizpress'),
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array('slug' => 'quiz_tag'),
			'singular_label' => 'Quiz Tag'
			)
		);
		register_taxonomy('quiz_category','quiz',	array( 
			'hierarchical' => true, 
			'public'		=> false,
			'label' => __('Quiz Category','quizpress'),
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array('slug' => 'quiz_category'),
			'singular_label' => 'Quiz Category'
			)
		);

	}
	//문제 메타 박스
	function question_box($post){
		//$question = get_post_meta($post->ID,'_question',true);
		//wp_editor($question,'question');
		wp_editor($post->post_content,'post_content');
	}
	//답안 메타 박스
	function answer_box($post){
		$answer_type = get_post_meta($post->ID, '_answer_type',true);
		$answers = get_post_meta($post->ID, '_answers',true);
		$answer = get_post_meta($post->ID, '_answer',true);
		wp_nonce_field( 'quizpress_meta_nonce', 'quizpress_meta_nonce' );
		$answer_type = ($answer_type == '') ? 'optional' : $answer_type;
?>
		<input type="hidden" name="answer_type" value="<?php echo $answer_type;?>"/>
		<div class="inside">
			<ul class="quizpress_tabs">
				<li<?php if($answer_type == 'optional'){echo ' class="selected"';}?> id="optional">
					<a href="#"><?php _e('Optional answers');?></a>
				</li>
				<li<?php if($answer_type == 'short'){echo ' class="selected"';}?> id="short">
					<a href="#"><?php _e('Short answers');?></a>
				</li>
			</ul>
			<div class="quizpress_panel" id="optional_panel"<?php if($answer_type == 'short'){echo ' style="display:none;"';}?>>
				<div id="answerswrap" class="inside">
					<ul id="answers" class="ui-sortable">
						<li class="correct_answer" style="border-bottom:1px dotted #000;margin-bottom:10px; padding-bottom:10px;">
							<input type="text" autocomplete="off" placeholder="Enter an correct answer here" value="<?php echo $answer;?>" tabindex="2" size="30" name="correct_answer">
						</li>
						<?php if($answer != ''):?>
						<?php $new = 1;?>
						<?php foreach($answers as $answer_v):?>
						<?php if($answer_v != $answer):?>
						<li>
							<input type="text" autocomplete="off" placeholder="Enter an wrong answer here" value="<?php echo $answer_v;?>" tabindex="2" size="30" name="answers[new<?php echo $new;?>]">
						</li>
						<?php endif;?>
						<?php $new++;?>
						<?php endforeach;?>
						<?php else:?>
						<li>
							<input type="text" autocomplete="off" placeholder="Enter an wrong answer here" value="" tabindex="2" size="30" name="answers[new1]">
						</li>
						<?php endif;?>
					</ul>

					<p id="add-answer-holder" style="display: block;">
						<button class="button">Add New Answer</button>
					</p>

				</div>
			</div>
			<div class="quizpress_panel" id="short_panel"<?php if($answer_type == 'optional'){echo ' style="display:none;"';}?>>
				<div id="answerswrap" class="inside">
					<input type="text" autocomplete="off" placeholder="Enter an answer here" value="<?php echo $answer;?>" tabindex="2" size="30" name="answer">
				</div>
			</div>
		</div>
<?php
	}
	//퀴즈정보 저장
	function save_post($post_id){

		if ( ! isset( $_POST['quizpress_meta_nonce'] ) )
			return $post_id;

		$nonce = $_POST['quizpress_meta_nonce'];

		if ( ! wp_verify_nonce( $nonce, 'quizpress_meta_nonce' ) )
			return $post_id;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		if ( 'quiz' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
			} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		$answer_type = sanitize_text_field( $_POST['answer_type'] );
		$answers = $_POST['answers'];
		$question = $_POST['question'];
		if($answer_type == 'optional'){
			$answer = $_POST['correct_answer'];
			$answers['answer'] = $answer;

		}else{
			$answer = $_POST['answer'];
		}

		update_post_meta( $post_id, '_answer_type', $answer_type );
		update_post_meta( $post_id, '_answers', $answers );
		update_post_meta( $post_id, '_answer', $answer );
		update_post_meta( $post_id, '_question', $question );

	}
	function title_save_pre($post_title){
		if($_POST['post_type'] == 'quiz'){
			$post_title = strip_tags($_POST['post_content']);
			$post_title = preg_replace("!<img(.*?)>!is","",$post_title);
			$post_title = preg_replace("!<a(.*?)></a>!is","",$post_title);
		}
		return $post_title;
	}
	//퀴즈프레스 쇼트 코드
	function quizpress($attr){

		if(empty($attr['id']) && empty($attr['category']))
			return;
		if(!is_numeric($attr['id']) && !is_numeric($attr['category']))
			return;
		/*
		$quiz = get_post($attr['id']);
		
		if($quiz->post_status != 'publish')
			return;
		*/
		if(is_numeric($attr['id']))
			$id = 'quizpress-quiz-'.$attr['id'];
		if(is_numeric($attr['category']))
			$id = 'quizpress-category-'.$attr['category'];
		$html = "<script type='text/javascript'>";
		$html .= "var quizpress{$attr['id']} = new QuizPress();";
		if(is_numeric($attr['id'])){
			$html .= "quizpress{$attr['id']}.id = {$attr['id']};";
			$html .= "quizpress{$attr['id']}.type = 'quiz';";
		}else if(is_numeric($attr['category'])){
			$html .= "quizpress{$attr['id']}.id = {$attr['category']};";
			$html .= "quizpress{$attr['id']}.type = 'category';";
		}
		$html .= "quizpress{$attr['id']}.ajax_url = '".admin_url('admin-ajax.php')."';";
		$html .= "quizpress{$attr['id']}.init()";
		$html .= "</script>";
		$html .= "<div class='quizpress' id='{$id}'></div>";
		return $html;

	}
	function get_quiz_html(){
		$success = false;
		$msg = '';
		$html = '';
		$id = empty($_POST['id']) ? '' : $_POST['id'];

		if(!is_numeric($id)){
			$success = false;
			$msg = '정상적인 접근이 아닙니다.';
		}
		$type = empty($_POST['type']) ? '' : $_POST['type'];
		if(!in_array($type,array('quiz','category'))){
			$success = false;
			$msg = '정상적인 접근이 아닙니다.';
		}
		$quiz_data = array();
		if($type == 'quiz'){
			$quiz_data['title'] = 'QuizPress';
		}else{
			$category = get_term_by( 'id', $id, 'quiz_category' );
			$quiz_data['title'] = $category->name;
		}
		
		$quiz_data['quiz'] = $this->get_quiz($id,$type);
		
		if($quiz_data):
		$success = true;
		/*
		ob_start();
		?>
		<div id="header">
			<h2>QuizPress</h2>
		</div>
		<div id="content">
			<!-- quizProcCon -->
			<div class="quizProcCon" id="quizProcCon">
				<?php /*if(has_post_thumbnail($post_id)):?>
				<div class="thumb">
					<?php
					$attachment_id = get_post_thumbnail_id( $post_id );
					$image_attributes = wp_get_attachment_image_src($attachment_id,$name);
					?>
					<img src="<?php echo $image_attributes[0];?>" alt="<?php echo $quiz->question;?>"/>
				</div>
				<?php endif;?>
				<dl class="quiz">

					<dt><dt><span>Q. </span><?php echo $quiz->question;?></dt></dt>
					<?php if($quiz->answer_type != 'short'):?>
					<?php 
					shuffle($quiz->answers);
					?>
					<?php foreach($quiz->answers as $id=>$answer):?>
					<dd class="answer">
						<a class="answer_link" id="<?php echo $id;?>" href="#"><?php echo $answer;?></a>
					</dd>
					<?php endforeach;?>
					<?php else:?>
					<dd class="answer">
						<input type="text" name="answer" value=""/>
						<a class="submit_link">제출</a>
					</dd>
					<?php endif;?>
				</dl>
			</div>
			<!-- //quizProcCon -->
	
		</div>
		<?php
		$html = ob_get_clean();
		ob_end_clean();
		*/
		endif;
		//echo json_encode(array('success'=>$success,'msg'=>$msg,'html'=>$html));
		echo json_encode(array('success'=>$success,'msg'=>$msg,'data'=>$quiz_data));
		die();
	}
	function submit_answer(){
		$quiz_id = empty($_POST['quiz_id']) ? '' : $_POST['quiz_id'];
		$answer = empty($_POST['answer']) ? '' : $_POST['answer'];
		$success = false;
		$oanswer = get_post_meta($quiz_id, '_answer',true);

		if($oanswer == $answer){
			$success = true;
		}
		echo json_encode(array('success'=>$success));
		die();
	}
	function get_quiz($id,$type = 'quiz'){
		$quiz = new stdClass;
		$args = array(
				'post_type'		=> 'quiz',
				'post_status'	=> 'publish'
			);
		if($type == 'quiz'){
			$args['p']	= $id;
		}else{
			$args['tax_query']	= array(
					array(
                            'taxonomy'  => 'quiz_category',
                            'field'     => 'id',
                            'terms'     => $id
                        )
				);
			$args['orderby']	= 'rand';
		}
		$quiz = array();
		$quizzes = new WP_Query($args);
		while($quizzes->have_posts()){
			$quizzes->the_post();

			$temp = array();
			$temp['quiz_id']		= get_the_ID();
			$temp['question']		= get_the_content();
			$temp['answers']		= get_post_meta(get_the_ID(), '_answers',true);
			$temp['answer_type']	= get_post_meta(get_the_ID(), '_answer_type',true);
			shuffle($temp['answers']);
			$quiz[] = $temp;
			unset($temp);

		}
		return $quiz;
	}
	function media_buttons() {
		global $post;
		if($post->post_type != 'quiz'){
			$title = __( 'Add Quiz', 'quizpress' );
			echo " <a href='edit.php?post_type=quiz&page=add-quiz&iframe&TB_iframe=true' onclick='return false;' id='add_poll' class='button thickbox' title='" . esc_attr( $title ) . "'><img src='{$this->base_url}img/polldaddy@2x.png' width='15' height='15' alt='" . esc_attr( $title ) . "' style='margin: -2px 0 0 -1px; padding: 0 2px 0 0; vertical-align: middle;' /> " . esc_html( $title ) . "</a>";	
		}
		
	}
	function admin_menu(){
		$add_quiz_hook = add_submenu_page( 'edit.php?post_type=quiz', __('Add Quiz','quizpress'), __('Add Quiz','mhboard'), 'edit_posts', 'add-quiz', array(&$this,'add_shortcode_quiz') );
		remove_submenu_page( 'edit.php?post_type=quiz','add-quiz' );
		add_action("load-$add_quiz_hook",array(&$this,'add_quiz_page_load'));
		
	}
	function add_shortcode_quiz(){
		?>
		<script type="text/javascript">
		
		jQuery(document).ready(function($){
			var win=window.dialogArguments||opener||parent||top;
			$('#nav a').bind('click',function(){
				$('#nav a').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				if($(this).attr('id') == 'one_quiz_tab'){
					$('#quiz_category_view').hide();
					$('#one_quiz_view').show();
				}else{
					$('#quiz_category_view').show();
					$('#one_quiz_view').hide();
				}
				return false;
			});
			$('#quiz_category_view form').bind('submit',function(){
				var category_id = $(this).find('select').val();
				if(category_id > 0){
					win.send_to_editor('[quizpress category='+category_id+']');
				}else{
					alert('카테고리 선택 필요');
				}
				
				return false;
			});
			$('#one_quiz_view form').bind('submit',function(){
				var quiz_id = $(this).find('input').val();
				if(quiz_id > 0){
					win.send_to_editor('[quizpress id='+quiz_id+']');
				}else{
					alert('퀴즈 아이디 입력 필요');
				}
				return false;
			});
		});
		</script>
		<div class="wrap">
			<h2><?php echo __('Insert into Quiz','quizpress');?></h2>
			<div id="nav">
				<h2 class="themes-php">
					<a href="#" id="quiz_category_tab" class="nav-tab nav-tab-active"><?php echo __('Quiz Category','quizpress');?></a>
					<a href="#" id="one_quiz_tab" class="nav-tab"><?php echo __('One quiz','quizpress');?></a>
				</h2>
				<div id="quiz_category_view">
					<form action="#" method="post">
						<label for="quiz_category"><?php echo __('Quiz Category','quizpress');?></label>
						<?php 
	                    wp_dropdown_categories(array(
	                        'show_option_all' => __('All quiz category','quizpress'),
	                        'taxonomy' => 'quiz_category',
	                        'name' => 'quiz_category',
	                        'orderby' => 'id',
	                        'show_count' => true,
	                        'hide_empty' => false,
	                    ));?>
	                    <button type="submit"><?php echo __('Add Quiz Shortcode');?></button>
					</form>
				</div>
				<div id="one_quiz_view" style="display:none;">
					<form action="#" method="post">
						<label for="quiz_id"><?php echo __('Quiz ID','quizpress');?></label>
						<input type="text" name="quiz_id" id="quiz_id" val=""/>
	                    <button type="submit"><?php echo __('Add Quiz Shortcode');?></button>
					</form>
				</div>
			</div>
		</div>
		<?php
		
	}
	function add_quiz_page_load(){
		if ( isset( $_GET['iframe'] ) ) {
			add_action( 'admin_head', array( &$this, 'hide_admin_menu' ) );
		}
	}
	function hide_admin_menu() {
		echo '<style>#adminmenuback,#adminmenuwrap,#screen-meta-links,#footer,#wpfooter,#wpadminbar{display:none;visibility:hidden;}#wpcontent{margin-left:10px;}</style>';
	}
	function register_quizpress_style(){

	}
	function register_quizpress_admin_style(){

	}
	//어드민 스크립트
	function admin_scripts(){
		wp_enqueue_script( 'quizpress-admin', plugins_url( 'admin/js/quizpress.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable', 'jquery-form' ), $this->version );
		$mh_translation = array(
						'answer_add_max'			=> __('Possible to add up to five.','quizpress')
					);
		wp_localize_script( 'quizpress-admin', 'quizpress', $mh_translation );
	}
	//퀴즈 옵션 페이지
	function management_page(){
		
?>
	<div class="wrap" id="manage-quizpress">
		<h2><?php
				if ( $this->is_author )
					printf( __( 'QuizPress <a href="%s" class="add-new-h2">Add New</a>', 'quizpress' ), esc_url( add_query_arg( array( 'action' => 'create', 'quiz' => false, 'message' => false ) ) ) );
				else
					_e( 'Quizzes ', 'quizpress' );
		?></h2>
<?php
		switch($this->action){
			case 'create':
				require_once(QUIZPRESS_ADMINPATH.'edit-quiz-form.php');
				break;
			default:
				break;

		}
?>
	</div>
<?php
	}
	function management_page_load(){

	}
	
}
function wp_quizpress_init(){
	global $quizpress;
	$quizpress = new WP_QuizPress();
	load_plugin_textdomain( 'quizpress', '', 'quizpress/language' );
	add_action( 'admin_menu', array( &$quizpress, 'admin_menu' ) );
}
function wp_quizpress_admin_init(){
	global $quizpress;
	$post_type = empty($_GET['post_type']) ? '' : $_GET['post_type'];
	$post_id = empty($_GET['post']) ? '' : $_GET['post'];
	if($post_type == '' && is_numeric($post_id)){
		$post_type = get_post_type($post_id);
	}
	add_meta_box( 'quizpress_question', __('Question','quizpress'), array(&$quizpress, 'question_box'),'quiz','normal');
	add_meta_box( 'quizpress_answers', __('Answers','quizpress'), array(&$quizpress, 'answer_box'),'quiz','normal');
	
	if($post_type == 'quiz'){
		add_action('admin_print_scripts-post-new.php',array(&$quizpress,'admin_scripts'));
		add_action('admin_print_scripts-post.php',array(&$quizpress,'admin_scripts'));
	}	
}
add_action('init','wp_quizpress_init');
add_action('admin_init','wp_quizpress_admin_init');