<?php

/*

	Plugin Name: Basic LTI Plutgin
	Plugin URI: https://sourceforge.net/projects/learningapps/
	Plugin Description: Allows users to log in Q2A  using BasicLTI
	Plugin Version: 1.0
	Plugin Date: 2011-09-28
	Plugin Author: Antoni Bertran
	Plugin Author URI: http://www.uoc.edu/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.4

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

  
  require_once QA_INCLUDE_DIR.'qa-db-users.php';
	class qa_basic_lti {
	  
	  /* This plugin enables q2a to have Single Sign On (SSO) with a sister app.
	     The sister app maintains the user list. q2a does an HTTP GET operation to
	     a preset URL on the sister app. The GET is done from the server that runs
	     q2a, not by a browser.
	     
	     The q2a server does the GET to the sister app, passing all of the available
	     cookies to the sister app. The q2a server acts as proxy to the sister app.
	     
	     The limitation is that the sister app's session cookies stored on the user's
	     browser need to be visible to the q2a server. This means that both the 
	     q2a and sister app have to share a common part of their domains and set their
	     cookie domains accordingly.
	     
	     Eg sister app can be at www.foo.com and q2a can be at q2a.foo.com. But both
	     apps must then set their cookie domains to be foo.com. The proxy won't work
	     if the sister app uses cookie domain www.foo.com.
	  
	     This plugin stores the following as options within q2a:
	     
	     blti_logout -- Required. Complete url for logging out at sister app.
	                         Can include query parameters. Will also be given 
	                         additional query parameter of redirect.
	                            
	     blti_new_user_msg -- Optional. A msg for the user the first time they sign in to
	                               q2a via proxy sso.             
	                            
	     blti_welcome_msg -- Optional. A welcome msg for the user when they sign in to
	                              q2a via proxy sso. Not used for the first time.
	     
	     Both proxy_sso_new_user_msg and blti_welcome_msg can use include arguments:
	       %1$s -- fname
	       %2$s -- name
	       %3$s -- handle assigned by q2a. May be diifferent from the requested handle if
                 another user already had that handle.
                 
	                            	     
	     The proxy_sso_url is called with a GET operation by the q2a server with any available
	     cookies from the browser. It returns:
	     * If no user is logged in: return a zero length HTTP body. -- No data at all.
	     * If a user is logged in: return the following as a JSON encoded hash/associative
	       array. Members:
	       
	       id         Required. An id for the user that is unique in the sister application. Can be
	                  a number or a string.
	       
	       The following are only used the first time to create the new user. 
         email      Required. User's email
         handle     Required. A proposed handle for the user. If it is already taken by someone else
                    in the q2a system, then it will be modified to be unique. The user can then 
                    further change it as desired in the account profile page. If your system
                    does not use handles, then you must create one for the user. Eg Initials; First 
                    name and initial from last name.
         confirmed  Required. Boolean. Has the email been verified to belong to the user?
         name       Required. Full name of the user. Not publicly shown.
         fname      Optional. First name. Used for "Welcome back Larry!" message
         location   Optional.
         website    Optional.
         about      Optional. A description.
         avatar     Optional. Complete url of a photo or avatar for the user.
	  
	  =========================================================================================
	  =========================================================================================	  
    REF
      http://www.question2answer.org/plugins.php

	  DEBUGGING
	  
	  To see the messages that are being received from the remove server via the proxy_sso_url:
	  
	  Add 	
	  define('QA_PROXY_SSO_DEBUG', true); // either in this file or the main qa-config.php file

	  */
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
			$this->source_name='basic_lti';
		}
		
		function basic_lti_active() {
			$basic_lti_enabled = strlen(qa_opt('basic_lti_enabled'));
			if ($basic_lti_enabled) {
				$required_class = 'IMSBasicLTI/uoc-blti/bltiUocWrapper.php';
				$exists=fopen ( $required_class, "r",1 );
				if (!$exists) {
		
					error_log('Required classes BASIC LTI not exists check the include_path is correct');
      				echo('Required classes BASIC LTI not exists check the include_path is correct');
				        
					return false;
				} 
	
			  	require_once $required_class;
			}
		  return $basic_lti_enabled;
		}
		
    // Function: check_login
    // Called by qa-page
    // Direct return: none
    // Side effect: Call library function qa_log_in_external_user if the user id logged
    //              in via the other service. 
    //              
		// Note: Called for every page display until someone is logged in. -- So needs to quickly determine
		//       that no one is logged in if that is the case.
		function check_login()
		{
			if (!$this->basic_lti_active())
				return;
			if (!$this->do_basic_lti_call()) // Don't make a remote url query if there's no point
				return;
			
			$context = new bltiUocWrapper(false, false);
		    if ( ! $context->valid ) {
				error_log('BASIC LTI Authentication Failed, not valid request (make sure that consumer is authorized and secret is correct)');
		        echo('BASIC LTI Authentication Failed, not valid request (make sure that consumer is authorized and secret is correct)');
		        return;
		    }
		    
		    $identifier = $context->getUserKey();
		    $blti_custom_username = qa_opt('blti_custom_username');
		    $basic_lti_username_disabled = qa_opt('basic_lti_username_disabled');
		    if ($basic_lti_username_disabled && strlen($blti_custom_username)>0) {
		 	   if (isset($context->info[$blti_custom_username]) && strlen($context->info[$blti_custom_username])>0) {
					$identifier = $context->info[$blti_custom_username];
				}
		    }
		    $identifier = str_replace(':','-',$identifier);  // TO make it past sanitize_user
		    $name = $context->getUserName();
			$email = $context->getUserEmail();
			$image = $context->getUserImage();
			$source = $this->source_name;
						    // qa_log_in_external_user is defined in qa-include/qa-app-users
			    // Docs for qa_log_in_external_user: http://www.question2answer.org/modules.php
			    // It will either login a user or create a new user and then log him in.
    			
    			// See if user is already registered with the q2a system
    			$users=qa_db_user_login_find($source, $identifier);
			    $new_user = count($users) == 0;
			    
			    // log in our guy. If this is the first time, then the method will create a 
			    // login in q2a as a side-effect
			  	qa_log_in_external_user($source, $identifier, array(
			  		'email' => $email,
			  		'confirmed' => 1,
			  		'handle' => $identifier,
					'name' => $name,
			  		'location' => '',
			  		'website' => '',
			  		'about' => '',
			  		'avatar' => strlen($image) ? qa_retrieve_url($image) : null,
			  	));
	        
	        	$handle = qa_get_logged_in_handle();
	        // Set flash welcome msg
	        // %1$s -- fname
	        // %2$s -- name
	        // %3$s -- handle assigned by q2a.
          $template = qa_opt($new_user ? 'blti_new_user_msg' : 'blti_welcome_msg');
          if (strlen($template)) 
			      $this->set_flash(sprintf($template, $user['fname'], $user['name'], $handle));
			    
		}
				
		function do_basic_lti_call() {
			return is_basic_lti_request();
		}

		function match_source($source)
		{
			return $source == $this->source_name;
		}
		
		function add_redirect($url, $to) 
		{
		   return $url . (strpos($url, '?') ? "&redirect=" : "?redirect=") . urlencode($to); 
		}
		
