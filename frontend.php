<?php

class MP3j_Front extends MP3j_Main
{

	//~~
	function addPrimaryHooks ()
	{
		add_action( 'plugins_loaded', array( $this, 'loadTranslation' ) );
		add_action( 'init', array( $this, 'onInit' ), 100 );
		add_action( 'widgets_init',  array( $this, 'registerWidgets' ) );
		
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'createAdminPages' ), 100 );
			add_action( 'deactivate_mp3-jplayer/mp3jplayer.php', array( $this, 'deactivate' ) );
		}
		
		add_action( 'wp_head', array( $this, 'header_scripts_handler' ), 2 );	
		add_action( 'wp_footer', array( $this, 'checkAddScripts' ), 1 ); 		// Final chance to enqueue, process this action early (priority < 20).
		add_action( 'wp_footer', array( $this, 'footercode_handler' ), 200 ); 	// Inline js, after enqueues. Keep at 200! Add-ons may go either side.
		
		add_filter( 'get_the_excerpt', array( $this, 'get_excerpt_handler' ), 1 );
		add_filter( 'the_content', array( $this, 'afterExcerpt' ), 9999 );
		
		if ( $this->theSettings['make_player_from_link'] == "true" ) {
			add_filter('the_content', array( $this, 'replace_links' ), 1);
		}
		
		if ( $this->theSettings['run_shcode_in_excerpt'] == "true" ) {
			add_filter( 'the_excerpt', 'shortcode_unautop' );
			add_filter( 'the_excerpt', 'do_shortcode' );
		}		
	}
	
	
	//~~
	function registerShortcodes ()
	{
		$ops = $this->theSettings;
		add_shortcode( 'mp3t', array( $this, 'inline_play_handler' ) );
		add_shortcode( 'mp3j', array( $this, 'inline_play_graphic' ) );
		add_shortcode( 'mp3-jplayer', array( $this, 'primary_player' ) );
		add_shortcode( 'mp3-popout', array( $this, 'popout_link_player' ) );
		
		remove_shortcode( 'popout' );
		add_shortcode( 'popout', array( $this, 'popout_link_player' ) );
		
		if ( $ops['replace_WP_playlist'] === 'true' && ! is_admin() ) {
			remove_shortcode( 'playlist' );
			add_shortcode( 'playlist', array( $this, 'replacePlaylistShortcode' ) );
		}
		
		if ( ! is_admin() && ( $ops['replace_WP_audio'] === 'true' || $ops['replace_WP_embedded'] === 'true' || $ops['replace_WP_attached'] === 'true' ) )	{
			remove_shortcode( 'audio' );
			add_shortcode( 'audio', array( $this, 'replaceAudioShortcode' ) );
		}
	}
	
	
	//~~
	function registerTagCallbacks ()
	{
		add_action( 'mp3j_addscripts', array( $this, 'scripts_tag_handler' ), 1, 1 );
		add_action( 'mp3j_put', array( $this, 'template_tag_handler' ), 10, 1 );
		add_action( 'mp3j_debug', array( $this, 'debug_info' ), 10, 1 );
		add_filter( 'mp3j_grab_library', array( $this, 'grablibrary_handler' ), 10, 1 );
		add_action( 'mp3j_settings', array( $this, 'mp3j_settings' ), 1, 1 );
	}
	
	
	//~~
	function loadTranslation () {
		$path = basename( dirname( __FILE__ ) ) . '/lang/';
		load_plugin_textdomain( 'mp3-jplayer', false, $path );
	}
	
	
	//~~
	function onInit () {
		$this->SKINS = $this->getSkinData();
		if ( is_admin() ) {
			add_action( 'wp_ajax_ax_mjp_liblist', 'ax_mjp_liblist' );
		}
	}
	
	
	//~~
	function deactivate () { 
		$O = get_option( MP3J_SETTINGS_NAME );
		if ( $O['remember_settings'] == "false" ) {
			delete_option( MP3J_SETTINGS_NAME );
		}
	}
	
	
	//~~
	function addPluginListLink( $links, $file ) { 
		if ( $file == 'mp3-jplayer/mp3jplayer.php' ) {
			$settings_link = '<a href="admin.php?page=mp3-jplayer">'.__('Settings').'</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
	
	
	//~~
	function mp3j_settings ( $devsettings = array() ) {
		foreach ( $this->setup as $k => $v ) {
			if ( array_key_exists( $k, $devsettings ) ) {
				$this->setup[ $k ] = $devsettings[ $k ];
			}
		}
	}
	
	
	//~~
	function registerWidgets () {
		register_widget( 'MP3_jPlayer' );
		register_widget( 'MP3j_single' );
	}
	
	
	//~~
	function grablibrary_handler ( $x ) {
		return $this->grab_library_info( $x );
	}

	
	//~~
	function scripts_tag_handler ( $style = "" ) {
		$this->stylesheet = ( $style == "" ) ? $this->theSettings['player_theme'] : $style;
		return;
	}
	
	
	//~~
	function checkAddScripts ()	{
		if ( $this->Player_ID > 0 && $this->SCRIPT_CALL === false ) {
			$this->enqueueJS();
		}
	}
	

	//~~
	function createAdminPages ()
	{
		/* extension page (arr)
			--
			[parent] str
			[title] str
			[menuName] str
			[capability] str
			[slug] str
			[drawFunction] str
			['scriptsFunction'] false/str
		*/
				
		//Core Settings pages
		$pluginpage = add_menu_page( 'Settings | MP3 jPlayer', 'MP3 jPlayer', 'manage_options', 'mp3-jplayer', 'mp3j_print_admin_page' ); //root				
		add_submenu_page( 'mp3-jplayer', 'Settings | MP3 jPlayer', 'Settings', 'manage_options', 'mp3-jplayer', 'mp3j_print_admin_page' ); //root in sub
		add_action( 'admin_head-'. $pluginpage, array( $this, 'mp3j_admin_header' ) ); 
		$this->menuHANDLES['parent'] = $pluginpage;
		
		if ( $this->setup['designPage'] === true ) {
			$subm_colours = add_submenu_page( 'mp3-jplayer', 'Design | MP3 jPlayer', 'Design', 'manage_options', 'mp3-jplayer-colours', 'mp3j_print_colours_page' );
			add_action( 'admin_head-'. $subm_colours, array( $this, 'mp3j_admin_colours_header' ) ); 
			$this->dbug['str'] .= 'colours handle: ' . $subm_colours;
			$this->menuHANDLES['design'] = $subm_colours;
		}
		
		//Extension pages
		foreach ( $this->EXTpages as $p ) {
			$submenu = add_submenu_page( $p['parent'], $p['title'], $p['menuName'], $p['capability'], $p['slug'], $p['drawFunction'] );
			$this->menuHANDLES[ $p['slug'] ] = $submenu;
			if ( $p['scriptsFunction'] !== false ) {
				add_action( 'admin_head-'. $submenu, $p['scriptsFunction'] );
			}
		}
		
		//add link to settings on the plugin list dashboard
		add_filter( 'plugin_action_links', array( $this, 'addPluginListLink' ), 10, 2 );		
	}
	
	
	//~~
	function mp3j_admin_header () 
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('mp3jp-settings-js', MP3J_PLUGIN_URL .'/js/admin/settings.2.5.js' );
		wp_enqueue_script('spectrum-CP-js', MP3J_PLUGIN_URL .'/js/spectrum/spectrum.js' );
		
		wp_localize_script( 
			'mp3jp-settings-js',
			'MJPajax',
			array(
				'WPajaxurl' => admin_url('admin-ajax.php')
			)
		);
		
		wp_enqueue_style( 'spectrum-css', MP3J_PLUGIN_URL . '/css/admin/spectrum-custom.css' );
		wp_enqueue_style('mp3jp-settings-css', MP3J_PLUGIN_URL . '/css/admin/admin-settings.css' );
	}
	
	
	//~~
	function mp3j_admin_colours_header ()
	{
		//scripts
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-widget'); 		
		wp_enqueue_script('jquery-ui-mouse');  		
		wp_enqueue_script('jquery-ui-slider');
		wp_enqueue_script('jquery-ui-resizable');
		wp_enqueue_script('mp3jp-colours-js', MP3J_PLUGIN_URL .'/js/admin/admin-colours.2.7.js' );
		wp_enqueue_script('spectrum-CP-js', MP3J_PLUGIN_URL .'/js/spectrum/spectrum.js' );
		
		//styles
		wp_enqueue_style('spectrum-CP-css', MP3J_PLUGIN_URL .'/css/admin/spectrum-custom.css' );
		wp_enqueue_style('mp3jp-colours-css', MP3J_PLUGIN_URL .'/css/admin/admin-colours.css' );
	}
	
	
	//~~
	function header_scripts_handler() {
		$this->enqueueCSS();
		$WPversion = substr( get_bloginfo('version'), 0, 3 );
		if ( $WPversion < 3.3 ) {
			$this->enqueueJS();
		}
		$this->defineJSvars();  
	}
	
	
	//~~
	function footercode_handler()
	{		
		$O = $this->theSettings;
		$JS = '';
		if ( $this->Player_ID > 0 )
		{ 
			$JS .= "\n<script type=\"text/javascript\">\njQuery(document).ready(function () {";
			$JS .= "\n\tif (typeof MP3_JPLAYER !== 'undefined') {";
			
			$JS .= ( $this->FIRST_FORMATS !== 'mp3' ) ? "\n\t\tMP3_JPLAYER.lastformats = '" . $this->FIRST_FORMATS . "';" : "";
			$JS .= "\n\t\tMP3_JPLAYER.plugin_path = '" . MP3J_PLUGIN_URL . "';";
			
			$JS .= ( $O['allowRangeRequests'] !== 'true' ) ? "\n\t\tMP3_JPLAYER.allowRanges = " . $O['allowRangeRequests'] . ";" : "";
			$JS .= "\n\t\tMP3_JPLAYER.pl_info = MP3jPLAYERS;";
			$JS .= ( $O['encode_files'] !== 'true' ) ? "\n\t\tMP3_JPLAYER.vars.play_f = " . $O['encode_files'] . ";" : "";
			$JS .= ( $O['force_browser_dload'] !== 'true' ) ? "\n\t\tMP3_JPLAYER.vars.force_dload = " . $O['force_browser_dload'] . ";" : "";
			$JS .= "\n\t\tMP3_JPLAYER.vars.dload_text = '" . $O['dload_text'] . "';";
			
			$JS .= "\n\t\tMP3_JPLAYER.hasListMeta = " . $O['hasListMeta'] . ";";
			
			if ( $this->setup['stylesheetPopout'] === true ) {
				$JS .= "\n\t\tMP3_JPLAYER.vars.stylesheet_url = '" . $this->PP_css_url . "';";
			}
			if ( $O['force_browser_dload'] == "true" && $O['dloader_remote_path'] !== "" ) {
				$JS .= 	"\n\t\tMP3_JPLAYER.vars.dl_remote_path = '" . $O['dloader_remote_path'] . "';";
			}
			
			if ( $O['autoResume'] == "false" ) {
				$JS .= 	"\n\t\tMP3_JPLAYER.pickup = false;";
			}
			
			$showErrors = $O['showErrors'];
			if ( $showErrors === 'admin' ) {
				$showErrors = ( current_user_can( 'manage_options' ) ) ? 'true' : 'false';
			}
			$JS .= 	"\n\t\tMP3_JPLAYER.showErrors = " . $showErrors . ";";
			
			$JS .= "\n\t\tMP3_JPLAYER.init();"; 
			$JS .= "\n\t}";
			$JS .= "\n});\n</script>\n";
			echo $JS;
		
			if ( $this->setup['cssHead'] === true ) {
				echo $this->writeColoursCSS();
			}
			
			$CSS = "\n\n";
			if ( $O['mp3tColour_on'] === 'true' ) 
			{
				$CSS .= "\n<style>\n";
				
				$CSS .=  "\n 
					span.textbutton_mp3j {
						color:" . $O['mp3tColour'] . "; 
					}
					span.vol_mp3t .ui-slider-handle,
					span.vol_mp3t .ui-slider-handle:hover,
					span.posbar_mp3j .ui-slider-handle,
					span.posbar_mp3j .ui-slider-handle:hover,
					.load_mp3j,
					.s-text .Smp3-tint {
						background-color:" . $O['mp3tColour'] . ";
						border-color:" . $O['mp3tColour'] . ";
					}
					.s-text .Smp3-tint {
						opacity:0.4;
						filter:alpha(opacity=40);
					}
					.s-text .Smp3-finding,
					.s-text .mp3-gtint,
					.load_mp3j {
						opacity:0.25;
						filter:alpha(opacity=25);
					}
					span.posbar_mp3j .ui-slider-handle,
					span.posbar_mp3j .ui-slider-handle:hover {
						background:rgba(" . $this->hexToRGB ( $O['mp3tColour'], true ) . ",0.3);
						border-color:" . $O['mp3tColour'] . ";
					}";
				
				$CSS .= "\n</style>\n";
			}
			
			if ( $O['mp3jColour_on'] === 'true' ) 
			{
				$CSS .= "\n<style>\n";
				
				$CSS .= "
					span.gfxbutton_mp3j.play-mjp, 
					span.gfxbutton_mp3j.pause-mjp,
					span.gfxbutton_mp3j.play-mjp:hover, 
					span.gfxbutton_mp3j.pause-mjp:hover,
					span.vol_mp3j .ui-slider-handle,
					span.vol_mp3j .ui-slider-handle:hover,
					span.posbarB_mp3j .ui-slider-handle,
					span.posbarB_mp3j .ui-slider-handle:hover,
					.loadB_mp3j,
					.s-graphic .Smp3-tint { 
						background-color:" . $O['mp3jColour'] . "; 
						border-color:" . $O['mp3jColour'] . "; 
					}
					.s-graphic .Smp3-tint {
						opacity:0.4;
						filter:alpha(opacity=40);
					}
					.s-graphic .Smp3-finding,
					.s-graphic .mp3-gtint,
					.loadB_mp3j {
						opacity:0.25;
						filter:alpha(opacity=25);
					}
					span.posbarB_mp3j .ui-slider-handle,
					span.posbarB_mp3j .ui-slider-handle:hover {
						background:rgba(" . $this->hexToRGB ( $O['mp3jColour'], true ) . ",0.3);
						border-color:" . $O['mp3jColour'] . ";
					}";
				
				$CSS .= "\n</style>\n";
			}
			
			echo $CSS;
		}
		
		// Write debug
		if ( $O['echo_debug'] == "true" ) { 
			$this->debug_info(); 
		}
		return;	
	}
	
	
/* Work out playlist for single players. */
	function decide_S_playlist( $track, $caption, $counterpart = '', $ids = '' )
	{		
		$TRACKS = false;
		$trackNumber = '';
		$listJS = '';
		
		global $post;
		$currentID = ( ! empty( $post->ID ) ) ? $post->ID : '';
		if ( $currentID !== $this->currentID ) {
			$this->S_arb = 1;
			$this->currentID = $currentID;
		}
		
		if ( $ids !== '' ) //came via [audio] shortcode, has 1 attachment
		{
			$TRACKS = $this->IDsToTracks( $ids, 'true' );
			if ( ! $TRACKS ) {
				return false;
			}
			$track = 1;					
			$playername = "inline_" . $this->S_no++;
			$trackNumber = ( $this->theSettings['add_track_numbering'] == "true" ) ? $this->S_arb . '. ' : '';
			$listJS = $this->writePlaylistJS( $TRACKS, $playername, $this->S_arb++ );
		}
		else
		{
			if ( $track == "" ) // Auto increment 
			{
				if ( ! $this->checkGrabFields() || $this->Caller == "widget" || $this->F_listlength <= $this->S_autotrack ) {
					return false;
				}
				$track = ++$this->S_autotrack;
				$playername = $this->F_listname;
				$trackNumber = ( $this->theSettings['add_track_numbering'] == "true" ) ? $track . '. ' : '';
				$TRACKS = $this->F_LISTS[ $playername ];
			}
			elseif ( is_numeric($track) ) // Has a track number
			{
				if ( ! $this->checkGrabFields() || $this->Caller == "widget" || $this->F_listlength < $track ) { 
					return false; 
				}
				$playername = $this->F_listname;
				$trackNumber = ( $this->theSettings['add_track_numbering'] == "true" ) ? $track . '. ' : '';
				$TRACKS = $this->F_LISTS[ $playername ];
			} 
			else // Has arbitrary file/uri				
			{
				$TRACKS = $this->stringsToTracks( $track, $counterpart, $caption );
				if ( !$TRACKS ) { 
					return false;
				}
				$track = 1;					
				$playername = "inline_" . $this->S_no++;
				$trackNumber = ( $this->theSettings['add_track_numbering'] == "true" ) ? $this->S_arb . '. ' : '';
				$listJS = $this->writePlaylistJS( $TRACKS, $playername, $this->S_arb++ );
			}
		}
		
		return array( 
			'track' => $track, 
			'playername' => $playername,
			'playlist' => $TRACKS,
			'trackNumber' => $trackNumber,
			'listJS' => $listJS
		);		
	}


/*	Handles [mp3t] shortcodes 
	single players with text buttons. */	
	function inline_play_handler( $atts, $content = null ) {
		
		$this->dbug['str'] .= "\n### Checking [mp3t]...";
		if ( ! $this->canRun() ) {
			return;
		}
		
		//Extensions - pre filter params hook.
		$atts = MJPfront_mp3t( $atts );
		
		//Merge user/default params.
		$defaults = $this->playerDefaultParams( 'mp3t' );
		extract( shortcode_atts( $defaults, $atts ) );
		
		//Alias some params.
		if ( $track == '' && $tracks != '' ) {
			$track = $tracks;
		}
		if ( $counterpart == '' && $counterparts != '' ) {
			$counterpart = $counterparts;
		}
		$cssclass = $style;
				
		//Try make a playlist
		$tn = $this->decide_S_playlist( $track, $caption, $counterpart, $ids );
		if ( !$tn ) { 
			$this->dbug['str'] .= "\nExiting (no track here)";
			return;
		}
		
		$C =  $this->theSettings['colour_settings'];
		$id = $this->Player_ID;			
		
		$CSSext = '-mjp';
		$font1Class = 	( $C['font_family_1'] === 'theme' ) 	? '' : ' ' . $C['font_family_1'] . $CSSext;
		
		$divO = '<span id="mp3jWrap_' . $id. '" class="mjp-s-wrapper s-text unsel-mjp ' . $cssclass . $font1Class . '">';
		$divC = "</span>";
		$b = "";
		if ( $flow == "n" || $this->Caller == "widget" ) {
			$divO = '<div id="mp3jWrap_' . $id. '" class="mjp-s-wrapper s-text unsel-mjp ' . $cssclass . $font1Class . '" style="font-size:' .$fontsize. ';">';
			$divC = "</div>";
		}
		
		// Set font weight
		$b = ( $bold == "false" || $bold == "0" || $bold == "n" ) ? " style=\"font-weight:500;\"" : " style=\"font-weight:700;\"";
		
		// Set spacer between elements depending on play/stop/title
		if ( $play != "" && $title != "" ){	
			$spacer = "&nbsp;"; 
		} else {
			$spacer = "";
			if ( $play == "" && $stop != "" ) { $stop = " " . $stop; }
		}
	
		// Prep title
		if ( $title === '' ) { //user specifically has blanked it
			$outputTitle = '';
		}
		else {
			if ( $title == "#USE#" ) { //get the one from the playlist
				$outputTitle = $tn['trackNumber'] . $tn['playlist'][($tn['track'] -1)]['title'] . ( ! empty($tn['playlist'][($tn['track'] -1)]['caption'] ) ? '<span> - ' . $tn['playlist'][($tn['track'] -1)]['caption'] . '</span>' : '' );
			}
			else { //user entered one, use it
				$outputTitle = $tn['trackNumber'] . $title;
			}
		}
				
		// Make id'd span elements
		$openWrap = $divO . "<span id=\"playpause_wrap_mp3j_" . $id . "\" class=\"wrap_inline_mp3j\"" . $b . ">";
		$vol_h = ( $volslider == 'true' || $volslider == 'Y' || $volslider == 'y' ) ? "<span class=\"vol_mp3t\" id=\"vol_mp3j_" . $id . "\"></span>" : "";
		$pos = "<span class=\"bars_mp3j\"><span class=\"load_mp3j\" id=\"load_mp3j_" . $id . "\"></span><span class=\"posbar_mp3j\" id=\"posbar_mp3j_" . $id. "\"></span>" . $vol_h  . "</span>";
		$play_h = "<span class=\"textbutton_mp3j play-mjp\" style=\"font-size:" .$fontsize. ";\" id=\"playpause_mp3j_" . $id . "\">" . $play . "</span>";
		
		$title_h = ( $title != "" ) ? "<span class=\"T_mp3j\" style=\"font-size:" .$fontsize. ";\" id=\"T_mp3j_" . $id . "\">" . $outputTitle . "</span>" : "";
		
		$closeWrap = ( $ind != "y" ) ? "<span style=\"display:none;\" id=\"statusMI_" . $id . "\"></span></span>" . $divC : "<span class=\"indi_mp3j\" style=\"font-size:" .(intval($fontsize)*0.7) . "px;\" id=\"statusMI_" . $id . "\"></span></span>" . $divC;
		$errorMsg =	"<span class=\"s-nosolution\" id=\"mp3j_nosolution_" . $id . "\" style=\"display:none;\"></span>";
				
		// Assemble them		
		$html = ( $flip != "false" ) ? $openWrap . $pos . $title_h . $spacer . $play_h . $closeWrap . $errorMsg : $openWrap . $pos . $play_h . $spacer . $title_h . $closeWrap . $errorMsg;
		
		// Add info to js info array
		$autoplay = ( $autoplay == "true" || $autoplay == "y" || $autoplay == "1" ) ? "true" : "false";
		$loop = ( $loop == "true" || $loop == "y" || $loop == "1" ) ? "true" : "false";
		$dload = ( $dload == "true" || $dload == "y" || $dload == "1" ) ? "true" : "false";
		
		$playerInfo = "{ list: MP3jPLAYLISTS." . $tn['playername'] . ", tr: " . ($tn['track']-1) . ", type: 'single', lstate: '', loop: " . $loop . ", play_txt: '" . $play . "', pause_txt: '" . $stop . "', pp_title: '', autoplay:" . $autoplay . ", download:" . $dload . ", vol: " . $vol . ", height: '' }";
		$playerJS = "<script>MP3jPLAYERS[" .$id. "] = " . $playerInfo . ";</script>";
		
		$this->dbug['str'] .= "\nOK (id " . $this->Player_ID . ")";
		$this->Player_ID++;
		return $html . $tn['listJS'] . $playerJS; 
	}
		
			
/*	Handles [mp3j] shortcodes.
	single players with button graphic */	
	function inline_play_graphic( $atts, $content = null ) {
		
		$this->dbug['str'] .= "\n### Checking [mp3j]...";
		if ( ! $this->canRun() ) {
			return;
		}
		
		//Extensions - pre filter params hook.
		$atts = MJPfront_mp3j( $atts );
		
		//Merge user/default params.
		$defaults = $this->playerDefaultParams( 'mp3j' );
		extract( shortcode_atts( $defaults, $atts ) );
		
		//Alias some params.
		if ( $track == '' && $tracks != '' ) {
			$track = $tracks;
		}
		if ( $counterpart == '' && $counterparts != '' ) {
			$counterpart = $counterparts;
		}
		$cssclass = $style;
		
		$tn = $this->decide_S_playlist( $track, $caption, $counterpart, $ids );
		if ( !$tn ) { 
			$this->dbug['str'] .= "\nExiting (no track here)";
			return;
		}
				
		$C =  $this->theSettings['colour_settings'];
		$id = $this->Player_ID;	
		
		$CSSext = '-mjp';
		$font1Class = 	( $C['font_family_1'] === 'theme' ) 	? '' : ' ' . $C['font_family_1'] . $CSSext;
		
		$divO = '<span id="mp3jWrap_' . $id. '" class="mjp-s-wrapper s-graphic unsel-mjp ' . $cssclass . $font1Class . '">';
		$divC = "</span>";
		$b = "";
		if ( $flow == "n" || $this->Caller == "widget" ) {
			$divO = '<div id="mp3jWrap_' . $id. '" class="mjp-s-wrapper s-graphic unsel-mjp ' . $cssclass . $font1Class . '" style="font-size:' .$fontsize. ';">';
			$divC = "</div>";
		}
	
		// Set font weight
		$b = ( $bold == "false" || $bold == "N" || $bold == "n" ) ? " style=\"font-weight:500;\"" : " style=\"font-weight:700;\"";
	
		// Prep title
		if ( $title === '' ) { //user specifically has blanked it
			$outputTitle = '';
		}
		else {
			if ( $title == "#USE#" ) { //get the one from the playlist
				$outputTitle = $tn['trackNumber'] . $tn['playlist'][($tn['track'] -1)]['title'] . ( ! empty($tn['playlist'][($tn['track'] -1)]['caption'] ) ? '<span> - ' . $tn['playlist'][($tn['track'] -1)]['caption'] . '</span>' : '' );
			}
			else { //user entered one, use it
				$outputTitle = $tn['trackNumber'] . $title;
			}
		}
		
		// Make id'd span elements
		$flippedcss = ( $flip == "false" ) ? "" : " flipped";
		$openWrap = $divO . "<span id=\"playpause_wrap_mp3j_" . $id . "\" class=\"wrap_inline_mp3j\"" . $b . ">";
		$vol_h = ( $volslider == 'true' || $volslider == 'y' || $volslider == 'Y' ) ? "<span class=\"vol_mp3j" . $flippedcss . "\" id=\"vol_mp3j_" . $id . "\"></span>" : "";
		$pos = "<span class=\"bars_mp3j\"><span class=\"loadB_mp3j\" id=\"load_mp3j_" . $id . "\"></span><span class=\"posbarB_mp3j\" id=\"posbar_mp3j_" . $id . "\"></span></span>";
		$play_h = "<span class=\"gfxbutton_mp3j play-mjp\" id=\"playpause_mp3j_" . $id . "\" style=\"font-size:" .$fontsize. ";\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
		$spacer = "";
		
		$title_h = ( $title != "" ) ? "<span class=\"T_mp3j\" id=\"T_mp3j_" . $id . "\" style=\"font-size:" .$fontsize. ";\">" . $outputTitle . "</span>" : "";
		$indi_h = ( $ind != "y" ) ? "<span style=\"display:none;\" id=\"statusMI_" . $id . "\"></span>" : "<span class=\"indi_mp3j\" style=\"font-size:" .(intval($fontsize)*0.7) . "px;\" id=\"statusMI_" . $id . "\"></span>";
		$errorMsg =	"<span class=\"s-nosolution\" id=\"mp3j_nosolution_" . $id . "\" style=\"display:none;\"></span>";
		// Assemble them		
		$html = ( $flip == "false" ) ? $openWrap . "<span class=\"group_wrap\">" . $pos . $title_h . $indi_h . "</span>" . $play_h . $vol_h . "</span>" . $divC . $errorMsg : $openWrap . $play_h . "&nbsp;<span class=\"group_wrap\">" . $pos . $title_h . $indi_h . "</span>" . $vol_h . "</span>" . $divC . $errorMsg;
		
		// Add info to js info array
		$autoplay = ( $autoplay == "true" || $autoplay == "y" || $autoplay == "1" ) ? "true" : "false";
		$loop = ( $loop == "true" || $loop == "y" || $loop == "1" ) ? "true" : "false";
		$dload = ( $dload == "true" || $dload == "y" || $dload == "1" ) ? "true" : "false";
		
		$playerInfo = "{ list: MP3jPLAYLISTS." . $tn['playername'] . ", tr:" . ($tn['track']-1) . ", type:'single', lstate:'', loop:" . $loop . ", play_txt:'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', pause_txt:'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', pp_title:'', autoplay:" . $autoplay . ", download:" . $dload . ", vol:" . $vol . ", height:'' }";
		$playerJS = "<script>MP3jPLAYERS[" .$id. "] = " . $playerInfo . ";</script>";
		
		$this->dbug['str'] .= "\nOK (id " . $this->Player_ID . ")";
		$this->Player_ID++;
		return $html . $tn['listJS'] . $playerJS;
	}	


//###############
	function getPostImageUrl ( $postID )
	{
		$thumb_id = get_post_thumbnail_id( $postID );
		if ( ! empty( $thumb_id ) ) {
			$size = ( $this->theSettings['imageSize'] === 'autoW' || $this->theSettings['imageSize'] === 'autoH' ) ? 'large' : $this->theSettings['imageSize'];
			$imageInfo = wp_get_attachment_image_src( $thumb_id, $size );
			$url = $imageInfo[0];
		} else {
			$url = 'false';
		}
		return $url;
	}
	

//###############	
	function getPostAttachedAudio ( $postID )
	{
		$O = $this->theSettings;
		
		if ( 'title' === $O['library_sortcol'] ) {
			$sortcol = 'title';
		} else if ( 'date' === $O['library_sortcol'] ) {
			$sortcol = 'post_date';
		} else {
			$sortcol = 'menu_order';
		}		
		
		$args = array(
			'post_type'   	=> 'attachment',
			'numberposts' 	=> -1,
			'post_status' 	=> 'any',
			'post_parent' 	=> $postID,
			'orderby'		=> $sortcol,
			'order'			=> $O['library_direction']
		);
		
		$attachments = get_posts( $args );
		$audio = array();
		
		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				if ( $this->isAllowedMimeType( $attachment->post_mime_type ) ) {
					$audio[] = $attachment;
				}
			}
		}
		return ( ( ! empty($audio) && is_array($audio) ) ? $audio : false );
	}
	

