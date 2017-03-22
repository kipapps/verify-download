<?php
/*
Plugin Name: Verify Social
Plugin URI: http://kipsoft.com
Description: Make sure a user is liking subscribed or following you before viewing certain content.
Author: KipSoft
Version: 1.0
Author URI: http://kipsoft.com
*/

//print_r(__DIR__);exit;
//require_once(ABSPATH.'wp-includes/pluggable.php');

global $user_ID,$_option_page_name,$ao,$wpdb;

$_options_name='wp_verify_social_options';
$_option_page_name='wp_verify_social_mainpage';

//________________________________** INITIALIZE VARIABLES      **_________________________________//

$wp_verify_social_options = array(
	'cron_password'		=>	'',
	//'channels'		=>	array(),
	'fb_app_callback'	=>	plugin_dir_url( __FILE__ ).'facebook/callback.php',
	'fb_app_secret'		=>	'',
	'fb_app_id'		=>	'',
	'fb_account_id'		=>	'',
	'twitter_app_callback'	=>	plugin_dir_url( __FILE__ ).'twitter/callback.php',
	'twitter_app_secret'	=>	'',
	'twitter_app_key'	=>	'',
	'youtube_app_callback'	=>	plugin_dir_url( __FILE__ ).'youtube/callback.php',
	'twitter_account_name'	=>	'',
	'youtube_app_secret'	=>	'',
	'youtube_app_key'	=>	'',
	'youtube_app_id'	=>	'',
	'youtube_account_name'	=>	'',  
	'last_cron_run'		=>	'',
	'cron_run_count'	=>	'0',
	'verify_page_name'	=>	'Verify Social',
	'verify_message'	=>	"Download button will appear here when",
	//'table_name'		=>	$wpdb->prefix.'verified_users',
	//'is_importing'	=>	false,
	'version'		=>	''
);
//global $current_user;
//$current_user = wp_get_current_user();if(is_admin()&&current_user_can('level_8')){add_action('admin_menu','WP_verify_social_menu');}


function WP_verify_social_menu() {
	if(function_exists('add_menu_page')) {
	add_menu_page('Verify Social','Verify Social',level_8,'verify-social-settings','WP_verify_social_settings','https://tracker.moodle.org/secure/attachmentzip/unzip/45721/24351%5B2%5D/completion-auto-enabled.png');
		//add_submenu_page('verify-social-settings','Verified Users','Verified Users','verify-social-users','WP_verify_social_users','');
	@add_submenu_page( 'verify-social-settings', 'Verified Users', 'Verified Users', 'manage_options', 'verify-social-users', 'WP_verify_social_users' ); 
	}
}


//add_action('init','WP_verify_social_init',0);
function WP_verify_social_init() {
	global $_options_name, $ao,$wp_verify_social_options;
	
	$ao = get_option($_options_name, $wp_verify_social_options);
	//print_r($ao);
}

function plugin_dir_url($p='') {
	if (empty($p)){$p='';}
	
	return VS_PLUGIN_FILE;
}



///Cron Job
//add_action('verify_social_hourly_event', 'do_this_hourly');
//function do_this_hourly() {
	// do something every hour
	//require_once("./app/cron.php");
	//twitgadget_run_cron();
//}

//add_shortcode( 'check_verify', 'verify_social_shortcode' );
function verify_social_shortcode( $atts, $content = null ) {
	global $ao;
	@extract($atts);
	$m=$ao[verify_message];
	$ln='verified';
	if (verifysubscription('y')&&verifysubscription('t')){//verifysubscription()
		if(checkdownloadtime($dluser->next_download)==true){ 
    	    	
    		$bg = "background-image: url(\"".plugins_url()."/download-manager/icon/wait.png\"); background-size:48px 48px;background-position: 2px center; background-repeat: no-repeat; min-height: 50px; max-height: 50px; vertical-align: middle; padding-left: 50px; "; 
    		 $html = "<div id='wpdm_file_{$id}' class='wpdm_file $template'>{$title}
    		 <div class='cont'>{$desc}{$password_field}
    		 <div class='btn_outer' >
    		 <div class='btn_outer_c' style='{$bg}'>
    		 <div class='btn_left $classrel $hc' style='padding-top: 0px;' >Must wait before downloading!</div>";
    		   
		
		$html .= "<div  class='btn_countdown'>".$candl[m]." minutes left</div>";                 
		$html .= "<span class='btn_right'>&nbsp;</span>";             
    		$html .= "</div></div><div class='clear'></div></div></div>";
    			return $html;
	    	} else 	{
	    		return do_shortcode($content);//wpdm_downloadable_nsc(array(id=>$content));
	    	}
	}else{
		if($message){$m=$message;}
		if($linkname){$ln=$linkname;}
		return $m ." <a href='/verify-social/'>".$ln."</a><p></p><br/>";
	}
	
   
}


