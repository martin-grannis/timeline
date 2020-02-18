<?php

	// ====================================================================================================================== //
	// ================================================    ProtectPages.com     ============================================= //
	// ====================================================================================================================== //
	// ====================================     Default library for all of our plugins    =================================== //
	// ========================   ( You see this file, because you are using one of our plugins )   ========================= //
	// ====================================================================================================================== //


/**
 * Base Library Class for all our plugins
 * 
 * @package   ProtectPages.com plugin
 * @author    T.Todua <contact@ProtectPages.com>
 * @license   GPL-2.0+
 * @link      https://www.protectpages.com
 * @copyright 2018-2019 ProtectPages.com
 *
*/

if(trait_exists('default_methods__ProtectPages_com')) return;

trait default_methods__ProtectPages_com{

	public function __construct($arg1=false){
		// #### dont use __FILE__ & __DIR__ here, because trait is being included in other classes. Better is reflection ####
		$reflection = (new \ReflectionClass(__CLASS__));				// set plugin's main file path
		$this->plugin_FILE		= $reflection->getFileName(); 			// set plugin's dir path
		$this->plugin_DIR		= dirname($this->plugin_FILE);			// set plugin's dir URL
		$this->plugin_DIR_URL	= plugin_dir_url($this->plugin_FILE);
		$this->plugin_NAMESPACE	= $reflection->getNamespaceName(); 		//
		$this->homeUrl			= home_url('/');
		$this->is_https			= ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || $_SERVER['SERVER_PORT']==443);
		$this->httpsCurrent		= $this->is_https ? 'https://' : 'http://';
		$this->httpsReal		= preg_replace('/(http(s|):\/\/)(.*)/i', '$1', $this->homeUrl);
		$this->domainCurrent	= $_SERVER['HTTP_HOST'];
		$this->domainReal		= $this->getDomain($this->homeUrl);
		$this->domain			= $this->httpsReal.$this->domainReal;

		$this->homeUrlStripped	= $this->stripUrlPrefixes( $this->homeUrl);
		$this->homePath			= home_url('/', 'relative');
		$this->urlAfterHome		= substr($_SERVER["REQUEST_URI"], strlen($this->homePath) );
		$this->pathAfterHome	= parse_url($this->urlAfterHome, PHP_URL_PATH);
		$this->is_localhost 	= (stripos($this->homeUrl,'://127.0.0.1')!==false || stripos($this->homeUrl,'://localhost')!==false || stripos($this->homeUrl,'://l/')!==false );
		$this->is_development 	= defined("protectpages_development_machine") || defined("itask_development_machine") ;		//set in my my_superglobal_functions.php
		$this->is_settings_page = false;

		// initial variables
		$this->my_plugin_vars(1);
		$this->initial_static_settings = array('show_opts'=>false, 'required_role'=>'manage_options', 'managed_from'=>'multisite', 'allowed_on'=>'both');
		$this->initial_user_options= array();
		if(method_exists($this, 'declare_static_settings')) {  $this->declare_static_settings();  }
		$this->static_settings = $this->initial_static_settings + $this->static_settings;
		$this->my_plugin_vars(2);																		//setup 2nd initial variables
		$this->opts= $this->refresh_options();															//setup final variables
		$this->refresh_options_TimeGone();
		$this->check_if_pro_plugin();
		$this->__construct_my();																		//all other custom construction hooks
		$this->define_option_tabs();
		$this->plugin_page_url= ( $this->opts['managed_from_primary_site'] ? network_admin_url('settings.php')	: admin_url( 'admin.php') ). '?page='.$this->plugin_slug; 	//options-general.php
		$this->plugin_page_url1=( $this->opts['managed_from_primary_site'] ? network_admin_url('admin.php')		: admin_url( 'admin.php') ). '?page='.$this->plugin_slug;
		$this->plugin_files =  array_merge(  (property_exists($this, 'plugin_files') ? $this->plugin_files : array()),   array('index.php') );
		$this->translated_phrases = $this->get_option_CHOSEN('`translated_phrases');
		$this->ip			= $_SERVER['REMOTE_ADDR'];

		//==== my other default hooks ===//
		// If plugin has options
		if($this->opts['show_opts'])
		{
			//add admin menu
			add_action( ( $this->opts['managed_from_primary_site'] ? 'network_' : ''). 'admin_menu', function(){
				$menu_button_name = (array_key_exists('menu_button_name', $this->opts) ? $this->opts['menu_button_name'] : $this->opts['Name'] );
				if(array_key_exists('menu_button_level', $this->opts)){
					$menu_level=$this->opts['menu_button_level'];
				}
				if(!isset($menu_level) || $menu_level=="submenu")
					add_submenu_page($this->opts['menu_pagePHP'], $menu_button_name, $menu_button_name, $this->opts['required_role'] , $this->plugin_slug,  array($this, 'opts_page_output') );
				else 																																			// icons: https://goo.gl/WXAYCi
					add_menu_page($menu_button_name, $menu_button_name, $this->opts['required_role'] , $this->plugin_slug,  array($this, 'opts_page_output'), $this->opts['menu_icon'] );
				// if target is custom link (not options page)
				if(array_key_exists('menu_button_link', $this->opts)){
					add_action( 'admin_footer', function (){
							?>
							<script type="text/javascript">
								jQuery('a.toplevel_page_<?php echo $this->plugin_slug;?>').attr('href','<?php echo $this->opts['menu_button_link'];?>').attr('target','_blank');
							</script>
							<?php
						}
					);
				}
			} );
			//redirect to settings page after activation (if not bulk activation)
			add_action('activated_plugin', function($plugin) { if ( $plugin == plugin_basename( $this->plugin_FILE ) && !((new WP_Plugins_List_Table())->current_action()=='activate-selected')) { exit( wp_redirect($this->plugin_page_url.'&isactivation') ); } } );
		}
		// add Settings & Donate buttons in plugins list
		add_filter( (is_network_admin() ? 'network_admin_' : ''). 'plugin_action_links_'.plugin_basename($this->plugin_FILE),  function($links){
			if(!$this->has_pro_version)	{ $links[] = '<a href="'.$this->opts['donate_url'].'">'.$this->opts['menu_text']['donate'].'</a>'; }
			if($this->opts['show_opts']){ $links[] = '<a href="'.$this->plugin_page_url.'">'.$this->opts['menu_text']['settings'].'</a>';  }
			return $links;
		});

		//translation hook
		add_action('plugins_loaded', array($this, 'load_textdomain') );

		//activation & deactivation (empty hooks by default. all important things migrated into `refresh_options`)
		register_activation_hook( $this->plugin_FILE, array($this, 'activate')   );
		register_deactivation_hook( $this->plugin_FILE, array($this, 'deactivate'));

		if(is_admin()) $this->add_default_uninstall();	//add_action( 'shutdown', array($this, 'my_shutdown_for_versioning'));

		add_action('admin_head', [$this,'admin_head_func']);
		// for backend ajax
		add_action( 'admin_enqueue_scripts',		array( $this, 'admin_scripts')	);
		add_action( 'wp_ajax_'.$this->plugin_slug_u.'_all',	array( $this, 'ajax_backend_call' ));

		if($this->has_pro_version){
			add_action( 'wp_ajax_'.$this->plugin_slug_u,		array( $this, 'backend_ajax_check_pro' ));
		}

