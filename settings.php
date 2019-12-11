<?php

add_action('init', 'getleads_settings_init');
add_action('admin_menu', 'getleads_page_init');
add_action('admin_notices', 'getleads_admin_notice' );
add_action('admin_enqueue_scripts', 'getleads_scripts_init');

function getleads_settings_init() {
}

function getleads_page_init() {
	add_options_page('Get Leads', 'Get Leads', 'manage_options', __FILE__, 'getleads_application');
}

function getleads_max_section() {
	$application = getleads_get_stored_application();
	$maxsection = 1;
	foreach ($application as $key) {
		if ($key['section'] >= $maxsection) {
			$maxsection = $key['section'] +1;
		}
	}
	return $maxsection;
}

function getleads_application (){
    
    $sections=$ontheright=$ontheleft=$termstarget=false;
    
    $application = getleads_get_stored_application();
    
	$maxsection = getleads_max_section();

    
    if( isset( $_POST['Submit']) && check_admin_referer("save_getleads")) {
		$options = array (
            'heading',
            'headingblurb',
            'thankyoutitle',
            'thankyoublurb',
            'notificationsubject',
            'confirmationsubject',
            'confirmationmessage',
            'registrationdetailsblurb',
            'fromname',
            'fromemail',
            'sendto',
            'contentposition',
            'background',
            'sort',
			'autocomplete',
            'nextbutton',
            'prevbutton',
            'submitbutton'
        );

		$messages = array();
		foreach ($options as $item) {
			if ($item == 'background') {
				$messages[$item] = $_POST[$item];
			} else {
				$messages[$item] = stripslashes($_POST[$item]);
				$messages[$item] = strip_tags($messages[$item],'<p><a><em><br>');
			}
        }
        
        update_option('getleads_settings', $messages);
        
        getleads_admin_notice(__('The general settings have been updated', 'getleads'));
    }
    
    if( isset( $_POST['SaveApplication']) && check_admin_referer("save_getleads")) {
        
        $settings	= getleads_get_stored_settings();
		$sort		= $_POST['sort'];
		$fields		= getleads_get_fields();
		
        $newApplication = array();
        
		// Loop through POST Variables
		foreach ($_POST['application'] as $iB => $iV) {
			
			$newApplication[$iB] = ['required' => ''];
			
			foreach ($iV as $field => $fV) {
				$newApplication[$iB][$field] = stripslashes($fV);
			}
			
			$newApplication[$iB]['js'] 	= $fields['all'][$iB]['js'];
			$newApplication[$iB]['type']= $fields['all'][$iB]['type'];
        }
		$settings['sort'] = $_POST['sort'];
		update_option('getleads_settings', $settings);
        update_option('getleads_application', $newApplication);
		
		$application = $newApplication;
		
        getleads_admin_notice(__('The form settings have been updated', 'getleads'));
    }
    
    if( isset( $_POST['Styles']) && check_admin_referer("save_getleads")) {
		$options = array (
            'primarycolour',
            'secondarycolour',
            'buttonlabel',
            'homepagecolour',
            'headingcolour',
            'headingsize',
            'headingblurbcolour',
            'headingblurbsize',
        );

		$messages = array();
			foreach ($options as $item) {
                $messages[$item] = stripslashes($_POST[$item]);
                $messages[$item] = strip_tags($messages[$item],'<p><a><em><br>');
            }
        
        update_option('getleads_styles', $messages);
        
        getleads_admin_notice(__('The general styles have been updated', 'getleads'));
    }

    // Reset the settings
    if( isset( $_POST['Reset']) && check_admin_referer("save_getleads")) {
        delete_option('getleads_settings');
        getleads_admin_notice(__('The general settings have been reset', 'getleads'));
    }
    
    // Reset the forms
    if( isset( $_POST['ResetApplication']) && check_admin_referer("save_getleads")) {
        delete_option('getleads_application');
        getleads_admin_notice(__('The form settings have been reset', 'getleads'));
    }
    
    $arr = $application = getleads_get_stored_application();
	$settings = getleads_get_stored_settings();
    $styles = getleads_get_stored_styles();

    ${$settings['contentposition']} = 'checked';
    
	$autocomplete = '';
	if ($settings['autocomplete'] == 'checked') {
		$autocomplete = 'checked="checked"';
	}
	
	$fields = getleads_get_fields();
	
    
	$dd = json_encode(['dropdown' => getleads_build_field($settings, '!K!', ['type' => 'dropdown'], true), 'conditional' => getleads_build_field($settings, '!K!', ['type' => 'conditional'], true),'range' => getleads_build_field($settings, '!K!', ['type' => 'range'], true),'captcha' => getleads_build_field($settings, '!K!', ['type' => 'captcha'], true), 'checkbox' => getleads_build_field($settings, '!K!', ['type' => 'checkbox'], true), 'other' => getleads_build_field($settings, '!K!', ['type' => 'text'], true), 'fields' => $fields['all']]);
	
    $content ='<script type="text/javascript">
		var getleads_defaults = '.$dd.';
	</script>
	<div class="wrap"><h1>'.__('Get Leads Settings', 'getleads').'</h1>
    <div class="getleads-settings">
    <form id="" method="post" action="">
    <div class="getleads-options">
    
    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">

    <h2>'.__('General Settings', 'getleads').'</h2>
    
    <p>'.__('Add the form to your site using the shortcode', 'getleads').': [getleads]</p>
    
    </fieldset>';
    
    $content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Form Fields', 'getleads').'</h2>
    
    <p>'.__('Check those fields you want to use. Drag and drop to change the order', 'getleads').'.</p>
    
	<div id="sorting">
		<ul id="getleads_sort">';
		 
		$sort = explode(",", $settings['sort']);
		
		foreach ($application as $k => $v) {
			
		}
		
		foreach ($sort as $key) {
			if ($arr[$key]['section'] > 0) {
				$value = $arr[$key];
				$content .= getleads_build_field($settings, $key, $value, false);
			}
		}

		$content .= '</ul>
	</div>
        
    <input type="hidden" id="getleads_settings_sort" name="sort" value="'.$settings['sort'].'" />
    <div class="toggle-new-field button-secondary">Add Field +</div>
    <div class="fieldlist" style="display: none;">
    <p>Select a field to add to the form from the list below:</p>
    <ul>';
    
    foreach ($arr as $key) {
        if ($key['section'] == 0) {
            $content .= '<li id="">'.$key['label'].'</li>';
        }
    }       
            
    $content .= '</ul></div>
    
    </fieldset>';
    
    // Submit Changes
    $content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
        
    <p><input type="submit" name="SaveApplication" class="button-primary" style="color: #FFF;" value="'.__('Save Form Settings', 'getleads').'" /> <input type="submit" name="ResetApplication" class="button-secondary" value="'.__('Reset Form Settings', 'getleads').'" onclick="return window.confirm( \'Are you sure you want to reset the form?\' );"/></p>
        
    </fieldset>';
    
    $content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <p>'.__('Enable Autocomplete', 'getleads').' <input type="checkbox" name="autocomplete" value="checked" '.$autocomplete.' /> <span class="description">When the last field in a section is completed the form moves to the next section.</span></p>
    
    <h2>'.__('Button Labels', 'getleads').'</h2>
    
    <p>'.__('Next', 'getleads').': <input type="text" style="width:6em" name="nextbutton" value="' . $settings['nextbutton'] . '" /> '.__('Previous', 'getleads').': <input type="text" style="width:6em" name="prevbutton" value="' . $settings['prevbutton'] . '" /> '.__('Submit', 'getleads').': <input type="text" style="width:6em" name="submitbutton" value="' . $settings['submitbutton'] . '" /></p>
    
    </fieldset>
    
    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">

    <h2>'.__('Homepage', 'getleads').'</h2>
    
    <p>'.__('Content position', 'getleads').': <input type="radio" name="contentposition" value="ontheleft" ' . $ontheleft . ' />On the left&nbsp;&nbsp;&nbsp;
    <input type="radio" name="contentposition" value="ontheright" ' . $ontheright . ' />On the right</p>
    
    <p>'.__('Background', 'getleads').'</p>';
    if ($settings['background']) $content .= '<img src="'.$settings['background'].'">';
    $content .='<p><input type="text" id="getleads_logo_image" class="background_text" name="background" value="'.$settings['background'].'"><input id="getleads_upload_logo_image" class="background_button button" type="button" value="Upload Image" /></p>
    
    <p>'.__('Heading', 'getleads').'<br>
    <input type="text" name="heading" value="' . $settings['heading'] . '" /></p>
    
    <p>'.__('Message', 'getleads').'<br>
    <input type="text" name="headingblurb" value="' . $settings['headingblurb'] . '" /></p>
    
    </fieldset>

    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('On screen message', 'getleads').'</h2>
    <p>'.__('Thank you title', 'getleads').'<br>
    <input type="text" name="thankyoutitle" value="' . $settings['thankyoutitle'] . '" /></p>
    
    <p>'.__('Thank you message', 'getleads').'<br>
    <textarea style="width:100%;height:50px;" name="thankyoublurb">' . $settings['thankyoublurb'] . '</textarea></p>
    
    </fieldset>
    
    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Notification email', 'getleads').'</h2>
    
    <p>'.__('Send to', 'getleads').' (<span class="description">'.__('Defaults to the', 'loanapplication').' <a href="'. get_admin_url().'options-general.php">'.__('Admin Email', 'loanapplication').'</a> '.__('if left blank', 'loanapplication').'</span>):<br><input type="text" name="sendto" value="' . $settings['sendto'] . '" /></p>
    
    <p>'.__('Notification subject', 'getleads').'<br>
    <input type="text" name="notificationsubject" value="' . $settings['notificationsubject'] . '" /></p>
    
    </fieldset>
    
    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Confirmation email', 'getleads').'</h2>
    
    <p>'.__('From name', 'getleads').' (<span class="description">'.__('Defaults to your', 'loanapplication').' <a href="'. get_admin_url().'options-general.php">'.__('Site Title', 'loanapplication').'</a> '.__('if left blank', 'loanapplication').'</span>):<br>
    <input type="text" name="fromname" value="' . $settings['fromname'] . '" /></p>
    
    <p>'.__('From email', 'getleads').' (<span class="description">'.__('Defaults to the', 'loanapplication').' <a href="'. get_admin_url().'options-general.php">'.__('Admin Email', 'loanapplication').'</a> '.__('if left blank', 'loanapplication').'</span>):<br><input type="text" name="fromemail" value="' . $settings['fromemail'] . '" /></p>
    
    <p>'.__('Confirmation subject', 'getleads').'<br>
    <input type="text" name="confirmationsubject" value="' . $settings['confirmationsubject'] . '" /></p>
    
    <p>'.__('Confirmation message', 'getleads').'<br>
    <textarea style="width:100%;height:50px;" name="confirmationmessage">' . $settings['confirmationmessage'] . '</textarea></p>
    
    <p>'.__('Application details message', 'getleads').'<br>
    <input type="text" name="registrationdetailsblurb" value="' . $settings['registrationdetailsblurb'] . '" /></p>

    </fieldset>';
    
    // Submit Changes
    $content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
        
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="'.__('Save General Settings', 'getleads').'" /> <input type="submit" name="Reset" class="button-secondary" value="'.__('Reset Settings', 'getleads').'" onclick="return window.confirm( \'Are you sure you want to reset?\' );"/></p>
        
    </fieldset>';
    
    $content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    <h2>'.__('General Styles', 'getleads').'</h2>
    <p>'.__('Primary Colour', 'getleads').' <span class="description">(Labels, button background, progress bar active steps, required fields border, thank you message)</span><p>
    <p><input type="text" class="getleads-color" label="primarycolour" name="primarycolour" value="' . $styles['primarycolour'] . '" />
    <p>'.__('Secondary Colour', 'getleads').' <span class="description">(Progress bar inactive steps, normal fields border)</span><p>
    <p><input type="text" class="getleads-color" label="secondarycolour" name="secondarycolour" value="' . $styles['secondarycolour'] . '" />
    <p>'.__('Button Label', 'getleads').'<p>
    <p><input type="text" class="getleads-color" label="buttonlabel" name="buttonlabel" value="' . $styles['buttonlabel'] . '" />
    
    </fieldset>
    
    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Homepage Styles', 'getleads').'</h2>
    
    <p>'.__('Primary Colour', 'getleads').' <span class="description">(Labels, thank you message)</span><p>
    <p><input type="text" class="getleads-color" label="homepagecolour" name="homepagecolour" value="' . $styles['homepagecolour'] . '" />
    
    <p>'.__('Heading', 'getleads').'<p>
    <p>Colour: <input type="text" class="getleads-color" label="headingcolour" name="headingcolour" value="' . $styles['headingcolour'] . '" /> Font Size: <input type="text" style="width:4em" name="headingsize" value="' . $styles['headingsize'] . '" /></p>
    
    <p>'.__('Blurb', 'getleads').'<p>
    <p>Colour: <input type="text" class="getleads-color" label="headingblurbcolour" name="headingblurbcolour" value="' . $styles['headingblurbcolour'] . '" /> Font Size: <input type="text" style="width:4em" name="headingblurbsize" value="' . $styles['headingblurbsize'] . '" /></p>
    
    </fieldset>
    
    <fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <p><input type="submit" name="Styles" class="button-primary" style="color: #FFF;" value="'.__('Save Styles', 'getleads').'" /> <input type="submit" name="Reset" class="button-secondary" value="'.__('Reset Styles', 'getleads').'" onclick="return window.confirm( \'Are you sure you want to reset?\' );"/></p>
    
    </fieldset>';
    
    $content .= wp_nonce_field("save_getleads");
	
	/*
		Build the elements for the selection list
	*/
	
	$string = '';
	foreach ($fields['all'] as $k => $v) {
		$string .= '<div rel="'.$k.'" class="selection-option"><div class="selection-option-border"><div class="selection-option-title">'.$k.'</div><div class="selection-option-type">'.$v['type'].'</div></div></div>';
	}
	
    $content .= '</form>
	
		<div id="selection-popup">
			<div id="selection-dialog">
				<div id="selection-list">
				'.$string.'
				</div>
				<div id="selection-close">X</div>
			</div>
			<div id="modal"></div>
		</div>
	
	</div>';

	echo $content;		
}

