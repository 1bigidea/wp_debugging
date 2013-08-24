<?php
/*
Plugin Name: WP Debug
Plugin URI: http://1bigidea.com/wordpress
Description: Hooks into WordPress for debugging
Version: 2.0
Author URI: http://1bigidea.com
*/

$debug = ((defined('TRR_DEBUG') && TRR_DEBUG) || (defined('WP_DEBUG') && WP_DEBUG));

if( $debug && !class_exists('krumo') ) require_once ('krumo/class.krumo.php');
if( $debug && !class_exists('ChromePhp') ) require_once('chromephp/ChromePhp.php');

if( !class_exists('trrDebugClass') ){
	class trrDebugClass{
		var $debug_stack = array();
		
		function __construct(){
			if( defined('TRR_DEBUG') && TRR_DEBUG ){
				add_action( 'wp_foot', array($this, 'trr_debug_dump') );
				add_action( 'admin_footer', array($this, 'trr_debug_dump') );
				add_action( 'all', array($this, 'trr_debug_hooks'));
				add_action( 'wp_enqueue_scripts', array($this, 'trr_debug_jq_dump'));
				add_action('admin_enqueue_scripts', array($this, 'trr_debug_jq_dump'));
			}
			if( defined('SAVEQUERIES') && SAVEQUERIES){
				add_action('wp_foot', array($this, 'trr_debug_queries'));				
				add_action('admin_footer', array($this, 'trr_debug_queries'));				
			}
		}
		function trr_debug_dump() {
			global $bb_post, $topic, $wp_actions, $bb_views;
			global $bb_current_user, $profile_menu;
			
			krumo($this->debug_stack);
			krumo($profile_menu);
			
			return;	
		}	
		function trr_debug_hooks() {
			global $debug_stack_trr;
			
			$debug_stack_trr[] = current_filter();
			
			return;
		}
		function trr_debug_jq_dump() {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jq_dump', plugins_url(basename(dirname(__FILE__)).'/jquery.dump.js'),array('jquery'));			
		}
		function trr_debug_queries () {
			global $wpdb;
			
			krumo($wpdb->queries);
		}
		function backTrace() 
		{ 
			$bt=debug_backtrace(); 
			$sp=0; 
			$trace=""; 
			foreach($bt as $k=>$v) 
			{ 
				extract($v); 
				$file=substr($file,1+strrpos($file,"/")); 
				if($file=="db.php")continue; // the db object 
				$trace.=str_repeat("&nbsp;",++$sp); //spaces(++$sp); 
				$trace.="file=$file, line=$line, function=$function<br>";        
			} 
			kickout('backtrace','Backtrace from '.$_SERVER['HTTP_HOST'], $trace); 
		}
	}
}
if( $debug )
	new trrDebugClass;
if( !function_exists('kickout') ){
	function kickout(){
		
		$args = func_get_args();
		
		$file_output = array_shift($args);
		
		$file_append = false;

		// prepare the output
		$output = '';
		for($i=0;$i<count($args);$i++){
			if( is_object($args[$i]) || is_array($args[$i]) ){
				$output .= print_r($args[$i], true)."\n";
			} elseif( $args[$i] == FILE_APPEND ) {
				$file_append = true;
			} else {
				$output .= "\n".$args[$i]."\n";
			}
		}

		file_put_contents(ABSPATH.'/wp-content/_'.$file_output.'.txt', $output, ($file_append) ? FILE_APPEND : null);	

	}
}
if( !function_exists('whereCalled') ){
	function whereCalled( $level = 1 ) {
	    $trace = debug_backtrace();
	    $file   = $trace[$level]['file'];
	    $line   = $trace[$level]['line'];
	    $object = $trace[$level]['object'];
	    if (is_object($object)) { $object = get_class($object); }

	    return "Where called: line $line of $object \n(in $file)";
	}
}

