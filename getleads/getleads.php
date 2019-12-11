<?php
/*
Plugin Name: GetLeads
Plugin URI: http://loanpaymentplugin.com/
Description: Loan application.
Version: 0.9
Author: aerin
Author URI: http://quick-plugins.com/
Text Domain: getleads
Domain Path: /languages
*/

require_once( plugin_dir_path( __FILE__ ) . '/options.php' );

add_shortcode('getleads', 'getleads_page');
add_shortcode('getleadshomepage', 'getleads_homepage');

add_action( 'wp_enqueue_scripts', 'getleads_scripts' );
add_action( 'wp_ajax_ajax_submit', 'getleads_ajax_submit' );
add_action( 'wp_ajax_nopriv_ajax_submit', 'getleads_ajax_submit' );

if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );

function ms_do_replace($subject, $array) {

	$keys = array_keys($array);
	
	foreach ($keys as $key) {

		$subject = str_replace('['.$key.']', $array[$key], $subject);
	}
	
	return $subject;
	
}
function getleads_ajax_submit() {

	$settings  = getleads_get_stored_settings();
	$log       = [];
	$return    = ['success' => false,'title' => '', 'message' => ''];
    
    $sort       = explode(",", $settings['sort']);
    
    foreach ($sort as $key) {
        $log[$key] = $_POST[$key];
    }
    
    //$yourname = explode(' ', $log['yourname'], 2);
    //$log['yourname'] = $yourname[0];
	
    $log['sentdate'] = date_i18n('d M Y');
    $log['timestamp'] = time();
			
    $content = getleads_build_complete_message($log);
			
    getleads_send_notification ($log,$content);
    getleads_send_confirmation ($log,$content);
    
    $yourname = explode(' ', $log['yourname'], 2);
    $log['yourname'] = $yourname[0];
    $return['success'] = true;
    $return['title']	= ms_do_replace($settings['thankyoutitle'],$log);
    $return['message']	=  ms_do_replace($settings['thankyoublurb'],$log);
    
	echo json_encode($return);
	
	die(0);
	
}

function getleads_homepage() {
    
    $settings = getleads_get_stored_settings();
    $styles = getleads_get_stored_styles();
    
    $content = '<style>';
    if ($settings['background']) $content .= '.getleads_homepage {background: url('.$settings['background'].') no-repeat;background-position: right top;background-size: cover;}';
    $content .= '#msform p {color: '.$styles['homepagecolour'].'}
    #msform .required {border: 1px solid '.$styles['primarycolour'].';}
    .getleads_homepage h1{color: '.$styles['headingcolour'].';font-size: '.$styles['headingsize'].'}
    .getleads_homepage h5{color: '.$styles['headingblurbcolour'].';'.$styles['headingblurbsize'].'}
    </style>
    <div class="getleads_homepage">';
    if ($settings['contentposition'] == 'ontheleft') {
        $content .= '<div class="gridcontent"><h1>'.$settings['heading'].'</h1>
        <h5>'.$settings['headingblurb'].'</h5>
        '.getleads_display_application ().'
        </div>
        <div></div>';
    } else {
        $content .= '<div></div>
        <div class="gridcontent"><h1>'.$settings['heading'].'</h1>
        <h5>'.$settings['headingblurb'].'</h5>
        '.getleads_display_application ().'
        </div>';
    }
    $content .= '</div>';
    
    return $content;
}

function getleads_page() {
    
    $settings = getleads_get_stored_settings();
    $styles = getleads_get_stored_styles();
    
    $content = '<style>
    #msform p {color: '.$styles['primarycolour'].'}
    #msform .required {border: 1px solid '.$styles['primarycolour'].';}
    #msform .action-button {background: '.$styles['primarycolour'].';}
    #progressbar li:before {color: '.$styles['primarycolour'].';}
    #progressbar li.active:before,  #progressbar li.active:after{background: '.$styles['primarycolour'].';}
    #msform input, #msform select, #msform textarea {border: 1px solid '.$styles['secondarycolour'].';}
    #progressbar li:before {background: '.$styles['secondarycolour'].';}
    #progressbar li:after {background: '.$styles['secondarycolour'].';}
    </style>
    <div class="getleads_page">';
    $content .= getleads_display_application ();
    $content .= '</div>';
    
    return $content;
}