		// if buttons needed
		$this->tinymce_funcs();


	}

	// ================  dont use activation/deactivation hooks =====================//
	public function activate($network_wide){
		//if activation allowed from only on multisite or singlesite or Both?
		$die= $this->opts['allowed_on'] == 'both' ?  false :  (   ($this->opts['allowed_on'] =='multisite' && !$network_wide && is_multisite()) || ( $this->opts['allowed_on'] =='singlesite' && ($network_wide || is_network_admin()) )  ) ;
		if($die) {
			$text= '<h2>('.$this->opts['Name'].') '. $this->opts['menu_text']['activated_only_from']. ' <b style="color:red;">'.strtoupper($this->opts['allowed_on']).'</b> WordPress </h2>';
			die('<script>alert("'.strip_tags($text).'");</script>'.$text);
			return false;
		}
		//$this->plugin_updated_hook();
		if(method_exists($this, 'activation_funcs') ) {   $this->activation_funcs();  }
	}
	public function deactivate($network_wide){
		if(method_exists($this, 'deactivation_funcs') ) {   $this->deactivation_funcs($network_wide);  }
	}


	public function my_plugin_vars($step){
		//add my default values
		if($step==1){ 
			//get default plugin data: https://goo.gl/Z3z8FW
			include_once(ABSPATH . "wp-admin/includes/plugin.php");
			$this->plugin_variables = get_plugin_data( $this->plugin_FILE, $markup = true, $translate = false);    //dont $translate, otherwise you will get error of: https://core.trac.wordpress.org/ticket/43869
			$this->plugin_slug	=  sanitize_key($this->plugin_variables['TextDomain']);
			$this->plugin_slug_u=  str_replace('-','_', $this->plugin_slug);
			$this->folder_slug	=  basename($this->plugin_DIR);

			$this->static_settings = $this->plugin_variables   +   array(
					'menu_text'			=> array(
						'donate'				=>__('Donate', 'default_methods__ProtectPages_com'),
						'settings'				=>__('Settings', 'default_methods__ProtectPages_com'),
						'open_settings'			=>__('You can access settings from dashboard of:', 'default_methods__ProtectPages_com'),
						'activated_only_from'	=>__('Plugin activated only from', 'default_methods__ProtectPages_com')
					),
					'lang'				=> $this->get_locale__SANITIZED(),
					'wp_rate_url'		=> 'https://wordpress.org/support/plugin/'.$this->folder_slug.'/reviews/#new-post',
					'public_assets_url'	=> 'https://ps.w.org/internal-functions-for-protectpages-com-users/trunk/',
					'donate_url'		=> 'http://paypal.me/ProtectPagesCom', // business: http://paypal.me/ProtectPagesCom   ||  personal : http://paypal.me/tazotodua
					'mail_errors'		=> 'wp_plugin_errors@protectpages.com',
					'licenser_domain'	=> 'https://www.protectpages.com/',
					'musthave_plugins'	=> 'https://www.protectpages.com/blog/must-have-wordpress-plugins/',
					'wp_tt_freelancers'	=> 'https://goo.gl/tyHZ2p',
					'wp_fl_freelancers'	=> 'https://goo.gl/oTMJ5i',
					'wp_pph_freelancers'=> 'https://goo.gl/qbpsWn',
					
			);
			$this->static_settings = $this->static_settings   +		array(
				'purchase_url'			=> $this->static_settings['licenser_domain'].'?purchase_wp_plugin='.$this->plugin_slug,
				'purchase_check'		=> $this->static_settings['licenser_domain'].'?purchase_wp_check'
			);
		}
		elseif($step==2){
			$this->static_settings = $this->static_settings    +	array(
				'managed_from_primary_site'	=> $this->static_settings['managed_from']=='multisite' && is_multisite(),
				'menu_pagePHP'				=> $this->static_settings['managed_from']=='multisite' && is_multisite() ? 'settings.php' : 'options-general.php'
			);
		}

	}


	//load translation
	public function load_textdomain(){
		load_plugin_textdomain( $this->plugin_slug, false, basename($this->plugin_DIR). '/lang/' );
	}


	//get latest options (in case there were updated,refresh them)
	public function refresh_options(){
		$this->opts	= $this->get_option_CHOSEN($this->plugin_slug, array());
		foreach($this->initial_user_options as $name=>$value){ if (!array_key_exists($name, $this->opts)) { $this->opts[$name]=$value;  $should_update=true; }  }
		$this->opts = array_merge($this->opts, $this->static_settings);
		if(isset($should_update)) {	$this->update_opts(); }
		return $this->opts;
	}

	public function refresh_options_TimeGone(){
		//if never updated
		if(empty($this->opts['last_update_time'])) {
			$this->opts['last_update_time'] = time();   $should_update=true;
		}
		if(empty($this->opts['last_updates'])) {
			$this->opts['last_updates'] = array();   $should_update=true;
		}
		//if plugin updated through hook or manually... to avoid complete break..
		if( empty($this->opts['last_version']) || $this->opts['last_version'] != $this->plugin_variables['Version'] ){
			$this->opts['last_version'] = $this->plugin_variables['Version'];
			$should_update=true;
			$reload_needed=true;
		}
		if(isset($should_update)) {	$this->update_opts(); }
		if(isset($reload_needed)) { $this->plugin_updated_hook(true); }
	}

	//update library file on activation/update
	public function plugin_updated_hook($redirect=false)
	{
		return;	//temporarily disabled, because i include file manually
		/*

define(__NAMESPACE__ .'\lib_path',	$lib = dirname(__DIR__).'/pp_default_lib.php' );
define(__NAMESPACE__ .'\lib_url',	$url = 'https://plugins.trac.wordpress.org/browser/internal-functions-for-protectpages-com-users/trunk/____defaults.php?format=txt&'.time() );
if( !file_exists($lib) ) { wp_remote_get ( $url, array( 'stream'=> true, 'filename' => $lib ) ); }
require_once($lib);

		*/

		register_shutdown_function( function(){
			if (! $this->is_development ) {
				wp_remote_get
				(
					constant($this->plugin_NAMESPACE.'\lib_url'),
					array(
						'timeout'  => 300,
						'stream'   => true,
						'filename' => constant($this->plugin_NAMESPACE.'\lib_path')
					)
				);
			}
			if($redirect) $this->js_redirect();   //TODO: causes activation error during activation validator hook
		});
	}

	// quick method to update this plugin's opts
	public function optName($optname, $prefix){
		if( substr($optname,  0, 1) == '`'  ) {  $prefix=true;  $optname= substr($optname,1); }
		return ( !$prefix || stripos($optname, $this->plugin_slug) !== false )  ? $optname :  $this->plugin_slug . '_' . $optname;
	}

	public function update_opts($opts=false){
		return $this->update_option_CHOSEN($this->plugin_slug, ( $opts ? $opts : $this->opts) );
	}

	public function get_option_CHOSEN($optname, $default=false        				, $prefix=false){
		return call_user_func("get_". ( $this->static_settings['managed_from_primary_site'] ? "site_" : "" ). "option",  $this->optName($optname, $prefix), $default );
	}
	public function update_option_CHOSEN($optname, $optvalue, $autoload=null		, $prefix=false){
		return call_user_func("update_". ( $this->static_settings['managed_from_primary_site'] ? "site_" : "" ). "option",  $this->optName($optname, $prefix), $optvalue, $autoload );
	}
	public function delete_option_CHOSEN($optname									, $prefix=false){
		return call_user_func("delete_". ( $this->static_settings['managed_from_primary_site'] ? "site_" : "" ). "option",  $this->optName($optname, $prefix) );
	}


	public function phrase($key, $removed=false) {  return ( isset($this->translated_phrases[$key])  ? $this->translated_phrases[$key] : $key) ; }

	public function add_prefix_to_array_keys($array, $prefix){
		$new_array =[];
		foreach ($array as $k => $v) {
			 $new_array[$prefix.$k] = $v;
		}
		return $new_array;
	}

	public function mail_scrambler($email) {  return str_replace('@', '&#64;', $email);}

	public function add_prefix_to_object_keys($object, $prefix){
		$new_object = new stdClass();
		foreach ($object as $k => $v) {
			$new_object->{$prefix . $k} = $v;
		}
		return $new_object;
	}

	public function dieMessage($txt){
		echo
		'<div style="padding: 50px; margin:100px auto; width:50%; text-align:center; line-height: 1.4; display:flex; justify-content:center; flex-direction:column; font-family: cursive; font-size: 1.7em; box-shadow:0px 0px 10px gray; border-radius: 10px;">'.
			 '<div><h3>'.$txt.'</h3></div>'.
		'</div>';
		exit;
	}
	public function get_errorslog(){
		$get_last_logs = 100;
		$all_data= $this->get_option_CHOSEN("`errorslogs", array());
		return array_slice($all_data, - $get_last_logs, $get_last_logs, true);
	}

	//i.e. $this->update_errorslog("couldnt get results", '<code>'.print_r($response, true).'</code>' );
	public function log()
	{
		return $this->update_errorslog(func_get_args());
	}
	public function update_errorslog()
	{
		$ar_data	= $this->get_errorslog();
		$current_date	= time();
		$ar_data[]		= array_merge( array($current_date), func_get_args() );
		$this->update_option_CHOSEN("`errorslogs", $ar_data );
	}

	public function send_error_mail($error){
		return wp_mail($this->opts['mail_errors'], 'wp plugin error at '. home_url(),  (is_array($error) ? print_r($error, true) : $error)  );
	}








	// ====================== tinymce buttons ==================== //
	public function tinymce_funcs()
	{
		// Add button in TinyMCE
		if( !empty($this->tinymce_buttons) ) {
			add_action( 'admin_init', 			function(){
					add_filter( 'mce_external_plugins',	array( $this, 'tinymce_js' ) );
					add_filter( 'mce_buttons_2',		array( $this, 'register_buttons' ) );
					//add_filter( 'tiny_mce_version',  function ( $ver ) { return $ver + 3;}  );
			} );
			//tinymce buttons if needed
			$this->tinymce_buttons_body();
			foreach($this->tinymce_buttons as $each_button){
				if( !empty($each_button["shortcode"]) ){
					add_shortcode($each_button["shortcode"],  array($this, $each_button["shortcode"]) );
				}
			}
		}
	}

	public function shortcode_example($shortcode, $array){
		$out="[$shortcode ";   foreach($array as $key=>$value){   $out .= $key.'="'.$this->valueToString($value) .'" ';  }   $out = trim($out). "]";
		return $out;
	}

	public function valueToString( $value ){
		return ( !is_bool( $value ) ?  $value : ($value ? 'true' : 'false' )  );
	}

	public function tinymce_js( $plugin_array )	 {
		$plugin_array[ "button_handle_" . $this->plugin_slug ] = $this->homeUrl  . '?tinymce_buttons_'.$this->plugin_slug;
		return $plugin_array;
	}

	public function register_buttons( $buttons ) {
		$button_names = array_map(  function($ar){ return $ar['button_name']; }, $this->tinymce_buttons );
		return array_merge( $buttons,   $button_names );
	}

	public function tinymce_buttons_body( )
	{
		if( ! isset($_GET['tinymce_buttons_'. $this->plugin_slug] ) ) return;

			session_cache_limiter('none');
		// http://stackoverflow.com/a/1385982/2377343
		//Caching with "CACHE CONTROL"
			header('Cache-control: max-age='. ($year=60*60*24*365) .', public');
		//Caching with "EXPIRES"  (no need of EXPIRES when CACHE-CONTROL enabled)
			//header('Expires: '.gmdate(DATE_RFC1123,time()+$year));
		//To get best cacheability, send Last-Modified header and ...
			header('Last-Modified: '.gmdate(DATE_RFC1123,filemtime(__file__)));  //i.e.  1467220550 [it's 30 june,2016]
		//reply using: status 304 (with empty body) if browser sends If-Modified-Since header.... This is cheating a bit (doesn't verify the date), but remove if you dont want to be cached forever:
			// if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {  header('HTTP/1.1 304 Not Modified');   die();	}
			header("Content-type: application/javascript;  charset=utf-8");
		?>
		// ************ these useful scripts got from: https://github.com/tazotodua/useful-javascript/   **********
		// "<script>"  dont remove this line,,, because, now JAVSCRIPT highlighting started in text-editor

		<?php
		$random_name = "button_".rand(1,999999999).rand(1,999999999);
		?>
		"use strict";

		(function ()
		{
		  // Name the plugin anything we want
			tinymce.create( 'tinymce.plugins.<?php echo $random_name;?>',
			{
				init: function (ed, url)
				{

				<?php foreach ($this->tinymce_buttons as $each_button ) { ?>
				  // The button name should be the same as used in PHP function of WP
					ed.addButton( '<?php echo $each_button["button_name"];?>',
					{
					  // Title of button
						title: '<?php echo $each_button["shortcode"];?>',
					  // icon url of button
						image: '<?php echo $each_button["icon"];?>', //url +
					  // Onclick action onto button
						onclick: function ()
						{
						  // Create shortcode string, with default values
							var val = '<?php echo $this->shortcode_example($each_button["shortcode"], $each_button["default_atts"]);?>';
						  // Insert shortcode in text-editor
							ed.execCommand( 'mceInsertContent', false, val );
						}
					});
				<?php } ?>


				},
				createControl: function (n, cm) {
					return null;
				}

			});

			// first parameter	- the same name as defined in PHP function of WP
			// second parameter	- the module name (as defined a bit above)
			tinymce.PluginManager.add( '<?php echo "button_handle_" . $this->plugin_slug;?>', tinymce.plugins.<?php echo $random_name;?> );

		})();
		//</script>
		<?php
		exit;
	}

	// ========================================== //


	public function get_fb_name_regex($fb_url){
		preg_match('/'.preg_quote('^(?:https?://)?(?:www.|m.|touch.)?(?:facebook.com|fb(?:.me|.com))/(?!$)(?:(?:\w)#!/)?(?:pages/)?(?:[\w-]/)?(?:/)?(?:profile.php?id=)?([^/?\s])(?:/|&|?)?.*$/'), $fb_url, $n);
		return $n[1];
	}

	// settings page
	public function define_option_tabs(){
		$this->show_tabs	= property_exists($this, 'display_tabs') && $this->display_tabs;
		$this->options_tabs	=
			array_merge (
				array("Options", "Translations"),
				array_merge (
					( property_exists($this, 'options_tabs') ? $this->options_tabs : array() ),
					( ! property_exists($this, 'errors_tab') || $this->errors_tab ? array('Errors log') : array()  )
				)
			);
		$this->show_rating_message	= property_exists($this, 'show_rating_message') && $this->show_rating_message;
		$this->show_author_block 	= property_exists($this, 'show_author_block') && $this->show_author_block;
	}

	public function options_tab($tabs_array =false){
		if(!$tabs_array) 	 $tabs_array = $this->options_tabs;
		$this->active_tab		= sanitize_text_field( isset($_GET['tab']) ? $_GET['tab'] : $this->options_tabs[0]);
		echo '<h2 class="nav-tab-wrapper '. (!$this->show_tabs ? "displaynone" : "") .'">';
		foreach($tabs_array as $each_tab){
			echo '<a  href="'.add_query_arg('tab', $each_tab).'" class="nav-tab '.sanitize_key($each_tab).' '. ( $this->active_tab == $each_tab ? 'nav-tab-active  whiteback' : ''). '">'. __($each_tab,'default_methods__ProtectPages_com').'</a>';
		}
		echo '</h2>';
	}

	public function settings_page_part($type){
		$this->is_settings_page = true;

		if(!empty($_POST[$this->plugin_slug])) {
			$this->opts['last_update_time'] = time();
			$this->update_opts();
		}
		if(isset( $_POST["_wpnonce"] ) && check_admin_referer( "nonce_" . $this->plugin_slug) ) {

			if(isset( $_POST[$this->plugin_slug]['clear_error_logs'] ) ){
				$this->update_option_CHOSEN("`errorslogs", array() );
			}


			if(isset( $_POST[$this->plugin_slug]['update_transl_phrases'] ) ){
				$this->translated_phrases =  array_filter($_POST[$this->plugin_slug]['phrases'], 'sanitize_text_field') ;
				$this->update_option_CHOSEN('`translated_phrases',  $this->translated_phrases  ) ;
			}


		}


		if($type=="start"){  ?>
			<div class="clear"></div>
			<div class="myplugin postbox version_<?php echo (!$this->has_pro_version  ? "free" : ($this->is_pro ? "pro" : "not_pro") );?>">
				<?php $this->options_tab(); ?>
				<!-- <h2 class="settingsTitle"><?php _e('Plugin Settings Page!', 'default_methods__ProtectPages_com');?></h2> -->




				<?php
				if ($this->active_tab == "Errors log")
				{ ?>
					<div class="errors_page">
						<table class="errors_log">
							<tbody>
								<?php
								$errors = $this->get_errorslog();
								rsort($errors);  //reverse order, last added to top

								if(!empty($errors)){

									$column_count =  count(array_keys( $errors[ count($errors) -1] ));
									foreach ($errors as $each_err){

										echo '<tr>';
										for($i=0; $i<$column_count; $i++){
											$out='';
											if (!empty($each_err[$i]) ){
												$out = $each_err[$i];
												// if time, convert to datetime
												if( is_numeric($out) && $i==0 &&  strlen($out)==10 ) {
													$out = get_date_from_gmt( gmdate("Y-m-d H:i:s", $out ) ) ;
												}
											}

											echo '<td>'. $out .'</td>';
										}
										echo '</tr>';
									}

								}
								?>
							</tbody>
						</table>
						<form method="post" action="">
							<input type="hidden" name="<?php echo $this->plugin_slug;?>[clear_error_logs]" value="ok" />
							<?php
							wp_nonce_field( "nonce_".$this->plugin_slug);
							submit_button(  __( 'Clear Errors Log', 'default_methods__ProtectPages_com' ), 'button-secondary red-button', '', true  );
							?>
						</form>
					</div>
				<?php
				}

				if ($this->active_tab == "Translations")
				{ ?>
					<div class="translations_page">
						<form method="post" action="">
							<?php _e("Here will show up all phrases that are outputed on your site fron-end by this plugin, so you can translate/customize them.", 'social-feed-grabber'); ?>
							<table class="translations_table">
								<tbody>
									<?php
									//get all phrases


									$cont='';
									foreach( $this->plugin_files as $each)
									{
										$cont .= file_get_contents(__DIR__.'/'.$this->folder_slug.'/'.$each);
									}
									preg_match_all( '/\$this\-\>phrase\(\W(.*?)\W\)/si', $cont, $phrases_array ); $phrases_array= array_flip($phrases_array[1]);

									$phrases = property_exists($this,"translated_phrases") ? $this->translated_phrases : array();

									if(!empty($phrases_array)){
										foreach ($phrases_array as $key=>$value){
											$value = array_key_exists($key, $phrases) ? $phrases[$key] : $key;
											echo '<tr>';
											echo '<td>'. $key.'</td><td><input type="text" name="'.$this->plugin_slug.'[phrases]['.$key.']" value="'. $value .'" /></td>';
											echo '</tr>';
										}
									}
									?>
								</tbody>
							</table>
							<input type="hidden" name="<?php echo $this->plugin_slug;?>[update_transl_phrases]" value="ok" />
							<?php
							wp_nonce_field( "nonce_".$this->plugin_slug);
							submit_button(  __( 'Save', 'default_methods__ProtectPages_com' ), 'button-secondary', '', true  );
							?>
						</form>
					</div>
				<?php
				}
				?>


			<?php
		}
		elseif($type=="end")
		{ ?>
				<div class="newBlock additionals">
					<div class="in_additional">
						<h4></h4>
						<h3><?php _e('More Actions', 'default_methods__ProtectPages_com');?></h3>
						<ul class="donations_block">
							<li class="donation_li">
								<?php printf(__('If you found this plugin useful, any <a href="%s" class="button" target="_blank">donation</a> is appreciated (it keeps us going on)',  'default_methods__ProtectPages_com'), $this->opts['donate_url']);?>.
							</li>
						</ul>
						<ul>
							<li>
								<?php //printf(__('You can check other useful plugins at: <a href="%s">Must have free plugins for everyone</a>', 'default_methods__ProtectPages_com'),  $this->opts['musthave_plugins'] ).'.';	?>
							</li>
						</ul>
						<ul class="freelancers">
							<li>
								<?php printf(__('To hire a qualified WordPress specialist, you can use:<br/><a target="_blank" href="%s">TopTal.com</a>, <a target="_blank" href="%s">FreeLancer.com</a> or <a target="_blank" href="%s">PeoplePerHour.com</a> ', 'default_methods__ProtectPages_com'),  $this->opts['wp_tt_freelancers'],  $this->opts['wp_fl_freelancers'], $this->opts['wp_pph_freelancers']  );  ?>.
							</li>
						</ul>
					<div>
				</div>
				<style>
				.myplugin { margin: 20px 20px 0 0; line-height:1.2em;}
				.myplugin * { position:relative;}
				.myplugin code {font-weight:bold; padding: 3px 5px;}
				.myplugin { max-width:100%; display:flex; flex-wrap:rap; justify-content:center; flex-direction:column; padding: 20px; }
				.myplugin >h2 {text-align:center;}
				.myplugin h3 {text-align:center; margin: 0.5em 1em 1em;}
				.myplugin table tr { border-bottom: 1px solid #cacaca; }
				.myplugin table td {min-width:50px;}
				.myplugin .form-table td { padding: 15px 5px; }
				.myplugin p.submit {text-align: center;}
				.myplugin .nav-tab-wrapper {margin: 0 0 30px 0;}
				   zz.myplugin input[type="text"]{width:100%;}
				.myplugin .additionals{ font-family: initial; font-size: 1.5em;   text-align:center; margin: 25px 5px 5px; padding: 5px; background: #efeab7;  padding: 5px 0 0 20px;  border-radius: 0% 20px 90% 140px;}
				z.myplugin .additionals:before { content: ""; position: absolute; top: 5%; left: 5%; height: 90%; width: 90%; background: #a222ff61; border-radius: 60% 60% 770% 110px;opacity: 0.6; transform: rotatez(-2deg); }
				.myplugin .additionals:after { content: ""; position: absolute; top: 5%; left: 5%; width: 90%; background: #6bd5ff45; border-radius: 10% 40% 20% 110px; opacity: 0.6; transform: rotatez(3deg); z-index: 0; height: 100px; }
				.myplugin .additionals a{font-weight:bold;font-size:1.1em; color:blue;}
				.myplugin .in_additional { z-index:3; width: 700px; background: #ffffff00; box-shadow: 0px 0px 20px #7d7474; border-radius: 30px; padding: 11px; margin: 0 auto; margin: 20px auto; }
				z.myplugin .additionals li { list-style-type: circle; list-style-type: circle; float: left; margin: 5px 0 5px 40px;}
				.myplugin .whiteback { background:white; border-bottom:1px solid white; }
				.myplugin.version_pro .donations_block, .myplugin.version_not_pro .donations_block { display:none; }
				.myplugin .donation_li a{  color: #d47b09; }
				.myplugin .nav-tab-wrapper .errorslog{ color: #903e4c; font-size: 0.7em; font-style: italic; opacity:  0.6;  float:right;}
				.myplugin .freelancers {font-style: italic; font-family: arial; font-size: 0.9em; margin: 15px; padding: 10px; border-radius: 5px; opacity: 0.7; }
				.myplugin .freelancers a{}
				.myplugin .button { top: -4px; }
				.myplugin .red-button { background: #ec5d5d;   zbackground:  #ffdfdf;}
				.myplugin .pink-button { background: pink; }
				.myplugin .green-button { background: #44d090; }
				.myplugin .float-left { float:left; }
				.myplugin .float-right { float:right; }
				.myplugin .displaynone { display:none; }
				.myplugin .clearboth { clear:both;  height: 20px;  }
				.myplugin .noinput { border: none!important; box-shadow:none!important; font-weight: bold; }
				.myplugin .translations_table { margin: 20px 0 0 30px; border-collapse: collapse;}

				.myplugin a,.myplugin a.button { display: inline; }
				.ui-dialog {z-index: 9222!important; }
				.myplugin .disabled { pointer-events: none; }
				.myplugin .nonclickable { pointer-events: none; }

				.myplugin .errors_page table {border-collapse: collapse;}
				.myplugin .errors_page table tr > * { border: 1px solid #c7c7c7; padding: 5px 10px; }
				.myplugin .errors_page table td:nth-child(1){ width: 5%;}

				.myplugin .nav-tab{ border-radius: 60% 30% 5% 0px; }
				.myplugin .nav-tab-active{ color: #43ceb5; pointer-events: none; }

				.myplugin .review_block{ float:right; }
				.myplugin 	.stars{ height:30px; }
				.myplugin 	span.leaverating {position:absolute; z-index:4; margin:0 auto; text-align:center; width:auto; white-space:nowrap; top:15px; color:#000000de; font-size:0.8em; left:20px; text-shadow:0px 0px 25px black;}
				.myplugin 	img.stars{ height:30px; vertical-align:middle; }

				.myplugin .site_author_block{ text-align:center; font-size:0.8em; }
				.myplugin .site_author_block a{ text-decoration:none; color:black;}
				.myplugin .shortcodes_block { box-shadow: 0px 0px 15px #00000066; padding: 10px 0 0 0; margin: 10px 0;}

				</style>
				<div class="clear"></div>
				<script> tt_ajax_action = '<?php echo $this->plugin_slug_u;?>_all';</script>
				<?php if( $this->has_pro_version && $this->is_pro === false) {  $this->purchase_pro_block(); } ?>
			</div>

			<?php if($this->show_rating_message) { ?>
			<div class="review_block">
				<a class="review_leave" href="<?php echo $this->opts['wp_rate_url'];?>" target="_blank">
					<span class="leaverating"><?php _e('Rate plugin', 'default_methods__ProtectPages_com');?></span>
					<img class="stars" src="<?php echo $this->opts['public_assets_url'];?>/assets/rating-transparent-shaded.png" />
				</a>
			</div>
			<?php
			}
			?>
			<?php if($this->show_author_block) { ?>
			<div class="site_author_block">
				<a class="autor_url" href="<?php echo $this->opts['AuthorURI'];?>" target="_blank">
					<?php echo parse_url($this->opts['AuthorURI'])["host"];?>
				</a>
			</div>
			<?php
			}
			?>
		<?php
		}
	}

	
	public function shortcodes_table($shortcode_name, $array=array())
	{ 
		/*======= example ========
		
		$this->shortcodes_table( "breadcrumbs", [
			[ 'id', 				'',			__('Post ID (you can ignore that parameter if you want to get for current post)', 'breadcrumbs-shortcode') ],
			[ 'delimiter',			'hello', 	__('Your desired delimiter', 'breadcrumbs-shortcode') ],
		]);
		*/
	?>
	<div class="shortcodes_block">
		<h3><?php _e('Shortcodes Usage', 'default_methods__ProtectPages_com');?></h3>
		<table class="form-table shortcodes">
		<tr>
			<td><?php _e('Example Shortcode:', 'default_methods__ProtectPages_com');?></td>
			<td><code>[<?php echo $shortcode_name;?> parameter=123 ...]</code></td>
		</tr>
		<tr>
			<td><?php _e('Example Function:', 'default_methods__ProtectPages_com');?></td>
			<td><code>$GLOBALS['<?php echo $this->plugin_NAMESPACE;?>']-><?php echo $shortcode_name;?>( ['parameter'=>123, ....] );</code></td>
		</tr>
		<tr>
			<td><?php _e('Parameters:', 'default_methods__ProtectPages_com');?></td>
			<td>
				<table>
				<tr style="font-weight:bold;color:orange;">
					<td><?php _e('name', 'default_methods__ProtectPages_com');?></td><td><?php _e('default value', 'default_methods__ProtectPages_com');?></td><td><?php _e('description', 'default_methods__ProtectPages_com');?></td>
				<tr>
				<?php 
				foreach($array as $key=>$value)
				{ ?>
				<tr>
					<td><code><?php echo $value[0];?></code></td><td><?php echo $value[1];?></td><td><?php echo $value[2];?></td>
				</tr>
				<?php 
				}
				?>
				</table>
			</td>
		</tr>
		</table>
	</div>
	  <?php
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	public function admin_head_func()
	{ 
		if( defined("ttLibrary_scripts_loaded") ) return;  define("ttLibrary_scripts_loaded", true);

		?>
		<script>
		ttLibrary =
		{

			spinner: function(action)
			{
				var spinner_id = "tt_spinner";
				if(action)
				{
					var div=
					'<div id="'+spinner_id+'" style="background:black; position:fixed; height:100%; width:100%; opacity:0.9; z-index:8999;   display: flex; justify-content: center; align-items: center;">'+
						'<div style=""><img style="height:80px; width:80px;" src="https://cdnjs.cloudflare.com/ajax/libs/galleriffic/2.0.1/css/loaderWhite.gif" />'+
					'</div>';
					document.body.insertAdjacentHTML("afterbegin", div);
				}
				else
				{
					var el = document.getElementById(spinner_id);
					if (el) {
						el.parentNode.removeChild(el);
					}
				}
			},


			dialog: function(message)
			{
				jQuery('<div class="ttDialog">'+message+'</div>').dialog({
					modal: true,
					width: 500,
					close: function (event, ui) {
						jQuery(this).remove();	// Remove it completely on close
					}
				});
			},

			message: function(message)
			{
				return ttLibrary.dialog(message);
			},

			backend_call : function (data, callback)
			{
				data["action"] = tt_ajax_action;
				ttLibrary.spinner(true);

				jQuery.post
				(
					ajaxurl,
					data,
					function(response){  ttLibrary.spinner(false);   callback(response); }
				);
			},

			blink_field : function (fieldObj)
			{
				fieldObj.animate({backgroundColor: '#00bb00'}, 'slow').animate({backgroundColor: '#FFFFFF'}, 'slow');
			}

		};


		</script>
	  <?php
	}






	public function ajax_backend_call()
	{
		if(isset($_POST['action']) && $_POST['action']==$this->plugin_slug_u .'_all')
		{
			if(method_exists($this, 'backend_call')){
				$this->backend_call( sanitize_key($_POST['act']) );
			}
		}
		exit( __('Unknown-action','default_methods__ProtectPages_com') );
	}
 





	public function admin_scripts($hook)  //i.e. edit.php
	{
		if($this->is_this_settings_page()){

			//jquery ui 
			$this->register_stylescript('style', 'wp-jquery-ui-core');
			$this->register_stylescript('script', 'jquery-ui-core');
			$this->register_stylescript('script', 'jquery-effects-core');

			$this->register_stylescript('style', 'ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',  false,  '1.1');

			$this->register_stylescript('style', 'wp-jquery-ui-dialog');
			$this->register_stylescript('script', 'jquery-ui-dialog');

			$this->register_stylescript('script', 'jquery-ui-tooltip');
			

			// spin.js
			// $this->register_stylescript('script', 'spin', 'https://cdnjs.cloudflare.com/ajax/libs/spin.js/2.3.2/spin.min.js',  array('jquery'),  '2.3.2', true);

			// touch-punch.js
			$this->register_stylescript('script', 'touch-punch', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js',  array('jquery'),  '0.2.3', true );
		}
	}

	public function register_stylescript($type, $handle=false, $url=false, $dependant=null, $version=false, $target=false)
	{
		if ( ! call_user_func("wp_".$type."_is",	$handle, "registered" ) ){
			call_user_func("wp_register_".$type,	$handle, $url,  $dependant,  $version, $target );   //,'jquery-migrate'
		}
		if ( ! call_user_func("wp_".$type."_is",	$handle, "enqueued" ) ){
			call_user_func("wp_enqueue_".$type,	$handle);
		}
	}

	public function unzip_url($url, $where)
	{
		$zipLoc = $where.'/'.(basename($url)).'.zip';
		wp_remote_get
		(
			$this->cat_thumbs_download_url,
			array(
				'timeout'  => 300,
				'stream'   => true,
				'filename' => $zipLoc
			)
		);
		// unzip
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		\WP_Filesystem();
		\unzip_file($zipLoc,  $where );
		@unlink($zipLoc);
	}

	// common funcs
	public function  str_replace_first($from, $to, $content, $type="plain"){
		if($type=="plain"){
			$pos = strpos($content, $from);
			if ($pos !== false) {
				$content = substr_replace($content, $to, $pos, strlen($from));
			}
			return $content;
		}
		elseif($type=="regex"){
			$from = '/'.preg_quote($from, '/').'/';
			return preg_replace($from, $to, $content, 1);
		}
	}

	public function safemode_basedir_set(){
		return ( ini_get('open_basedir') || ini_get('safe_mode')) ;
	}

	public function is_activation(){
		return (isset($_GET['isactivation']));
	}

	public function reload_without_query($params=array(), $js_redir=true){
		$url = remove_query_arg( array_merge($params,array('isactivation') ) );
		if ($js_redir=="js"){ $this->js_redirect($url); }
		else { $this->php_redirect($url); }
	}

	public function if_activation_reload_with_message($message){
		if($this->is_activation()){
			echo '<script>alert(\''.$message.'\');</script>';
			$this->reload_without_query();
		}
	}

	public function add_default_uninstall(){
		if( is_admin() && !$this->is_development)
		{
			$wp_uninstall_file = $this->plugin_DIR.'/uninstall.php';
			if( !file_exists($wp_uninstall_file) )
			{
				$content=
				'<'.'?php
				// If uninstall not called from WordPress, then exit
				if ( ! defined( "WP_UNINSTALL_PLUGIN" ) ) {
					exit;
				}

				$lib = dirname(__DIR__)."/'.basename(__FILE__).'";
				if(file_exists($lib)){
					//@unlink($lib);
				}';

				file_put_contents($wp_uninstall_file, $content);
			}
		}
	}
	public function getDomain($url){
		return preg_replace('/http(s|):\/\/(www.|)(.*?)(\/.*|$)/i', '$3', $url);
	}

	public function adjustedUrlPrefixes($url){
		if(strpos($url, '://') !== false){
			return preg_replace('/^(http(s|)|):\/\/(www.|)/i', 'https://www.', $url);
		}
		else{
			return 'https://www.'.$url;
		}
	}

	public function stripUrlPrefixes($url){
		return preg_replace('/http(s|):\/\/(www.|)/i', '',  $url);
	}

	public function stripDomain($url){
		return str_replace( $this->adjustedUrlPrefixes($this->domainReal), '', $this->adjustedUrlPrefixes($url) );
	}

	public function try_increase_exec_time($seconds){
		if( ! $this-> safemode_basedir_set() ) {
			set_time_limit($seconds);
			ini_set('max_execution_time', $seconds);
			return true;
		}
		return false;
	}

	public function is_this_settings_page(){
		return (stripos(get_current_screen()->base, $this->plugin_slug) !== false  &&  (isset($_GET['page']) && $_GET['page']==$this->plugin_slug)  );
	}

	public function convert_urls_in_text($text) {
		return preg_replace('@([^\"\']https?://([-\w\.]+)+(:\d+)?(/([\w/_\.%-=#][^<]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
	}

	public function randomString($length = 11) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1, $length);
	}

	public function OneSlash($url){
		$prefix='';
		if(substr($url,0,2)=='//'){
			$prefix = '//';
			$url=substr($url,2);
		}
		return $prefix.preg_replace( '/([^:])\/\//',  '$1/', $url);
	}

	public function get_locale__SANITIZED(){
		return ( get_locale() ? "en" : preg_replace('/_(.*)/','',get_locale()) ); //i.e. 'en'
		//$x=$GLOBALS['wpdb']->get_var("SELECT lng FROM ".$this->options." WHERE `lang` = '".$lang."'"); return !empty($x);
		// preg_replace('/[^\w\d_\-]/', '',  filter_var($input,	FILTER_SANITIZE_STRING)	);
	}


	public function readUrl( $url){
		return  wp_remote_retrieve_body(  wp_remote_get( $url )  );
	}

	public function set_cookie($name, $val, $time_length = 86400, $path=false, $domain=false, $httponly=true){
		$site_urls = parse_url( (function_exists('home_url') ? home_url() : $_SERVER['SERVER_NAME']) );
		$real_domain = $site_urls["host"];
		$path = $path ? $path : ( (!empty($this) && property_exists($this,'homePath') ) ?  $this->homePath : '/');
		$domain = $domain ? $domain : (substr($real_domain, 0, 4) == "www.") ? substr($real_domain, 4) : $real_domain;
		setcookie ( $name , $val , time()+$time_length, $path = $path, $domain = $domain,  $only_on_secure_https = FALSE,  $httponly  );
	}

	public function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public function MessageAgainstMaliciousAttempt(){
		return 'Not allowed. Try again.';//'Well... I know that these words won\'t change you, but I\'ll do it again: Developers try to create a balance & harmony in internet, and some people like you try to steal things from other people. Even if you can it, please don\'t do that.';
	}

	public function FullIframeScript(){ ?>
		<script>
		function MakeIframeFullHeight_tt(iframeElement, cycling, overwrite_margin){
			cycling= cycling || false;
			overwrite_margin= overwrite_margin || false;
			iframeElement.style.width	= "100%";
			var ifrD = iframeElement.contentDocument || iframeElement.contentWindow.document;
			var mHeight = parseInt( window.getComputedStyle( ifrD.documentElement).height );  // Math.max( ifrD.body.scrollHeight, .. offsetHeight, ....clientHeight,
			var margins = ifrD.body.style.margin + ifrD.body.style.padding + ifrD.documentElement.style.margin + ifrD.documentElement.style.padding;
			if(margins=="") { margins=0; if(overwrite_margin) {  ifrD.body.style.margin="0px"; } }
			(function(){
			   var interval = setInterval(function(){
				if(ifrD.readyState  == 'complete' ){
					setTimeout( function(){
						if(!cycling) { setTimeout( function(){ clearInterval(interval);}, 500); }
						iframeElement.style.height	= (parseInt(window.getComputedStyle( ifrD.documentElement).height) + parseInt(margins)+1) +"px";
					}, 200 );
				}
			   },200)
			})();
				//var funcname= arguments.callee.name;
				//window.setTimeout( function(){ console.log(funcname); console.log(cycling); window[funcname](iframeElement, cycling); }, 500 );
		}
		</script>
		<?php
	}





	public function cookieFuncs(){
	?>
	<script>
	// ================= create, read,delete cookies  =================
	function Is_Cookie_Set_tt(cookiename) { return document.cookie.indexOf('; '+cookiename+'=');}

	function createCookie_tt(name,value,days) {
		var expires = "";
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days*24*60*60*1000));
			expires = "; expires=" + date.toUTCString();
		}
		document.cookie = name + "=" + (value || "")  + expires + "; path=/";
	}
	function readCookie_tt(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}
	function eraseCookie_tt(name) {
		document.cookie = name+'=; Max-Age=-99999999;';
	}
			function setCookie(name,value,days) { createCookie(name,value,days); }
			function getCookie(name) { return readCookie(name); }
			function setCookieOnce(name) { createCookie(name, "okk" , 1000); }
	// ===========================================================================================
	</script>
	<?php
	}


	public function startSessionIfNotStarted(){
		if(session_status() == PHP_SESSION_NONE)  { $this->session_being_opened = true; session_start();  }
	}
	public function endSessionIfWasStarted( $method=2){
		if(session_status() != PHP_SESSION_NONE && property_exists($this,"session_being_opened") )  {
			if($method==1) session_destroy();
			elseif($method==2) session_write_close();
			elseif($method==3) session_abort();
		}
	}

	public function rmdir_recursive($path){
		if(!empty($path) && is_dir($path) ){
			$dir  = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS); //upper dirs not included,otherwise DISASTER HAPPENS :)
			$files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($files as $f) {if (is_file($f)) {unlink($f);} else {$empty_dirs[] = $f;} } if (!empty($empty_dirs)) {foreach ($empty_dirs as $eachDir) {rmdir($eachDir);}} rmdir($path);
			return true;
		}
		return true;
		//include_once(ABSPATH.'/wp-admin/includes/class-wp-filesystem-base.php');
		//\WP_Filesystem_Base::rmdir($fullPath, true);
	}

	public function array_map_recursive(callable $func, array $array) {
		return filter_var($array, \FILTER_CALLBACK, ['options' => $func]);
	}


		//$transient_name = md5( json_encode( $args ) );
		//$transient_results = get_transient( $transient_name );
		//set_transient( $transient_name, $result,  $this->opts['feed_cache_expire'] * MINUTE_IN_SECONDS );

	public function nextKeyInArray($target_keyname, $array){
		$keys = array_keys($array);
		$index_of_target_keyname = array_search($target_keyname,  $keys , true);
		return (count($array) > $index_of_target_keyname+1 ) ? $keys[$index_of_target_keyname+1]  :  $keys[0];
	}

	public function nextValueInArray($target_value, $array, $by_key=false){
		$keys = array_keys($array);
		$target_keyname = $by_key ? $target_value : array_search($target_value,  $array, true );
		$index_of_target_keyname = array_search($target_keyname,  $keys, true );
		return (count($array) > $index_of_target_keyname+1 ) ? $array[ $keys[$index_of_target_keyname+1] ]  :  $array[  $keys[0]  ];
	}

	public function getIndexOfKey($array, $key){
		return array_search($key, array_keys($array) );
	}

	public function getMemberByIndex($array, $idx){
		$keys= array_keys($array);
		return (!empty($keys) && !empty($array[$keys[$idx]])) ;
	}

	public function getIndexOfValue($array, $key){
		return array_search($key, $array );
	}

	public function resortArrayByKey($array, $key, $remove_current= false){
		$remaining =  array_splice ($array, $this->getIndexOfKey($array, $key)   );
		if($remove_current){
			$array[$key]= $remaining[$key];
			unset($remaining[$key] );
		}
		return array_merge($remaining, $array);
	}


	public function ListAllInDir($path, $only_files = false) {
		$all_list = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
				( $only_files ? \RecursiveIteratorIterator::LEAVES_ONLY : \RecursiveIteratorIterator::SELF_FIRST )
		);
		$files = array();
		foreach ($all_list as $file)
			$files[] = $file->getPathname();

		return $files;
	}

	public function unzip_in_dir($dir)
	{
		foreach( array_filter(glob($dir.'/*.zip'), 'is_file')  as $each_zip)
		{
			$uniqueTag	= md5($each_zip);
			$each_dir	= substr($each_zip, 0, -4); //trim .zip
			if (empty($each_dir)) return; // ! must have, to avoid empty directory threat

			// remove if previous unpack was partial.
			if( is_dir($each_dir) )
			{
				if( empty($this->opts['folderOk_'.$uniqueTag]) )
				{
					$this->rmdir_recursive($each_dir);
					usleep(500000);
					//$this->mkdir_recursive($pathh);
				}
			}
			if( !is_dir($each_dir) )
			{
				$this->opts['folderOk_'.$uniqueTag] = false; $this->update_opts();
				$this->wp_unzip($each_zip);
				$this->opts['folderOk_'.$uniqueTag] = true;  $this->update_opts();
			}
		}
	}

	public function wp_unzip($file)
	{
		// unzip
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		\WP_Filesystem();
		\unzip_file($file, dirname($file) );
		usleep(300000);
	}

	public function replace_occurences_in_dir($dir_base, $from, $to, $exts=array('php','shtml') ){
		$dirIterator = $this->ListAllInDir($dir_base, true);
		foreach($dirIterator as $idx => $value) {
			$filext = pathinfo($value, PATHINFO_EXTENSION);
			if( in_array($filext,  $exts ) ){
				$cont = file_get_contents($value);
				if(stripos($cont, $from) !== false){
					$new_cont = str_replace($from, $to, file_get_contents($value) );
					file_put_contents($value, $new_cont);
				}
			}
		}
	}

	public function update_or_insert($tablename, $NewArray, $WhereArray=array()){	global $wpdb; $arrayNames= array_keys($WhereArray);
		//convert array to STRING
		$o=''; $i=1; foreach ($WhereArray as $key=>$value){ $o .= $key . ' = \''. $value .'\''; if ($i != count($WhereArray)) { $o .=' AND '; $i++;}  }
		//check if already exist
		$CheckIfExists = $wpdb->get_var("SELECT ".$arrayNames[0]." FROM ".$tablename." WHERE ".$o);
		if ( $wpdb->update($tablename,	$NewArray,	$WhereArray	) ) return true;
		if ( $wpdb->insert($tablename, 	array_merge($NewArray, $WhereArray)	) ) return true;
		return false;
	}

	public function startsWith($haystack, $needle) {   return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false; }
	public function endsWith($haystack, $needle) { $length = strlen($needle);  return $length === 0 ||  (substr($haystack, -$length) === $needle); }
	public function contains($content, $needle, $case_sens= true){ return ($case_sens ? strpos($content, $needle) : stripos($content, $needle)) !== false;  }

	// ================ flash rules ================= //
	// unique func to flush rewrite rules when needed. HANGS WP_options resaves, if not hooked into wp_footer
	public function flush_rules_if_needed($temp_key=false){
		// lets check if refresh needed
		$key="b".get_current_blog_id()."_". md5(    (empty($temp_key) ?  "sample" : ( stripos($temp_key, basename($this->plugin_DIR)) !== false ? md5(filemtime($temp_key)) : $temp_key ))    );
		if( !array_key_exists($key, $this->opts['last_updates']) || $this->opts['last_updates'][$key] < $this->opts['last_update_time']){
			$this->opts['last_updates'][$key] = $this->opts['last_update_time'];
			$this->update_opts();
			add_action('wp_footer', function(){ $this->flush_rules("js"); } );
		}
	}

	public function is_JSON_string($string){
	   return (is_string($string) && is_array(json_decode($string, true)));
	}

	public function arrayed_json($answer){
		$result = array();
		if(!$this->is_JSON_string($answer)){
			$result['error'] = $answer;
		}
		else{
			$result = json_decode($answer, true);
		}
		return $result;
	}

	public function arrayed_answer($answer){
		$result = array();
		if(!$this->is_JSON_string($answer)){
			$result['error'] = $answer;
		}
		else{
			$result = json_decode($answer, true);
		}
		return $result;
	}


	public function flush_rules($redirect=false){
		flush_rewrite_rules();
		if($redirect) {
			if ($redirect=="js"){ $this->js_redirect(); }
			else { $this->php_redirect(); }
		}
	}
	public function js_redirect($url=false, $echo=true){
		$str = '<script>window.location = "'. ( $url ?: $_SERVER['REQUEST_URI'] ) .'";</script>';
		if($echo) { exit($str); }  else { return $str; }
	}
	public function php_redirect($url=false){
		header("location: ". ( $url ?: $_SERVER['REQUEST_URI'] ), true, 302); exit;
	}

	public function js_redirect_message($message, $url=false){
		echo '<script>alert(\''.$message.'\');</script>';
		$this->js_redirect($url);
	}

	public function mkdir_recursive($dest, $permissions=0755, $create=true){
		if(!is_dir(dirname($dest))){ mkdir_recursive(dirname($dest), $permissions, $create); }
		elseif(!is_dir($dest)){ mkdir($dest, $permissions, $create); }
		else{return true;}
	}
	// ================ flash rules ================= //









































	// ========= my functions for PRO plugins ========== //
	public function check_if_pro_plugin(){
		$this->is_pro = null;
		if( property_exists($this, "has_pro_version") && $this->has_pro_version ){
			//$this->has_pro_version = true;  // it is price of plugin
			$this->is_pro 	= false;
			if(file_exists( $this->plugin_DIR .'/addon.php')){
				$ar= $this->get_license();
				if($ar['status']){
					$this->is_pro = true;
				}
			}
		}
		else{
			$this->has_pro_version = false;
		}
	}

	public function license_keyname(){
		return $this->plugin_slug_u ."_l_key";
	}

	public function get_license($key=false){
		$def_array = array(
			'status' => false,
			'key' => '',
		);
		$license_arr = get_site_option($this->license_keyname(), $def_array );
		return ($key ? $license_arr[$key] : $license_arr);
	}

	public function update_license($val, $val1=false){
		if(is_array($val)){
			$array = $val;
		}
		else{
			$array= $this->get_license();
			$array[$val]=$val1;
		}
		update_site_option( $this->license_keyname(), $array );
	}



	public function get_check_answer($key){

		$this->info_arr		=  array('siteurl'=>home_url(), 'plugin_slug'=>$this->plugin_slug ) + $this->plugin_variables;

		$answer =
			wp_remote_retrieve_body(
				wp_remote_post($this->opts['purchase_check'],
					array(
						'method' => 'POST',
						'timeout' => 25,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array( 'key' => $key ) + $this->info_arr,
						'cookies' => array()
					)
				)
			);
		return $answer;
	}

	public function backend_ajax_check_pro(){
		if(isset($_POST['check_key'])){
			$key = sanitize_text_field( $_POST['check_key'] );
			$answer = $this->get_check_answer($key);

			if(!$this->is_JSON_string($answer)){
				$result = array();
				$result['error'] = $answer;
			}
			else{
				$result = json_decode($answer, true);
			}
			//
			if(isset($result['valid'])){
				if($result['valid']){
					if(!empty($result['files'])){
						foreach($result['files'] as $name=>$val){
							if(stripos($name,'/')!== false || stripos($name,'..')!== false){
								$result['error'] = $answer;
								break;
							}
							else{
								$name = sanitize_file_name($name);
								file_put_contents( $this->plugin_DIR .'/'.basename($name),  $val);
							}
						}
					}
					if(!isset($result['error'])){
						$this->update_license( 'status', true );
					}
				}
			}
			else{
				$result['error'] = $answer;
			}
			echo json_encode($result);

			if(isset($result['error'])) {
				$this->send_error_mail($result['error']);
			}
		}

		elseif(isset($_POST['save_results'])){

		}
		wp_die();
	}



	public function pro_field(){
		if($this->has_pro_version && !$this->is_pro){
			echo 'data-pro-overlay="pro_overlay"';
			//echo '<span class="pro_overlay overlay_lines"></span> ';
		}
	}

	public function purchase_pro_block(){ ?>
		<div class="pro_block">
			<style>
			.myplugin.version_free .get_pro_version, .myplugin.version_pro .get_pro_version{ display:none; }
			.myplugin .dialog_enter_key{ display:none; }
			.get_pro_version { line-height: 1.2; z-index: 1; background: #ff1818;  text-align: center; border-radius: 10px; display: inline-block;  position: fixed; bottom: 0px; right: 0; left: 0; padding: 10px 10px; max-width: 750px; margin: 0 auto; text-shadow: 0px 0px 6px white;  box-shadow: 0px 0px 52px black; }
			.get_pro_version .centered_div > span  { font-size: 1.5em; }
			.get_pro_version .centered_div > span  a { font-size: 1em; color: #7dff83;}
			.init_hidden{ display:none; }
			z#check_results{ display:inline; flex-direction:row; font-style:italic; }
			#check_results .correct{  background: #a8fba8;  }
			#check_results .incorrect{  background: pink;  }
			#check_results span{  padding:3px 5px;  }
			.dialog_enter_key_content {  display: flex; flex-direction: column; align-items: center;  }
			.dialog_enter_key_content > *{  margin: 10px ;  }
			.or_enter_key_phrase{ font-style: italic; }

			[data-pro-overlay=pro_overlay]{  pointer-events: none;  cursor: default;  position:relative;  min-height: 2em;  padding:5px; }
			[data-pro-overlay=pro_overlay]::before{   content:""; width: 100%; height: 100%; position: absolute; background: black; opacity: 0.3; z-index: 1;  top: 0;   left: 0;
				background: url("https://ps.w.org/internal-functions-for-protectpages-com-users/trunk/assets/overlay-1.png");
			}
			[data-pro-overlay=pro_overlay]::after{ text-shadow: 0px 0px 5px black; padding: 5px;  opacity:1;  text-align: center;  animation-name: blinking;  zzanimation-name: moving;  animation-duration: 8s;  animation-iteration-count: infinite;  overflow:hidden;  white-space: nowrap;
				content: "<?php _e('Only available in FULL VERSION', 'default_methods__ProtectPages_com');?>"; position: absolute; top: 0; left: 0; bottom: 0; right: 0; z-index: 3; overflow: hidden; font-size: 2em; color: red;
			}
			@keyframes blinking {
				0% {opacity: 0;}
				50% {opacity: 1;}
				100% {opacity: 0;}
			}
			@keyframes moving {
				0% {left: 30%;}
				40% {left: 100%;}
				100% {left: 0%;}
			}
			</style>
			<div class="get_pro_version">
				<span class="centered_div">
					<span class="purchase_phrase">
						<a id="purchase_key" href="<?php echo esc_url($this->opts['purchase_url']);?>" target="_blank"><?php _e('GET FULL VERSION', 'default_methods__ProtectPages_com');?></a> <span class="price_amnt">(<?php _e('only', 'default_methods__ProtectPages_com');?> <?php echo $this->has_pro_version;?>$)</span>
					</span>

					<span class="or_enter_key_phrase">
					<?php _e('or', 'default_methods__ProtectPages_com');?> <a id="enter_key"  href=""><?php _e('Enter License Key', 'default_methods__ProtectPages_com');?></a>
					</span>

				</span>
			</div>

			<div class="dialog_enter_key">
				<div class="dialog_enter_key_content" title="Enter the purchased license key">
					<input id="key_this" class="regular-text" type="text" value="<?php echo $this->get_license('key');?>"  />
					<button id="check_key" ><?php _e( 'Check key', 'default_methods__ProtectPages_com' );?></button>
					<span id="check_results">
						<span class="correct init_hidden"><?php _e( 'correct', 'default_methods__ProtectPages_com' );?></span>
						<span class="incorrect init_hidden"><?php _e( 'incorrect', 'default_methods__ProtectPages_com' );?></span>
					</span>
				</div>
			</div>
		</div>
		<?php
		$this->plugin_scripts();
	}

	public function plugin_scripts(){
		?>
		<script>
		function main_tt(){

			var this_action_name = '<?php echo $this->plugin_slug_u;?>';

			(function ( $ ) {
				$(function () {
					//$("#purchase").on("click", function(e){ this_name_tt.open_license_dialog(); } );
					$("#enter_key").on("click", function(e){ return this_name_tt.enter_key_popup(); } );
					$("#check_key").off().on("click", function(e){ return this_name_tt.check_key(); } );
				});
			})( jQuery );

			// Create our namespace
			this_name_tt = {

				spinner_initialized : false,

				InitializeSpinner: function() {

					// See all options definitions on the above source link
					var opts = {
						lines: 13, length: 38, width: 17, radius: 45, scale: 1, corners: 1, color: '#333333', fadeColor: '#e7e7e7', opacity: 0.25, rotate: 0, direction: 1, speed: 1, trail: 60, fps: 5, zIndex: 889999, className: 'spin-js-spinner', top: '50%', left: '50%', shadow: '0px 0px 5px black', position: 'fixed'
					};
					this.spinner_el = new Spinner(opts).spin();
					this.spinner_initialized = true;

				},

				reload_this_page : function(){
					window.location = window.location.href;
				},

				/*
				*	Method to show/hide spinner
				*/
				spin: function(action) {

					// If it is the first call to spinner, then at first, it is initialized.
					if (!this.spinner_initialized) {
						this.InitializeSpinner();
					}

					// Check which action was called
					if (action == "show") {

						// We create a background and attach the spinner too, to the document.body
						var s = jQuery('<div class="spin-js">');
						s.append('<div id="blackground_spinner" style="top:0px; width:100%;height:100%;position:fixed;background:white; z-index:2; opacity:0.8;"></div>');
						s.append(this.spinner_el.el);
						jQuery("body").append(s);

					} else if (action == "hide") {

						// We remove the spinner & background
						jQuery("#blackground_spinner").remove();
						this.spinner_el.el.remove();

					}

				},


				/*
				*	Method to check (using AJAX, which calls WP back-end) if inputed username is available
				*/
				enter_key_popup: function(e) {

					// Show jQuery dialog
					jQuery('.dialog_enter_key_content').dialog({
						modal: true,
						width: 500,
						close: function (event, ui) {
							//jQuery(this).remove();	// Remove it completely on close
						}
					});
					return false;
				},

				IsJsonString: function(str) {
					try {
						JSON.parse(str);
					} catch (e) {
						return false;
					}
					return true;
				},

				check_key : function(e) {

					var this1 = this;

					var inp_value = jQuery("#key_this").val();

					if (inp_value == ""){  return;  }

					// Show spinner
					this.spin("show");

					jQuery.post(
						// Url to backend
						ajaxurl,
						// Data to send
						{
							'action': this_action_name,
							'check_key': inp_value
						},
						// Function when request complete
						function (answer) {
							// Hide spinner
							this1.spin("hide");

							if(typeof window.debug_this != "undefined"){  console.log(answer);  }

							if(this1.IsJsonString(answer)){
								var new_res=  JSON.parse(answer);
								if(new_res.hasOwnProperty('valid')){
									if(new_res.valid){
										this1.show_green();
									}
									else{
										var reponse1 = JSON.parse(new_res.response);
										this1.show_red(reponse1.message);
									}
								}
								else {
									this1.show_red(new_res);
								}
							}
							else{
								this1.show_red(answer);
							}
						}
					);
					return false;
				},

				show_green : function(){
					jQuery("#check_results .correct").show();
					jQuery("#check_results .incorrect").hide();
					this.reload_this_page();
					//this.save_results();
				},

				show_red : function(e){
					jQuery("#check_results .correct").hide();
					jQuery("#check_results .incorrect").show();
					jQuery("#check_results .incorrect").html(e);

					/*
					var message = 'Your inputed username "' + tw_usr + '" is incorrect! \nPlease, change it.';
					// Show jQuery dialog
					jQuery('<div>' + message + '</div>').dialog({
						modal: true,
						width: 500,
						close: function (event, ui) {
							jQuery(this).remove();	// Remove it completely on close
						}
					});
					*/
				},


			};
		}
		main_tt();
		</script>

		<?php
	}

	// ================================================   ##end of default block##  ============================================= //
	// ========================================================================================================================== //
}
?>