//###############
	function isAllowedMimeType ( $mimeType )
	{
		return ( (stripos( $mimeType, 'audio' ) === 0 ) ? true : false );
	}
	
	
//####	Work out playlist for playlist players
	function decide_M_playlist( $ATTS )
	{
		extract( $ATTS );		
		$TRACKS = $this->stringsToTracks( $tracks, $counterparts, $captions, $images, $imglinks );
		if ( ! $TRACKS )
		{ 
			if ( $tracks != "" && $id == "" && $ids == "" ) { //if tracks was the specified param, then don't fallback
				return false;
			}
			if ( $id == "" && $ids == "" && (is_home() || is_archive() || is_search()) && $this->Caller == "widget" ) { //dont allow widgets to try mode 1 on index pages
				return false;
			} 
			
			if ( $ids !== '' ) { //got media item id list (post ids)
				$TRACKS = $this->IDsToTracks( $ids, $images );
				if ( ! $TRACKS ) {
					return false;
				}
			} 
			else {
				//Try pick up fields either from another id or this post if id is empty. Do
				//this before attachments for backwards compatibility!
				$TRACKS = $this->customFieldsToTracks( $id );
				if ( ! $TRACKS ) {
					//Possible last resort - could look for attachments here too so that
					//users using the [mp3-jplayer] name can also pick them up?..
					//
					//..Not for the mo, keeping it so that attachments must be in 
					// a pre-built param (either 'ids' or 'tracks') which is done by 
					// the ui-widget(mode 0) or replaceAudioShortcode handler.
					return false;
				}
			}
		}
		
		if ( $pick != "" && $pick >= 1 ) { 
			$TRACKS = $this->pickTracks( $pick, $TRACKS );
		}
		if ( $shuffle && is_array( $TRACKS ) ) { 
			shuffle( $TRACKS ); 
		}
		return $TRACKS;
	}


