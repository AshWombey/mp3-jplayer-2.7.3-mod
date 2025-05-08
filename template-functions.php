<?php

//hooks - settings
function MJPsettings_players() {
	do_action( 'MJPsettings_players' );
}
function MJPsettings_mp3t() {
	do_action( 'MJPsettings_mp3t' );
}
function MJPsettings_mp3j() {
	do_action( 'MJPsettings_mp3j' );
}
function MJPsettings_after_mp3tj() { 
	do_action( 'MJPsettings_after_mp3tj' );
}
function MJPsettings_playlist() {
	do_action( 'MJPsettings_playlist' );
} 
function MJPsettings_downloads_above() {
	do_action( 'MJPsettings_downloads_above' );
}

function MJPsettings_submit() {
	do_action( 'MJPsettings_submit' );
}


//hooks - design
function MJPdesign_text() {
	do_action( 'MJPdesign_text' );
}
function MJPdesign_areas() {
	do_action( 'MJPdesign_areas' );
}
function MJPdesign_fonts() {
	do_action( 'MJPdesign_fonts' );
}
function MJPdesign_alignments() {
	do_action( 'MJPdesign_alignments' );
}
function MJPdesign_mods() {
	do_action( 'MJPdesign_mods' );
}
function MJPdesign_submit() {
	do_action( 'MJPdesign_submit' );
}


//hooks - process
function MJPfront_mp3t( $atts = array() ) {
	return apply_filters( 'MJPfront_mp3t', $atts );
}
function MJPfront_mp3j( $atts = array() ) {
	return apply_filters( 'MJPfront_mp3j', $atts );
}
function MJPfront_playlist_player( $atts = array() ) {
	return apply_filters( 'MJPfront_playlist_player', $atts );
}
function MJPfront_popout_player( $atts = array() ) {
	return apply_filters( 'MJPfront_popout_player', $atts );
}
function MJPwrite_js_playlist( $js = '', $track ) {
	return apply_filters( 'MJPwrite_js_playlist', $js, $track );
}


//hooks - frontend templates
function mp3j_addscripts( $style = "" ) {
	do_action('mp3j_addscripts', $style);
}
function mp3j_put( $shortcodes = "" ) {
	do_action( 'mp3j_put', $shortcodes );
}
function mp3j_debug( $display = "" ) {
	do_action('mp3j_debug', $display);
}
function mp3j_grab_library( $format = "" ) { 
	$lib = apply_filters('mp3j_grab_library', '' );
	return $lib;
}
function mp3j_settings ( $settings = array() ) {
	do_action('mp3j_settings', $settings );
}


//retired
function mp3j_set_meta( $tracks, $captions = "", $startnum = 1 ) { } //since 1.7
function mp3j_flag( $set = 1 ) { } //since 1.6
function mp3j_div() { } //since 1.8



// Fetch / Draw Library list
//~~~ Draw
function ax_mjp_liblist ()
{
	$I = ax_mjp_getPostInfo();
	$stuff = ax_mjp_getLibraryPaged( $I );
	$html = '';
	
	if ( isset( $stuff['results'] ) && is_array( $stuff['results'] ) ) {
	
		$AUDIO = $stuff['results'];
		$blank = '<span class="blank">-</span>';
		
		$n = 1 + $I['offset'];
		$rowClass = 'even';
		
		$html .= '<div class="library-list"><table class="fileList">';
		$html .= 	'<tr>';
		$html .= 		'<th style="width:35px; padding-right:0;"></th>';
		$html .= 		'<th style="width:20px; text-align:right;"></th>';
		$html .= 		'<th>Title</th>';
		$html .= 		'<th>Artist</th>';
		
		$html .= 		'<th>Album</th>';
		$html .= 		'<th>Caption</th>';
		$html .= 		'<th>Uploaded</th>';
		$html .= 		'<th>Filename</th>';
		$html .= 	'</tr>';								
		
		foreach ( $AUDIO as $i => $post )
		{	
			$id3data = wp_get_attachment_metadata( $post->ID, true );
			
			if ( ! is_array( $id3data ) ) {
				$id3data = array();
			}
			$id3data['artist'] = ( empty( $id3data['artist'] ) ) ? $blank : $id3data['artist'];
			$id3data['album'] = ( empty( $id3data['album'] ) ) ? $blank : $id3data['album'];
			
			$niceDate = date( 'jS F Y', strtotime( $post->post_date_gmt ) );
			$rowClass = ( $rowClass === 'even' ) ? 'odd' : 'even';
			
			$filename = strrchr( $post->guid, "/");
			$filename = str_replace( "/", "", $filename );
			
			$html .= '<tr class="' . $rowClass . '">';
			$html .= 	'<td class="files-edit" style="width:35px; padding-right:0;"><a href="post.php?post=' . $post->ID . '&amp;action=edit" target="_blank">Edit</a></td>';
			$html .= 	'<td class="files-number" style="width:20px; text-align:right;">' . $n . '</td>';
			$html .= 	'<td class="files-title">' . $post->post_title . '</td>';
			$html .= 	'<td class="files-artist">' . $id3data['artist'] . '</td>';
			
			$html .= 	'<td class="files-album">' . $id3data['album'] . '</td>';
			$html .= 	'<td class="files-caption">' . $post->post_excerpt . '</td>';
			$html .= 	'<td class="files-date">' . $niceDate . '</td>';
			$html .= 	'<td class="files-url">' . $filename . '</td>';
			$html .= '</tr>';
										
			$n++;
		}
		$html .= '</table></div>';
		
	} else {
		$html = '<p>No</p>';
		$html .= '<span class="tabD">No audio in the Media Library, <a href="media-new.php">Upload new &raquo;</a></strong>';
	}
	
	echo $html . '#DATA#' . $stuff['rows'][0][0];
	die();
}


//~~~ Get data
function ax_mjp_getLibraryPaged ( $I )
{
	global $MP3JP;
	$ops = $MP3JP->theSettings;
	$LIB = false;
	
	$MIMES = "post_mime_type = 'audio/mpeg' OR post_mime_type = 'audio/ogg' OR post_mime_type = 'audio/wav' OR post_mime_type = 'audio/webm'";
	
	switch( $MP3JP->theSettings['library_sortcol'] ) {
		case "date": 
			$order = " ORDER BY post_date " . $ops['library_direction']; 
			break;
		case "title":
			$order = " ORDER BY post_title " . $ops['library_direction']; 
			break;
		case "caption": 
			$order = " ORDER BY post_excerpt " . $ops['library_direction'] . ", post_title " . $ops['library_direction']; 
			break;
		default: 
			$order = "";
	}
		
	$offset = ( isset( $I['offset'] ) ) ? $I['offset'] . ',' : '0,';
	$limit = ( isset( $I['limit'] ) && 'all' !== $I['limit'] ) ? ' LIMIT ' . $offset . $I['limit'] : '';
	
	global $wpdb;
	$results = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts WHERE " . $MIMES . $order . $limit );
	$totalRows = $wpdb->get_results( 'SELECT FOUND_ROWS()', ARRAY_N );
	
	return array(
		'results'	=> $results,
		'rows'		=> $totalRows,
	);
}


//~~~ Clean POST
function ax_mjp_getPostInfo ()
{
	global $MP3JP;
	$cleaned = array();
	if ( isset( $_POST['info'] ) && is_array( $_POST['info'] ) ) {
		foreach ( $_POST['info'] as $k => $val ) {
			$cleaned[ $k ] = $MP3JP->strip_scripts( $val ); 
		}
	}
	return $cleaned;
}


?>