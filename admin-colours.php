<?php

function mp3j_print_colours_page()
{ 
	global $MP3JP;
	$O = $MP3JP->getAdminOptions();
	$openTab = '0';
	
	if ( isset( $_POST['save_MP3JP'] ) )
	{
		//colours array
		foreach ( $O['colour_settings'] as $key => $val ) {
			if ( isset($_POST[ $key ]) ) {
				$O['colour_settings'][ $key ] = $_POST[ $key ];
			}
		}
		
		//TODO: move these out of colours array?
		$O['colour_settings']['titleBold'] 	 = ( isset($_POST['titleBold']) ) 	? "true" : "false";
		$O['colour_settings']['titleHide'] 	 = ( isset($_POST['titleHide']) ) 	? "true" : "false";
		$O['colour_settings']['titleItalic'] = ( isset($_POST['titleItalic']) ) ? "true" : "false";
		$O['colour_settings']['captionBold'] = ( isset($_POST['captionBold']) ) ? "true" : "false";
		$O['colour_settings']['captionItalic'] = ( isset($_POST['captionItalic']) ) ? "true" : "false";
		$O['colour_settings']['listBold'] = ( isset($_POST['listBold']) ) ? "true" : "false";
		$O['colour_settings']['listItalic'] = ( isset($_POST['listItalic']) ) ? "true" : "false";
		$O['colour_settings']['adminCheckerIMG'] = ( isset($_POST['adminCheckerIMG']) ) ? "true" : "false";
		$O['colour_settings']['imgOverflow'] = ( isset($_POST['imgOverflow']) ) ? "visible" : "hidden";
		
		
		$O['playerHeight'] = $MP3JP->prep_value( $_POST['playerHeight'] );
		$O['custom_stylesheet'] = $MP3JP->prep_path( $_POST['custom_stylesheet'] ); 
		$O['player_theme'] = $MP3JP->prep_value( $_POST['player_theme'] );
		$O['imageSize'] = $MP3JP->prep_value( $_POST['imageSize'] );
		
		
		//update
		update_option( MP3J_SETTINGS_NAME, $O );
		$MP3JP->theSettings = $O;
		
		MJPdesign_submit();
		?>
		<!-- save message -->
		<div class="updated"><p><strong><?php _e( 'Settings saved.', 'mp3-jplayer' ); ?></strong></p></div>
	<?php
	}
	
	$C = $O['colour_settings'];
	
	
	//Write js vars..
	//$css_urls = $MP3JP->getSkinData();
	$css_urls = $MP3JP->SKINS;
	
	//..current stylesheet url to js
	$player_theme = $O['player_theme'];
	if ( isset( $css_urls[ $player_theme ]['url'] ) ) {
		$js_current_css = "\nvar MP3J_THEME_PATH = '" . $css_urls[ $player_theme ]['url'] . "';";
		//$js_current_css .= "\nvar MP3J_THEME_NAME = '" . $css_urls[ $player_theme ]['opValue'] . "';";
	}
	else { //fall back to default
		$js_current_css = "\nvar MP3J_THEME_PATH = '" . $css_urls['defaultLight']['url'] . "';";
		//$js_current_css .= "\nvar MP3J_THEME_NAME = '" . $css_urls['defaultLight']['opValue'] . "';"; 
	}
	
	
	//..other stylesheet urls to js
	//$js_stylesheets = '';
	$c = count( $css_urls );
	$i = 1;
	$js_stylesheets = "\nvar SKINDATA = {";
	foreach ( $css_urls as $data )
	{
		//$js_stylesheets .= "\nvar " . $data['opValue'] . " = '" . $data['url'] . "';";
		$js_stylesheets .= "\n\t" .$data['opValue']. ": '" .$data['url']. "'" . ( $i === $c ? '' : ',' );
		$i++;
	}
	$js_stylesheets .= "\n};";
	

	//..write the vars
	echo '<script type="text/javascript">';
	echo $js_current_css;
	echo $js_stylesheets;
	//echo $js_colours;
	
	$imgSizesWP = array ( 'thumbnail', 'medium', 'large' );
	
	echo "\nvar imgDimsWP = {\n";
	foreach ( $imgSizesWP as $i => $size ) {
		$s = $MP3JP->getImageSizeWP( $size );
		echo "\t" . $size . "_w: " . $s['width'] . ",\n";
		echo "\t" . $size . "_h: " . $s['height'];
		echo ( $i < 2 ? ",\n" : "\n" );
	}
	echo "};\n";
	echo '</script>';
	
	//write css settings
	echo $MP3JP->writeColoursCSS();
	?>
	
	<div class="wrap">
		<h2 style="font-size:4px;line-height:4px;">&nbsp;</h2>
		<h1><?php _e( 'Player Design', 'mp3-jplayer' ); ?></h1>
		<p>&nbsp;</p>
		
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

		
		
			<!-- THEME / URL -->
			<?php $rowCSS = ( $O['player_theme'] != "styleI" ) ? "display:none;" : ""; ?>
			<table>
				<tr>
					<td><strong><?php _e( 'Skin', 'mp3-jplayer' ); ?></strong>:&nbsp;&nbsp;</td>
					<td><select name="player_theme" id="player-select" style="width:350px; font-weight:700;">
							<?php
							foreach ( $css_urls as $data ) {
								$selected = ( $data['opValue'] === $O['player_theme'] ) ? ' selected="selected"' : '';
								echo '<option value="' .$data['opValue']. '"' .$selected. '>' .$data['opName']. '</option>';
							}
							?>
						</select>
					</td>
					<td></td>
				</tr>
				<tr id="customCSSrow" style="<?php echo $rowCSS; ?>">
					<td><span id="player-csssheet">&nbsp;&nbsp; <?php _e( 'url:', 'mp3-jplayer' ); ?>&nbsp;&nbsp;</span></td>
					<td><input type="text" id="mp3fcss" name="custom_stylesheet" style="width:100%;" value="<?php echo $O['custom_stylesheet']; ?>" /></td>
					<td><span class="button-secondary" id="reload_stylesheet_button" style="font-size:90%; font-weight:700;">&nbsp;<?php _e( 'Reload', 'mp3-jplayer' ); ?>&nbsp;</span></td>
				</tr>
			</table>
			<br>
			
			
			
			<!-- PLAYER PREVIEW --> 
			<div id="sizer" style="overflow:hidden; background-color:<?php echo $C['adminBG']; ?>; width:<?php echo $C['adminSizer_w']; ?>; height:<?php echo $C['adminSizer_h']; ?>;">
				<div id="display_player_liner">
					
					<?php					
					$heightProp = ( !empty($O['playerHeight']) ) ? " height:" . $O['playerHeight'] . ";" : ""; 
					$imgCSS = '';			//inline css added to image wrapper
					$tweakerClass = '';		//class affecting image
					if ( 'autoW' === $O['imageSize'] ) { 		//fit images to player width.
						$imgCSS .= ' width:100%; height:' .$O['playerHeight']. ';';
					}
					elseif ( 'autoH' === $O['imageSize'] ) { 	//fit images to player height.
						$imgCSS .= ' width:auto; height:' .$O['playerHeight']. ';';
						$tweakerClass = ' Himg';
					}
					elseif ( 'full' === $O['imageSize'] ) {	 	//leave images alone.
						$imgCSS .= ' width:auto; height:' .$O['playerHeight']. ';';
						$tweakerClass = ' Fimg';
					}
					else { 										//use specific WP media sizes, ad auto set the player height.
						$dims = $MP3JP->getImageSizeWP( $O['imageSize'] );
						$imgCSS .= ' width:' .$dims['width']. 'px; height:' .$dims['height']. 'px;';
						$heightProp = ' height:' .$dims['height']. 'px;';
					}
					
					$CSSext = "-mjp";
					$titleAlign = ' ' . $C['titleAlign'] . $CSSext;
					$listAlign  = ' ' . $C['listAlign'] . $CSSext;
					$imageAlign = ' ' . $C['imageAlign'] . $CSSext;
					$ulClass = 		( $C['playlist_tint'] === 'none' ) 	? '' : ' ' . $C['playlist_tint'] . $CSSext;
					$font1Class = 	( $C['font_family_1'] === 'theme' ) ? '' : ' ' . $C['font_family_1'] . $CSSext;
					$font2Class = 	( $C['font_family_2'] === 'theme' ) ? '' : ' ' . $C['font_family_2'] . $CSSext;
					$posbarClass = 	( $C['posbar_tint'] === 'none' ) 	? '' : ' ' . $C['posbar_tint'] . $CSSext;
					$liClass = 		( $C['list_divider'] === 'none' ) 	? '' : ' ' . $C['list_divider'] . $CSSext;
					$titleBold = 	( $C['titleBold'] === 'true' ) 			? ' bold' . $CSSext : ' norm' . $CSSext;
					$titleItalic =	( $C['titleItalic'] === 'true' ) 		? ' italic' . $CSSext : ' plain' . $CSSext;
					$captionWeight =( $C['captionBold'] === 'true' ) 		? ' childBold' . $CSSext : ' childNorm' . $CSSext;
					$captionItalic =( $C['captionItalic'] === 'true' ) 		? ' childItalic' . $CSSext : ' childPlain' . $CSSext;
					$listWeight =	( $C['listBold'] === 'true' ) 			? ' childBold' . $CSSext : ' childNorm' . $CSSext;
					$listItalic =	( $C['listItalic'] === 'true' ) 		? ' childItalic' . $CSSext : ' childPlain' . $CSSext;
					?>					
					
					<div id="wrapperMI_1" class="wrap-mjp <?php echo $C['userClasses']; ?>" style="position:relative; padding:0px 0px 0px 0px; margin:0px; width:100%;">
						<div class="subwrap-MI">
							
							<div class="jp-innerwrap">
								<div class="innerx"></div>
								<div class="innerleft"></div>
								<div class="innerright"></div>
								<div class="innertab" id="playerBG1"></div>
								
								<div class="interface-mjp<?php echo $font1Class; ?>" style="<?php echo $heightProp; ?>" id="playerT1"> 
									<div id="image-mjp" class="MI-image<?php echo $tweakerClass . $imageAlign; ?>" style="<?php echo $imgCSS . ' overflow:' . $C['imgOverflow'] . ';'; ?>"></div>
									<div id="trackTitles" class="player-track-title<?php echo $titleAlign . $titleBold . $titleItalic . $captionWeight . $captionItalic; ?>" style="left:<?php echo $C['titleOffset']; ?>; right:<?php echo $C['titleOffsetR']; ?>; top:<?php echo $C['titleTop']; ?>;">Example Track Title<div>Example Caption</div></div>
									
									<div id="bars-mjp" class="bars_holder">
										<div class="loadMI_mp3j" id="playerBG3" style="width:100%;"></div>
										<div class="poscolMI_mp3j<?php echo $posbarClass; ?>" id="playerBG4" style="width:60%;"></div>
										<div class="posbarMI_mp3j" style="width:70%;"></div>
									</div>
									
									<div id="P-Time-MI_1" class="jp-play-time">3:24</div>
									<div id="T-Time-MI_1" class="jp-total-time">10:00</div>
									<div id="statusMI_1" class="statusMI"><span class="mjp-playing">Playing</span></div>
									
									<div class="transport-MI">
										<div class="pause-mjp" id="playpause_mp3j_1">Pause</div>
										<div class="stop-mjp" id="stop_mp3j_1">Stop</div>
										<div class="next-mjp" id="Next_mp3j_1">Next&raquo;</div>
										<div class="prev-mjp" id="Prev_mp3j_1">&laquo;Prev</div>
									</div>
									
									<div class="buttons-wrap-mjp">
										<div class="playlist-toggle-MI" id="listtog-mjp">HIDE PLAYLIST</div>
										<div class="mp3j-popout-MI"><?php echo $O['popout_button_title']; ?></div>
										<div id="download_mp3j_1" class="dloadmp3-MI"><a class=""><?php echo $O['dload_text']; ?></a></div>
									</div>
								</div>
								
								<div class="mjp-volwrap">
									<div class="MIsliderVolume" id="volslider-mjp">
										<div class="ui-widget-header" style="position:absolute; left:0; width:100%; height:100%;"></div>
										<div class="ui-slider-handle" style="position:absolute; left:100%;"></div>
									</div>
									<div class="innerExt1" id="innerExt1_1"></div>
									<div class="innerExt2" id="innerExt2_1"></div>
								</div>
							</div>
							
							<div class="listwrap_mp3j" id="listwrap-mjp" style="overflow:auto; max-height:450px;<?php echo ( $O['playlist_show'] !== 'true' ? ' display:none;' : '' ); ?>">
								<div class="wrapper-mjp">
									<div class="playlist-colour" id="playerBG2"></div>
									<div class="wrapper-mjp">
										<ul id="ul-mjp" class="ul-mjp<?php echo $ulClass . $liClass . $listWeight. $listItalic . $listAlign; ?>">
											<li class="li-mjp"><a id="playerT2" class="a-mjp" href="#"><?php _e( 'Example List Item', 'mp3-jplayer' ); ?><br><span><?php _e( 'Example Caption', 'mp3-jplayer' ); ?></span></a></li>
											<li class="li-mjp"><a id="playerT3" class="a-mjp" style="background:<?php echo $C['listBGa_hover']; ?>; color:<?php echo $C['list_hover_colour']; ?>;" href="#"><?php _e( 'Example List item (hover)', 'mp3-jplayer' ); ?><br><span><?php _e( 'Example Caption', 'mp3-jplayer' ); ?></span></a></li>
											<li class="li-mjp mjp-li-last"><a id="playerT4" class="a-mjp" style="background:<?php echo $C['listBGa_current']; ?>; color:<?php echo $C['list_current_colour']; ?>;" href="#"><?php _e( 'Example List Item (Current)', 'mp3-jplayer' ); ?><br><span><?php _e( 'Example Caption', 'mp3-jplayer' ); ?></span></a></li>
										</ul>
									</div>
								</div>
							</div>
							
						</div>
					</div>					
					
					<!-- Admin BG Colour -->
					<div class="testingSettings">
						<input type="text" id="adminBG" name="adminBG" value="<?php echo $C['adminBG']; ?>" />
					</div>
									
				</div><!-- close #display_player_liner -->
			</div><!-- close #sizer -->
			
			
			
			<!-- TABS start -->
			<div class="mp3j-tabbuttons-wrap">
				<div class="mp3j-tabbutton first" id="mp3j_tabbutton_0"><?php _e( 'Text', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_1"><?php _e( 'Areas', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_2"><?php _e( 'Fonts', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_3"><?php _e( 'Alignments', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton last" id="mp3j_tabbutton_4"><?php _ex( 'Mods', 'as in modifications', 'mp3-jplayer' ); ?></div>
				<br class="clearB" />
			</div>
			<div class="mp3j-tabs-wrap">
				
				<!-- TAB TEXT -->
				<div class="mp3j-tab" id="mp3j_tab_0">
					<div style="float:left; max-width:390px; min-height:273px; padding:5px;">
						<table class="colours unselectable">
							<tr>
								<td><strong><?php _e( 'Titles:', 'mp3-jplayer' ); ?></strong></td>
								<td>
									<div class="patch">
										<div id="patchT1" onclick="MP3jP.clickPatch('T1','');" class="PatchCol" style="background-color:<?php echo $C['screen_text_colour']; ?>;" title="Retrieve"></div>
										<div id="minusT1" class="minus" onclick="MP3jP.clickMinus('T1','color','T1');"></div>
										<div id="plusT1" class="plus" onclick="MP3jP.clickPlus('T1','color');"></div>
									</div>
								</td>
								<td><input type="checkbox" name="titleHide" id="titleHide" value="true"<?php echo ( $C['titleHide'] === 'true' ? ' checked="checked"' : ''); ?>/><label for="titleHide" style="font-weight:500;">&nbsp;<strong><?php _e( 'Hide', 'mp3-jplayer' ); ?></strong></label></td>
								<td></td>
								<td></td>								
								
							</tr>
							<tr>
								<td><strong><?php _e( 'Playlist:', 'mp3-jplayer' ); ?></strong></td>
								<td>
									<div class="patch">
										<div id="patchT2" onclick="MP3jP.clickPatch('T2','');" class="PatchCol" style="background-color:<?php echo $C['list_text_colour']; ?>;" title="Retrieve"></div>
										<div id="minusT2" class="minus" onclick="MP3jP.clickMinus('T2','color','T2');"></div>
										<div id="plusT2" class="plus" onclick="MP3jP.clickPlus('T2','color');"></div>
									</div>
								</td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td>&nbsp;&nbsp;&nbsp;<span class=""><?php _e( 'Hover:', 'mp3-jplayer' ); ?></span></td>
								<td>
									<div class="patch">
										<div id="patchT3" onclick="MP3jP.clickPatch('T3','');" class="PatchCol" style="background-color:<?php echo $C['list_hover_colour']; ?>;" title="Retrieve"></div>
										<div id="minusT3" class="minus" onclick="MP3jP.clickMinus('T3','color','T3');"></div>
										<div id="plusT3" class="plus" onclick="MP3jP.clickPlus('T3','color');"></div>
									</div>
								</td>
								<td>
									<div class="patch fR">
										<div id="patchBG5" onclick="MP3jP.clickPatch('BG5','');" class="PatchCol" style="background-color:<?php echo $C['listBGa_hover']; ?>;" title="Retrieve"></div>
										<div id="minusBG5" class="minus" onclick="MP3jP.clickMinus('BG5','background-color','T3');"></div>
										<div id="plusBG5" class="plus" onclick="MP3jP.clickPlus('BG5','background-color','T3');"></div>
									</div>
								</td>
								<td><span class="" style="font-size:12px;"><?php _e( 'Background', 'mp3-jplayer' ); ?></span></td>
								<td></td>
							</tr>
							<tr>
								<td>&nbsp;&nbsp;&nbsp;<span class=""><?php _e( 'Current:', 'mp3-jplayer' ); ?></span></td>
								<td>
									<div class="patch">
										<div id="patchT4" onclick="MP3jP.clickPatch('T4','');" class="PatchCol" style="background-color:<?php echo $C['list_current_colour']; ?>;" title="Retrieve"></div>
										<div id="minusT4" class="minus" onclick="MP3jP.clickMinus('T4','color','T4');"></div>
										<div id="plusT4" class="plus" onclick="MP3jP.clickPlus('T4','color');"></div>
									</div>
								</td>
								<td>
									<div class="patch fR">
										<div id="patchBG6" onclick="MP3jP.clickPatch('BG6','');" class="PatchCol" style="background-color:<?php echo $C['listBGa_current']; ?>;" title="Retrieve"></div>
										<div id="minusBG6" class="minus" onclick="MP3jP.clickMinus('BG6','background-color','T4');"></div>
										<div id="plusBG6" class="plus" onclick="MP3jP.clickPlus('BG6','background-color','T4');"></div>
									</div>
								</td>
								<td><span class="" style="font-size:12px;"><?php _e( 'Background', 'mp3-jplayer' ); ?></span></td>
								<td></td>
							</tr>
							<?php
							MJPdesign_text();
							?>
						</table>
						
						<p style="margin:28px 0 12px 0; font-weight:700;"><a href="javascript:" onclick="jQuery('#hiddenFields1').toggle();"><?php _e( 'Show Field Values', 'mp3-jplayer' ); ?></a></p>
						<div id="hiddenFields1" style="display:none;">
							<table class="colours">
								<tr>
									<td><?php _e( 'Titles:', 'mp3-jplayer' ); ?></td>
									<td><input type="text" id="T1" name="screen_text_colour" value="<?php echo $C['screen_text_colour']; ?>" /></td>
								</tr>
								<tr>
									<td><?php _e( 'List:', 'mp3-jplayer' ); ?></td>
									<td><input type="text" id="T2" name="list_text_colour" value="<?php echo $C['list_text_colour']; ?>" /></td>
								</tr>
								<tr>
									<td><?php _e( 'List Hover:', 'mp3-jplayer' ); ?></td>
									<td><input type="text" id="T3" name="list_hover_colour" value="<?php echo $C['list_hover_colour']; ?>" /></td>
								</tr>
								<tr>
									<td><?php _e( 'List Hover BG:', 'mp3-jplayer' ); ?></td>
									<td><input id="BG5" type="text" name="listBGa_hover" value="<?php echo $C['listBGa_hover']; ?>" /></td>
								</tr>
								<tr>
									<td><?php _e( 'List Current:', 'mp3-jplayer' ); ?></td>
									<td><input type="text" id="T4" name="list_current_colour" value="<?php echo $C['list_current_colour']; ?>" /></td>
								</tr>
								<tr>
									<td><?php _e( 'List Current BG:', 'mp3-jplayer' ); ?></td>
									<td><input id="BG6" type="text" name="listBGa_current" value="<?php echo $C['listBGa_current']; ?>" /></td>
								</tr>
								<tr style="display:none;">
									<td><?php _e( 'Track', 'mp3-jplayer' ); ?></td>
									<td>
										<?php _e( 'Bold', 'mp3-jplayer' ); ?> <input type="checkbox" value="true" id="titleBold" onclick="MP3jP.fontCheckers('#titleBold', '#trackTitles', 'bold', 'norm');" name="titleBold"<?php if ( 'true' == $C['titleBold'] ) {  echo ' checked="checked"'; } ?> />
										<br><?php _e( 'Italic', 'mp3-jplayer' ); ?> <input type="checkbox" value="true" id="titleItalic" onclick="MP3jP.fontCheckers('#titleItalic', '#trackTitles','italic', 'plain');" name="titleItalic"<?php if ( 'true' == $C['titleItalic'] ) {  echo ' checked="checked"'; } ?> />
									</td>
								</tr>
								<tr style="display:none;">
									<td><?php _e( 'Caption', 'mp3-jplayer' ); ?></td>
									<td>
										<?php _e( 'Bold', 'mp3-jplayer' ); ?> <input type="checkbox" value="true" id="captionBold" onclick="MP3jP.fontCheckers('#captionBold', '#trackTitles', 'childBold', 'childNorm');" name="captionBold"<?php if ( 'true' == $C['captionBold'] ) {  echo ' checked="checked"'; } ?> />
										<br><?php _e( 'Italic', 'mp3-jplayer' ); ?> <input type="checkbox" value="true" id="captionItalic" onclick="MP3jP.fontCheckers('#captionItalic', '#trackTitles','childItalic', 'childPlain');" name="captionItalic"<?php if ( 'true' == $C['captionItalic'] ) {  echo ' checked="checked"'; } ?> />
									</td>
								</tr>
								<tr style="display:none;">
									<td><?php _e( 'Playlist', 'mp3-jplayer' ); ?></td>
									<td>
										<?php _e( 'Bold', 'mp3-jplayer' ); ?> <input type="checkbox" value="true" id="listBold" onclick="MP3jP.fontCheckers('#listBold', '#ul-mjp', 'childBold', 'childNorm');" name="listBold"<?php if ( 'true' == $C['listBold'] ) {  echo ' checked="checked"'; } ?> />
										<br><?php _e( 'Italic', 'mp3-jplayer' ); ?> <input type="checkbox" value="true" id="listItalic" onclick="MP3jP.fontCheckers('#listItalic', '#ul-mjp','childItalic', 'childPlain');" name="listItalic"<?php if ( 'true' == $C['listItalic'] ) {  echo ' checked="checked"'; } ?> />
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<div style="float:left; width:230px; height:273px; overflow:hidden;">
						<input type="text" value="#bc5c5c" id="spectrumPicker" />
					</div>
				</div><!-- close TAB -->
				
				
				<!-- TAB AREAS -->
				<div class="mp3j-tab" id="mp3j_tab_1">					
					<div style="float:left; width:380px; min-height:273px; padding:5px;">
						<table class="colours unselectable">
							<tr>
								<td><strong><?php _e( 'Screen', 'mp3-jplayer' ); ?></strong>:</td>
								<td>
									<div class="patch">
										<div id="patchBG1" onclick="MP3jP.clickPatch('BG1','Alpha');" class="PatchCol" style="background-color:<?php echo $C['screen_colour']; ?>;" title="Retrieve"></div>
										<div id="minusBG1" class="minus" onclick="MP3jP.clickMinus('BG1','background-color','BG1');"></div>
										<div id="plusBG1" class="plus" onclick="MP3jP.clickPlus('BG1','background-color');"></div>
									</div>
								</td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Load Bar', 'mp3-jplayer' ); ?></strong>:</td>
								<td>
									<div class="patch">
										<div id="patchBG3" onclick="MP3jP.clickPatch('BG3','Alpha');" class="PatchCol" style="background-color:<?php echo $C['loadbar_colour']; ?>;" title="Retrieve"></div>
										<div id="minusBG3" class="minus" onclick="MP3jP.clickMinus('BG3','background-color','BG3');"></div>
										<div id="plusBG3" class="plus" onclick="MP3jP.clickPlus('BG3','background-color');"></div>
									</div>
								</td>
								<td><span class="" style="font-size:12px;"><?php _e( 'Indicator', 'mp3-jplayer' ); ?></span></td>
								<td><select name="indicator" style="width:90px; font-size:11px;">
										<option value="tint" <?php if ( 'tint' == $C['indicator'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Greyscale', 'mp3-jplayer' ); ?></option>
										<option value="colour" <?php if ( 'colour' == $C['indicator'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Colour', 'mp3-jplayer' ); ?></option>
									</select></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Position Bar', 'mp3-jplayer' ); ?></strong>:</td>
								<td>
									<div class="patch">
										<div id="patchBG4" onclick="MP3jP.clickPatch('BG4','Alpha');" class="PatchCol" style="background-color:<?php echo $C['posbar_colour']; ?>;" title="Retrieve"></div>
										<div id="minusBG4" class="minus" onclick="MP3jP.clickMinus('BG4','background-color','BG4');"></div>
										<div id="plusBG4" class="plus" onclick="MP3jP.clickPlus('BG4','background-color');"></div>
									</div>
								</td>
								<td><span class="" style="font-size:12px;"><?php _e( 'Gradient', 'mp3-jplayer' ); ?></span></td>
								<td><select name="posbar_tint" id="posbar_tint" style="width:90px; font-size:11px;">
										<option value="none" <?php if ( 'none' == $C['posbar_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'None', 'mp3-jplayer' ); ?></option>
										<option value="soften" <?php if ( 'soften' == $C['posbar_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Light grad', 'mp3-jplayer' ); ?></option>
										<option value="softenT" <?php if ( 'softenT' == $C['posbar_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Pipe', 'mp3-jplayer' ); ?></option>
										<option value="darken" <?php if ( 'darken' == $C['posbar_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Dark grad', 'mp3-jplayer' ); ?></option>
									</select></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Playlist', 'mp3-jplayer' ); ?></strong>:</td>
								<td>
									<div class="patch">
										<div id="patchBG2" onclick="MP3jP.clickPatch('BG2','Alpha');" class="PatchCol" style="background-color:<?php echo $C['playlist_colour']; ?>;" title="Retrieve"></div>
										<div id="minusBG2" class="minus" onclick="MP3jP.clickMinus('BG2','background-color','BG2');"></div>
										<div id="plusBG2" class="plus" onclick="MP3jP.clickPlus('BG2','background-color');"></div>
									</div>
								</td>
								<td><span class="" style="font-size:12px;"><?php _e( 'Gradient', 'mp3-jplayer' ); ?></span></td>
								<td><select id="playlist_tint" name="playlist_tint" style="width:90px; font-size:11px;">
										<option value="none" <?php if ( 'none' == $C['playlist_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'None', 'mp3-jplayer' ); ?></option>
										<option value="lighten1" <?php if ( 'lighten1' == $C['playlist_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Light Short', 'mp3-jplayer' ); ?></option>
										<option value="lighten2" <?php if ( 'lighten2' == $C['playlist_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Light Long', 'mp3-jplayer' ); ?></option>
										<option value="darken1" <?php if ( 'darken1' == $C['playlist_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Dark Short', 'mp3-jplayer' ); ?></option>
										<option value="darken2" <?php if ( 'darken2' == $C['playlist_tint'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Dark Long', 'mp3-jplayer' ); ?></option>
									</select></td>
							</tr>
							<tr>
								<td></td>
								<td></td>
								<td><span class="" style="font-size:12px;"><?php _e( 'Dividers', 'mp3-jplayer' ); ?></span></td>
								<td><select id="list_divider" name="list_divider" style="width:100%; font-size:11px;">
										<option value="none" <?php if ( 'none' == $C['list_divider'] ) { echo 'selected="selected"'; } ?>><?php _e( 'None', 'mp3-jplayer' ); ?></option>
										<option value="light" <?php if ( 'light' == $C['list_divider'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Light', 'mp3-jplayer' ); ?></option>
										<option value="med" <?php if ( 'med' == $C['list_divider'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Medium', 'mp3-jplayer' ); ?></option>
										<option value="dark" <?php if ( 'dark' == $C['list_divider'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Dark', 'mp3-jplayer' ); ?></option>										
									</select></td>
							</tr>
							<?php
							MJPdesign_areas();
							?>
						</table>
						
						<p style="margin:0px 0 12px 0px; font-weight:700;"><a href="javascript:" onclick="jQuery('#hiddenFields0').toggle();"><?php _e( 'Show Field Values', 'mp3-jplayer' ); ?></a></p>
						<div id="hiddenFields0" style="display:none;">
							<table class="colours">
								<tr>
									<td><?php _e( 'Screen:', 'mp3-jplayer' ); ?></td>
									<td><input id="BG1" type="text" name="screen_colour" value="<?php echo $C['screen_colour']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td><?php _e( 'Load Bar:', 'mp3-jplayer' ); ?></td>
									<td><input id="BG3" type="text" name="loadbar_colour" value="<?php echo $C['loadbar_colour']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td><?php _e( 'Position Bar:', 'mp3-jplayer' ); ?></td>
									<td><input id="BG4" type="text" name="posbar_colour" value="<?php echo $C['posbar_colour']; ?>" style="width:200px;" /></td>
								</tr>
								<tr>
									<td><?php _e( 'Playlist:', 'mp3-jplayer' ); ?></td>
									<td><input id="BG2" type="text" name="playlist_colour" value="<?php echo $C['playlist_colour']; ?>" style="width:200px;" /></td>
								</tr>
							</table>
						</div>
					</div>					
					
					<div style="float:left; width:230px; min-height:273px; overflow:hidden;">
						<input type="text" value="rgb(189,89,89)" id="spectrumPickerAlpha" />
					</div>
				</div><!-- close TAB -->
				
				
				<!-- TAB FONTS -->
				<div class="mp3j-tab" id="mp3j_tab_2">
					<div style=" min-height:250px; padding:10px 5px 5px 5px;">
						<table class="colours unselectable">
							<tr>
								<td><strong><?php _e( 'Titles:', 'mp3-jplayer' ); ?></strong></td>
								<td><select id="font_family_1" name="font_family_1" style="width:110px; font-size:12px;">
										<option value="theme"<?php if ( 'theme' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Theme\'s Font', 'mp3-jplayer' ); ?></option>
										<option value="arial"<?php if ( 'arial' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Arial', 'mp3-jplayer' ); ?></option>
										<option value="verdana"<?php if ( 'verdana' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Verdana', 'mp3-jplayer' ); ?></option>
										<option value="times"<?php if ( 'times' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Times', 'mp3-jplayer' ); ?></option>
										<option value="palatino"<?php if ( 'palatino' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Palatino', 'mp3-jplayer' ); ?></option>
										<option value="courier"<?php if ( 'courier' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Courier New', 'mp3-jplayer' ); ?></option>
										<option value="lucida"<?php if ( 'lucida' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Lucida Console', 'mp3-jplayer' ); ?></option>
										<option value="gill"<?php if ( 'gill' == $C['font_family_1'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Gill Sans', 'mp3-jplayer' ); ?></option>
									</select></td>
								<td style="width:85px;"><div class="sliderWrap"><div id="fontSlider_1" class="fontSizeSlider"></div></div></td>
								<td><input type="text" id="font_size_1" name="font_size_1" style="width:30px; font-size:11px;" value="<?php echo $C['font_size_1']; ?>" /> <span class="description">px</span></td>
							</tr>	
							<tr>
								<td></td>
								<td style="text-align:right;"><span class="" style="font-size:13px;"><?php _e( 'Track:', 'mp3-jplayer' ); ?>&nbsp;&nbsp;</span>
									<label for="titleBold" id="titleBold_label" class="unselectable format-button-B<?php if ( 'true' == $C['titleBold'] ) {  echo ' formatOn'; } ?>">B</label>
									<label for="titleItalic" id="titleItalic_label" class="unselectable format-button-i<?php if ( 'true' == $C['titleItalic'] ) {  echo ' formatOn'; } ?>">i</label>
								</td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td></td>
								<td style="text-align:right;"><span class="" style="font-size:13px;"><?php _e( 'Caption:', 'mp3-jplayer' ); ?>&nbsp;&nbsp;</span>
									<label for="captionBold" id="captionBold_label" class="unselectable format-button-B<?php if ( 'true' == $C['captionBold'] ) {  echo ' formatOn'; } ?>">B</label>
									<label for="captionItalic" id="captionItalic_label" class="unselectable format-button-i<?php if ( 'true' == $C['captionItalic'] ) {  echo ' formatOn'; } ?>">i</label>
								</td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td colspan="4">&nbsp;</td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Playlist:', 'mp3-jplayer' ); ?></strong></td>
								<td><select id="font_family_2" name="font_family_2" style="width:110px; font-size:12px;">
										<option value="theme"<?php if ( 'theme' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Theme\'s Font', 'mp3-jplayer' ); ?></option>
										<option value="arial"<?php if ( 'arial' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Arial', 'mp3-jplayer' ); ?></option>
										<option value="verdana"<?php if ( 'verdana' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Verdana', 'mp3-jplayer' ); ?></option>
										<option value="times"<?php if ( 'times' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Times', 'mp3-jplayer' ); ?></option>
										<option value="palatino"<?php if ( 'palatino' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Palatino', 'mp3-jplayer' ); ?></option>
										<option value="courier"<?php if ( 'courier' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Courier New', 'mp3-jplayer' ); ?></option>
										<option value="lucida"<?php if ( 'lucida' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Lucida Console', 'mp3-jplayer' ); ?></option>
										<option value="gill"<?php if ( 'gill' == $C['font_family_2'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Gill Sans', 'mp3-jplayer' ); ?></option>
									</select></td>
								
								<td><div class="sliderWrap"><div id="fontSlider_2" class="fontSizeSlider"></div></div></td>
								<td><input type="text" id="font_size_2" name="font_size_2" style="width:30px; font-size:11px;" value="<?php echo $C['font_size_2']; ?>" /> <span class="description">px</span></td>
							</tr>
							<tr>
								<td></td>
								<td style="text-align:right;">
									<label for="listBold" id="listBold_label" class="unselectable format-button-B<?php if ( 'true' == $C['listBold'] ) {  echo ' formatOn'; } ?>">B</label>
									<label for="listItalic" id="listItalic_label" class="unselectable format-button-i<?php if ( 'true' == $C['listItalic'] ) {  echo ' formatOn'; } ?>">i</label>
								</td>
								<td></td>
								<td></td>
							</tr>
							<?php
							MJPdesign_fonts();
							?>
						</table>
					</div>
				</div><!-- close TAB -->
				
				
				<!-- TAB ALIGNMENT -->
				<div class="mp3j-tab" id="mp3j_tab_3">					
					<div style=" min-height:250px; padding:10px 5px 5px 5px;">
						<?php
						$greyoutClass = '';
						$quietClass = '';
						if ( $O['imageSize'] === "thumbnail" || $O['imageSize'] === "medium" || $O['imageSize'] === "large" ) {
							$greyoutClass = " quietInput";
							$quietClass = " quietText";
						}
						?>
						<table class="colours unselectable">
							<tr>
								<td><span id="playerHeightWrap1" class="<?php echo $quietClass; ?>"><strong><?php _e( 'Player Height', 'mp3-jplayer' ); ?></strong>:</span></td>
								<td colspan="2">
									<div class="sliderWrap" style="padding-left:5px;" onmouseover="jQuery('#playerT1').addClass('highlight');" onmouseout="jQuery('#playerT1').removeClass('highlight');">
										<div id="offsetSlider_5" class="fontSizeSlider" style="width:250px;"></div>
									</div>
								</td>
								<td><input type="text" id="playerHeight" class="<?php echo $greyoutClass; ?>" style="width:50px; font-size:11px;" name="playerHeight" value="<?php echo $O['playerHeight']; ?>" />
									<span id="playerHeightWrap2" class="description <?php echo $quietClass; ?>"></span></td>
								<td></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Images', 'mp3-jplayer' ); ?></strong>:</td>
								<td><select name="imageAlign" id="imageAlign" style="font-weight:500;">
										<option value="left"<?php if ( 'left' == $C['imageAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Left', 'mp3-jplayer' ); ?></option>
										<option value="centre"<?php if ( 'centre' == $C['imageAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Centre', 'mp3-jplayer' ); ?></option>
										<option value="right"<?php if ( 'right' == $C['imageAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Right', 'mp3-jplayer' ); ?></option>
									</select>
								</td>
								<td><select name="imageSize" id="imageSize" style="width:165px; font-weight:500;">
										<option value="full"<?php if ( 'full' == $O['imageSize'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Original size', 'mp3-jplayer' ); ?></option>
										<option value="autoW"<?php if ( 'autoW' == $O['imageSize'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Fit to player width', 'mp3-jplayer' ); ?></option>
										<option value="autoH"<?php if ( 'autoH' == $O['imageSize'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Fit to player height', 'mp3-jplayer' ); ?></option>
										<option value="thumbnail"<?php if ( 'thumbnail' == $O['imageSize'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Thumbnail', 'mp3-jplayer' ); ?></option>
										<option value="medium"<?php if ( 'medium' == $O['imageSize'] ) { echo ' selected="selected"'; } ?>><?php _e( 'Medium', 'mp3-jplayer' ); ?></option>
										<option value="large"<?php if ( 'large' == $O['imageSize'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Large', 'mp3-jplayer' ); ?></option>
									</select>
								</td>
								<td>
									<input type="checkbox" name="imgOverflow" id="imgOverflow" value="visible"<?php echo ( $C['imgOverflow'] === 'visible' ? ' checked="checked"' : ''); ?>/>
									<label for="imgOverflow" class="f500"><?php _e( 'overflow', 'mp3-jplayer' ); ?></label>
								</td>
								<td></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Playlist', 'mp3-jplayer' ); ?></strong>:</td>
								<td><select name="listAlign" id="listAlign">
										<option value="left"<?php if ( 'left' == $C['listAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Left', 'mp3-jplayer' ); ?></option>
										<option value="centre"<?php if ( 'centre' == $C['listAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Centre', 'mp3-jplayer' ); ?></option>
										<option value="right"<?php if ( 'right' == $C['listAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Right', 'mp3-jplayer' ); ?></option>
									</select></td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Titles', 'mp3-jplayer' ); ?></strong>:</td>
								<td><select name="titleAlign" id="titleAlign">
										<option value="left"<?php if ( 'left' == $C['titleAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Left', 'mp3-jplayer' ); ?></option>
										<option value="centre"<?php if ( 'centre' == $C['titleAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Centre', 'mp3-jplayer' ); ?></option>
										<option value="right"<?php if ( 'right' == $C['titleAlign'] ) {  echo ' selected="selected"'; } ?>><?php _e( 'Right', 'mp3-jplayer' ); ?></option>
									</select></td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td style="text-align:right;"><span class="" style="font-size:11px;"><?php _e( 'Top', 'mp3-jplayer' ); ?></span></td>
								<td colspan="2">
									<div class="sliderWrap" style="padding-left:5px;" onmouseover="jQuery('#trackTitles').addClass('highlight');" onmouseout="jQuery('#trackTitles').removeClass('highlight');">
										<div id="offsetSlider_6" class="fontSizeSlider" style="width:250px;"></div>
									</div>
								</td>
								<td><input type="text" name="titleTop" id="titleTop" style="width:45px; font-size:11px;" value="<?php echo $C['titleTop']; ?>" />
									<span class="description" style="font-size:11px;"><?php _e( 'px or %', 'mp3-jplayer' ); ?></span>
								</td>
								<td></td>
							</tr>
							<tr>
								<td style="text-align:right;"><span class="" style="font-size:11px;"><?php _e( 'Margins', 'mp3-jplayer' ); ?></span></td>
								<td colspan="2">
									<div class="sliderWrap" style="padding-left:5px;" onmouseover="jQuery('#trackTitles').addClass('highlight');" onmouseout="jQuery('#trackTitles').removeClass('highlight');">
										<div id="offsetSlider_1" class="fontSizeSlider" style="width:250px;"></div>
									</div>
								</td>
								<td><input type="text" name="titleOffset" id="titleOffset1" style="width:45px; font-size:11px;" value="<?php echo $C['titleOffset']; ?>" />
									<input type="text" name="titleOffsetR" id="titleOffset2" style="width:45px; font-size:11px;" value="<?php echo $C['titleOffsetR']; ?>" />
									<span class="description" style="font-size:11px;"><?php _e( 'px or %', 'mp3-jplayer' ); ?></span>
								</td>
								<td></td>
							</tr>
							<?php
							MJPdesign_alignments();
							?>
						</table>
					</div>
				</div><!-- Close Tab -->
				
				
				<!-- TAB MODS -->
				<div class="mp3j-tab" id="mp3j_tab_4">
					<div style="padding:10px 5px 5px 5px; min-height:250px;">
						<strong><?php _e( 'Modifiers:', 'mp3-jplayer' ); ?></strong>
						<br><br><input type="text" name="userClasses" id="userClasses" value="<?php echo $C['userClasses']; ?>" style="width:100%; max-width:500px;"/>
						<br><br><span class="description">&nbsp;<?php _e( 'Use this field to add modifier names. Use a space to separate names, the following are supported by default:', 'mp3-jplayer' ); ?></span>
						<br><br>
						<table class="colours unselectable">
							<tr>
								<td><code>flip</code></td>
								<td><?php _e( 'Puts play buttons on the left', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>noplayer</code></td>
								<td><?php _e( 'Hides the entire player (eg. background audio)', 'mp3-jplayer' ); ?></td>
							</tr>
							
							
							
							<tr>
								<td><code>nostop</code></td>
								<td><?php _e( 'Hides the stop button', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>nopn</code></td>
								<td><?php _e( 'Hides the prev/next buttons', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>novol</code></td>
								<td><?php _e( 'Hides the volume control', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>notitle</code></td>
								<td><?php _e( 'Hides the track title and caption', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>nolistbutton</code></td>
								<td><?php _e( 'Hides the playlist button', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>nopopoutbutton</code></td>
								<td><?php _e( 'Hides the popout button', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>fullbars</code></td>
								<td><?php _e( 'Position slider fills the screen/image area', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>nobars</code></td>
								<td><?php _e( 'removes the position and load bars', 'mp3-jplayer' ); ?></td>
							</tr>
							<tr>
								<td><code>nocase</code></td>
								<td><?php _e( 'removes the container bar around the buttons', 'mp3-jplayer' ); ?></td>
							</tr>
						</table>
						<?php
						MJPdesign_mods();
						?>
					</div>
				</div><!-- Close Tab -->
				
			</div><!-- close TABS wrapper -->
			
			
			
			<!-- TEST IMAGE -->
			<br class="clearB" /><hr>
			<div class="testingSettingsBottom">
				<input type="checkbox" id="adminCheckerIMG" name="adminCheckerIMG" value="true" <?php if ( $C['adminCheckerIMG'] == "true" ) { echo 'checked="checked" '; } ?>/>
				<label for="adminCheckerIMG"><strong><?php _e( 'Test Image', 'mp3-jplayer' ); ?></strong> &nbsp; <?php _e( 'url:', 'mp3-jplayer' ); ?></label> <input type="text" id="adminIMG" name="adminIMG" style="width:300px;" value="<?php echo $C['adminIMG']; ?>" />
				<span class="button-secondary" style="font-size:11px; height:25px;" id="reloadIMG">&nbsp;<?php _e( 'Reload', 'mp3-jplayer' ); ?>&nbsp;</span>
			</div>
			
			
			
			<!-- SAVE SETTINGS -->
			<div class="savewrap">
				<input type="submit" name="save_MP3JP" class="button-primary" style="font-weight:700;" value="<?php _e( 'Save All Changes', 'mp3-jplayer' ); ?>" />
				<input type="hidden" name="version_MP3JP" value="<?php echo $MP3JP->version_of_plugin; ?>" />
				<input type="hidden" name="adminSizer_w" id="adminSizer_w" value="<?php echo $C['adminSizer_w']; ?>" />
				<input type="hidden" name="adminSizer_h" id="adminSizer_h" value="<?php echo $C['adminSizer_h']; ?>" />
			</div>
			
			
			
		</form><!-- close form -->
		<br>
		<hr>
		<div style="margin: 15px 0px 0px 0px; min-height:30px;">
			<p class="description" style="margin: 0px 120px px 0px; font-weight:700; color:#d0d0d0;">
				<a class="button-secondary" target="_blank" href="http://mp3-jplayer.com/help-docs/"><?php _e( 'Help & Docs', 'mp3-jplayer' ); ?> &raquo;</a>
				&nbsp;&nbsp; <a class="button-secondary" target="_blank" href="http://mp3-jplayer.com/skins"><?php _e( 'Get Skins', 'mp3-jplayer' ); ?> &raquo;</a>
				&nbsp;&nbsp; <a class="button-secondary" target="_blank" href="http://mp3-jplayer.com/add-ons"><?php _e( 'Get Add-Ons', 'mp3-jplayer' ); ?> &raquo;</a>
			</p>
		</div>
		
		
		<div style="margin: 15px auto; height:100px;">
		</div>
		
		
		
		
		
	</div><!-- close .wrap -->

	<script> 
	jQuery(document).ready( function () {
		MP3jP.init();
	});	
	</script>

<?php
}
?>