//#############
	function replaceAudioShortcode (  $attr, $content = '' )
	{
		/*	WP 4.0 [audio] shortcode attributes:	
		 *	---
		 *     @type string $src      URL to the source of the audio file. Default empty.
		 *     @type string $loop     The 'loop' attribute for the `<audio>` element. Default empty.
		 *     @type string $autoplay The 'autoplay' attribute for the `<audio>` element. Default empty.
		 *     @type string $preload  The 'preload' attribute for the `<audio>` element. Default empty.
		 *     @type string $class    The 'class' attribute for the `<audio>` element. Default 'wp-audio-shortcode'.
		 *     @type string $id       The 'id' attribute for the `<audio>` element. Default 'audio-{$post_id}-{$instances}'.
		 *     @type string $style    The 'style' attribute for the `<audio>` element. Default 'width: 100%'.
		*/
		
		$ops = $this->theSettings;
		$passToWP = true;
		$url = '';
		$i = 0;
		$output = '';
				
		if ( ! empty($attr['src']) ) //url text or embed shortcode
		{
			if ( $ops['replace_WP_embedded'] === 'true' ) {			
				$url = $attr['src'];
				$passToWP = false;
			}
		} 
		else //direct audio shortcode
		{
			if ( $ops['replace_WP_audio'] === 'true' )
			{			
				if ( ! empty($attr['mp3']) ) {
					$url = $attr['mp3'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['mp4']) ) {
					$url = $attr['mp4'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['m4a']) ) {
					$url = $attr['m4a'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['ogg']) ) {
					$url = $attr['ogg'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['oga']) ) {
					$url = $attr['oga'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['wav']) ) {
					$url = $attr['wav'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['webm']) ) {
					$url = $attr['webm'];
					$passToWP = false;
				}
				elseif ( ! empty($attr['track']) || ! empty($attr['tracks']) ) { //support user added 'track' param
					$url = ( ! empty($attr['track']) ) ? $attr['track'] : $attr['tracks'];
					$passToWP = false;
				}
				else { 
					//nothing specified - will fallback to attachments, then custom fields (it's ok to fallback to
					//fields last as new users are unlikely to use them and old users may want the functionality).
				}
			}
			
			if ( $url === '' && $ops['replace_WP_attached'] === 'true' )
			{
				global $post;
				$attachments = $this->getPostAttachedAudio( $post->ID );
				
				$images = '';
				$ids = '';
				if ( $attachments !== false ) {
					$images = 'true';
					foreach ( $attachments as $a ) {
						$ids .= $a->ID . ',';
						$i++;
					}
				}
				$attr['ids'] = $ids;
				if ( empty($attr['images']) || $attr['images'] !== 'false' ) {
					$attr['images'] = $images;
				}
				$passToWP = false;
			}
		}
		
		if ( $passToWP ) { //bailout
			$output = $content;
			if ( function_exists('wp_audio_shortcode') ) { //let WP process it
				$output = wp_audio_shortcode( $attr, $content );
			}
			return $output;
		}
		
		//alias the params
		$attr['track'] = $url;
		$attr['loop'] = ( empty($attr['loop']) ) ? 'n' : 'y';
		$attr['autoplay'] = ( empty($attr['autoplay']) ) ? 'n' : 'y';
		$attr['text'] = ( ! empty($attr['title']) && empty($attr['text']) ) ? $attr['title'] : ( empty($attr['text']) ? '' : $attr['text'] ); //popout link text
				
		//process it
		$ops = $this->theSettings;
		if ( $i > 1 ) 
		{
			$output = ( $ops['replacerShortcode_playlist'] === 'player' ) ? $this->primary_player( $attr, $content ) : $this->popout_link_player( $attr, $content );
		} 
		else 
		{
			if ( $ops['replacerShortcode_single'] === 'mp3j' ) {
				$output = $this->inline_play_graphic( $attr, $content );
			}
			elseif ( $ops['replacerShortcode_single'] === 'mp3t' ) {
				$output = $this->inline_play_handler( $attr, $content );
			}
			elseif ( $ops['replacerShortcode_single'] === 'popout' ) {
				$output = $this->popout_link_player( $attr, $content );
			}
			else {
				$output = $this->primary_player( $attr, $content );
			}
		}
		return $output;
	}