function getleads_build_field($settings, $k, $v, $default = false) {
	
	$maxsection = getleads_max_section();
	
	$dd = '';
	if ($default) {
		$k = '!K!';
		$dd= $v['type'];
		$v = ['type' => '!T!', 'label' => '!L!', 'required' => '', 'section' => '', 'options' => '!O!', 'placeholder' => '!P!', 'mask' => '!M!', 'min' => '!MIN!', 'max' => '!MAX!', 'step' => '!STEP!', 'initial' => '!INITIAL!', 'question' => '!Q!'];
	}

    $content = '<li id="'.$k.'" class="ui-state-default"><table>
		<tr>
    <td class="bank_number" style="width:4%"></td>
    <td>
    <table>
    <thead>
        <tr><th>Req</th><th>Sect.</th><th>Type</th><th>Label/Options</th></tr>
        </thead>
    <tr>
    <td width="6%"><input type="checkbox" name="application['.$k.'][required]" ' . $v['required'] . ' value="checked" /></td>
    <td width="4%"><select class="gl_section" name="application['.$k.'][section]">';
	
	if (!$default) {
		for ($i = 1; $i <= $maxsection; $i++) {
			$content .= '<option '.(($v['section'] == $i)? 'selected="selected"':'').'>'.$i.'</option>';
		}
	}
	
	$content .= '</select></td>
	
    <td width="10%"><em>'.$v['type'].'</em></td>
	
    <td width="70%"><input name="application['.$k.'][label]" type="text" value="'.$v['label'].'" /></td>
    </tr>';
    
    if ($v['type'] == 'dropdown' || $dd == 'dropdown') {
        $content .= '<tr>
        <td></td>
        <td></td>
        <td><em>Options</em></td>
        <td><input name="application['.$k.'][options]" type="text" value="'.$v['options'].'" /></td>
        </tr>';
    } elseif ($v['type'] == 'conditional' || $dd == 'conditional') {
        $content .= '<tr>
        <td></td>
        <td></td>
        <td><em>Options</em></td>
        <td><input name="application['.$k.'][options]" type="text" value="'.$v['options'].'" /></td>
        </tr>
        <tr>
        <td></td>
        <td></td>
        <td><em>Question</em></td>
        <td><input name="application['.$k.'][question]" type="text" value="'.$v['question'].'" /></td>
        </tr>';
    } elseif ($v['type'] == 'captcha' || $dd == 'captcha') {
        $content .= '<tr>
        <td></td>
        <td></td>
        <td></td>
        <td>Adds a spam checker to the form</td>
        </tr>';
    } elseif ($v['type'] == 'checkbox' || $dd == 'checkbox') {
        $content .= '<tr>
        <td></td>
        <td></td>
        <td></td>
        <td>To add a link to the caption the format is: <em>Text &lt;a href="link_url"&gt;Anchor&lt;/a&gt; more text.</em></td>
        </tr>';
    } elseif ($v['type'] == 'range' || $dd == 'range') {
        $content .= '<tr>
        <td></td>
        <td></td>
        <td></td>
        <td>Min: <input name="application['.$k.'][min]" type="text" style="width:3em;" value="'.$v['min'].'"> 
        Max: <input name="application['.$k.'][max]" type="text" style="width:3em;" value="'.$v['max'].'"> 
        Initial: <input name="application['.$k.'][initial]" type="text" style="width:3em;" value="'.$v['initial'].'"> 
        Step: <input name="application['.$k.'][step]" type="text" style="width:3em;" value="'.$v['step'].'"></td>
        </tr>';
    } else {
        $content .= '<tr>
        <td></td>
        <td></td>
        <td><em>Placeholder</em></td>
        <td><input name="application['.$k.'][placeholder]" type="text" style="width:40%;" value="'.$v['placeholder'].'" />&nbsp;&nbsp;&nbsp;<em>Input mask</em>&nbsp;&nbsp;<input name="application['.$k.'][mask]" type="text" style="width:40%;" value="'.$v['mask'].'" /></td>
        </tr>';
    }
    
	$content .= '</table>
    </td>
    </tr>
    </table>
    <a class="gl_close" href="javascript:void(0);" onclick="getleads_close(this)"><i class="fa fa-trash-alt" aria-hidden="true"></i></a>
	</li>';
	
    return $content;
}

function getleads_admin_notice($message) {if (!empty( $message)) echo '<div class="updated"><p>'.$message.'</p></div>';}

function getleads_scripts_init() {
    wp_enqueue_style('getleads_settings',plugins_url('settings.css', __FILE__));
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style( 'load-fa', 'https://use.fontawesome.com/releases/v5.5.0/css/all.css' );
    wp_enqueue_media();
    wp_enqueue_script('getleads_media', plugins_url('media.js', __FILE__ ), array( 'wp-color-picker' ));
    wp_add_inline_script( 'getleads_media', '
		var getleads_sort;
		jQuery(function() {
			
			getleads_enable_sort()
			
		})
	');
}