function ifverified_social_shortcode( $atts, $content = null ) {
	global $ao;
	@extract($atts);
	$m=$ao[verify_message];
	$ln='verified';
	//if (verifysubscription()){//verifysubscription()
		return wpdm_downloadable_nsc(array(id=>$content));
	//}else{
		//if($message){$m=$message;}
		//if($linkname){$ln=$linkname;}
		//return $m ." <a href='/verify-social/'>".$ln."</a><p></p><br/>";
	//}
   
}
//add_shortcode( 'if_verify', 'ifverified_social_shortcode' );

function verify_process_func($atts){
	
	//@session_start();
	$html='<iframe scrolling="no" src="'.''.'verify-social-frontpage.php" width="500px" height="400"></iframe>';
	echo $html;
	//return $html;
 
}

function test_page(){
	
	include_once('test.php');
 
}

function front_page(){
	
	$html;
	switch (false) {
		case verifysubscription('f'):
			
			$_SESSION['verify_plugin_path']=plugin_dir_url( __FILE__ );
			$html='<p></p><img src="./facebook/icon.png" height="128" width="128" /> <iframe scrolling="no" frameBorder="0" src="'.''.'./sdk/facebook/index.php" width="300px"></iframe>';
			break;
		case verifysubscription('t'):
			$html='<p></p><img src="'.plugin_dir_url( __FILE__ ).'/twitter/icon.png" height="128" width="128" /> <iframe scrolling="no" frameBorder="0" src="'.plugin_dir_url( __FILE__ ).'twitter/connect.php" width="300px"></iframe>';
			break;
		case verifysubscription('y'):
		// $html='<p></p><img src="'.plugin_dir_url( __FILE__ ).'youtube/icon.png" height="128" width="128" /> <iframe scrolling="no" src="'.plugin_dir_url( __FILE__ ).'youtube/check.php" width="300px"></iframe>';
			$html='<p></p><img src="'.plugin_dir_url( __FILE__ ).'youtube/icon.png" height="128" width="128" /> <iframe scrolling="no" frameBorder="0" src="'.plugin_dir_url( __FILE__ ).'youtube/check.php" width="300px"></iframe>';
			break;
			default: 
			$html='<p></p><img height="256" width="256" src="https://www.getneighbour.com/images/verified.png" /> ';
			break;
	}
	//$video_id=get_post_meta($post->ID, 'youtubevidid', true);
	//if($video_id){
	echo $html;
 
}
//add_shortcode( 'verify', 'verify_process_func' );

function verify_file_shortcode( $atts, $content = null ) {
	global $ao;
	@extract($atts);
	if(function_exists('wpdm_get_package')) {
		return verify_wpdm($html);
	} else {
	 	return $html;
	 }
	$f=wpdm_get_package($id);
	print_r($f);
	
	if (verifysubscription('y')&&verifysubscription('t')){//verifysubscription()
		return do_shortcode($content);//wpdm_downloadable_nsc(array(id=>$content));
	}else{
		if($message){$m=$message;}
		if($linkname){$ln=$linkname;}
		return $m ." <a href='/verify-social/'>".$ln."</a><p></p><br/>";
	}
   
}
//add_shortcode( 'verify_file', 'verify_file_shortcode' );