//##############
	function replacePlaylistShortcode ( $attr, $content = '' )
	{
		/*	WP 4.0 [playlist] shortcode attributes:	
		 *	---
		 *     @type string  $type         Type of playlist to display. Accepts 'audio' or 'video'. Default 'audio'.
		 *     @type string  $order        Designates ascending or descending order of items in the playlist.
		 *                                 Accepts 'ASC', 'DESC', or 'RAND'. Default 'ASC'.
		 *     @type string  $orderby      Any column, or columns, to sort the playlist. If $ids are
		 *                                 passed, this defaults to the order of the $ids array ('post__in').
		 *                                 Otherwise default is 'menu_order ID'.
		 *     @type int     $id           If an explicit $ids array is not present, this parameter
		 *                                 will determine which attachments are used for the playlist.
		 *                                 Default is the current post ID.
		 *     @type array   $ids          Create a playlist out of these explicit attachment IDs. If empty,
		 *                                 a playlist will be created from all $type attachments of $id.
		 *                                 Default empty.
		 *     @type array   $exclude      List of specific attachment IDs to exclude from the playlist. Default empty.
		 *     @type string  $style        Playlist style to use. Accepts 'light' or 'dark'. Default 'light'.
		 *     @type bool    $tracklist    Whether to show or hide the playlist. Default true.
		 *     @type bool    $tracknumbers Whether to show or hide the numbers next to entries in the playlist. Default true.
		 *     @type bool    $images       Show or hide the video or audio thumbnail (Featured Image/post
		 *                                 thumbnail). Default true.
		 *     @type bool    $artists      Whether to show or hide artist name in the playlist. Default true.
		*/
		
		if ( isset($attr['type']) && 'video' === $attr['type'] ) //bailout
		{
			$output = $content;
			if ( function_exists('wp_playlist_shortcode') ) //let WP process video
			{
				$output = wp_playlist_shortcode( $attr );
			}
			return $output;
		}
		
		//alias the params
		if ( ! isset( $attr['list'] ) ) {
			$attr['list'] = ( isset($attr['tracklist']) && $attr['tracklist'] === 'false' ) ? 'n' : 'y';
		}
		if ( ! isset( $attr['captions'] ) ) {
			$attr['captions'] = ( isset($attr['artists']) && $attr['artists'] === 'false' ) ? false : '';
		}
		$attr['images'] = ( !isset($attr['images']) ) ? 'true' : $attr['images'];
		$attr['text'] = ( ! empty($attr['title']) && empty($attr['text']) ) ? $attr['title'] : ( empty($attr['text']) ? '' : $attr['text'] ); //popout link text
		
		//process it
		$ops = $this->theSettings;
		return ( $ops['replacerShortcode_playlist'] === 'player' ? $this->primary_player( $attr, $content ) : $this->popout_link_player( $attr, $content ) );
	}


