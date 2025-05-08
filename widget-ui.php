<?php
/**	
*	UI WIDGET
*	adds one playlist player at a time via mode/tickbock ui
*	---
*/

if ( class_exists("WP_Widget") ) {
	if ( !class_exists("MP3_jPlayer") ) {
		
		class MP3_jPlayer extends WP_Widget	{

			function __construct () 
			{ 
				$widget_ops = array( 
					'classname' => 'mp3jplayerwidget', 
					'description' => __('Adds a playlist player. Choose playback mode and options.', 'mp3-jplayer' ) 
				);
				$control_ops = array( 
					'id_base' => 'mp3-jplayer-widget'
				);
				parent::__construct( 'mp3-jplayer-widget', __( 'MP3-jPlayer|Playlister', 'mp3-jplayer' ), $widget_ops, $control_ops );
			}
		
		
			/*	Sets up widget playlist and writes player (required by api) */
			function widget( $args, $instance )
			{
				global $MP3JP;
				
				$MP3JP->dbug['str'] .= "\n### Checking UI widget...";
				if ( !is_home() && !is_archive() && !is_singular() && !is_search() ) { 
					return; 
				}
				if ( $MP3JP->page_filter( $instance['restrict_list'], $instance['restrict_mode'] ) ) { 
					$MP3JP->dbug['str'] .= "\nExiting (page filter says no)";
					return;
				}

				//Build shortcode
				//modes
				$SHORTCODE = '[mp3-jplayer';
				if ( $instance['widget_mode'] == "1" )
				{	
					if ( !is_singular() ) {
						return;
					}
				} 
				else if ( $instance['widget_mode'] == "2" )
				{						
					$SHORTCODE .= ' tracks="' . $instance['arb_playlist'] . '"';
				}
				else if ( $instance['widget_mode'] == "3" )
				{
					$tracks = "";
					if ( $instance['play_library'] == "true" ) {
						$tracks .= "FEED:LIB,";
					}
					if ( $instance['play_folder'] == "true" ) {
						$folder = ( $instance['folder_to_play'] == "" ) ? "DF" : $instance['folder_to_play'];						
						$tracks .= "FEED:" . $folder . ",";
					}
					$SHORTCODE .= ' tracks="' . $tracks . '"';
					
					if ( $instance['play_page'] == "true" && $instance['id_to_play'] !='' ) {
						$SHORTCODE .= ' id="' . $instance['id_to_play'] . '"';
					}
				}
				else if ( $instance['widget_mode'] == "0" )
				{
					if ( !is_singular() ) {
						return;
					}
					global $post;
					$attachments = $MP3JP->getPostAttachedAudio( $post->ID );
					$filenames = '';
					
					if ( $attachments !== false ) {
						foreach ( $attachments as $a ) {
							$filenames .= substr( strrchr( $a->guid, "/" ), 1 ) . ',';
						}
					}
					else {
						return;
					}
					$SHORTCODE .= ' tracks="' . $filenames . '"';
				}
				
				//params
				$list = ( $instance['playlist_mode'] == "true" ) ? "y" : "n";
				$autoplay = ( $instance['autoplay'] == "true" ) ? "y" : "n";
				$loop = ( $instance['loop'] == "true" ) ? "y" : "n";
				$pn = ( $instance['pn_buttons'] == "true" ) ? "y" : "n";
				$stop = ( $instance['stop_button'] == "true" ) ? "y" : "n";
				$images = ( $instance['images'] == "true" ) ? "true" : "false";
				$height = ( $instance['player_height'] == "" ) ? $MP3JP->theSettings['playerHeight'] : $instance['player_height'];
				
				$SHORTCODE .= ' list="' . $list . '"';
				$SHORTCODE .= ' autoplay="' . $autoplay . '"';
				$SHORTCODE .= ' loop="' . $loop . '"';
				$SHORTCODE .= ' pn="' . $pn . '"';
				$SHORTCODE .= ' stop="' . $stop . '"';
				$SHORTCODE .= ' images="' . $images . '"';
				//
				$SHORTCODE .= ' vol="' . $instance['volume'] . '"';
				$SHORTCODE .= ' dload="' . $instance['download_link'] . '"';
				$SHORTCODE .= ' pos="' . $instance['position'] . '"';
				$SHORTCODE .= ' shuffle="' . $instance['shuffle'] . '"';
				$SHORTCODE .= ' pick="' . $instance['slice_size'] . '"';
				$SHORTCODE .= ' height="' . $height . '"';
				$SHORTCODE .= ' style="' . $instance['style'] . '"';
				$SHORTCODE .= ']';

				//process it
				$MP3JP->Caller = "widget";
				$shortcodes_return = do_shortcode( $SHORTCODE );
				$MP3JP->Caller = false;
				
				//print it
				if ( '' !== $shortcodes_return )
				{
					extract( $args ); // supplied WP theme vars 
					echo $before_widget;
					if ( $instance['title'] ) { 
						echo $before_title . $instance['title'] . $after_title; 
					}
					echo $shortcodes_return;
					echo $after_widget;
				}
				
				return;
			}
			
			
			/*	Updates the widget settings (required by api) */			
			function update( $new_instance, $old_instance )
			{
				global $MP3JP;
				
				$instance = $old_instance;
				$instance['title'] = $new_instance['title'];
				$instance['id_to_play'] = strip_tags( $new_instance['id_to_play'] );
				$instance['widget_mode'] = $new_instance['widget_mode'];
				$instance['shuffle'] = $new_instance['shuffle'];
				$instance['restrict_list'] = strip_tags( $new_instance['restrict_list'] );
				$instance['restrict_mode'] = $new_instance['restrict_mode'];
				$instance['play_library'] = $new_instance['play_library'];
				$instance['arb_playlist'] = strip_tags( $new_instance['arb_playlist'] );
				$instance['play_page'] = $new_instance['play_page'];
				$instance['slice_size'] = strip_tags( $new_instance['slice_size'] );
				$instance['play_folder'] = $new_instance['play_folder'];
				$instance['download_link'] = $new_instance['download_link'];
				$instance['playlist_mode'] = $new_instance['playlist_mode'];
				$instance['player_width'] = $new_instance['player_width'];
				$instance['autoplay'] = $new_instance['autoplay'];
				$instance['loop'] = $new_instance['loop'];
				$instance['mods'] = $new_instance['mods'];
				$instance['position'] = $new_instance['position'];
				$instance['pn_buttons'] = $new_instance['pn_buttons'];
				$instance['stop_button'] = $new_instance['stop_button'];
				$instance['player_height'] = $new_instance['player_height'];
				$instance['style'] = $new_instance['style'];
				$instance['images'] = $new_instance['images'];
				
				/*
				$instance['folder_to_play'] = strip_tags( $new_instance['folder_to_play'] );
				if ( strpos($instance['folder_to_play'], "http://") === false && strpos($instance['folder_to_play'], "www.") === false ) {
					if ( !empty($instance['folder_to_play']) ) {
						$instance['folder_to_play'] = trim($instance['folder_to_play']);
						if ( $instance['folder_to_play'] != "/" ) {
							$instance['folder_to_play'] = trim($instance['folder_to_play'], "/");
							$instance['folder_to_play'] = "/" . $instance['folder_to_play'];
						}
					}
				}
				*/
				
				//$instance['folder_to_play'] = $MP3JP->prep_path( strip_tags( $new_instance['folder_to_play'] ) );
				$instance['folder_to_play'] = $MP3JP->prep_path( $new_instance['folder_to_play'] );
				
				$instance['volume'] = preg_replace("/[^0-9]/", "", $new_instance['volume']); 
				if ($instance['volume'] < 0 || $instance['volume']=="") { $instance['volume'] = "0"; }
				if ($instance['volume'] > 100) { $instance['volume'] = "100"; }
								
				return $instance;
			}

			
			/*	Creates defaults and writes widget panel (required by api) */						
			function form( $instance )
			{
				global $MP3JP;
				$MP3JP->theSettings = get_option('mp3FoxAdminOptions');
				
				$defaultvalues = array(
					'title' => '',
					'id_to_play' => '',
					'widget_mode' => '1',
					'shuffle' => 'false',
					'restrict_list' => '',
					'restrict_mode' => 'exclude',
					'play_library' => 'false',
					'arb_playlist' => '',
					'play_page' => 'false',
					'slice_size' => '',
					'play_folder' => 'false',
					'folder_to_play' => $MP3JP->theSettings['mp3_dir'],
					'download_link' => $MP3JP->theSettings['show_downloadmp3'],
					'playlist_mode' => $MP3JP->theSettings['playlist_show'],
					'player_width' => '100%',
					'autoplay' => $MP3JP->theSettings['auto_play'],
					'loop' => $MP3JP->theSettings['playlist_repeat'],
					'volume' => $MP3JP->theSettings['initial_vol'],
					'mods' => 'false',
					'position' => 'rel-L',
					'pn_buttons' => 'false',
					'stop_button' => 'false',
					'player_height' => $MP3JP->theSettings['playerHeight'],
					'style' => 'nolistbutton',
					'images' => 'true'
				);
				
				$instance = wp_parse_args( (array) $instance, $defaultvalues );
				$helptext_col = "color:#a0a0a0;";
				?>					
					
					<h3><?php _e( 'Play Mode:', 'mp3-jplayer' ); ?></h3>
					<input type="radio" id="<?php echo $this->get_field_id( 'widget_mode' ); ?>_0" name="<?php echo $this->get_field_name( 'widget_mode' ); ?>" value="0" <?php if ($instance['widget_mode'] == "0") { echo 'checked="checked"'; }?> /> 
					<label style="padding:0; margin:0;" for="<?php echo $this->get_field_id( 'widget_mode' ); ?>_0"><strong><?php _e( 'Play Attached audio', 'mp3-jplayer' ); ?></strong></label>
					<p style="margin:3px 0 15px 18px;"><span class="description"><?php _e( 'Automatically adds a player on SINGLE posts/pages when they have audio attachments.', 'mp3-jplayer' ); ?></span></p>
					
					<input type="radio" id="<?php echo $this->get_field_id( 'widget_mode' ); ?>_1" name="<?php echo $this->get_field_name( 'widget_mode' ); ?>" value="1" <?php if ($instance['widget_mode'] == "1") { echo 'checked="checked"'; }?> />
					<label for="<?php echo $this->get_field_id( 'widget_mode' ); ?>_1"><strong><?php _e( 'Play Custom fields', 'mp3-jplayer' ); ?></strong></label> 
					<p style="margin:3px 0 15px 18px;"><span class="description"><?php _e( 'Automatically adds a player on SINGLE posts/pages, if you have used custom fields to make a playlist.', 'mp3-jplayer' ); ?></span></p>
					
					<input type="radio" id="<?php echo $this->get_field_id( 'widget_mode' ); ?>_2" name="<?php echo $this->get_field_name( 'widget_mode' ); ?>" value="2" <?php if ($instance['widget_mode'] == "2") { echo 'checked="checked"'; }?> />
					<label for="<?php echo $this->get_field_id( 'widget_mode' ); ?>_2"><strong><?php _e( 'Write a Playlist...', 'mp3-jplayer' ); ?></strong></label>
					<p style="margin:3px 0 10px 18px;"><span class="description"><?php _e( 'A list of URLs, library/default folder filenames, or FEEDs, separated by', 'mp3-jplayer' ); ?></span> <code><?php echo $MP3JP->theSettings['f_separator']; ?></code></p>
					<div style="margin:0px 0 15px 24px;"><textarea class="widefat" style="font-size:11px;" rows="4" cols="80" id="<?php echo $this->get_field_id( 'arb_playlist' ); ?>" name="<?php echo $this->get_field_name( 'arb_playlist' ); ?>"><?php echo $instance['arb_playlist']; ?></textarea></div>
					
					<input type="radio" id="<?php echo $this->get_field_id( 'widget_mode' ); ?>_3" name="<?php echo $this->get_field_name( 'widget_mode' ); ?>" value="3" <?php if ($instance['widget_mode'] == "3") { echo 'checked="checked"'; }?> />
					<label for="<?php echo $this->get_field_id( 'widget_mode' ); ?>_3"><strong><?php _e( 'Generate Playlist...', 'mp3-jplayer' ); ?></strong></label>
					<div style="margin-left:24px;">
						<p style="margin-bottom:0px;"><input type="checkbox" id="<?php echo $this->get_field_id( 'play_library' ); ?>" name="<?php echo $this->get_field_name( 'play_library' ); ?>" value="true" <?php if ($instance['play_library'] == "true") { echo 'checked="checked"'; }?> />
							<?php _e( 'My library', 'mp3-jplayer' ); ?></p>
							
						<?php
						$folder = $instance['folder_to_play'];
						$fdetails = $MP3JP->grabFolderURLs( $folder, $MP3JP->formatsFeedRegex );
						if ( is_array( $fdetails ) )
						{
							$foldertracks = $fdetails['files'];
							
							if ( ($c = count($foldertracks)) > 0 ) { 
								$style = "color:#282;";
								$txt = $c . "&nbsp;file";
								if ( $c != 1 ) { $txt .= "s"; }
								$txt .= "&nbsp; available";
							} else {
								$style = "color:#aaa;";
								$txt = __( 'There are no audio files here', 'mp3-jplayer' );
							}
						} 
						elseif ( $fdetails === true )
						{
							$txt = __( 'Folder not found, check path and permissions', 'mp3-jplayer' ); 
							$style = "color:#dfad00;";
						}
						else
						{
							$txt = __( 'Remote or inaccessible folder', 'mp3-jplayer' );
							$style = "color:#f56b0f;";
						}
						?>
						<p style="margin-top:2px; margin-bottom:5px;"><input type="checkbox" id="<?php echo $this->get_field_id( 'play_folder' ); ?>" name="<?php echo $this->get_field_name( 'play_folder' ); ?>" value="true" <?php if ($instance['play_folder'] == "true") { echo 'checked="checked"'; }?> />
							<?php _e( 'A folder:', 'mp3-jplayer' ); ?> &nbsp;
							<input class="widefat" type="text" style="max-width:200px; font-size:12px;" id="<?php echo $this->get_field_id( 'folder_to_play' ); ?>" name="<?php echo $this->get_field_name( 'folder_to_play' ); ?>" value="<?php echo $instance['folder_to_play']; ?>" /></p>
						<p class="description" style="margin:0px 0px 0px 80px; font-size:12px; font-weight:700; <?php echo $style; ?>"><?php echo $txt; ?></p>
					</div>
					
	
					<hr/>
					<h3><?php _e( 'Player Settings:', 'mp3-jplayer' ); ?></h3>
					
					<div style="float:left; margin:0 30px 0 0; padding:6px 0 20px 0;">
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'autoplay' ); ?>" name="<?php echo $this->get_field_name( 'autoplay' ); ?>" value="true" <?php if ($instance['autoplay'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Autoplay', 'mp3-jplayer' ); ?></strong></p>
						
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'loop' ); ?>" name="<?php echo $this->get_field_name( 'loop' ); ?>" value="true" <?php if ($instance['loop'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Repeat', 'mp3-jplayer' ); ?></strong></p>
						
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'playlist_mode' ); ?>" name="<?php echo $this->get_field_name( 'playlist_mode' ); ?>" value="true" <?php if ($instance['playlist_mode'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Show playlist', 'mp3-jplayer' ); ?></strong></p>
							
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'images' ); ?>" name="<?php echo $this->get_field_name( 'images' ); ?>" value="true" <?php if ($instance['images'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Show images', 'mp3-jplayer' ); ?></strong></p>
						
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'stop_button' ); ?>" name="<?php echo $this->get_field_name( 'stop_button' ); ?>" value="true" <?php if ($instance['stop_button'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Stop button', 'mp3-jplayer' ); ?></strong></p>
							
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'pn_buttons' ); ?>" name="<?php echo $this->get_field_name( 'pn_buttons' ); ?>" value="true" <?php if ($instance['pn_buttons'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Prev/next', 'mp3-jplayer' ); ?></strong></p>
						
						
						
						<p style="margin:0 0 2px 0;"><input type="checkbox" id="<?php echo $this->get_field_id( 'shuffle' ); ?>" name="<?php echo $this->get_field_name( 'shuffle' ); ?>" value="true" <?php if ($instance['shuffle'] == "true") { echo 'checked="checked"'; }?> />
							<strong><?php _e( 'Shuffle', 'mp3-jplayer' ); ?></strong></p>
					</div>
					
					<div style="float:right; padding:0px 0 10px 0;">
						<table>
							<tr>
								<td><strong><?php _e( 'Volume', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input class="widefat" style="width:40px; text-align:right;" type="text" id="<?php echo $this->get_field_id( 'volume' ); ?>" name="<?php echo $this->get_field_name( 'volume' ); ?>" value="<?php echo $instance['volume']; ?>" /> 
									<span class="description">(0 - 100)</span></td>
							</tr>
						
							<tr>
								<td><strong><?php _e( 'Pick', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input class="widefat" style="width:40px; text-align:right;" type="text" id="<?php echo $this->get_field_id( 'slice_size' ); ?>" name="<?php echo $this->get_field_name( 'slice_size' ); ?>" value="<?php echo $instance['slice_size']; ?>" />
									&nbsp;<span class="description"><?php _e( 'track(s)', 'mp3-jplayer' ); ?></span></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Download', 'mp3-jplayer' ); ?></strong>:</td>
										<td><select id="<?php echo $this->get_field_id( 'download_link' ); ?>" name="<?php echo $this->get_field_name( 'download_link' ); ?>" class="widefat" style="max-width:100px;">
												<option value="true" <?php if ( 'true' == $instance['download_link'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Yes', 'mp3-jplayer' ); ?></option>
												<option value="false" <?php if ( 'false' == $instance['download_link'] ) { echo 'selected="selected"'; } ?>><?php _e( 'No', 'mp3-jplayer' ); ?></option>
												<option value="loggedin" <?php if ( 'loggedin' == $instance['download_link'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Logged in', 'mp3-jplayer' ); ?></option>
											</select></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Width', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input class="widefat" style="max-width:100px;" type="text" id="<?php echo $this->get_field_id( 'player_width' ); ?>" name="<?php echo $this->get_field_name( 'player_width' ); ?>" value="<?php echo $instance['player_width']; ?>" /></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Height', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input class="widefat" style="max-width:100px;" type="text" id="<?php echo $this->get_field_id( 'player_height' ); ?>" name="<?php echo $this->get_field_name( 'player_height' ); ?>" value="<?php echo $instance['player_height']; ?>" /></td>
							</tr>
							<tr>
								<td><strong><?php _e( 'Align', 'mp3-jplayer' ); ?></strong>:</td>
								<td><select id="<?php echo $this->get_field_id( 'position' ); ?>" name="<?php echo $this->get_field_name( 'position' ); ?>" class="widefat" style="max-width:100px;">
										<option value="rel-L" <?php if ( 'rel-L' == $instance['position'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Left', 'mp3-jplayer' ); ?></option>
										<option value="rel-C" <?php if ( 'rel-C' == $instance['position'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Centre', 'mp3-jplayer' ); ?></option>
										<option value="rel-R" <?php if ( 'rel-R' == $instance['position'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Right', 'mp3-jplayer' ); ?></option>
										<option value="left" <?php if ( 'left' == $instance['position'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Float left', 'mp3-jplayer' ); ?></option>
										<option value="right" <?php if ( 'right' == $instance['position'] ) { echo 'selected="selected"'; } ?>><?php _e( 'Float right', 'mp3-jplayer' ); ?></option>
									</select></td>
							</tr>
						</table>
					</div>
					
					<br class="clear">
					<strong><?php _e( 'Style', 'mp3-jplayer' ); ?></strong>:<br>
					<input class="widefat" style="max-width:80%;" type="text" id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>" value="<?php echo $instance['style']; ?>" />
						
					
					
					
					
					<br><br><br><hr/>
					<h3><?php _e( 'Widget Heading:', 'mp3-jplayer' ); ?></h3>
					<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
					
					
					
					<br>
					<h3><?php _e( 'Page Filter:', 'mp3-jplayer' ); ?></h3>
					<p style="line-height:200%; margin-top:-10px;"><strong><?php _e( 'Include', 'mp3-jplayer' ); ?></strong> 
						<input type="radio" id="<?php echo $this->get_field_id( 'restrict_mode' ); ?>" name="<?php echo $this->get_field_name( 'restrict_mode' ); ?>" value="include" <?php if ($instance['restrict_mode'] == "include") { echo 'checked="checked"'; }?> />
						<?php _e( 'or', 'mp3-jplayer' ); ?>&nbsp;
						<input type="radio" id="<?php echo $this->get_field_id( 'restrict_mode' ); ?>" name="<?php echo $this->get_field_name( 'restrict_mode' ); ?>" value="exclude" <?php if ($instance['restrict_mode'] == "exclude") { echo 'checked="checked"'; }?> />
						<strong><?php _e( 'Exclude', 'mp3-jplayer' ); ?></strong> &nbsp;
						<input class="widefat" style="font-size:11px; max-width:200px;" type="text" id="<?php echo $this->get_field_id( 'restrict_list' ); ?>" name="<?php echo $this->get_field_name( 'restrict_list' ); ?>" value="<?php echo $instance['restrict_list']; ?>" />
					</p>
					<p style="line-height:140%; margin-top:-8px; margin-bottom:20px;"><span><?php _e( 'A comma separated list, it can contain <code>index</code>, <code>archive</code>, <code>post</code>, <code>search</code>, and any <strong>post IDs</strong>.', 'mp3-jplayer' ); ?></span></p>
					
					
					
					<!--retired-->
					<div style="display:none;"><input type="checkbox" id="<?php echo $this->get_field_id( 'play_page' ); ?>" name="<?php echo $this->get_field_name( 'play_page' ); ?>" value="true" <?php if ($instance['play_page'] == "true") { echo 'checked="checked"'; }?> />
						From page ID &nbsp;
						<input class="widefat" style="width:55px;" type="text" id="<?php echo $this->get_field_id( 'id_to_play' ); ?>" name="<?php echo $this->get_field_name( 'id_to_play' ); ?>" value="<?php echo $instance['id_to_play']; ?>" />
						<input type="checkbox" id="<?php echo $this->get_field_id( 'mods' ); ?>" name="<?php echo $this->get_field_name( 'mods' ); ?>" value="true" <?php if ($instance['mods'] == "true") { echo 'checked="checked"'; }?> />
					</div>
					
					<hr/><br>
					
					
			<?php	
			}
		} //end class
	}	
}
?>