//		// Called from page qa-page for the Login in the top menu,
//		// plus qa-page-register and qa-page-login
//		function login_html($tourl, $context)
//		{
//			if (!$this->basic_lti_active())
//				return;
//			$url = $this->add_redirect(qa_opt('proxy_blti_login'), $tourl);
//			$l = qa_opt('proxy_blti_login_label');
//			$label = strlen($l) ? $l : 'Login';
//			echo "<a class='qa-nav-user-link' href='" . $url . "'>" . $label . "</a>"; 
//		}
		
//		function logout_html($tourl)
//		{
//			if (!$this->basic_lti_active())
//				return;
//			$url = $this->add_redirect(qa_opt('blti_logout'), $tourl);
//			echo "<a class='qa-nav-user-link' href='" . $url . "'>Logout</a>"; 
//		}
		
		function admin_form()
		{
			$saved=false;

			if (qa_clicked('blti_save_button')) {
				qa_opt('basic_lti_enabled', qa_post_text('basic_lti_enabled'));
				qa_opt('basic_lti_username_disabled', qa_post_text('basic_lti_username_disabled'));
				qa_opt('blti_custom_username', qa_post_text('blti_custom_username'));
				qa_opt('blti_logout', qa_post_text('blti_logout'));
				qa_opt('blti_new_user_msg', qa_post_text('blti_new_user_msg'));
				qa_opt('blti_welcome_msg', qa_post_text('blti_welcome_msg'));
				$saved=true;
			}
		  
		  // Forms are processed by qa-theme-base. See functions form_field_rows, form_label,\
		  // form_data et al
			return array(
				'ok' => $saved ? 'Settings saved' : null,
				'title' => "BasicLTI Settings",
				
				'fields' => array(
					array(
						'label' => 'Enable Plugin',
						'type' => 'checkbox',
						'value' => (int)qa_opt('basic_lti_enabled'),
						'tags' => 'NAME="basic_lti_enabled" ID="basic_lti_enabled"',
					),
			
					array(
						'label' => 'Disable Default BLTI Username',
						'type' => 'checkbox',
						'value' => (int)qa_opt('basic_lti_username_disabled'),
						'tags' => 'NAME="basic_lti_username_disabled" ID="basic_lti_username_disabled"',
						'note' => 'You have to define the parameter of basic lti with the custom username instead of the default BasicLTI Parameter'
					),
			
					array(
						'label' => 'Parameter Username:',
						'value' => qa_html(qa_opt('blti_custom_username')),
						'tags' => 'NAME="blti_custom_username"',
						'note' => 'If you enabled Disable BLTI Username you have to indicate the custom username, usuarlly is custom_username'
					),


					array(
						'label' => 'Logout URL:',
						'value' => qa_html(qa_opt('blti_logout')),
						'tags' => 'NAME="blti_logout"',
						'note' => 'Full url (include http://) to logout from the SSO and this Q2A application'
					),

					array(
						'label' => 'New user message:',
						'value' => qa_html(qa_opt('blti_new_user_msg')),
						'tags' => 'NAME="blti_new_user_msg"',
						'note' => 'Optional. Message shown to new users when they login for the first time.
						<br/>Can include arguments: %1$s&#151;first&nbsp;name; %2$s&#151;full&nbsp;name; %3$s&#151;assigned handle within Question 2 Answer. Note that the assigned handle will be different from the handle in your SSO application if someone else already had that handle within this Q2A system.<br/><br/>
Example:<br/>
Welcome to the Question & Answer site!&lt;p&gt;You have been assigned user name %3$s. You can change it by clicking on "My Account" on the upper right corner of the screen.&lt;/p&gt;'
					),

					array(
						'label' => 'Welcome message:',
						'value' => qa_html(qa_opt('blti_welcome_msg')),
						'tags' => 'NAME="blti_welcome_msg"',
						'note' => 'Optional. Message shown to users when they login other than the first time.<br/>Can include same arguments as the New user message.<br/><br/>
Example:<br/>
Welcome back %1$s!'
					),

				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="blti_save_button"',
					),
				),
			);
		}
	
	/**
    * From the Vanilla project. www.vanillaforums.org GPL v3 license
    * See file Vanilla/library/core/functions.general.php
    *
	  * Return the value from an associative array or an object.
	  *
	  * @param string $Key The key or property name of the value.
	  * @param mixed $Collection The array or object to search.
	  * @param mixed $Default The value to return if the key does not exist.
     * @param bool $Remove Whether or not to remove the item from the collection.
	  * @return mixed The value from the array or object.
	  */
	 function get_value($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
	 	$Result = $Default;
	 	if(is_array($Collection) && array_key_exists($Key, $Collection)) {
	 		$Result = $Collection[$Key];
          if($Remove)
             unset($Collection[$Key]);
	 	} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
	 		$Result = $Collection->$Key;
          if($Remove)
             unset($Collection->$Key);
       }
	 		
       return $Result;
	 }
	 
	 function set_flash($msg) {
  		  $_SESSION['qa_flash']=$msg;
   }	
		
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/