//#####	Handles [mp3-jplayer] playlist player shortcodes	
	function primary_player ( $atts, $content = null )
	{		
		//Bailout
		$this->dbug['str'] .= "\n### Checking [mp3-jplayer]...";
		if ( ! $this->canRun() ) {
			return;
		}
		
		//Extensions - Send the pre filtered params.
		$atts = MJPfront_playlist_player( $atts );
		
		$O = $this->theSettings;
		$pID = $this->Player_ID;
		
		$defaults = $this->playerDefaultParams( 'playlist' );
		$ATTS = shortcode_atts( $defaults, $atts );
				
		//Alias params
		if ( $ATTS['tracks'] == '' && $ATTS['track'] != '' ) { 
			$ATTS['tracks'] = $ATTS['track'];
		}
		if ( $ATTS['counterparts'] == '' && $ATTS['counterpart'] != '' ) { 
			$ATTS['counterparts'] = $ATTS['counterpart'];
		}
		$ATTS['userClasses'] = $O['colour_settings']['userClasses'] . ' ' . $ATTS['style'];
		
		//Try build a playlist
		$TRACKS = $this->decide_M_playlist( $ATTS );
		if ( !$TRACKS ) { 
			$this->dbug['str'] .= "\nExiting (no tracks here)";
			return;
		}
		$ATTS['trackCount'] = count( $TRACKS );
		
		//Make js list
		$PlayerName = "MI_" . $pID; 
		$listJS = $this->writePlaylistJS( $TRACKS, $PlayerName );
		
		//Make settings..
		$trnum = 0;
		$pp_height = (int)$ATTS['height'];
		$pp_height = ( empty($pp_height) || $pp_height === 0 ) ? 'false' : $pp_height;
		$pp_title = ( $ATTS['title'] == "" ) ? get_bloginfo('name') : $ATTS['title'] . " | " . get_bloginfo('name');
		$pp_title = str_replace("'", "\'", $pp_title);
		$pp_title = str_replace("&#039;", "\'", $pp_title);
		$ATTS['list'] = ( $ATTS['list'] == "true" || $ATTS['list'] == "y" || $ATTS['list'] == "1" ) ? "true" : "false";	
		
		if ( $ATTS['dload'] == "true" || $ATTS['dload'] == "y" || $ATTS['dload'] == "1"  ) {
			$dload_info = "true";
			$ATTS['dload_html'] = "<div id=\"download_mp3j_" . $pID . "\" class=\"dloadmp3-MI\"></div>";
		} elseif ( $ATTS['dload'] == "loggedin" ) {
			if ( is_user_logged_in() ) {
				$dload_info = "true";
				$ATTS['dload_html'] = "<div id=\"download_mp3j_" . $pID . "\" class=\"dloadmp3-MI\"></div>";
			} else {
				$dload_info = "false";
				if ( $O['loggedout_dload_text'] == "" ) {
					$ATTS['dload_html'] = "";
				} else {
					if ( $O['loggedout_dload_link'] != "" ) {
						$ATTS['dload_html'] = "<div id=\"download_mp3j_" . $pID . "\" class=\"dloadmp3-MI whilelinks\"><a href=\"" . $O['loggedout_dload_link'] . "\">" . $O['loggedout_dload_text'] . "</a></div>";
					} else {
						$ATTS['dload_html'] = "<div id=\"download_mp3j_" . $pID . "\" class=\"dloadmp3-MI logintext\"><p>" . $O['loggedout_dload_text'] . "</p></div>";
					}
				}
			}
		} else {
			$dload_info = "false";
			$ATTS['dload_html'] = "";
		}
		
		$ATTS['autoplay'] = ( $ATTS['autoplay'] == "true" || $ATTS['autoplay'] == "y" || $ATTS['autoplay'] == "1" ) ? "true" : "false";
		$ATTS['loop'] = ( $ATTS['loop'] == "true" || $ATTS['loop'] == "y" || $ATTS['loop'] == "1" ) ? "true" : "false";
		
		//Make transport buttons
		$ATTS['prevnext'] = ( $ATTS['trackCount'] > 1 && $ATTS['pn'] == "y" ) ? "<div class=\"next-mjp\" id=\"Next_mp3j_" . $pID . "\">Next&raquo;</div><div class=\"prev-mjp\" id=\"Prev_mp3j_" . $pID . "\">&laquo;Prev</div>" : "";
		$ATTS['play_h'] = "<div class=\"play-mjp\" id=\"playpause_mp3j_" . $pID . "\">Play</div>";
		$ATTS['stop_h'] = ( $ATTS['stop'] == "y" ) ? "<div class=\"stop-mjp\" id=\"stop_mp3j_" . $pID . "\">Stop</div>" : "";
	
		//Build player html
		$ATTS['width'] = ( $this->Caller && $ATTS['width'] == "" ) ? "100%" : $ATTS['width']; //set a default width when called by tag/sc-widget and it wasn't specified
		$player = $this->drawPlaylistPlayer( $ATTS );
		
		//js player info
		$popoutcss = ( $this->setup['cssPopout'] === true ) ? "{ enabled:true, " .$player['js']. "}" : "{ enabled:false, " .$player['js']. "}";
		$playerInfo = "{ list:MP3jPLAYLISTS." .$PlayerName. ", tr:" .$trnum. ", type:'MI', lstate:" .$ATTS['list']. ", loop:" .$ATTS['loop']. ", play_txt:'Play', pause_txt:'Pause', pp_title:'" .$pp_title. "', autoplay:" .$ATTS['autoplay']. ", download:" .$dload_info. ", vol:" .$ATTS['vol']. ", height:" .$pp_height. ", cssclass:'" .$ATTS['userClasses']. "', popout_css:" .$popoutcss. " }";
		$playerJS = "<script>MP3jPLAYERS[" .$pID. "] = " . $playerInfo . ";</script>\n\n";
		
		
		//Finish up
		$this->dbug['str'] .= "\nOK (id " . $this->Player_ID . ")";
		$this->Player_ID++;
		
		return $player['html'] . $listJS . $playerJS;
	}