function verify_setcookie($title,$value){
	global $user_ID,$_option_page_name,$ao,$wp_verify_social_options;
	
	setcookie($title, $value, time() + 86400, "/" );
	//setcookie($title, $value, time() + 86400, "", "/", false);
	
    }

	
function verify_delcookie($title){
	global $user_ID,$_option_page_name,$ao,$wp_verify_social_options;
	 setcookie($title, null, -1, '/');
         setcookie($title.'Test1', null, -1, '/');
         unset($_COOKIE[$title]);
         unset($_COOKIE[$title.'Test1']);
	
	//setcookie($title, false, time(), "/" );
	print_r($_COOKIE);
}

function candownloadflp() {
	$ip=$_SERVER['REMOTE_ADDR'];
	if (verifysubscription()) {
		if (!$_COOKIE[$ip]){
			//setcookie($ip, generatedownloadhash(), time()+900, "/wp/" );
			return true;
		}
		
	} else {
	
	}
		
	return false;
}

function verifysubscription($v='all') {
//print_r($_COOKIE);
	switch ($v) {
    case 'f':
	if (@$_COOKIE['verify_fb']==true||@$_SESSION['verify_fb']==true){return true;}
        break;
    case 't':
        if (@$_COOKIE['verify_t']==true||$_SESSION['verify_t']==true){return true;}
        break;
    case 'y':
        if ($_COOKIE['verify_yt']==true||$_SESSION['verify_yt']==true){return true;}
        break;
    case 'token':
    	//print_r($_COOKIE);
        if ($_COOKIE['token']==''){}else{return true;}
        break;
	case 'all':
		if ($_COOKIE['verify_t']==true&&$_COOKIE['verify_yt']==true) { //$_COOKIE[verify_fb]==true&&
			if($_COOKIE['token']==''){
				verify_delcookie('verify_fb');
				verify_delcookie('verify_t');
				verify_delcookie('verify_yt');
					
			}else{return true;}
		}
		break;
	}

	return false;
	
}

function verify_wpdm($html) {
global $user_ID,$_option_page_name,$ao,$wpdb;
	$temp;
	$dluser = $wpdb->get_row("SELECT * FROM wp_verified_users WHERE user_ip='".$_SERVER["REMOTE_ADDR"]."'");  
	$dllimit_time=$o[download_limit_time];
	$t1 = date("Y-m-d H:i:s T",mktime(date("H")-4, date("i"), date("s"), date("m"), date("d"), date("Y")));
	$candl=array();
	$candl=checkdownloadtime($dluser->next_download);
	//print_r($candl);
    	if(checkdownloadtime($dluser->next_download)==false){ 
    		
    	} else {
    		$bg = "background-image: url(\"".plugins_url()."/download-manager/icon/wait.png\"); background-size:48px 48px;background-position: 2px center; background-repeat: no-repeat; min-height: 50px; max-height: 50px; vertical-align: middle; padding-left: 50px; "; 
    		 $html = "<div id='wpdm_file_{$id}' class='wpdm_file $template'>{$title}
    		 <div class='cont'>{$desc}{$password_field}
    		 <div class='btn_outer' >
    		 <div class='btn_outer_c' style='{$bg}'>
    		 <div class='btn_left $classrel $hc' style='padding-top: 0px;' >Must wait before downloading!</div>";
    		   
		
		$html .= "<div  class='btn_countdown'>".$candl[m]." minutes left</div>";                 
		$html .= "<span class='btn_right'>&nbsp;</span>";             
    		$html .= "</div></div><div class='clear'></div></div></div>";
    	}
    	if(($dluser->enabled)==false){
    	$bg = "background-image: url(\"".plugins_url()."/download-manager/icon/wait.png\"); background-size:48px 48px;background-position: 2px center; background-repeat: no-repeat; min-height: 50px; max-height: 50px; vertical-align: middle; padding-left: 50px; "; 
    		 $html = "<div id='wpdm_file_{$id}' class='wpdm_file $template'>{$title}
    		 <div class='cont'>{$desc}{$password_field}
    		 <div class='btn_outer' >
    		 <div class='btn_outer_c' style='{$bg}'>
    		 <div class='btn_left $classrel $hc' style='padding-top: 0px;' >Must wait </div>";
    		   
		
		$html .= "<div  class='btn_countdown'>".''." Temporarily disabled!</div>";                 
		$html .= "<span class='btn_right'>&nbsp;</span>";             
    		$html .= "</div></div><div class='clear'></div></div></div>";
    	}
	return $html;
	
}