// Application form
function getleads_display_application () {

	$ajaxurl = admin_url( 'admin-ajax.php' );
	
    $settings = getleads_get_stored_settings();
    $application = getleads_get_stored_application();
    $arr = array_map('array_shift', $application);
    
    $maxsection = 1;
    
	$functions = [];
	foreach ($application as $name => $field) {
        $js = $field['js'] ? $field['js'] : "function(obj){ return false; }";
		
		$functions[] = '"'.(($name == 'captcha')? 'youranswer':$name).'":{"required":'.(($field['required'] == 'checked')? 'true':'false').',"callback":'.$js.'}';
        if ($field['section'] > $maxsection) {
            $maxsection = $field['section'];
        }
	}
	
	$functions = implode(",\n			",$functions);
	$autocomplete = (($settings['autocomplete'] == 'checked')? 1:0);
    $content = '<!-- getleads form -->
	<script type="text/javascript">
		var getleads_fields = {'.$functions.'};
		var getleads_ajax_url = "'.$ajaxurl.'";
		var getleads_auto_complete = '.$autocomplete.';
	</script>
    <form id="msform" form action="'.admin_url('admin-ajax.php').'" method="POST">
    <!-- progressbar -->
    <ul id="progressbar">
        <li class="active" style="width:'.(100/$maxsection).'%"></li>';
    for ($i = 1; $i < ($maxsection); $i++) {
        $content .= '<li style="width:'.(100/$maxsection).'%"></li>';
    }
    $content .= '</ul>
    <!-- fieldsets --><div class="fieldsets">';
    
    $sort = explode(",", $settings['sort']);
    
    for($i = 1; $i <= $maxsection; $i++) {
        
        $content .= '<fieldset class="section'.$i.'">';
        foreach ($sort as $key) {
			
            if ($application[$key]['section'] == $i) {
				
                $class = '';
                if (isset($application[$key]['class'])) $class = $application[$key]['class'];
                
                if ($application[$key]['type'] == 'text') {
				    $required = ($application[$key]['required'] ? ' class = "required" ' : null );
				    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
				    <input id="'.$key.'" name="'.$key.'" type="text" '.$required.' value="" /></p>'."\n";
                }

                if ($application[$key]['type'] == 'dollars') {
				    $required = ($application[$key]['required'] ? ' class = "required" ' : null );
				    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
				    <input id="'.$key.'" type="text" '.$required.' name="'.$key.'" value="" placeholder="'.$application[$key]['placeholder'].'" /><script type="text/javascript">jQuery(document).ready(function() {jQuery("input[name=\"'.$key.'\"]").mask("'.$application[$key]['mask'].'");});</script></p>';  
                }
                
                if ($application[$key]['type'] == 'number') {
				    $required = ($application[$key]['required'] ? ' class = "required" ' : null );
				    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
				    <input id="'.$key.'" name="'.$key.'" type="number" '.$required.' value="" min="0" max="10"/></p>'."\n";
                }
                    
                if ($application[$key]['type'] == 'date') {
                    $required = ($application[$key]['required'] ? ' class = "required" ' : null );
                    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
                    <input id="'.$key.'" type="text" '.$required.' name="'.$key.'" value="" placeholder="'.$application[$key]['placeholder'].'" /><script type="text/javascript">jQuery(document).ready(function() {jQuery("input[name=\"'.$key.'\"]").mask("'.$application[$key]['mask'].'");});</script></p>';    
                }
                    
                if ($application[$key]['type'] == 'telephone') {
                    $required = ($application[$key]['required'] ? ' class = "required" ' : null );
                    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
                    <input id="'.$key.'" type="tel" '.$required.' name="'.$key.'" value="" placeholder="'.$application[$key]['placeholder'].'" /><script type="text/javascript">jQuery(document).ready(function() {jQuery("input[name=\"'.$key.'\"]").mask("'.$application[$key]['mask'].'");});</script></p>';    
                }
                
                if ($application[$key]['type'] == 'range') {
                    $label = str_replace('[value]', '<span class="rangeoutput"></span>', $application[$key]['label']);
                    $content .= '<p class="inputfield '.$class.'">'.$label.'<br>
                    <input id="'.$key.'" type="range" name="'.$key.'" min="'.$application[$key]['min'].'" max="'.$application[$key]['max'].'" step="'.$application[$key]['step'].'" value="'.$application[$key]['initial'].'" /></p>';    
                }
                    
                if ($application[$key]['type'] == 'dropdown') {
				    $required = ($application[$key]['required'] ? ' required' : null );
                    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
				    <select class="minimal'.$required.'" name="'.$key.'" '.$required.'>'."\r\t";
                    $d = explode(",",$application[$key]['options']);
				    foreach ($d as $item) {
					   $content .= '<option value="' .  $item . '">' .  $item . '</option>'."\r\t";
				    }
				    $content .= '</select></p>'."\r\t";
                }
                
                if ($application[$key]['type'] == 'conditional') {
				    $required = ($application[$key]['required'] ? ' required' : null );
                    $content .= '<p class="inputfield '.$class.'">'.$application[$key]['label'].'<br>
				    <select class="minimal'.$required.'" name="'.$key.'" '.$required.'>'."\r\t";
                    $d = explode(",",$application[$key]['options']);
				    foreach ($d as $item) {
					   $content .= '<option value="' .  $item . '">' .  $item . '</option>'."\r\t";
				    }
				    $content .= '</select></p>'."\r\t";
                    $content .= '<p class="inputfield conditional_hidden">'.$application[$key]['question'].'<br>
				    <input id="'.$key.'" name="'.$key.'" type="text" '.$required.' value="" /></p>'."\n";
                }
                    
                if ($application[$key]['type'] == 'checkbox') {
				    $required = ($application[$key]['required'] ? ' class="required"' : null );
                    $content .= '
					<p style="text-align:left;"><input type="checkbox"'.$required.' name="'.$key.'" id="'.$key.'" value="checked"><label for="'.$key.'">'.$application[$key]['label'].'</label></p>';   
                }
                    
                if ($application[$key]['type'] == 'link') {
				    $required = ($application[$key]['required'] ? ' style = "color:' . $style['required-color'] . '" ' : null );
				    if ($errors[$key]) $required = ' style = "color:'.$style['error-colour'].';"';
				    $msg = $application[$key]['label'];
				    if ($settings['termstarget']) $target = ' target="blank" ';
				    $msg = str_replace('[a]', '<a href= "'.$application[$key]['termsurl'].'"'.$target.'>', $msg);
				    $msg = str_replace('[/a]', '</a>', $msg);
				    $content .= '<p'.$required.'  class="'.$class.'"><input type="checkbox" name="'.$key.'" value="checked" /> '.$msg.'</p>';
                }
                    
                if ($application[$key]['type'] == 'captcha') {
                    $digit1 = mt_rand(1,10);
                    $digit2 = mt_rand(1,10);
                    if( $digit2 >= $digit1 ) {
                        $thesum = "$digit1 + $digit2";
                        $answer = $digit1 + $digit2;
                    } else {
                        $thesum = "$digit1 - $digit2";
                        $answer = $digit1 - $digit2;
                    }
                    $content .= '<p class="inputfield">Answer the sum: '.$thesum.' = </span><input class="required" id="youranswer" name="youranswer" type="text" style="width:3em;"  value="" /></p>
                        <input type="hidden" name="answer" value="' . strip_tags($thesum) . '" />
                        <input type="hidden" name="thesum" value="' . strip_tags($answer) . '" />';
                }
            }
        }
        
		$content .= '<div class="buttons">';
        if ($i == 1) $content .= '<input type="button" name="next" class="next action-button" value="'.$settings['nextbutton'].'" />';
        elseif ($i == $settings['sections']) $content .= '<input type="button" name="previous" class="previous action-button" value="'.$settings['prevbutton'].'" /><input type="submit" name="submit" id="ms_submit" class="submit action-button" value="'.$settings['submitbutton'].'" />';
        else $content .= '<input type="button" name="previous" class="previous action-button" value="'.$settings['prevbutton'].'" /><input type="button" name="next" class="next action-button" value="'.$settings['nextbutton'].'" />';
		$content .= '</div>';
		$content .= '<div class="buttons_working">';
		$content .= '	<p class="working_loading"></p>';
		$content .= '</div>';
        $content .= '</fieldset>';
    }
	
    $content .= '<fieldset class="section_success"><h2 class="section_success_title">Success</h2><p class="section_success_blurb">You successfully submitted the form</p></fieldset>';
	$content .= '<fieldset class="section_failure"><h2>Oops</h2><p>The form didn\'t submit</p></fieldset>';
	$content .= '<fieldset class="section_loading"><p class="loading"></p></fieldset>';
	
    $content .= '</div></form>';
    return $content;
}

