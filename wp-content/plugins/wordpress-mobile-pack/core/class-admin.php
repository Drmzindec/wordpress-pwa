<?php
if ( ! class_exists( 'WMobilePackAdmin' ) ) {
     //require_once '../libs/htmlpurifier-4.6.0/library/HTMLPurifier.safe-includes.php'; 
	/**
	 * WMobilePackAdmin class for creating the admin area for the Wordpress Mobile Pack plugin
	 *
	 */
	class WMobilePackAdmin {

		
		/**
         * 
		 * Method used to render the main admin page (free version)
		 *
		 */
		public function wmp_options() {
			     
			global $wmobile_pack;
			
            WMobilePack::wmp_update_settings('whats_new_updated', 0);
            
			// load view
			include(WMP_PLUGIN_PATH.'admin/wmp-admin-main.php');        
		}
		
		
		/**
         * 
		 * Method used to render the main admin page for the premium dashboard
		 *
		 */
		public function wmp_premium_options() {
			
			global $wmobile_pack;
			 
			// load view
			include(WMP_PLUGIN_PATH.'admin/wmp-admin-premium.php'); 
		}
		
         
        /**
		 * Static method used to request the content for the What's New page.
		 * The method returns an array containing the latest content or an empty array by default.
		 *
		 */
		public static function wmp_whatsnew_updates() { 
			
			$json_data = get_transient("wmp_whats_new_updates"); 
            
			// the transient is not set or expired
			if (!$json_data) {
			
                // check if we have a https connection
                $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
                
    			// jSON URL which should be requested
    			$json_url = ($is_secure ? WMP_WHATSNEW_UPDATES_HTTPS : WMP_WHATSNEW_UPDATES);
    			
				// get response
				$json_response = self::wmp_read_data($json_url);
				
				if ($json_response !== false && $json_response != '') {
					
					// Store this data in a transient
					set_transient( 'wmp_whats_new_updates', $json_response, 3600*24*2 );
					
					// get response
					$response = json_decode($json_response, true);
			
					if (isset($response["content"]) && is_array($response["content"]) && !empty($response["content"])){
					   
                        if (isset($response['content']['last_updated']) && is_numeric($response['content']['last_updated'])){
                            
                            $last_updated = intval($response['content']['last_updated']);  
                            $option_last_updated = intval(WMobilePack::wmp_get_setting('whats_new_last_updated'));
                            
                            if ($last_updated > $option_last_updated){
                                
                                WMobilePack::wmp_update_settings('whats_new_last_updated', $last_updated);
                                WMobilePack::wmp_update_settings('whats_new_updated', 1);
                            }
                        }
                        
						// return response
						return $response["content"];
                    }
					
				} elseif($json_response == false) {
					
					// Store this data in a transient
					set_transient('wmp_whats_new_updates', 'warning', 3600*24*2 );
                    
					// return message
					return 'warning';	
				}
				
			} else {
					
                if ($json_data == 'warning')
                    return $json_data;
                    
				// get response
				$response = json_decode($json_data, true);
			
				if (isset($response["content"]) && is_array($response["content"]) && !empty($response["content"]))
					return $response["content"];
			}
            
			// by default return empty array
			return array();
		}
		

		/**
         * 
		 * Method used to render the themes selection page from the admin area (free version)
		 *
		 */
		public function wmp_theme_options() {
			
			global $wmobile_pack;
			
			// load view
			include(WMP_PLUGIN_PATH.'admin/wmp-admin-theme.php');
		}

		
		/**
         * 
		 * Method used to render the content selection page from the admin area (free version)
		 *
		 */
		public function wmp_content_options() {
			
			global $wmobile_pack;
			
			// load view
			include(WMP_PLUGIN_PATH.'admin/wmp-admin-content.php');
		}
		
		
		/**
         * 
		 * Method used to render a form with a page's details (free version)
		 *
		 */
		public function wmp_page_content() {
			
			global $wmobile_pack;
			
			include(WMP_PLUGIN_PATH.'libs/htmlpurifier-4.6.0/library/HTMLPurifier.safe-includes.php');
			if (isset($_GET) && is_array($_GET) && !empty($_GET)){
				 
				 if (isset($_GET['id'])) { 
				 
				 	if (is_numeric($_GET['id'])) {
							
						// get page
						$page = get_page($_GET['id']); 
										  
						if($page != null) {
							
							$config = HTMLPurifier_Config::createDefault();
							$config->set('Core.Encoding', 'UTF-8'); 									
							
                            $config->set('HTML.AllowedElements','div,a,p,ol,li,ul,img,blockquote,em,span,h1,h2,h3,h4,h5,h6,i,u,strong,b,sup,br,cite,iframe');
						  	$config->set('HTML.AllowedAttributes', 'class, src, width, height, target, href, name,frameborder,marginheight,marginwidth,scrolling');
						    
                            $config->set('Attr.AllowedFrameTargets', '_blank, _parent, _self, _top');
							
							$config->set('HTML.SafeIframe',1);
							$config->set('Filter.Custom', array( new HTMLPurifier_Filter_Iframe()));
							
							// disable cache
							$config->set('Cache.DefinitionImpl',null);
							
							$purifier  = new HTMLPurifier($config); 
							
							// first check if the admin edited the content for this page
							if(get_option( 'wmpack_page_' .$page->ID  ) === false)
								$content = apply_filters("the_content",$page->post_content);
							else
								$content = apply_filters("the_content",get_option( 'wmpack_page_' .$page->ID  ));
							$content = $purifier->purify(stripslashes($content));
							
							// load view
							include(WMP_PLUGIN_PATH.'admin/wmp-admin-page-details.php');	
						}
					}			
				}
			}		
		}
		
		
        /**
         * 
         * Method used to save the categories settings in the database (free version)
         * 
         */
        public function wmp_content_save() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    
                    error_log(date('[Y-m-d H:i e] '). "Categories Save - POST not empty" . PHP_EOL, 3, $log);
                    
                    if (isset($_POST['id']) && isset($_POST['status'])){
                        
                        // set category id
                        error_log(date('[Y-m-d H:i e] '). "Categories Save - Category Id ".$_POST['id'] . PHP_EOL, 3, $log);
                        // set category status
                        error_log(date('[Y-m-d H:i e] '). "Categories Save - Category status ".$_POST['status'] . PHP_EOL, 3, $log);
                    
                        
                        if (is_numeric($_POST['id']) && ($_POST['status'] == 'active' || $_POST['status'] == 'inactive')){
                            
                            $status = 1;
                             
                            $category_id = intval($_POST['id']);
                            $category_status = strval($_POST['status']);
                            
                            // get inactive categories option
                            $inactive_categories = unserialize(WMobilePack::wmp_get_setting('inactive_categories'));
                            
                            // add or remove the category from the option
                            if (in_array($category_id, $inactive_categories) && $category_status == 'active')
                                $inactive_categories = array_diff($inactive_categories, array($category_id));
                            
                            if (!in_array($category_id, $inactive_categories) && $category_status == 'inactive')
                                $inactive_categories[] = $category_id;
                                
                            // save option
                            WMobilePack::wmp_update_settings('inactive_categories', serialize($inactive_categories));
                        
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Categories Save - The data in POST is not valid" . PHP_EOL, 3, $log);
                    
                    } else
                        error_log(date('[Y-m-d H:i e] '). "Categories Save - The data in POST is not set(id and status)" . PHP_EOL, 3, $log);   
                } else
                    error_log(date('[Y-m-d H:i e] '). "Categories Save - POST is empty" . PHP_EOL, 3, $log);   
                
                echo $status;
            }
            
            exit();
        }
		
		
		 /**
         * 
         * Method used to save the pages settings in the database (free version)
         * 
         */
        public function wmp_content_pagestatus() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
                    
                    error_log(date('[Y-m-d H:i e] '). "Pages Save - POST not empty" . PHP_EOL, 3, $log);
                    
                    if (isset($_POST['id']) && isset($_POST['status'])){
                    
                        // set category id
                        error_log(date('[Y-m-d H:i e] '). "Pages Save - Page Id ".$_POST['id'] . PHP_EOL, 3, $log);
                        // set category status
                        error_log(date('[Y-m-d H:i e] '). "Pages Save - Page status ".$_POST['status'] . PHP_EOL, 3, $log);
                    
                        if (is_numeric($_POST['id']) && ($_POST['status'] == 'active' || $_POST['status'] == 'inactive')){
                            
                            
                            $status = 1;
                             
                            $page_id = intval($_POST['id']);
                            $page_status = strval($_POST['status']);
                            
                            // get inactive pages option
                            $inactive_pages = unserialize(WMobilePack::wmp_get_setting('inactive_pages'));
                            
                            // add or remove the page from the option
                            if (in_array($page_id, $inactive_pages) && $page_status == 'active')
                                $inactive_pages = array_diff($inactive_pages, array($page_id));
                            
                            if (!in_array($page_id, $inactive_pages) && $page_status == 'inactive')
                                $inactive_pages[] = $page_id;
                                
                            // save option
                            WMobilePack::wmp_update_settings('inactive_pages', serialize($inactive_pages));
                        
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Pages Save - The data is POST is not valid ". PHP_EOL, 3, $log);
                        
                    } else 
                        error_log(date('[Y-m-d H:i e] '). "Pages Save - The data is POST is not set (id or status) ". PHP_EOL, 3, $log);
                    
                } else
                    error_log(date('[Y-m-d H:i e] '). "Pages Save - POST is empty ". PHP_EOL, 3, $log);
                
                echo $status;
            }
            
            exit();
        }
		
		
		
		/**
        * 
        * Method used to save the order of pages and categories in the database (free version)
        * 
        */
        public function wmp_content_order() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    
                    error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order  Save - POST not empty" . PHP_EOL, 3, $log);
                    
                    if (isset($_POST['ids']) && isset($_POST['type'])){
                      
                        // set category id
                        error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order - Ids ".$_POST['ids'] . PHP_EOL, 3, $log);
                        // set category status
                        error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order - Type ".$_POST['type'] . PHP_EOL, 3, $log);
                    
                        
                        if ($_POST['ids'] != '' && ($_POST['type'] == 'pages' || $_POST['type'] == 'categories')){
                             
							// check ids
							$arrPagesIds = array_filter(explode(",", $_POST['ids']));
							
							if (count($arrPagesIds) > 0) {
								
								$valid_ids = true;
							
								foreach ($arrPagesIds as $page_id) {
									
									if (!is_numeric($page_id)) // 4page_is is not numeric
										$valid_ids = false;
								}
		        	
								if ($valid_ids) {
									
									 $status = 1;
									
									// save option
                           			if ($_POST['type'] == 'pages')
										WMobilePack::wmp_update_settings('ordered_pages', serialize($arrPagesIds));
									elseif ($_POST['type'] == 'categories')
										WMobilePack::wmp_update_settings('ordered_categories', serialize($arrPagesIds));
								
                                } else
                                    error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order  Save - Ids not valid" . PHP_EOL, 3, $log);
							}
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order  Save - POST data is not valid" . PHP_EOL, 3, $log);
                    } else
                        error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order  Save - POST data is not set correctly" . PHP_EOL, 3, $log);
                            
                } else
                    error_log(date('[Y-m-d H:i e] '). "Categories / Pages Order  Save - POST is empty" . PHP_EOL, 3, $log);
                
                echo $status;
            }
            
            exit();
        }
		
		
		/**
         * 
         * Method used to save the page details content in the database (free version)
         * 
         */
        public function wmp_content_pagedetails() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                global $wmobile_pack;
            	
                $status = 0;
               
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    
                    error_log(date('[Y-m-d H:i e] '). "Page details Save - POST not empty" . PHP_EOL, 3, $log);
                    
                    
                    if (isset($_POST['wmp_pageedit_id']) && isset($_POST['wmp_pageedit_content'])){
                        
                        // set category id
                        error_log(date('[Y-m-d H:i e] '). "Page details Save - Id ".$_POST['wmp_pageedit_id'] . PHP_EOL, 3, $log);
                        // set category status
                        error_log(date('[Y-m-d H:i e] '). "Page details Save - Content ".$_POST['wmp_pageedit_content'] . PHP_EOL, 3, $log);
                    
                        
                        if (is_numeric($_POST['wmp_pageedit_id']) && trim($_POST['wmp_pageedit_content']) != ''){
                            
							// set HTML Purifier
							include(WMP_PLUGIN_PATH.'libs/htmlpurifier-4.6.0/library/HTMLPurifier.safe-includes.php');
							$config = HTMLPurifier_Config::createDefault();
							$config->set('Core.Encoding', 'UTF-8'); 									
							
                            $config->set('HTML.AllowedElements','div,a,p,ol,li,ul,img,blockquote,em,span,h1,h2,h3,h4,h5,h6,i,u,strong,b,sup,br,cite,iframe');
						  	$config->set('HTML.AllowedAttributes', 'class, src, width, height, target, href, name,frameborder,marginheight,marginwidth,scrolling');
						    
							$config->set('Attr.AllowedFrameTargets', '_blank, _parent, _self, _top');
							
							$config->set('HTML.SafeIframe',1);
							$config->set('Filter.Custom', array( new HTMLPurifier_Filter_Iframe()));
							
							// disable cache
							$config->set('Cache.DefinitionImpl',null);
							
							$purifier  = new HTMLPurifier($config); 
							
                            $status = 1;
                            
                            $page_id = intval($_POST['wmp_pageedit_id']);
                            $page_content = $purifier->purify(stripslashes($_POST['wmp_pageedit_content']));
                            
                            // save option in the db
							update_option( 'wmpack_page_' . $page_id, $page_content );
                            
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Page details Save - POST data is not valid" . PHP_EOL, 3, $log);
                    } else
                         error_log(date('[Y-m-d H:i e] '). "Page details Save - POST data is not set correctly" . PHP_EOL, 3, $log);    
                } else
                     error_log(date('[Y-m-d H:i e] '). "Page details Save - POST is empty" . PHP_EOL, 3, $log);
                
                echo $status;
            }
            
            exit();
        }
		
		
		
		/**
         * 
         * Method used to send a feedback e-mail from the admin 
         * 
         * Handle request then display 1 for success and 0 for error.
         * 
         */
        public function wmp_send_feedback() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                $status = 0;
               
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                     
                    error_log(date('[Y-m-d H:i e] '). "Send Feedback - POST not empty" . PHP_EOL, 3, $log);
                    
                     
                    if (isset($_POST['wmp_feedback_page']) && isset($_POST['wmp_feedback_name']) && isset($_POST['wmp_feedback_email']) && isset($_POST['wmp_feedback_message'])){
                        
                        // set feedback page
                        error_log(date('[Y-m-d H:i e] '). "Send Feedback - Page ".$_POST['wmp_feedback_page'] . PHP_EOL, 3, $log);
                        // set feedback email
                        error_log(date('[Y-m-d H:i e] '). "Send Feedback - Email ".$_POST['wmp_feedback_email'] . PHP_EOL, 3, $log);
                     
                        
                        if (is_string($_POST['wmp_feedback_page']) && $_POST['wmp_feedback_page'] != '' && $_POST['wmp_feedback_name'] != "" && $_POST['wmp_feedback_email'] && $_POST['wmp_feedback_message'] != '' ){
                          
    					  	// get admin e-mail and name
    					  	if (is_admin()) {
    							
    							$admin_email = $_POST['wmp_feedback_email'];
                                
    							// filter e-mail														
    							if (filter_var($admin_email, FILTER_VALIDATE_EMAIL) !== false ){
    								 
    								// set e-mail variables
                                    $message = "Name: ".strip_tags($_POST["wmp_feedback_name"])."\r\n \r\n";
                                    $message .= "E-mail: ".$_POST["wmp_feedback_email"]."\r\n \r\n";
    								$message .= "Message: ".strip_tags($_POST["wmp_feedback_message"])."\r\n \r\n";
                                    $message .= "Page: ".stripslashes(strip_tags($_POST['wmp_feedback_page']));
                                    
    								$subject = 'WP Mobile Pack Feeback';
    								$to = WMP_FEEDBACK_EMAIL;
                                    
    								// set headers
    								$headers = 'From:'.$admin_email."\r\nReply-To:".$admin_email;
                                    
    								// send e-mail		
    								if (mail($to, $subject, $message, $headers)) 
                                        $status = 1;
    							}
    						} else
                                error_log(date('[Y-m-d H:i e] '). "Send Feedback - The user is not admin ". PHP_EOL, 3, $log);
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Send Feedback - The POST data is not valid ". PHP_EOL, 3, $log);
                    } else 
                         error_log(date('[Y-m-d H:i e] '). "Send Feedback - The POST data is not set ". PHP_EOL, 3, $log);   
                } else
                     error_log(date('[Y-m-d H:i e] '). "Send Feedback - The POST is empty ". PHP_EOL, 3, $log);
                
                echo $status;
            }
            
            exit();
        }
		
		
		/**
		 * Static method used to request the news and updates from an endpoint on a different domain.
         * 
		 * The method returns an array containing the latest news and updates or an empty array by default.
		 *
		 */ 
		public static function wmp_news_updates() {
			
			$json_data =  get_transient("wmp_newsupdates");
            
            if (!$json_data) {
			
                // check if we have a https connection
                $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
                
    			// JSON URL that should be requested
    			$json_url = ($is_secure ? WMP_NEWS_UPDATES_HTTPS : WMP_NEWS_UPDATES);
                
				// get response
				$json_response = self::wmp_read_data($json_url);
				
				if($json_response !== false && $json_response != '') {
					
					// Store this data in a transient
					set_transient('wmp_newsupdates', $json_response, 3600*24*2);
					
					// get response
					$response = json_decode($json_response, true);
					
					if ( (isset($response["news"]) && is_array($response["news"]) && !empty($response["news"])) || 
						(isset($response["whitepaper"]) && is_array($response["whitepaper"]) && !empty($response["whitepaper"])) ) {
							
						return $response;    
					}
				} 
			
			} else {
					
				// get response
				$response = json_decode($json_data, true);
				
                if ( (isset($response["news"]) && is_array($response["news"]) && !empty($response["news"])) || 
                    (isset($response["whitepaper"]) && is_array($response["whitepaper"]) && !empty($response["whitepaper"])) ) {
                    
                    return $response;
                }
			}
			
			// by default return empty array
			return array();
		}
		
		
		/**
         * 
		 * Method used to render the settings selection page from the admin area (free version)
		 *
		 */
		public function wmp_settings_options() {
			
			global $wmobile_pack;
			
			// load view
			include(WMP_PLUGIN_PATH.'admin/wmp-admin-settings.php');
		}
        
  
        /**
         * 
         * Method used to save the settings display mode, color schemes and fonts or joined waitlists. (free version)
         * 
         */
        public function wmp_settings_save() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )) {
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    // post not empty
                    error_log(date('[Y-m-d H:i e] '). "Settings save - POST not empty" . PHP_EOL, 3, $log);
                     
                    // handle display mode (settings page)
                    if (isset($_POST['wmp_editsettings_displaymode']) && $_POST['wmp_editsettings_displaymode'] != ''){
                        if (in_array($_POST['wmp_editsettings_displaymode'], array('normal', 'preview', 'disabled'))){
                            
                            // set display mode
                            error_log(date('[Y-m-d H:i e] '). "Settings save - Display mode ".$_POST['wmp_editsettings_displaymode'] . PHP_EOL, 3, $log);
                    
                            
                            $status = 1;
                            // save google analytics id
    						if (isset($_POST["wmp_editsettings_ganalyticsid"])) {
    							
                                // set google analytics id
                                error_log(date('[Y-m-d H:i e] '). "Settings save - Google analytics id ".$_POST['wmp_editsettings_ganalyticsid'] . PHP_EOL, 3, $log);
                    
    							// validate google analytics id
    							if (preg_match('/^ua-\d{4,9}-\d{1,4}$/i', strval($_POST["wmp_editsettings_ganalyticsid"])))
    								WMobilePack::wmp_update_settings('google_analytics_id', $_POST['wmp_editsettings_ganalyticsid']);
                                elseif ($_POST["wmp_editsettings_ganalyticsid"] == "")
                                    WMobilePack::wmp_update_settings('google_analytics_id', "");
    							
    						}
                            // save option
                            WMobilePack::wmp_update_settings('display_mode', $_POST['wmp_editsettings_displaymode']);
                        }
                    }
                    
                    // handle color schemes and fonts (look & feel page)
                    if (isset($_POST['wmp_edittheme_colorscheme']) && $_POST['wmp_edittheme_colorscheme'] != '' &&
                        isset($_POST['wmp_edittheme_fontheadlines']) && $_POST['wmp_edittheme_fontheadlines'] != '' &&
                        isset($_POST['wmp_edittheme_fontsubtitles']) && $_POST['wmp_edittheme_fontsubtitles'] != '' &&
                        isset($_POST['wmp_edittheme_fontparagraphs']) && $_POST['wmp_edittheme_fontparagraphs'] != ''){
                        
                        if (in_array($_POST['wmp_edittheme_colorscheme'], array(1,2,3)) && 
                            in_array($_POST['wmp_edittheme_fontheadlines'], WMobilePack::$wmp_allowed_fonts) && 
                            in_array($_POST['wmp_edittheme_fontsubtitles'], WMobilePack::$wmp_allowed_fonts) &&
                            in_array($_POST['wmp_edittheme_fontparagraphs'], WMobilePack::$wmp_allowed_fonts)){
                            
                            $status = 1;
                            
                            // save options
                            WMobilePack::wmp_update_settings('color_scheme', $_POST['wmp_edittheme_colorscheme']);
                            WMobilePack::wmp_update_settings('font_headlines', $_POST['wmp_edittheme_fontheadlines']);
                            WMobilePack::wmp_update_settings('font_subtitles', $_POST['wmp_edittheme_fontsubtitles']);
                            WMobilePack::wmp_update_settings('font_paragraphs', $_POST['wmp_edittheme_fontparagraphs']);
                        }
                    }
                    
                    // handle joined waitlists
                    if (isset($_POST['joined_waitlist']) && $_POST['joined_waitlist'] != ''){
                        
                        if (in_array($_POST['joined_waitlist'], array('content', 'settings', 'lifestyletheme',  'businesstheme','themes_features'))){
                            
                            $option_waitlists = WMobilePack::wmp_get_setting('joined_waitlists');
                            
                            if ($option_waitlists != '')
                                $joined_waitlists = unserialize(WMobilePack::wmp_get_setting('joined_waitlists'));
                            
                            if ($joined_waitlists == null || !is_array($joined_waitlists))
                                $joined_waitlists = array();
                                
                            if (!in_array($_POST['joined_waitlist'], $joined_waitlists)) {
                                
                                $status = 1;
                                
                                $joined_waitlists[] = $_POST['joined_waitlist'];
                                
                                // save option
                                WMobilePack::wmp_update_settings('joined_waitlists', serialize($joined_waitlists));
                            }
                        }
                    }        
                }
                
                // set settings save status
                error_log(date('[Y-m-d H:i e] '). "Settings save - Status ".$status . PHP_EOL, 3, $log);
                    
                
                echo $status;
            }
            
            exit(); 
        }
		
		/**
         * 
         * Method used to save the api key (connect to premium)
         * 
         */
        public function wmp_premium_save() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    
                    // post not empty
                    error_log(date('[Y-m-d H:i e] '). "Api key added - POST not empty" . PHP_EOL, 3, $log);
                    
                    if (isset($_POST['api_key'])){
                        
                        // set api key to error log
                        error_log(date('[Y-m-d H:i e] '). "Api key added - Api key - ".$_POST['api_key'] . PHP_EOL, 3, $log);
                 
                        if (preg_match('/^[a-zA-Z0-9]+$/', $_POST['api_key']) ){
                        
                            // save options
                            if(WMobilePack::wmp_update_settings('premium_api_key',$_POST['api_key']))
								$status = 1;
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Api key added - Api kei is not valid" . PHP_EOL, 3, $log);  
                        
                    } else
                        error_log(date('[Y-m-d H:i e] '). "Api key added - Api key is not in POST" . PHP_EOL, 3, $log);  
                        
                } else
                    error_log(date('[Y-m-d H:i e] '). "Api key added - POST is empty" . PHP_EOL, 3, $log);  
                
                echo $status;
                // set  api key valid and saved
                error_log(date('[Y-m-d H:i e] '). "Api key added - Status - ".$status . PHP_EOL, 3, $log);
                 
            }
            
            exit();
        }
		
		
		
		/**
         * 
         * Method used to save the premium settings 
         * 
         */
        public function wmp_premium_connect() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can('manage_options')){
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    
                    // post not empty
                    error_log(date('[Y-m-d H:i e] '). "Premium connect - POST not empty" . PHP_EOL, 3, $log);
                    
                    
                    if (isset($_POST['api_key']) && isset($_POST['valid']) && isset($_POST['config_path'])){
                        
                        // set connect - api key
                        error_log(date('[Y-m-d H:i e] '). "Premium connect - Api key - ".$_POST['api_key'] . PHP_EOL, 3, $log);
                        // set api key to error log
                        error_log(date('[Y-m-d H:i e] '). "Premium connect - Valid - ".$_POST['valid'] . PHP_EOL, 3, $log);
                        // set api key to error log
                        error_log(date('[Y-m-d H:i e] '). "Premium connect - Config path - ".$_POST['config_path'] . PHP_EOL, 3, $log);
                 
                        
                        if (
								preg_match('/^[a-zA-Z0-9]+$/', $_POST['api_key']) && 
								($_POST['valid'] == '0' || $_POST['valid'] == '1') && 
								$_POST['config_path'] != '' && filter_var($_POST['config_path'], FILTER_VALIDATE_URL)
							){
                            
                            if ($_POST['api_key'] == WMobilePack::wmp_get_setting('premium_api_key')) {
						 
                                $arrData = array(
                                    'premium_api_key' => $_POST['api_key'],
                                    'premium_active'  => $_POST['valid'],
                                    'premium_config_path' => $_POST['config_path']
                                );
                                    
                                if (WMobilePack::wmp_update_settings($arrData)) {
                                    
                                    // attempt to load the settings json
                                    $json_config_premium = WMobilePack::wmp_set_premium_config();
                                    
                                    if ($json_config_premium !== false){
                                        $status = 1;
                                    } else {       
                                        WMobilePack::wmp_update_settings('premium_active', 0);
                                    }
                                }
							} else
                                error_log(date('[Y-m-d H:i e] '). "Premium connect - Api key is not the same " . PHP_EOL, 3, $log);
                   
                        } else 
                            error_log(date('[Y-m-d H:i e] '). "Premium connect - The data in POST is not valid " . PHP_EOL, 3, $log);
                    } else
                        error_log(date('[Y-m-d H:i e] '). "Premium connect - The data in POST is not set " . PHP_EOL, 3, $log);
                } else
                    error_log(date('[Y-m-d H:i e] '). "Premium connect - POST is not set " . PHP_EOL, 3, $log);
                
                echo $status;
                
                // set  premium connect status
                error_log(date('[Y-m-d H:i e] '). "Premium connect - Status - ".$status . PHP_EOL, 3, $log);
                
            }
            
            exit();
        }
		
		/**
         * 
         * Method used to disconnect the dashboard from Appticles and rever to basic theme
         * 
         */
        public function wmp_premium_disconnect() {
            
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            
            if (current_user_can( 'manage_options' )){
                
                global $wmobile_pack;
            	
                $status = 0;
                
                if (isset($_POST) && is_array($_POST) && !empty($_POST)){
                    
                    // post not empty
                    error_log(date('[Y-m-d H:i e] '). "Premium disconnect - POST not empty" . PHP_EOL, 3, $log);
                    
                    
                    if (isset($_POST['api_key']) && isset($_POST['active'])){
                        
                        // set connect - api key
                        error_log(date('[Y-m-d H:i e] '). "Premium disconnect - Api key - ".$_POST['api_key'] . PHP_EOL, 3, $log);
                        // set connect - active
                        error_log(date('[Y-m-d H:i e] '). "Premium disconnect - Active - ".$_POST['active'] . PHP_EOL, 3, $log);
                        
                        
                        if (preg_match('/^[a-zA-Z0-9]+$/', $_POST['api_key']) && $_POST['active'] == 0){
                                
							$arrData = array(
											 	'premium_api_key' => '',
												'premium_active'  => 0,
												'premium_config_path' => ''
											 );	
								
                            // save options
                           if( WMobilePack::wmp_update_settings($arrData))	
						   	$status = 1;
							
                        } else
                            error_log(date('[Y-m-d H:i e] '). "Premium disconnect - The data in POST is not valid " . PHP_EOL, 3, $log);
                    } else
                        error_log(date('[Y-m-d H:i e] '). "Premium disconnect - The data in POST is not set " . PHP_EOL, 3, $log);   
                } else
                    error_log(date('[Y-m-d H:i e] '). "Premium connect - POST is not set " . PHP_EOL, 3, $log);
                
                echo $status;
                
                // set  premium connect status
                error_log(date('[Y-m-d H:i e] '). "Premium disconnect - Status - ".$status . PHP_EOL, 3, $log);
            }
            
            exit();
        }
		
        
        /**
         * 
         * Method used to save the icon and logo
         * 
         */
         public function wmp_settings_editimages() {
		
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
            
            if (current_user_can( 'manage_options' )){
                
                $action = null;
                
                if (!empty($_GET) && isset($_GET['type']))
                    if ($_GET['type'] == 'upload' || $_GET['type'] == 'delete')
                        $action = $_GET['type'];
                        
                // action
                error_log(date('[Y-m-d H:i e] '). "Edit images - Action: ".$action . PHP_EOL, 3, $log);
                    
                $arrResponse = array(
                    'status' => 0,
                    'messages' => array()
                );
                
                if ($action == 'upload'){
              
                    if (!empty($_FILES) && sizeof($_FILES) > 0){
                           
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        
                        if (!function_exists( 'wp_handle_upload' ) ) 
                            require_once( ABSPATH . 'wp-admin/includes/file.php' );
                        
                        // check if the upload folder is writable
            			if (!is_writable(WMP_FILES_UPLOADS_DIR)){
                            
                            $arrResponse['messages'][] = "Error uploading images, the upload folder ".WMP_FILES_UPLOADS_DIR." is not writable.";
                        
                        } else {
                            
                            $has_uploaded_files = false;
                            
                            foreach ($_FILES as $file => $info) {
                                
                                if (!empty($info['name'])){
            
                                    $has_uploaded_files = true;
                                    
                                    if ($info['error'] >= 1 || $info['size'] <= 0) {
            
                                    	$arrResponse['status'] = 0;
                                    	$arrResponse["messages"][] = "We encountered a problem processing your ".($file == "wmp_editimages_icon" ? "icon" : "logo").". Please choose another image!";
            
                                    } elseif ( $info['size'] > 1048576 ){
            
                                    	$arrResponse['status'] = 0;
                                    	$arrResponse["messages"][] = "Your ".($file == "wmp_editimages_icon" ? "icon" : "logo")." size is greater than 1Mb!";
            
                                    } else {
                                        
                                        /****************************************/
                        				/*										*/
                        				/* SET FILENAME, ALLOWED FORMATS AND SIZE */
                        				/*										*/
                        				/****************************************/
                        
                                        // make unique file name for the image
                                        $arrFilename = explode(".", $info['name']);
                                        $fileExtension = end($arrFilename);
                                        
                                        if ($file == "wmp_editimages_icon") {
                                            
                                            $arrAllowedExtensions = array('jpg', 'jpeg', 'png','gif');
                                            $arrMaximumSize = array('width' => 256, 'height' => 256);
                                             
                                        } else {
                                            
                                            $arrAllowedExtensions = array('png');
                                            $arrMaximumSize = array('width' => 120, 'height' => 120);
                                        }
                                        
                                        // check file extension
                                        if (!in_array(strtolower($fileExtension), $arrAllowedExtensions)) {
                                            
                                            $arrResponse['messages'][] = "Error saving image, please add a ".implode(' or ',$arrAllowedExtensions)." image for your ".($file == "wmp_editimages_icon" ? "icon" : "logo")."!";
                                            
                                        } else {
                                            
                                            /****************************************/
                            				/*										*/
                            				/* UPLOAD IMAGE                         */
                            				/*										*/
                            				/****************************************/
                                        
                                            $uniqueFilename = ($file == "wmp_editimages_icon" ? "icon" : "logo").'_'.time().'.'.$fileExtension;
                                            
                                            // upload to the default uploads folder
                                            $upload_overrides = array( 'test_form' => false );
                                            $movefile = wp_handle_upload( $info, $upload_overrides );
                                            
                                            if ($movefile) {
                                                
                                                /****************************************/
                                				/*										*/
                                				/* RESIZE AND COPY IMAGE                */
                                				/*										*/
                                				/****************************************/
                                            
                                                $copied_and_resized = false;
                                                
                                                $image = wp_get_image_editor( $movefile['file'] );
                                                
                                                if (!is_wp_error( $image ) ) {
                                                    
                                                    $image_size = $image->get_size();
                                                    
                                                    // if the image exceeds the size limits
                                                    if ($image_size['width'] > $arrMaximumSize['width'] || $image_size['height'] > $arrMaximumSize['height']) {
                                                        
                                                        // resize and copy to the wmp uploads folder
                                                        $image->resize( $arrMaximumSize['width'], $image_size['height'] );
                                                        $image->save( WMP_FILES_UPLOADS_DIR.$uniqueFilename );
                                                        
                                                        $copied_and_resized = true;
                                                        
                                                    } else {
                                                    
                                                        // copy file without resizing to the wmp uploads folder
                                                        $copied_and_resized = copy($movefile['file'], WMP_FILES_UPLOADS_DIR.$uniqueFilename);
                                                    }
                                                    
                                                } else {
                                                    
                                                    $arrResponse["messages"][] = "We encountered a problem resizing your ".($file == "wmp_editimages_icon" ? "icon" : "logo").". Please choose another image!";
                                                }
                                                
                                                /****************************************/
                                				/*										*/
                                				/* DELETE PREVIOUS IMAGE AND SET OPTION */
                                				/*										*/
                                				/****************************************/
                                                
                                                if ($copied_and_resized) {
                                                        
                                                    // delete previous icon / logo
                                                    $previous_file_path = WMobilePack::wmp_get_setting($file == "wmp_editimages_icon" ? "icon" : "logo");
                                                    
                                                    if ($previous_file_path != ''){
                                                        unlink(WMP_FILES_UPLOADS_DIR.$previous_file_path);
                                                    }
                                                    
                                                    // save option
                                                    WMobilePack::wmp_update_settings($file == "wmp_editimages_icon" ? "icon" : "logo", $uniqueFilename);
                                                    
                                                    // add path in the response
                                                    $arrResponse['status'] = 1;
                                                    $arrResponse['uploaded_'.($file == "wmp_editimages_icon" ? "icon" : "logo")] = WMP_FILES_UPLOADS_URL.$uniqueFilename;
                                                }
                                                
                                                // remove file from the default uploads folder
                                                unlink($movefile['file']);
                                            }   
                                        }
                                    }
                                }                      
                            }
                            
                            if ($has_uploaded_files == false){
                                $arrResponse['messages'][] = "Please upload at least one image!";
                            }
                        }
                    } 
                        
                } elseif ($action == 'delete'){
                    
                    /****************************************/
    				/*										*/
    				/* DELETE ICON / LOGO        			*/
    				/*										*/
    				/****************************************/
                            
                    // delete icon or logo, depending on the 'source' param
                    if (isset($_GET['source'])) {
                        if ($_GET['source'] == 'icon' || $_GET['source'] == 'logo'){
                            
                            $file = $_GET['source'];
                            
                            // get the previous file name from the options table
                            $previous_file_path = WMobilePack::wmp_get_setting($file);
                                            
                            // check if we have to delete the file and remove it
                            if ($previous_file_path != ''){
                                if (file_exists(WMP_FILES_UPLOADS_DIR.$previous_file_path))
                                    unlink(WMP_FILES_UPLOADS_DIR.$previous_file_path);
                            }
                            
                            // save option with an empty value
                            WMobilePack::wmp_update_settings($file, '');
                            
                            $arrResponse['status'] = 1;
                        }
                    }
                }
                
                // action
                error_log(date('[Y-m-d H:i e] '). "Edit images - Status: ".$arrResponse['status'] . PHP_EOL, 3, $log);
                
                if(is_array($arrResponse['messages']) && !empty($arrResponse['messages'])) {
                    
                    foreach($arrResponse['messages'] as $Message){
                        // check messages
                        error_log(date('[Y-m-d H:i e] '). "Edit images - Message: ".$Message . PHP_EOL, 3, $log);
                
                    }
                }
                // echo json with response
                echo json_encode($arrResponse);
            }
            
            exit();
        }
		
		
		/**
         * 
         * Method used to save the cover
         * 
         */
         public function wmp_settings_editcover() {
		
            // set log url
		    $log = WMP_PLUGIN_PATH.'wmp_log.log';
        
            if (current_user_can( 'manage_options' )){
                
                $action = null;
                
                if (!empty($_GET) && isset($_GET['type']))
                    if ($_GET['type'] == 'upload' || $_GET['type'] == 'delete')
                        $action = $_GET['type'];
                        
                
                // action
                error_log(date('[Y-m-d H:i e] '). "Edit cover - Action: ".$action . PHP_EOL, 3, $log);
                
                $arrResponse = array(
                    'status' => 0,
                    'messages' => array()
                );
                
                if ($action == 'upload'){
              
                    if (!empty($_FILES) && sizeof($_FILES) > 0){
                           
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        
                        if (!function_exists( 'wp_handle_upload' ) ) 
                            require_once( ABSPATH . 'wp-admin/includes/file.php' );
                        
                        // check if the upload folder is writable
            			if (!is_writable(WMP_FILES_UPLOADS_DIR)){
                            
                            $arrResponse['messages'][] = "Error uploading images, the upload folder ".WMP_FILES_UPLOADS_DIR." is not writable.";
                        
                        } else {
                            
                            $has_uploaded_files = false;
                            
                            foreach ($_FILES as $file => $info) {
                                
                                if (!empty($info['name'])){
            
                                    $has_uploaded_files = true;
                                    
                                    if ($info['error'] >= 1 || $info['size'] <= 0) {
            
                                    	$arrResponse['status'] = 0;
                                    	$arrResponse["messages"][] = "We encountered a problem processing your cover. Please choose another image!";
            
                                    } elseif ( $info['size'] > 1048576 ){
            
                                    	$arrResponse['status'] = 0;
                                    	$arrResponse["messages"][] = "Your cover size is greater than 1Mb!";
            
                                    } else {
                                        
                                        /****************************************/
                        				/*										*/
                        				/* SET FILENAME, ALLOWED FORMATS AND SIZE */
                        				/*										*/
                        				/****************************************/
                        
                                        // make unique file name for the image
                                        $arrFilename = explode(".", $info['name']);
                                        $fileExtension = end($arrFilename);
                                        
                                        if ($file == "wmp_editcover_cover") {
                                            
                                            $arrAllowedExtensions = array('jpg', 'jpeg', 'png','gif');
                                            $arrMaximumSize = array('width' => 1000, 'height' => 1000);
                                             
                                        } 
                                           
                                        
                                        // check file extension
                                        if (!in_array(strtolower($fileExtension), $arrAllowedExtensions)) {
                                            
                                            $arrResponse['messages'][] = "Error saving image, please add a ".implode(' or ',$arrAllowedExtensions)." image for your cover!";
                                            
                                        } else {
                                            
                                            /****************************************/
                            				/*										*/
                            				/* UPLOAD IMAGE                         */
                            				/*										*/
                            				/****************************************/
                                        
                                            $uniqueFilename = 'cover_'.time().'.'.$fileExtension;
                                            
                                            // upload to the default uploads folder
                                            $upload_overrides = array( 'test_form' => false );
                                            $movefile = wp_handle_upload( $info, $upload_overrides );
                                            
                                            if ($movefile) {
                                                
                                                /****************************************/
                                				/*										*/
                                				/* RESIZE AND COPY IMAGE                */
                                				/*										*/
                                				/****************************************/
                                            
                                                $copied_and_resized = false;
                                                
                                                $image = wp_get_image_editor( $movefile['file'] );
                                                
                                                if (!is_wp_error( $image ) ) {
                                                    
                                                    $image_size = $image->get_size();
                                                    
                                                    // if the image exceeds the size limits
                                                    if ($image_size['width'] > $arrMaximumSize['width'] || $image_size['height'] > $arrMaximumSize['height']) {
                                                        
                                                        // resize and copy to the wmp uploads folder
                                                        $image->resize( $arrMaximumSize['width'], $image_size['height'] );
                                                        $image->save( WMP_FILES_UPLOADS_DIR.$uniqueFilename );
                                                        
                                                        $copied_and_resized = true;
                                                        
                                                    } else {
                                                    
                                                        // copy file without resizing to the wmp uploads folder
                                                        $copied_and_resized = copy($movefile['file'], WMP_FILES_UPLOADS_DIR.$uniqueFilename);
                                                    }
                                                    
                                                } else {
                                                    
                                                    $arrResponse["messages"][] = "We encountered a problem resizing your cover. Please choose another image!";
                                                }
                                                
                                                /****************************************/
                                				/*										*/
                                				/* DELETE PREVIOUS IMAGE AND SET OPTION */
                                				/*										*/
                                				/****************************************/
                                                
                                                if ($copied_and_resized) {
                                                        
                                                    // delete previous cover
                                                    $previous_file_path = WMobilePack::wmp_get_setting("cover");
                                                    
                                                    if ($previous_file_path != ''){
                                                        unlink(WMP_FILES_UPLOADS_DIR.$previous_file_path);
                                                    }
                                                    
                                                    // save option
                                                    WMobilePack::wmp_update_settings("cover", $uniqueFilename);
                                                    
                                                    // add path in the response
                                                    $arrResponse['status'] = 1;
                                                    $arrResponse['uploaded_cover'] = WMP_FILES_UPLOADS_URL.$uniqueFilename;
                                                }
                                                
                                                // remove file from the default uploads folder
                                                unlink($movefile['file']);
                                            }   
                                        }
                                    }
                                }                      
                            }
                            
                            if ($has_uploaded_files == false){
                                $arrResponse['messages'][] = "Please upload a image!";
                            }
                        }
                    } 
                        
                } elseif ($action == 'delete'){
                    
                    /****************************************/
    				/*										*/
    				/* DELETE ICON / LOGO        			*/
    				/*										*/
    				/****************************************/
                            
                    // delete cover, depending on the 'source' param
                    if (isset($_GET['source'])) {
                        if ($_GET['source'] == 'cover'){
                            
                            $file = $_GET['source'];
                            
                            // get the previous file name from the options table
                            $previous_file_path = WMobilePack::wmp_get_setting($file);
                                            
                            // check if we have to delete the file and remove it
                            if ($previous_file_path != ''){
                                if (file_exists(WMP_FILES_UPLOADS_DIR.$previous_file_path))
                                    unlink(WMP_FILES_UPLOADS_DIR.$previous_file_path);
                            }
                            
                            // save option with an empty value
                            WMobilePack::wmp_update_settings($file, '');
                            
                            $arrResponse['status'] = 1;
                        }
                    }
                }
                
                // action
                error_log(date('[Y-m-d H:i e] '). "Edit cover - Status: ".$arrResponse['status'] . PHP_EOL, 3, $log);
                
                if(is_array($arrResponse['messages']) && !empty($arrResponse['messages'])) {
                    
                    foreach($arrResponse['messages'] as $Message){
                        // check messages
                        error_log(date('[Y-m-d H:i e] '). "Edit cover - Message: ".$Message . PHP_EOL, 3, $log);
                
                    }
                }
                
                echo json_encode($arrResponse);
            }
            
            exit();
        }
		
		
        
		/**
		 * Method used to render the upgrade page from the admin area
		 */
		public function wmp_upgrade_options() {
			
			global $wmobile_pack;
			
			// load view
			include(WMP_PLUGIN_PATH.'admin/wmp-admin-upgrade.php'); 
		}
        
        /**
		 * Static method used to request the content for the More page.
		 * The method returns an array containing the latest content or an empty array by default.
		 *
		 */
		public static function wmp_more_updates() {
			
			$json_data = get_transient("wmp_more_updates");
            
			// the transient is not set or expired
			if (!$json_data) {
			
                // check if we have a https connection
                $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
                
    			// JSON URL that should be requested
    			$json_url = ($is_secure ? WMP_MORE_UPDATES_HTTPS : WMP_MORE_UPDATES);
                
				// get response
				$json_response = self::wmp_read_data($json_url);
				
				if($json_response !== false && $json_response != '') {
					
					// Store this data in a transient
					set_transient( 'wmp_more_updates', $json_response, 3600*24*2 );
					
					// get response
					$response = json_decode($json_response, true);
			
					if (isset($response["content"]) && is_array($response["content"]) && !empty($response["content"])){
					   
						// return response
						return $response["content"];
                    }
					
				} elseif($json_response == false) {
					
					// Store this data in a transient
					set_transient('wmp_more_updates', 'warning', 3600*24*2 );
                    
					// return message
					return 'warning';	
				}
				
			} else {
			     
                if ($json_data == 'warning')
                    return $json_data;
                    
				// get response
				$response = json_decode($json_data, true);
			
				if (isset($response["content"]) && is_array($response["content"]) && !empty($response["content"]))
					return $response["content"];
			}
            
			// by default return empty array
			return array();
		}
	
	
	
		/**
		 * Static method used to request the content of different pages using curl or fopen
		 * This method returns false if both curl and fopen are dissabled and an empty string ig the json could not be read
		 *
		 */
		public static function wmp_read_data($json_url) {

			// check if curl is enabled
			if (extension_loaded('curl')) {
				
				$send_curl = curl_init($json_url);
			
				// set curl options
				curl_setopt($send_curl, CURLOPT_URL, $json_url);
				curl_setopt($send_curl, CURLOPT_HEADER, false);
				curl_setopt($send_curl, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($send_curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($send_curl, CURLOPT_HTTPHEADER,array('Accept: application/json', "Content-type: application/json"));
				curl_setopt($send_curl, CURLOPT_FAILONERROR, FALSE);
				curl_setopt($send_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($send_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
				$json_response = curl_exec($send_curl);
				
				// get request status
				$status = curl_getinfo($send_curl, CURLINFO_HTTP_CODE);
				curl_close($send_curl);
				
				// return json if success
				if ($status == 200)
					return $json_response;
				
			} elseif (ini_get( 'allow_url_fopen' )) { // check if allow_url_fopen is enabled
				
				// open file
				$json_file = fopen( $json_url, 'rb' );
				
				if($json_file) {
					
					$json_response = '';
                    
					// read contents of file
					while (!feof($json_file)) {	
						$json_response .= fgets($json_file);
					}
				}
				
				// return json response
				if($json_response)
					return $json_response;
					
			} else 
				// both curl and fopen are disabled
				return false;
			
			// by default return an empty string
    		return '';	
    		
		}
	}
	

}