function verify_download_success($data){
	global $ao,$wpdb;
	//print_r($data);exit;
	$row = $wpdb->get_row("SELECT * FROM `".$ao['table_name']."` WHERE `user_ip` =  '".$_SERVER["REMOTE_ADDR"]."' ");
	$sql = "UPDATE wp_verified_users SET download_count=download_count+1 , last_download=DATE_ADD(NOW(),INTERVAL 1 HOUR) ,  next_download=DATE_ADD(NOW(),INTERVAL 2 HOUR) WHERE user_ip='".$_SERVER["REMOTE_ADDR"]."'";
	//$sql2 = "UPDATE `wp_verified_users` SET `last_file_downloaded` =  '".$data['file']."' user_ip='".$_SERVER["REMOTE_ADDR"]."'";
	$sql2 = "UPDATE `".$ao["table_name"]."` SET  `last_file_downloaded` = '".serialize($data)."' WHERE `".$ao["table_name"]."`.`id` =".$row->id;
	$wpdb->query($sql);
	$wpdb->query($sql2);
	return true;
	

}

function verify_wpdb_facebook($data){
	global $ao,$wpdb;
	$u=$data;
	$SQL;
	$facebookdata=array(
		'id' => $u['id'],
		'name' => $u['name'],
		'fname' => $u['first_name'],
		'lname' => $u['last_name'],
		'link' => $u['link'],
		'image' => 'https://graph.facebook.com/'.$u['id'].'/picture', //?width=100&height=100
		'username' => $u['username'],
		'verified' => $u['verified']
	);
	$row = $wpdb->get_row("SELECT * FROM `".$ao["table_name"]."` WHERE `user_ip` =  '".$_SERVER["REMOTE_ADDR"]."' ");
	//print_r($row);exit;
	if(is_null($row)){
		$SQL = "INSERT IGNORE INTO `".$ao["table_name"]."` (`id`, `user_ip`, `facebook`) 
		VALUES 
		(NULL, '".$_SERVER["REMOTE_ADDR"]."', '". serialize($facebookdata)."')";
		
	} else {
		$SQL = "UPDATE `".$ao["table_name"]."` SET  `facebook` = '".serialize($facebookdata)."' WHERE `".$ao["table_name"]."`.`id` =".'$row->id';	
	}
	unset($row);
	//print_r($SQL);exit;
	$wpdb->query($SQL);
	$row = $wpdb->get_row("SELECT * FROM `".$ao['table_name']."` WHERE `user_ip` =  '".$_SERVER["REMOTE_ADDR"]."' ");
	if(is_null($row)){print_r("Error");}else{
		//print_r($row);exit;
	
		verify_setcookie('verify_fb',true);
		verify_setcookie( 'dbidwp', $row->id );
		$_SESSION[verify_fb]=true;	
		$_SESSION[dbidwp]=$row->id;
		//print_r($_COOKIE);exit;
	
	}
		
	
		
	
	
	return true;
	

}