function getleads_send_notification ($values,$content) {
    
    $settings = getleads_get_stored_settings();
    if (!$settings['sendto']) $settings['sendto'] = get_bloginfo('admin_email');
    
    $subject = ms_do_replace($settings['notificationsubject'],$values);
    
    $headers = "From: ".$values['yourname']." <".$values['youremail'].">\r\n"
    . "Content-Type: text/html; charset=\"utf-8\"\r\n";	
    
    $message = '<html>'.$content.'</html>';
    
    wp_mail($settings['sendto'], $subject, $message, $headers);
}

function getleads_send_confirmation ($values,$content) {
    
    $settings = getleads_get_stored_settings();
    
    $subject = $settings['confirmationsubject'] ? $settings['confirmationsubject'] : 'Loan Application';
    
    if (!$settings['fromemail']) $settings['fromemail'] = get_bloginfo('admin_email');
    if (!$settings['fromname']) $settings['fromname'] = get_bloginfo('name');

    $msg = ms_do_replace($settings['confirmationmessage'],$values);
    
    $message = '<html>' . $msg . '<h2>'.$settings['registrationdetailsblurb'].'</h2>' . $content.'</html>';
    
    $headers = "From: ".$settings['fromname']." <{$settings['fromemail']}>\r\n"
. "Content-Type: text/html; charset=\"utf-8\"\r\n";	
    
    wp_mail($values['youremail'], $subject, $message, $headers);
}