/*	Handles [mp3-popout] shortcode
	link to popout player. */	
	function popout_link_player ( $atts, $content = null )
	{
		//bailout conditions
		$this->dbug['str'] .= "\n### Checking [mp3-popout]...";
		if ( ! $this->canRun() ) {
			return;
		}
		
		//Extensions - Send the pre filtered params.
		$atts = MJPfront_popout_player( $atts );
		
		$O = $this->theSettings;
		$pID = $this->Player_ID;
			
		$defaults = $this->playerDefaultParams( 'popout' );
		$ATTS = shortcode_atts( $defaults, $atts );

		//Alias some params
		if ( $ATTS['tracks'] == '' && $ATTS['track'] != '' ) {
			$ATTS['tracks'] = $ATTS['track'];
		}
		if ( $ATTS['counterparts'] == '' && $ATTS['counterpart'] != '' ) {
			$ATTS['counterparts'] = $ATTS['counterpart'];
		}
		$ATTS['userClasses'] = $O['colour_settings']['userClasses'] . ' ' . $ATTS['style'];
		
		$ATTS['pptext'] = $ATTS['text'];
		
		//Try build a playlist
		$TRACKS = $this->decide_M_playlist( $ATTS );
		if ( !$TRACKS ) { 
			$this->dbug['str'] .= "\nExiting (no tracks here)";
			return;
		}
		$ATTS['trackCount'] = count( $TRACKS );
		
		extract( $ATTS );
		
		//Make js list
		$PlayerName = "popout_" . $pID; 
		$listJS = $this->writePlaylistJS( $TRACKS, $PlayerName );
		
		//Make settings..
		$pp_height = (int)$height;
		$pp_height = ( empty($pp_height) || $pp_height === 0 ) ? 'false' : $pp_height;
		$pp_title = ( $title == "" ) ? get_bloginfo('name') : $title;
		$pp_title = str_replace("'", "\'", $pp_title);
		$pp_title = str_replace("&#039;", "\'", $pp_title);
		$list = ( $list == "true" || $list == "y" || $list == "1" ) ? "true" : "false";
		$dload_info = ( $dload == "true" || $dload == "y" || $dload == "1" ) ? "true" : "false";
		$autoplay = ( $autoplay == "true" || $autoplay == "y" || $autoplay == "1" ) ? "true" : "false";
		$loop = ( $loop == "true" || $loop == "y" || $loop == "1" ) ? "true" : "false";
		
		
		//Make player	
		$image_h = ( $image === "" ) ? '<div class="popout-image-mjp"></div>' : '<img class="popout-image-mjp-custom" src="' . $image . '" />';
		$text_h = ( $text !== "" ) ? '<div class="popout-text-mjp"><'.$tag.'>' . $text . '</'.$tag.'></div>' : '';
		
		$player = '<div class="popout-wrap-mjp unsel-mjp" id="mp3j_popout_' . $pID . '">';
		$player .= $image_h . $text_h;
		$player .= '</div>';
		

		$output = $this->drawPlaylistPlayer( $ATTS, true );
		//js player info
		$popoutcss = ( $this->setup['cssPopout'] === true ) ? "{ enabled:true, " .$output['js']. "}" : "{ enabled:false, " .$output['js']. "}";
		$playerInfo = "{ list: MP3jPLAYLISTS." . $PlayerName . ", tr:0, type:'popout', lstate:" . $list . ", loop:" . $loop . ", play_txt:'Play', pause_txt:'Pause', pp_title:'" . $pp_title . "', autoplay:" . $autoplay . ", download:" . $dload_info . ", vol:" . $vol . ", height:" . $pp_height . ", cssclass: '" . $ATTS['userClasses'] . "', popout_css:" .$popoutcss. " }";
		$playerJS = "<script>MP3jPLAYERS[" .$pID. "] = " . $playerInfo . ";</script>\n\n";
		
		//Finish up
		$this->dbug['str'] .= "\nOK (id " . $this->Player_ID . ")";
		$this->Player_ID++;
		
		return $player . $listJS . $playerJS;
	}


	//~~
	function template_tag_handler( $stuff = "" ) {
		if ( ! empty( $stuff ) ) {
			$this->checkGrabFields(); //for singles
			$this->Caller = "tag";
			$players = do_shortcode( $stuff );				
			$this->Caller = false;
			echo $players;
		}
		return;			
	}
		

	//~~
	function checkGrabFields ()
	{
		global $post;
		if ( ! empty( $post->ID ) && $post->ID != $this->postID )
		{
			$this->postID = $post->ID; 
			$this->F_listname = false; 
			$this->F_listlength = false; 
			$this->S_autotrack = 0;
			$this->dbug['str'] .= "\nLooking in custom fields on post id " . $this->postID . " - ";
			
			$TRACKS = $this->customFieldsToTracks( $post->ID );
			if ( $TRACKS ) {
				$count = count( $TRACKS );
				$this->F_listname = "fields_" . $this->F_no++;
				echo $this->writePlaylistJS( $TRACKS, $this->F_listname );
				$this->F_listlength = $count;
				$this->F_LISTS[ $this->F_listname ] = $TRACKS;
				$this->dbug['str'] .= "\nDone, " . $this->F_listlength . " track(s) found.";
			}
		}
		return $this->F_listname;
	}
	
} //end class
?>