function verify_wpdb_twitter($data){
	global $ao,$wpdb;

	$row = $wpdb->get_row("SELECT * FROM `".$ao["table_name"]."` WHERE `id` =  '".$_SESSION[dbidwp]."' ");
	//print_r($row);exit;
	if(is_null($row)){
		$SQL = "INSERT IGNORE INTO `".$ao["table_name"]."` (`id`, `user_ip`, `facebook`) 
		VALUES 
		(NULL, '".$_SERVER["REMOTE_ADDR"]."', '". serialize($data)."')";
				
	} else {
		$SQL = "UPDATE  `".$ao[table_name]."` SET  `twitter` =  '".serialize($data)."' WHERE  `".$ao[table_name]."`.`id` =  '".$row->id."' ";	
	}
	unset($row);
	//print_r($SQL);exit;
	$result=$wpdb->query($SQL);
	$row = $wpdb->get_row("SELECT * FROM `".$ao['table_name']."` WHERE `user_ip` =  '".$_SERVER["REMOTE_ADDR"]."' ");
	if(is_null($row)){}else{
		//print_r($row);//exit;
	
		verify_setcookie('verify_t',true);
		verify_setcookie( 'dbidwp', $row->id );
  		$_SESSION[verify_t] = true;
  		$_SESSION[dbidwp]=$row->id;
		//print_r($_SESSION);
	
	}
		
		
  		
	return true;
	

}
function verify_wpdb_youtube($data){
	global $ao,$wpdb;
	//echo "<h2>Now Subscribed to KipBeats On Youtube</h2>";
	//print_r($_COOKIE);exit;
	$veryfied=false;
	$row = $wpdb->get_row("SELECT * FROM `".$ao["table_name"]."` WHERE `id` =  '".$_COOKIE[dbidwp]."' ");
	//print_r($row);exit;
	if(verifysubscription('f')&&verifysubscription('t')&&$_COOKIE[dbidwp]!=0){
		if(is_null($row)){}else{
		$veryfied=true;
		$hash=generatehash();
		$SQL="UPDATE `".$ao[table_name]."` SET `token` = '".$hash."', `youtube` = '".serialize($data)."', `veryfied` = '".$veryfied."' WHERE `".$ao[table_name]."`.`id` =".$row->id;
			
		$wpdb->query($SQL);
		
		verify_setcookie('verify_yt',true);
		//setcookie('token', $hash, time() + 604800, "/" );
		$_SESSION[verify_yt] = true;
  		$_SESSION[dbidwp]=$row->id;
			//verify_delcookie('verify_fb');
			//verify_delcookie('verify_t');
			//verify_delcookie('verify_yt');
			
		}
		
	}
		
	return true;
	

}
function checkdownloadtime($future){ 
$now = date("Y-m-d H:i:s",mktime(date("H")-6, date("i"), date("s"), date("m"), date("d"), date("Y")));
//print_r ($future .'   '. $now.'     wptime='.current_time( 'mysql', 0 ). '     ');
    if($future <= $now){// Time has already elapsed 
    return false; 
    }else{ //echo ("time not elapsed");
        // Get difference between times 
       $date1=new DateTime($future);$diff=$date1->diff(new DateTime($now));//print_r ($diff);
	
        $final = array(  
           'd' => $diff->format('%d'),  
           'h' => $diff->format('%h'), 
           'm' => $diff->format('%i'), 
           's' => $diff->format('%s')
           ); 
           //print_r($final);
        return $final;
    } 
} 

/* returns an array of [days],[hours],[minutes],[seconds] time left from now to timestamp given */
function timelefttodownload($time_left) {
	$nowdate = date("Y-m-d H:i:s",mktime(date("H")-4, date("i"), date("s"), date("m"), date("d"), date("Y")));$tdate = $time_left;
	//print_r( $time_left);//print_r( $nowdate);
	$days = floor($time_left / (60 * 60 * 24));$remainder = $time_left % (60 * 60 * 24);$hours = floor($remainder / (60 * 60));
	$remainder = $remainder % (60 * 60);$minutes = floor($remainder / 60);$seconds = $remainder % 60; 

	//return array(0, 0, 0, 0);
	//print_r("D=".$days."M=".$hours."M=".$minutes."S=".$seconds);
	return array('days' => $days,'hours' => $hours,'minutes' => $minutes,'seconds' => $seconds);
}

function generatehash() {
	$length = rand(27, 32);
    	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    	$count = mb_strlen($chars);

	for ($i = 0, $result = ''; $i < $length; $i++) {
		$index = rand(0, $count - 1);
		$result .= mb_substr($chars, $index, 1);
	}
	 //md5(uniqid(mt_rand(), true));
	return md5($result);
}



//require_once("verify-social-settings.php");
//require_once("verify-social-users.php");


?>