function getleads_build_complete_message($values) {
    
    $application = getleads_get_stored_application();
    $arr = array_map('array_shift', $application);
    
    $content = '<table>';

    foreach ($arr as $key => $value) {
        if ($values[$key]) $content .= '<tr><td><b>'.$application[$key]['label'].'</b></td><td>' . strip_tags(stripslashes($values[$key])) . '</td></tr>';
    }
    
    $content .= '<tr>
    <td><b>Application Date</b></td>
    <td>' . strip_tags(stripslashes($values['sentdate'])) . '</td>
    </tr>
    </table>';

    return $content;
}

function getleads_display_result($values) {
    
    $messages = getleads_get_stored_settings();
    
    $messages['thankyoutitle'] = str_replace('[yourname]', $values['yourname'],$messages['thankyoutitle']);
    
    $msg = $messages['thankyoublurb'];
    $msg = str_replace('[yourname]', $values['yourname'],$msg);
    $msg = str_replace('[amountrequired]', $values['amountrequired'], $msg);
    $msg = str_replace('[youremail]', $values['youremail'], $msg);
    
    $content = '<fieldset><h2>'.$messages['thankyoutitle'].'</h2>'.$msg.'</fieldset>';
    
    return $content;
}

// Enqueue Scripts and Styles

function getleads_scripts() {
    wp_enqueue_style( 'getleads_style',plugins_url('getleads.css', __FILE__));
    wp_enqueue_script("jquery-effects-core");
    wp_enqueue_style( 'load-fa', 'https://use.fontawesome.com/releases/v5.5.0/css/all.css' );
    wp_enqueue_script('getleads_mask_script',plugins_url('jquery.mask.js', __FILE__ ), array( 'jquery' ), false, true );
	wp_enqueue_script('getleads_script',plugins_url('getleads.js', __FILE__ ), array( 'jquery' ), false, true );
}