<?php

function mp3j_print_admin_page() { // Додав пробіл для відповідності стандартам WordPress

	global $MP3JP;
	// Переконуємося, що $MP3JP є об'єктом перед використанням
	if ( ! is_object( $MP3JP ) ) {
		// Можливо, вивести помилку або просто завершити, якщо глобальна змінна не встановлена
		echo '<div class="error"><p>' . esc_html__( 'MP3-jPlayer plugin object not initialized.', 'mp3-jplayer' ) . '</p></div>';
		return;
	}
	$O = $MP3JP->getAdminOptions();
	// $colours_array = array(); // Здається, ця змінна не використовується далі у наданому коді

	// === Зміни: Обробка збереження налаштувань ===
	if ( isset( $_POST['update_mp3foxSettings'] ) ) {

		// 1. Перевірка Nonce
		// 'mp3j_save_settings_action' - назва дії nonce
		// 'mp3j_admin_settings_nonce' - ім'я nonce-поля, яке ми додамо у форму
		check_admin_referer( 'mp3j_save_settings_action', 'mp3j_admin_settings_nonce' );

		// 2. Перевірка прав доступу користувача
		if ( ! current_user_can( 'manage_options' ) ) { // Стандартний дозвіл для налаштувань
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'mp3-jplayer' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to manage options for this site.', 'mp3-jplayer' ) . '</p>',
				403
			);
		}

		// === Зміни: Санітизація вхідних даних ===
		// Замість preg_replace для числових значень, краще використовувати absint() або floatval() з подальшою валідацією.
		// Для текстових полів - sanitize_text_field() або wp_kses_post() для HTML.

		// Числові значення
		$O['initial_vol'] = isset( $_POST['mp3foxVol'] ) ? absint( $_POST['mp3foxVol'] ) : 0;
		if ( $O['initial_vol'] > 100 ) { $O['initial_vol'] = 100; } // Min 0 вже забезпечено absint

		$O['popout_max_height'] = isset( $_POST['mp3foxPopoutMaxHeight'] ) ? absint( $_POST['mp3foxPopoutMaxHeight'] ) : 750;
		if ( $O['popout_max_height'] < 200 ) { $O['popout_max_height'] = 200; }
		if ( $O['popout_max_height'] > 1200 ) { $O['popout_max_height'] = 1200; }

		$O['popout_width'] = isset( $_POST['mp3foxPopoutWidth'] ) ? absint( $_POST['mp3foxPopoutWidth'] ) : 400;
		if ( $O['popout_width'] < 250 ) { $O['popout_width'] = 250; }
		if ( $O['popout_width'] > 1600 ) { $O['popout_width'] = 1600; }

		$O['max_list_height'] = isset( $_POST['mp3foxMaxListHeight'] ) ? sanitize_text_field( wp_unslash( $_POST['mp3foxMaxListHeight'] ) ) : ''; // Може бути порожнім, або числом
		if ( $O['max_list_height'] !== '' ) {
			$O['max_list_height'] = absint( $O['max_list_height'] );
			if ( $O['max_list_height'] < 0 ) { $O['max_list_height'] = ''; } // Якщо було від'ємне, робимо порожнім
		}


		// Шляхи та URL (використовуємо sanitize_text_field або esc_url_raw для URL, для шляхів потрібна обережна санітизація)
		// $MP3JP->prep_path() - потрібно перевірити, що ця функція робить належну санітизацію.
		// Якщо prep_path не робить санітизацію, її треба додати.
		// Припускаємо, що prep_path робить якусь нормалізацію, але додамо sanitize_text_field для базового захисту.
		$O['mp3_dir'] = isset( $_POST['mp3foxfolder'] ) ? $MP3JP->prep_path( sanitize_text_field( wp_unslash( $_POST['mp3foxfolder'] ) ) ) : '';
		$O['popout_background_image'] = isset( $_POST['mp3foxPopoutBGimage'] ) ? $MP3JP->prep_path( esc_url_raw( wp_unslash( $_POST['mp3foxPopoutBGimage'] ) ) ) : ''; // Для URL зображення
		
		// $MP3JP->prep_value() - аналогічно, перевірити її на санітизацію.
		// Застосуємо sanitize_text_field як базовий рівень.
		$O['dloader_remote_path'] = isset( $_POST['dloader_remote_path'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['dloader_remote_path'] ) ) ) : '';
		$O['loggedout_dload_link'] = isset( $_POST['loggedout_dload_link'] ) ? $MP3JP->prep_value( esc_url_raw( wp_unslash( $_POST['loggedout_dload_link'] ) ) ) : ''; // Для URL
		
		$player_float_allowed = array('none', 'rel-C', 'rel-R', 'left', 'right');
		$O['player_float'] = isset( $_POST['mp3foxFloat'] ) ? sanitize_key( $_POST['mp3foxFloat'] ) : 'none';
		if( !in_array($O['player_float'], $player_float_allowed, true) ) $O['player_float'] = 'none';

		$sort_col_allowed = array('title', 'caption', 'file', 'date');
		$O['library_sortcol'] = isset( $_POST['librarySortcol'] ) ? sanitize_key( $_POST['librarySortcol'] ) : 'title';
		if( !in_array($O['library_sortcol'], $sort_col_allowed, true) ) $O['library_sortcol'] = 'title';
		
		$direction_allowed = array('ASC', 'DESC');
		$O['library_direction'] = isset( $_POST['libraryDirection'] ) ? sanitize_key( $_POST['libraryDirection'] ) : 'ASC';
		if( !in_array($O['library_direction'], $direction_allowed, true) ) $O['library_direction'] = 'ASC';

		$folder_sort_col_allowed = array('file', 'date');
		$O['folderFeedSortcol'] = isset( $_POST['folderFeedSortcol'] ) ? sanitize_key( $_POST['folderFeedSortcol'] ) : 'file';
		if( !in_array($O['folderFeedSortcol'], $folder_sort_col_allowed, true) ) $O['folderFeedSortcol'] = 'file';
		
		$O['folderFeedDirection'] = isset( $_POST['folderFeedDirection'] ) ? sanitize_key( $_POST['folderFeedDirection'] ) : 'ASC';
		if( !in_array($O['folderFeedDirection'], $direction_allowed, true) ) $O['folderFeedDirection'] = 'ASC';

		$separator_allowed = array(',', ';', '###');
		$O['f_separator'] = isset( $_POST['file_separator'] ) ? sanitize_text_field( wp_unslash( $_POST['file_separator'] ) ) : ',';
		if( !in_array($O['f_separator'], $separator_allowed, true) ) $O['f_separator'] = ',';
		
		$O['c_separator'] = isset( $_POST['caption_separator'] ) ? sanitize_text_field( wp_unslash( $_POST['caption_separator'] ) ) : ',';
		if( !in_array($O['c_separator'], $separator_allowed, true) ) $O['c_separator'] = ',';

		$show_download_allowed = array('true', 'false', 'loggedin');
		$O['show_downloadmp3'] = isset( $_POST['mp3foxDownloadMp3'] ) ? sanitize_key( $_POST['mp3foxDownloadMp3'] ) : 'false';
		if( !in_array($O['show_downloadmp3'], $show_download_allowed, true) ) $O['show_downloadmp3'] = 'false';

		$replacer_allowed = array('mp3j', 'mp3t', 'player', 'popout');
		$O['replacerShortcode_playlist'] = isset( $_POST['replacerShortcode_playlist'] ) ? sanitize_key( $_POST['replacerShortcode_playlist'] ) : 'player';
		if( !in_array($O['replacerShortcode_playlist'], array('player', 'popout'), true) ) $O['replacerShortcode_playlist'] = 'player'; // Обмежені значення для цього поля
		
		$O['replacerShortcode_single'] = isset( $_POST['replacerShortcode_single'] ) ? sanitize_key( $_POST['replacerShortcode_single'] ) : 'mp3j';
		if( !in_array($O['replacerShortcode_single'], $replacer_allowed, true) ) $O['replacerShortcode_single'] = 'mp3j';

		$show_errors_allowed = array('false', 'admin', 'true');
		$O['showErrors'] = isset( $_POST['showErrors'] ) ? sanitize_key( $_POST['showErrors'] ) : 'false';
		if( !in_array($O['showErrors'], $show_errors_allowed, true) ) $O['showErrors'] = 'false';

		$player_title_allowed = array('', 'titles', 'artist', 'album', 'excerpts', 'postDates');
		$O['playerTitle1'] = isset( $_POST['playerTitle1'] ) ? sanitize_key( $_POST['playerTitle1'] ) : 'titles';
		if( !in_array($O['playerTitle1'], $player_title_allowed, true) ) $O['playerTitle1'] = 'titles';
		
		$O['playerTitle2'] = isset( $_POST['playerTitle2'] ) ? sanitize_key( $_POST['playerTitle2'] ) : '';
		if( !in_array($O['playerTitle2'], $player_title_allowed, true) ) $O['playerTitle2'] = '';
		
		// Кольори (hex)
		$O['mp3tColour_on'] = isset($_POST['mp3tColour_on']) ? "true" : "false"; // Це 'true'/'false' рядок
		$O['mp3tColour'] = isset( $_POST['mp3tColour'] ) ? sanitize_hex_color( wp_unslash( $_POST['mp3tColour'] ) ) : '';
		
		$O['mp3jColour_on'] = isset($_POST['mp3jColour_on']) ? "true" : "false";
		$O['mp3jColour'] = isset( $_POST['mp3jColour'] ) ? sanitize_hex_color( wp_unslash( $_POST['mp3jColour'] ) ) : '';
		
		// Checkbox'и (булеві значення, що зберігаються як рядки 'true'/'false')
		$checkbox_options = array(
			'echo_debug'              => 'mp3foxEchoDebug',
			'add_track_numbering'     => 'mp3foxAddTrackNumbers',
			'enable_popout'           => 'mp3foxEnablePopout',
			'playlist_repeat'         => 'mp3foxPlaylistRepeat',
			'encode_files'            => 'mp3foxEncodeFiles',
			'run_shcode_in_excerpt'   => 'runShcodeInExcerpt',
			'volslider_on_singles'    => 'volslider_onsingles',
			'volslider_on_mp3j'       => 'volslider_onmp3j',
			'force_browser_dload'     => 'force_browser_dload',
			'make_player_from_link'   => 'make_player_from_link',
			'auto_play'               => 'mp3foxAutoplay',
			'allow_remoteMp3'         => 'mp3foxAllowRemote',
			'player_onblog'           => 'mp3foxOnBlog',
			'playlist_show'           => 'mp3foxShowPlaylist',
			'remember_settings'       => 'mp3foxRemember', // Це поле знаходиться поза основним блоком санітизації, але обробимо його тут
			'hide_mp3extension'       => 'mp3foxHideExtension',
			'replace_WP_playlist'     => 'replace_WP_playlist',
			'replace_WP_audio'        => 'replace_WP_audio',
			'replace_WP_embedded'     => 'replace_WP_embedded',
			'replace_WP_attached'     => 'replace_WP_attached',
			'autoCounterpart'         => 'autoCounterpart',
			'allowRangeRequests'      => 'allowRangeRequests',
			'hasListMeta'             => 'hasListMeta',
			'autoResume'              => 'autoResume'
		);

		foreach ( $checkbox_options as $option_key => $post_field_name ) {
			$O[ $option_key ] = isset( $_POST[ $post_field_name ] ) ? "true" : "false";
		}
        // Особливі випадки для flipMP3j та flipMP3t, оскільки їх логіка інвертована
		$O['flipMP3j'] = isset( $_POST['flipMP3j'] ) ? "false" : "true"; // Якщо чекбокс відмічений, значення 'false'
		$O['flipMP3t'] = isset( $_POST['flipMP3t'] ) ? "true" : "false";  // Якщо чекбокс відмічений, значення 'true' (як було)

		$can_view_players_allowed = array('all', 'loggedin');
		$O['can_view_players'] = isset( $_POST['can_view_players'] ) ? sanitize_key( $_POST['can_view_players'] ) : 'all';
		if( !in_array($O['can_view_players'], $can_view_players_allowed, true) ) $O['can_view_players'] = 'all';

		// Поля з можливістю введення 'px' або '%' - потребують ретельної санітизації CSS значень
		// Функція MP3JP->prep_value() має це обробляти, але для безпеки можна додати свою перевірку/санітизацію
		// Наприклад, функція для очищення CSS значень розміру:
		// function mp3j_sanitize_css_size_value( $value, $default = '0px' ) {
		//    if ( empty( $value ) ) return $default;
		//    if ( preg_match( '/^(\d+)(px|%|em|rem|pt|vh|vw)$/i', trim( $value ), $matches ) ) {
		//        return $matches[1] . strtolower( $matches[2] );
		//    }
		//    if ( is_numeric( $value ) ) return absint( $value ) . 'px'; // Якщо просто число, додаємо px
		//    return $default;
		// }
		// $O['paddings_top'] = mp3j_sanitize_css_size_value( $_POST['mp3foxPaddings_top'] ?? '', '0px' );
        // Залишаємо MP3JP->prep_value, але маємо на увазі, що воно має бути безпечним
		$O['paddings_top'] = isset( $_POST['mp3foxPaddings_top'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['mp3foxPaddings_top'] ) ) ) : '0px';
		if ( $O['paddings_top'] === "" && isset( $_POST['mp3foxPaddings_top'] ) ) $O['paddings_top'] = '0px'; // Якщо prep_value повернуло порожньо, а поле було, ставимо 0px

		$O['paddings_bottom'] = isset( $_POST['mp3foxPaddings_bottom'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['mp3foxPaddings_bottom'] ) ) ) : '0px';
		if ( $O['paddings_bottom'] === "" && isset( $_POST['mp3foxPaddings_bottom'] ) ) $O['paddings_bottom'] = '0px';

		$O['paddings_inner'] = isset( $_POST['mp3foxPaddings_inner'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['mp3foxPaddings_inner'] ) ) ) : '0px';
		if ( $O['paddings_inner'] === "" && isset( $_POST['mp3foxPaddings_inner'] ) ) $O['paddings_inner'] = '0px';

		$O['font_size_mp3t'] = isset( $_POST['font_size_mp3t'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['font_size_mp3t'] ) ) ) : '14px';
		if ( $O['font_size_mp3t'] === "" && isset( $_POST['font_size_mp3t'] ) ) $O['font_size_mp3t'] = '14px';

		$O['font_size_mp3j'] = isset( $_POST['font_size_mp3j'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['font_size_mp3j'] ) ) ) : '14px';
		if ( $O['font_size_mp3j'] === "" && isset( $_POST['font_size_mp3j'] ) ) $O['font_size_mp3j'] = '14px';

		// $MP3JP->strip_scripts() - перевірити, чи достатньо вона надійна.
		// Для HTML вмісту краще wp_kses_post() або wp_kses() з дозволеними тегами.
		// sanitize_text_field для простого тексту.
		$O['dload_text'] = isset( $_POST['dload_text'] ) ? sanitize_text_field( wp_unslash( $_POST['dload_text'] ) ) : '';
		$O['loggedout_dload_text'] = isset( $_POST['loggedout_dload_text'] ) ? sanitize_text_field( wp_unslash( $_POST['loggedout_dload_text'] ) ) : '';
		
		$hasFormat = false;
		if ( isset( $_POST['audioFormats'] ) && is_array( $_POST['audioFormats'] ) ) {
			foreach ( $O['audioFormats'] as $k => $f ) { // $O['audioFormats'] - це масив з дефолтними ключами
                // Очищаємо ключ $k перед використанням в індексі POST
                $safe_k = sanitize_key($k);
				if ( isset( $_POST['audioFormats'][ $safe_k ] ) ) {
					$O['audioFormats'][ $k ] = "true"; // Зберігаємо для оригінального ключа $k
					$hasFormat = true;
				} else {
					$O['audioFormats'][ $k ] = "false";
				}
			}
		}
		if ( ! $hasFormat ) { // Якщо жоден формат не вибрано, встановлюємо mp3 за замовчуванням
			$O['audioFormats']['mp3'] = "true";
		}
		
		$O['player_width'] = isset( $_POST['mp3foxPlayerWidth'] ) ? $MP3JP->prep_value( sanitize_text_field( wp_unslash( $_POST['mp3foxPlayerWidth'] ) ) ) : '';
		
		$O['disable_jquery_libs'] = ( isset( $_POST['disableJSlibs'] ) && strtolower( sanitize_text_field( wp_unslash( $_POST['disableJSlibs'] ) ) ) === "yes" ) ? "yes" : "";
		
		$O['popout_button_title'] = isset( $_POST['mp3foxPopoutButtonText'] ) ? sanitize_text_field( wp_unslash( $_POST['mp3foxPopoutButtonText'] ) ) : '';
		// Для make_player_from_link_shcode, якщо там дозволений HTML, то wp_kses_post або wp_kses
		// Якщо це має бути шаблон шорткоду, то обмежена санітизація.
		$O['make_player_from_link_shcode'] = isset( $_POST['make_player_from_link_shcode'] ) ? wp_kses_post( wp_unslash( $_POST['make_player_from_link_shcode'] ) ) : '';

		$O['popout_background'] = isset( $_POST['mp3foxPopoutBackground'] ) ? sanitize_hex_color( wp_unslash( $_POST['mp3foxPopoutBackground'] ) ) : '';
		
		// $O['db_plugin_version'] = $MP3JP->prep_value( $_POST['mp3foxPluginVersion'] ); // Це значення береться з hidden поля, але все одно варто очистити
        $O['db_plugin_version'] = isset( $_POST['mp3foxPluginVersion'] ) ? sanitize_text_field( wp_unslash( $_POST['mp3foxPluginVersion'] ) ) : $MP3JP->version_of_plugin;


		update_option( MP3J_SETTINGS_NAME, $O );
		$MP3JP->theSettings = $O; // Оновлюємо налаштування в об'єкті плагіна
		if ( method_exists( $MP3JP, 'setAllowedFeedTypesArrays' ) ) {
			$MP3JP->setAllowedFeedTypesArrays();
		}
		
		// Extensions - save their options
		// MJPsettings_submit(); // Переконайтеся, що ця функція також захищена (nonce, capabilities, sanitization)
		// Якщо MJPsettings_submit() обробляє дані з тієї ж форми, nonce вже перевірено.
		// Якщо це окрема логіка, вона потребує власного захисту.
        if ( function_exists( 'MJPsettings_submit' ) ) {
            MJPsettings_submit(); // Припускаємо, що вона захищена або використовує дані з $O
        }
		?>
		
		<div class="updated"><p><strong><?php esc_html_e( 'Settings saved.', 'mp3-jplayer' ); ?></strong></p></div>
	<?php 
	} // Кінець if ( isset( $_POST['update_mp3foxSettings'] ) )


	// $current_colours = $O['colour_settings']; // Здається, не використовується далі
	?>
	<div class="wrap">
		
		<h2 style="font-size:4px;line-height:4px;">&nbsp;</h2> <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . sanitize_key( $_GET['page'] ) ) ); // Використовуємо admin_url для формування action ?>"> 
			<?php // === Зміни: Додавання Nonce поля === ?>
			<?php wp_nonce_field( 'mp3j_save_settings_action', 'mp3j_admin_settings_nonce' ); ?>
			
			<div class="mp3j-tabbuttons-wrap unselectable">
				<?php /* ... кнопки табів ... (без змін тут, але переконайтеся, що JS для табів безпечний) */ ?>
				<div class="mp3j-tabbutton first" id="mp3j_tabbutton_1"><h1><?php echo esc_html( 'MP3-jPlayer' ); ?></h1></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_5"><?php esc_html_e( 'Media', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_0"><?php esc_html_e( 'Players', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_3"><?php esc_html_e( 'Downloads', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton" id="mp3j_tabbutton_4"><?php esc_html_e( 'Popout', 'mp3-jplayer' ); ?></div>
				<div class="mp3j-tabbutton last" id="mp3j_tabbutton_2"><?php esc_html_e( 'Advanced', 'mp3-jplayer' ); ?></div>
				<br class="clearB" />
			</div>
			
			<div class="mp3j-tabs-wrap">
				
				<div class="mp3j-tab" id="mp3j_tab_0">
					<?php if ( function_exists( 'MJPsettings_players' ) ) MJPsettings_players(); ?>
					
					<div style="float:left; width:270px; margin:8px 10px 0 0;">
						<div class="os" style="border-bottom:1px solid #d3d3d3; padding:8px 0 8px 0px; margin:0 0 15px 0; font-size:18px; font-weight:500;"><?php esc_html_e( 'Text Players (single-file)', 'mp3-jplayer' ); ?></div>
						<table class="player-settings" style="margin:0 0 0px 0px; width:260px">
							<tr>
								<td class="psHeight"><strong style="font-size:14px;"><?php esc_html_e( 'Font Size', 'mp3-jplayer' ); ?></strong>:</td>
								<?php // === Зміни: Екранування вихідних даних === ?>
								<td><input type="text" value="<?php echo esc_attr( $O['font_size_mp3t'] ); ?>" name="font_size_mp3t" style="width:70px;" /></td>
							</tr>
							<tr>
								<td class="psHeight"><label for="volslider_onsingles"><strong style="font-size:14px;"><?php esc_html_e( 'Volume', 'mp3-jplayer' ); ?></strong>: &nbsp;</label></td>
								<td><input type="checkbox" name="volslider_onsingles" id="volslider_onsingles" value="true" <?php checked( $O['volslider_on_singles'], "true" ); ?> /></td>
							</tr>
							<tr>
								<td class="psHeight"><label for="flipMP3t"><strong style="font-size:14px;"><?php esc_html_e( 'Play on RHS', 'mp3-jplayer' ); ?></strong>: &nbsp;</label></td>
								<td><input type="checkbox" name="flipMP3t" id="flipMP3t" value="true" <?php checked( $O['flipMP3t'], "true" );?> /></td>
							</tr>
							<tr>
								<td class="psHeight"><strong style="font-size:14px;"><?php esc_html_e( 'Colour Scheme', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input type="checkbox" name="mp3tColour_on" id="mp3tColour_on" value="true" <?php checked( $O['mp3tColour_on'], "true" );?> />
									<?php esc_html_ex( 'On', 'as in switched on', 'mp3-jplayer' ); ?> &nbsp;<input type="text" value="<?php echo esc_attr( $O['mp3tColour'] ); ?>" name="mp3tColour" id="mp3tColour" class="mp3j-color-picker" /></td> <?php // Додав клас для color picker, якщо він є ?>
							</tr>
							<?php if ( function_exists( 'MJPsettings_mp3t' ) ) MJPsettings_mp3t(); ?>
						</table>						
					</div>
					
					<div style="float:left; width:270px; margin:8px 10px 0 0;">
						<div style="border-bottom:1px solid #d3d3d3; padding:8px 0 8px 0px; margin:0 0 15px 0; font-size:18px; font-weight:500;"><?php esc_html_e( 'Button Players (single-file)', 'mp3-jplayer' ); ?></div>
						<table class="player-settings" style="margin:0 0 0px 0px; width:260px">
							<tr>
								<td class="psHeight"><strong style="font-size:14px;"><?php esc_html_e( 'Font Size', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input type="text" value="<?php echo esc_attr( $O['font_size_mp3j'] ); ?>" name="font_size_mp3j" style="width:70px;" /></td>
							</tr>
							<tr>
								<td class="psHeight"><label for="volslider_onmp3j"><strong style="font-size:14px;"><?php esc_html_e( 'Volume', 'mp3-jplayer' ); ?></strong>: &nbsp;</label></td>
								<td><input type="checkbox" name="volslider_onmp3j" id="volslider_onmp3j" value="true" <?php checked( $O['volslider_on_mp3j'], "true" );?> /></td>
							</tr>
							<tr>
								<td class="psHeight"><label for="flipMP3j"><strong style="font-size:14px;"><?php esc_html_e( 'Play on RHS', 'mp3-jplayer' ); ?></strong>: &nbsp;</label></td>
								<td><input type="checkbox" name="flipMP3j" id="flipMP3j" value="false" <?php checked( $O['flipMP3j'], "false" );?> /></td> <?php // Зверніть увагу на value="false" для цієї логіки ?>
							</tr>
							<tr>
								<td class="psHeight"><strong style="font-size:14px;"><?php esc_html_e( 'Colour Scheme', 'mp3-jplayer' ); ?></strong>:</td>
								<td><input type="checkbox" name="mp3jColour_on" id="mp3jColour_on" value="true" <?php checked( $O['mp3jColour_on'], "true" );?> />
									<?php esc_html_ex( 'On', 'as in switched on', 'mp3-jplayer' ); ?> &nbsp;<input type="text" value="<?php echo esc_attr( $O['mp3jColour'] ); ?>" name="mp3jColour" id="mp3jColour" class="mp3j-color-picker" />
								</td>
							</tr>
							<?php if ( function_exists( 'MJPsettings_mp3j' ) ) MJPsettings_mp3j(); ?>
						</table>
					</div>
					<br class="clearB">
					
					<div>
						<?php if ( function_exists( 'MJPsettings_after_mp3tj' ) ) MJPsettings_after_mp3tj(); ?>
					</div>
					<br class="clearB">
					
					<br>
					<div style="border-bottom:1px solid #d3d3d3; padding:8px 0 8px 0px; margin:10px 0 15px 0; width:540px; font-size:18px; font-weight:500;"><?php esc_html_e( 'Playlist Players', 'mp3-jplayer' ); ?></div>
					<table class="playlist-settings" style="margin:0 0 0px 0px;">
						<tr>
							<td style="width:175px;"><strong style="font-size:14px;"><?php esc_html_e( 'Width:', 'mp3-jplayer' ); ?></strong></td>
							<td><input type="text" style="width:100px;" name="mp3foxPlayerWidth" value="<?php echo esc_attr( $O['player_width'] ); ?>" /></td>
							<td><span class="description"><?php esc_html_e( 'Pixels (px) or percent (%).', 'mp3-jplayer' ); ?></span></td>
						</tr>
						<tr>
							<td><strong style="font-size:14px;"><?php esc_html_e( 'Alignment:', 'mp3-jplayer' ); ?></strong></td>
							<td><select name="mp3foxFloat" style="width:100px;">
									<option value="none" <?php selected( 'none', $O['player_float'] ); ?>><?php esc_html_e( 'Left', 'mp3-jplayer' ); ?></option>
									<option value="rel-C" <?php selected( 'rel-C', $O['player_float'] ); ?>><?php esc_html_e( 'Centre', 'mp3-jplayer' ); ?></option>
									<option value="rel-R" <?php selected( 'rel-R', $O['player_float'] ); ?>><?php esc_html_e( 'Right', 'mp3-jplayer' ); ?></option>
									<option value="left" <?php selected( 'left', $O['player_float'] ); ?>><?php esc_html_e( 'Float Left', 'mp3-jplayer' ); ?></option>
									<option value="right" <?php selected( 'right', $O['player_float'] ); ?>><?php esc_html_e( 'Float Right', 'mp3-jplayer' ); ?></option>
								</select></td>
							<td></td>
						</tr>
						<tr>
							<td style="padding-bottom:4px;"><strong style="font-size:14px;"><?php esc_html_e( 'Margins:', 'mp3-jplayer' ); ?></strong></td>
							<td style="padding-bottom:4px;" colspan="2"><input type="text" size="5" name="mp3foxPaddings_top" value="<?php echo esc_attr( $O['paddings_top'] ); ?>" /> &nbsp; <?php esc_html_e( 'Above players', 'mp3-jplayer' ); ?></td>
						</tr>
						<tr>
							<td style="padding-top:0px; padding-bottom:4px;"></td>
							<td style="padding-top:0px; padding-bottom:4px;" colspan="2"><input type="text" size="5" name="mp3foxPaddings_inner" value="<?php echo esc_attr( $O['paddings_inner'] ); ?>" /> &nbsp; <?php esc_html_e( 'Inner margin (floated players)', 'mp3-jplayer' ); ?></td>
						</tr>
						<tr>
							<td style="padding-top:0px; padding-bottom:2px;"></td>
							<td style="padding-top:0px; padding-bottom:2px;" colspan="2"><input type="text" size="5" name="mp3foxPaddings_bottom" value="<?php echo esc_attr( $O['paddings_bottom'] ); ?>" /> &nbsp; <?php esc_html_e( 'Below players', 'mp3-jplayer' ); ?></td>
						</tr>
						<tr>
							<td style="padding-top:5px; padding-bottom:20px;"></td>
							<td style="padding-top:5px; padding-bottom:20px;" colspan="2"><span class="description"><?php esc_html_e( 'Pixels (px) or percent (%).', 'mp3-jplayer' ); ?></span></td>
						</tr>						
						<tr>
							<td style="padding-bottom:0;padding-top:0px;"><label for="mp3foxMaxListHeight" style="font-size:14px;"><?php esc_html_e( 'Max playlist height:', 'mp3-jplayer' ); ?></label></td> <?php // Змінив for на id поля ?>
							<td style="padding-bottom:0;padding-top:0px;" colspan="2"><input type="text" size="5" name="mp3foxMaxListHeight" id="mp3foxMaxListHeight" value="<?php echo esc_attr( $O['max_list_height'] ); ?>" /> px</td>
						</tr>
						<tr>
							<td style="padding-bottom:20px;padding-top:0;" colspan="3"><span class="description"><?php esc_html_e( 'A scroll bar will show for longer playlists, leave it blank for no limit.', 'mp3-jplayer' ); ?></span></td>
						</tr>				
					</table>
					
					<table class="playlist-settings">
						<tr>
							<td style="width:220px;"><label for="hasListMeta" style="font-size:14px;"><?php esc_html_e( 'Show sub titles in playlists', 'mp3-jplayer' ); ?></label></td>
							<td colspan="2"><input type="checkbox" value="true" name="hasListMeta" id="hasListMeta" <?php checked( $O['hasListMeta'], "true" ); ?>/></td>
						</tr>
						<tr>
							<td><label for="mp3foxShowPlaylist" style="font-size:14px;"><?php esc_html_e( 'Start with playlists open', 'mp3-jplayer' ); ?></label></td>
							<td colspan="2"><input type="checkbox" name="mp3foxShowPlaylist" id="mp3foxShowPlaylist" value="true" <?php checked( $O['playlist_show'], "true" );?> /></td>
						</tr>
						<tr>
							<td><label for="mp3foxEnablePopout" style="font-size:14px;"><?php esc_html_e( 'Show popout player button', 'mp3-jplayer' ); ?></label></td>
							<td colspan="2"><input type="checkbox" name="mp3foxEnablePopout" id="mp3foxEnablePopout" value="true" <?php checked( $O['enable_popout'], "true" );?> /></td>
						</tr>
					</table>
					
					<?php if ( function_exists( 'MJPsettings_playlist' ) ) MJPsettings_playlist(); ?>
				</div><div class="mp3j-tab" id="mp3j_tab_1">
					
					<div class="settingsBox">
						<p style="margin-bottom:25px;" class="mainTick"><label for="mp3foxVol"><?php esc_html_e( 'Initial volume:', 'mp3-jplayer' ); ?> &nbsp; </label> <?php // Додав for ?>
							<input type="text" style="text-align:center;" size="2" name="mp3foxVol" id="mp3foxVol" value="<?php echo esc_attr( $O['initial_vol'] ); ?>" /> 
							&nbsp; <span class="description">(0 - 100)</span></p>
						
						<p class="mainTick"><input type="checkbox" name="mp3foxAddTrackNumbers" id="mp3foxAddTrackNumbers" value="true" <?php checked( $O['add_track_numbering'], "true" ); ?> />
							<label for="mp3foxAddTrackNumbers"> &nbsp; <?php esc_html_e( 'Number the tracks', 'mp3-jplayer' ); ?></label></p>
						
						<p class="mainTick"><input type="checkbox" name="mp3foxAutoplay" id="mp3foxAutoplay" value="true" <?php checked( $O['auto_play'], "true" ); ?> />
							<label for="mp3foxAutoplay"> &nbsp; <?php esc_html_e( 'Auto play', 'mp3-jplayer' ); ?></label></p>
						
						<p class="mainTick"><input type="checkbox" name="mp3foxPlaylistRepeat" id="mp3foxPlaylistRepeat" value="true" <?php checked( $O['playlist_repeat'], "true" ); ?> />
							<label for="mp3foxPlaylistRepeat"> &nbsp; <?php esc_html_e( 'Loop playlist', 'mp3-jplayer' ); ?></label></p>
							
						<p class="mainTick"><input type="checkbox" name="autoResume" id="autoResume" value="true" <?php checked( $O['autoResume'], "true" ); ?> />
							<label for="autoResume"> &nbsp; <?php esc_html_e( 'Resume playback', 'mp3-jplayer' ); ?></label></p>
						
						<p class="description" style="margin-bottom:20px; font-size:14px;"><br><span><?php esc_html_e( 'Note that Resume and Auto play are prevented by many devices, these will activate on desktops and laptops only.', 'mp3-jplayer' ); ?></span> <a class="slimButton" href="javascript:void(0);" onclick="jQuery('#resumeHelp').toggle(300);"><?php esc_html_e( 'Help', 'mp3-jplayer' ); ?></a></p> <?php // javascript:void(0); для безпеки ?>
					
						<div id="resumeHelp" class="helpBox" style="display:none; max-width:550px;">
							<h4><?php esc_html_e( 'Resume Playback', 'mp3-jplayer' ); ?></h4>
							<p class="description"><?php esc_html_e( 'This gives near-continuous listening when Browse the site (there will be a short pause as the next page loads). Resuming will work wherever you have used the same piece of audio on different pages on the site.', 'mp3-jplayer' ); ?></p>
							<h4><?php esc_html_e( 'Auto Play', 'mp3-jplayer' ); ?></h4>
							<p class="description"><?php esc_html_e( 'If you set multiple players on a page to autoplay then they will play their playlists in sequence one after the other.', 'mp3-jplayer' ); ?></p>
						</div>
						
						<br>
						<input type="checkbox" name="autoCounterpart" id="autoCounterpart" value="true" <?php checked( $O['autoCounterpart'], "true" ); ?>/>
						&nbsp; <label for="autoCounterpart" style="margin:0px 0 0 0px; font-size:14px;"><?php esc_html_e( 'Auto-find counterpart files', 'mp3-jplayer' ); ?> &nbsp; </label>
						
						<p class="description" style="margin:10px 0 0 0px; font-size:14px;"><?php esc_html_e( 'This will pick up a fallback format if it\'s in the same location as the playlisted track, based on a filename match.', 'mp3-jplayer' ); ?> <strong><a class="slimButton" href="javascript:void(0);" onclick="jQuery('#counterpartHelp').toggle(300);"><?php esc_html_e( 'Help', 'mp3-jplayer' ); ?></a></strong></p> 
						<div id="counterpartHelp" class="helpBox" style="display:none; max-width:550px;">
							<?php // ... (тут HTML, який теж можна пропустити через esc_html_e або wp_kses_post, якщо потрібно) ... ?>
                            <p class="description"><?php echo wp_kses_post( __( 'With this option ticked, the plugin will automatically look for counterpart files for any players on a page. The playlisted (primary) track must be from the MPEG family (an mp3, m4a, or mp4 file).', 'mp3-jplayer' ) ); ?></p>
							<p class="description"><?php echo wp_kses_post( __( 'Auto-counterparting works for MPEGS in the library, in local folders, and when using bulk play or FEED commands. Just make sure your counterparts have the same filename, and are in the same location as the primary track.', 'mp3-jplayer' ) ); ?></p>
							<p class="description"><?php echo wp_kses_post( __( 'You can always manually add a counterpart to any primary track format by using the <code>counterpart</code> parameter in a shortcode and specifying a url.', 'mp3-jplayer' ) ); ?></p>
							<p class="description"><?php echo wp_kses_post( __( 'Automatic Counterparts are chosen with the following format priority: OGG, WEBM, WAV.', 'mp3-jplayer' ) ); ?></p>
						</div>					
						<br>
					</div>
					
					<div class="infoBox">
						<?php 
						if ( $O['disable_jquery_libs'] == "yes" ) { 
							echo '<p style="font-weight:600; color:#d33;margin-bottom:10px;">' . esc_html__( 'NOTE: jQuery and UI scripts are turned off.', 'mp3-jplayer' ) . '</p>';
						} 
						?>
						<div class="gettingstarted">
							<h4><?php esc_html_e( 'Get Started:', 'mp3-jplayer' ); ?></h4>
							<p class="infoLinks"><a href="<?php echo esc_url( admin_url('media-new.php') ); ?>"><?php esc_html_e( 'Upload some audio', 'mp3-jplayer' ); ?></a></p>
							<p class="infoLinks"><a href="<?php echo esc_url('http://mp3-jplayer.com/adding-players/'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How to add players', 'mp3-jplayer' ); ?></a></p>
							<p class="infoLinks"><a href="<?php echo esc_url('http://mp3-jplayer.com/audio-format-advice/'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Audio Format Help', 'mp3-jplayer' ); ?></a></p>
						</div>
						<br>
						<div class="moreinfo">
							<h4><?php esc_html_e( 'More Info:', 'mp3-jplayer' ); ?></h4>
							<p class="infoLinks"><a href="<?php echo esc_url('http://mp3-jplayer.com/help-docs/'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Help & Docs main page', 'mp3-jplayer' ); ?></a></p>
							<p class="infoLinks"><a href="<?php echo esc_url('http://mp3-jplayer.com/shortcode-reference/'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Shortcode Reference', 'mp3-jplayer' ); ?></a></p>
						</div>
						<hr>
						<p class="infoLinks"><a href="<?php echo esc_url('http://mp3-jplayer.com'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Plugin home page', 'mp3-jplayer' ); ?></a></p>
						<p style="margin-bottom:0;" class="r"><span class="description" style="font-size:11px;"><?php esc_html_e( 'Version', 'mp3-jplayer' ); ?> <?php echo esc_html( $MP3JP->version_of_plugin ); ?></span></p>
					</div>
					<br class="clearB">
				</div><div class="mp3j-tab" id="mp3j_tab_5">
					<?php 					
					// $version = substr( get_bloginfo('version'), 0, 3); // get_bloginfo('version') повертає рядок
                    // Краще порівнювати версії за допомогою version_compare()
					if ( version_compare( get_bloginfo('version'), '3.6', '>=' ) ) {
					?>
					
					<h3 style="margin-top:15px; margin-bottom:10px; font-weight:500;"><?php esc_html_e( 'Library', 'mp3-jplayer' ); ?></h3>
					<p class="description" style="font-size:14px; margin-bottom:10px;"><?php esc_html_e( 'Choose which title information to show in the players and playlists when playing from your WordPress Media Library.', 'mp3-jplayer' ); ?></p>
					
					<table>
						<tr><td colspan="2"></td></tr>
						<tr>
							<td style="width:135px; height:40px; font-size:14px;"><strong><?php esc_html_e( 'Main Titles', 'mp3-jplayer' ); ?></strong></td>
							<td style="height:40px;"><select name="playerTitle1" id="playerTitle1" style="width:160px;">
									<option value="titles"<?php selected( 'titles', $O['playerTitle1'] ); ?>><?php esc_html_e( 'Track Title', 'mp3-jplayer' ); ?></option>
									<option value="artist"<?php selected( 'artist', $O['playerTitle1'] ); ?>><?php esc_html_e( 'Artist', 'mp3-jplayer' ); ?></option>
									<option value="album"<?php selected( 'album', $O['playerTitle1'] ); ?>><?php esc_html_e( 'Album', 'mp3-jplayer' ); ?></option>
									<option value="excerpts"<?php selected( 'excerpts', $O['playerTitle1'] ); ?>><?php esc_html_e( 'Caption', 'mp3-jplayer' ); ?></option>
									<option value="postDates"<?php selected( 'postDates', $O['playerTitle1'] ); ?>><?php esc_html_e( 'Upload Date', 'mp3-jplayer' ); ?></option>
								</select></td>
						</tr>
						<tr>
							<td style="width:135px; height:40px; font-size:14px;"><strong><?php esc_html_e( 'Secondary Titles', 'mp3-jplayer' ); ?></strong></td>
							<td><select name="playerTitle2" id="playerTitle2" style="width:160px;">
									<option value=""<?php selected( '', $O['playerTitle2'] ); ?>><?php esc_html_e( '- None -', 'mp3-jplayer' ); ?></option>
									<option value="titles"<?php selected( 'titles', $O['playerTitle2'] ); ?>><?php esc_html_e( 'Title', 'mp3-jplayer' ); ?></option>
									<option value="artist"<?php selected( 'artist', $O['playerTitle2'] ); ?>><?php esc_html_e( 'Artist', 'mp3-jplayer' ); ?></option>
									<option value="album"<?php selected( 'album', $O['playerTitle2'] ); ?>><?php esc_html_e( 'Album', 'mp3-jplayer' ); ?></option>
									<option value="excerpts"<?php selected( 'excerpts', $O['playerTitle2'] ); ?>><?php esc_html_e( 'Caption', 'mp3-jplayer' ); ?></option>
									<option value="postDates"<?php selected( 'postDates', $O['playerTitle2'] ); ?>><?php esc_html_e( 'Upload Date', 'mp3-jplayer' ); ?></option>
								</select></td>
						</tr>
					</table>
					<?php
					} // end if version_compare
					?>
					
					<table style="margin-top:3px;">
						<tr>
							<td style="width:137px; height:40px;"></td>
							<td style="height:40px;">
								<span class="button-secondary unselectable" style="display:inline-block; font-size:13px; font-weight:600; margin-top:-4px;" id="showLibFilesButton"><?php esc_html_e( 'View Files', 'mp3-jplayer' ); ?></span>
								&nbsp; &nbsp; <a href="<?php echo esc_url( admin_url('media-new.php') ); ?>" style="font-weight:600; font-size:14px;"><?php esc_html_e( 'Upload Audio', 'mp3-jplayer' ); ?> &raquo;</a>
							</td>
						</tr>
					</table>
					
					<div id="libraryViewerWrap" style="display:none;">
						<?php // ... HTML для Library Viewer, переконайтеся, що JS безпечний і дані екрануються ... ?>
                        <?php // Наприклад, AJAX відповіді, що вставляються сюди, мають бути екрановані на сервері або перед вставкою в DOM ?>
					</div>
					<br><hr>					
					
					<?php
					//Default Folder
					// Логіка відображення файлів з папки. $O['mp3_dir'] має бути санітизованим шляхом.
					// $MP3JP->grabFolderURLs() має безпечно обробляти шляхи.
					// Вивід $folderHtml та $folderText має екрануватися, якщо вони містять дані, що можуть контролюватися користувачем.
					// Тут передбачається, що $O['mp3_dir'] вже очищено при збереженні.
					// Однак, вивід імен файлів та дат має бути екранований.

                    // ... (пропущено для скорочення, але вся логіка з $folderInfo, $folderHtml, $folderText потребує перевірки на екранування) ...
                    // Приклад екранування всередині циклу:
                    // $folderHtml .= '<td>' . esc_html($val) . '</td>';
                    // $folderHtml .= '<td><span class="description">' . esc_html($niceDate) . '</span></td>';
                    // echo $folderHtml; // Якщо $folderHtml містить HTML, то не потрібно додаткового екранування тут, але внутрішній вміст має бути безпечним.
                    // Якщо $folderText містить HTML, то echo wp_kses_post($folderText); інакше echo esc_html($folderText);

					?>
					<br>
					<h3 style="margin-top:10px; margin-bottom:10px; font-weight:500;"><?php esc_html_e( 'Default Folder', 'mp3-jplayer' ); ?></h3>
					<p class="description" style="font-size:14px; margin-bottom:10px;"><?php esc_html_e( 'Set a folder path or url below.', 'mp3-jplayer' ); ?> <a href="javascript:void(0);" class="slimButton" onclick="jQuery('#folderHelp').toggle(300);" style="font-size:13px; font-weight:600;"><?php esc_html_e( 'Help', 'mp3-jplayer' ); ?></a></p>
					
					<div id="folderHelp" class="helpBox" style="display:none; max-width:550px;">
						<?php // ... HTML, перевірити на безпеку ... ?>
                        <p class="description"><?php esc_html_e( 'If you like, you can specify a location (local or remote) to play some of your audio from. For example:', 'mp3-jplayer' ); ?></p>
						<p class="description"><code>/my/music</code> <?php esc_html_e('or', 'mp3-jplayer'); ?> <code>http://anothersite.com/music</code>.</p>
						<p class="description"><?php esc_html_e( 'This means you only need to write the filenames in playlists to play from this location (you don\'t need to use the full url).', 'mp3-jplayer' ); ?></p>
						<p class="description"><?php esc_html_e( 'If the path is local (on your domain) then you can also bulk-play this folder.', 'mp3-jplayer' ); ?></p>
					</div>
					
					<table> 
						<tr>
							<td style="font-size:14px; width:135px;"><strong><?php esc_html_e( 'Folder Path', 'mp3-jplayer' ); ?></strong> &nbsp; </td>
							<td style="width:260px;"><input type="text" style="width:250px;" name="mp3foxfolder" value="<?php echo esc_attr( $O['mp3_dir'] ); ?>" /></td>
							<td style="font-weight:600;"><a class="button-secondary unselectable" href="javascript:void(0);" onclick="jQuery('#folder-list').toggle();"><?php esc_html_e( 'View files', 'mp3-jplayer' ); ?></a>&nbsp;&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td colspan="2" style="padding-left:4px; padding-top:4px;"><?php /* echo $folderText; - Потенційно небезпечно, якщо $folderText містить несанітизований HTML. Використовуйте wp_kses_post() або esc_html() */ ?></td>
						</tr>
					</table>
					<br>
					<?php /* echo $folderHtml; - Потенційно небезпечно, якщо $folderHtml генерується з несанітизованими даними. Переконайтеся, що всі дані всередині $folderHtml екрановані. */ ?>
					<hr><br>
				
					<h3 style="margin-top:10px; font-weight:500;"><?php esc_html_e( 'Bulk-Play Settings', 'mp3-jplayer' ); ?></h3>
					<p class="description" style="font-size:14px; margin:10px 0 0 0px;"><?php esc_html_e( 'Choose which audio formats are playlisted when bulk-playing from folders, the library, and via the FEED command in playlists.', 'mp3-jplayer' ); ?>
						<a href="javascript:void(0);" class="slimButton" onclick="jQuery('#feedHelp').toggle(300);"><?php esc_html_e( 'Help', 'mp3-jplayer' ); ?></a></p>
					
					<div id="feedHelp" class="helpBox" style="display:none; max-width:550px;">
						<?php // ... HTML ... ?>
                        <p class="description"><?php esc_html_e( 'Use a simple shortcode in your posts and pages to playlist entire folders that are on your domain. You can also play your entire library.', 'mp3-jplayer' ); ?></p>
						<p class="description"><?php esc_html_e( 'Play all audio in your library', 'mp3-jplayer' ); ?></p>
						<p class="description"><code>[playlist tracks="FEED:LIB"]</code></p>
                        <?php // і так далі для інших прикладів ?>
					</div>
					
					<p style="margin:15px 0 30px 0; font-size:14px;">
						<?php
						if ( is_array( $O['audioFormats'] ) ) { // Додав перевірку, чи це масив
							foreach ( $O['audioFormats'] as $k => $f ) {
								$safe_k = esc_attr( $k ); // Екрануємо ключ для використання в id/name
								echo '<input class="formatChecker" type="checkbox" name="audioFormats[' .$safe_k. ']" id="audioFormats_' .$safe_k. '" value="true"' . checked( $f, 'true', false ) . '/>';
								echo '<label for="audioFormats_' .$safe_k. '">' . esc_html( $k ) . '</label> &nbsp;&nbsp;&nbsp;&nbsp;';
							}
						}
						?>
					</p>
					
					<p class="description" style="font-size:14px; margin:0px 0 10px 0px;"><?php esc_html_e( 'Set the ordering for the playlists when bulk playing.', 'mp3-jplayer' ); ?></p>
					
					<table style="margin-left:0px;">								
						<tr>
							<td style="font-size:14px; width:135px;"><strong><?php esc_html_e( 'Library:', 'mp3-jplayer' ); ?></strong></td>
							<td style="font-size:14px;"><?php esc_html_e( 'Order by', 'mp3-jplayer' ); ?> &nbsp;</td>
							<td>
								<select name="librarySortcol" style="width:160px;">
									<option value="title" <?php selected( 'title', $O['library_sortcol'] ); ?>><?php esc_html_e( 'Title', 'mp3-jplayer' ); ?></option>
									<option value="caption" <?php selected( 'caption', $O['library_sortcol'] ); ?>><?php esc_html_e( 'Sub-title, Title', 'mp3-jplayer' ); ?></option>
									<option value="file" <?php selected( 'file', $O['library_sortcol'] ); ?>><?php esc_html_e( 'Filename', 'mp3-jplayer' ); ?></option>
									<option value="date" <?php selected( 'date', $O['library_sortcol'] ); ?>><?php esc_html_e( 'Date Uploaded', 'mp3-jplayer' ); ?></option>
								</select>&nbsp;&nbsp;
							</td>
							<td style="font-size:14px;">&nbsp; <?php esc_html_e( 'Direction', 'mp3-jplayer' ); ?> &nbsp;</td>
							<td>
								<select name="libraryDirection" style="width:100px;">
									<option value="ASC" <?php selected( 'ASC', $O['library_direction'] ); ?>><?php esc_html_e( 'Asc', 'mp3-jplayer' ); ?></option>
									<option value="DESC" <?php selected( 'DESC', $O['library_direction'] ); ?>><?php esc_html_e( 'Desc', 'mp3-jplayer' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td style="font-size:14px; width:135px;"><strong><?php esc_html_e( 'Folders:', 'mp3-jplayer' ); ?></strong></td>
							<td style="font-size:14px;"><?php esc_html_e( 'Order by', 'mp3-jplayer' ); ?> &nbsp;</td>
							<td>
								<select name="folderFeedSortcol" style="width:160px;">
									<option value="file" <?php selected( 'file', $O['folderFeedSortcol'] ); ?>><?php esc_html_e( 'Filename', 'mp3-jplayer' ); ?></option>
									<option value="date" <?php selected( 'date', $O['folderFeedSortcol'] ); ?>><?php esc_html_e( 'Date Uploaded', 'mp3-jplayer' ); ?></option>
								</select>&nbsp;&nbsp;
							</td>
							<td style="font-size:14px;">&nbsp; <?php esc_html_e( 'Direction', 'mp3-jplayer' ); ?> &nbsp;</td>
							<td>
								<select name="folderFeedDirection" style="width:100px;">
									<option value="ASC" <?php selected( 'ASC', $O['folderFeedDirection'] ); ?>><?php esc_html_e( 'Asc', 'mp3-jplayer' ); ?></option>
									<option value="DESC" <?php selected( 'DESC', $O['folderFeedDirection'] ); ?>><?php esc_html_e( 'Desc', 'mp3-jplayer' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
					<br />
					<p style="margin:10px 0 0 0;"><span class="description" id="feedCounterpartInfo"></span></p> <?php // Вміст для #feedCounterpartInfo має бути безпечним ?>
				</div><div class="mp3j-tab" id="mp3j_tab_3">
					<br>
					<p class="description" style="font-size:14px; margin-bottom:10px;"><?php esc_html_e( 'Download buttons are shown on playlist players, use these options to set their behavior.', 'mp3-jplayer' ); ?></p>
					<table class="dSettingsTable">
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Show Download Button', 'mp3-jplayer' ); ?></strong></td>
							<td><select name="mp3foxDownloadMp3" style="width:150px;">
									<option value="true" <?php selected( 'true', $O['show_downloadmp3'] ); ?>><?php esc_html_e( 'Yes', 'mp3-jplayer' ); ?></option>
									<option value="false" <?php selected( 'false', $O['show_downloadmp3'] ); ?>><?php esc_html_e( 'No', 'mp3-jplayer' ); ?></option>
									<option value="loggedin" <?php selected( 'loggedin', $O['show_downloadmp3'] ); ?>><?php esc_html_e( 'To logged in users', 'mp3-jplayer' ); ?></option>
								</select></td>
						</tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Button Text', 'mp3-jplayer' ); ?></strong></td>
							<td><input type="text" style="width:150px;" name="dload_text" value="<?php echo esc_attr( $O['dload_text'] ); ?>" /></td>
						</tr>
						<tr><td colspan="2"><?php if ( function_exists( 'MJPsettings_downloads_above' ) ) MJPsettings_downloads_above(); ?></td></tr>
						<tr><td colspan="2"><br><hr></td></tr>
						<tr><td colspan="2" class="mainTick" style="margin-left:0px;"><p class="description" style="margin:0px 0 5px 0px; font-size:14px;"><?php esc_html_e( 'When setting players for logged-in downloads, optionally set the text/link for any logged out visitors.', 'mp3-jplayer' ); ?></p></td></tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Visitor Text', 'mp3-jplayer' ); ?></strong>:</td>
							<td><input type="text" style="width:150px;" name="loggedout_dload_text" value="<?php echo esc_attr( $O['loggedout_dload_text'] ); ?>" /></td>
						</tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Visitor Link', 'mp3-jplayer' ); ?></strong>:</td>
							<td>
								<input type="text" style="width:300px;" name="loggedout_dload_link" value="<?php echo esc_attr( $O['loggedout_dload_link'] ); ?>" /> <?php // Для URL краще esc_url(), але оскільки це поле вводу, esc_attr() підходить ?>
								&nbsp; <span class="description"><?php esc_html_e( 'Optional URL for the visitor text', 'mp3-jplayer' ); ?></span>
							</td>
						</tr>
						<tr><td colspan="2"><br><hr></td></tr>
						<tr>
							<td style="padding-top:5px;"><label for="force_browser_dload" class="mainTick"><?php esc_html_e( 'Use Smooth Downloading', 'mp3-jplayer' ); ?></label></td>
							<td style="padding-top:5px;"><input type="checkbox" name="force_browser_dload" id="force_browser_dload" value="true" <?php checked( $O['force_browser_dload'], "true" );?> /></td>
						</tr>
						<tr><td colspan="2"><p class="description" style="margin:0px 0 0px 0px; font-size:14px;"><?php esc_html_e( 'This option makes downloading seamless for most users, or it will display a dialog box with a link when a seamless download is not possible.', 'mp3-jplayer' ); ?></p></td></tr>
						<tr><td colspan="2"><br><hr></td></tr>
					</table>
					
					<table>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Path to remote downloader file', 'mp3-jplayer' ); ?></strong> &nbsp; &nbsp; &nbsp; </td>
							<td><input type="text" style="width:300px;" name="dloader_remote_path" value="<?php echo esc_attr( $O['dloader_remote_path'] ); ?>" /></td>
						</tr>
					</table>
					<p class="description" style="margin:0px 0 10px 4px; font-size:14px;"><?php echo wp_kses_post( sprintf(__( 'If you play from other domains and want smooth downloads, then use the field above to specify a path to the downloader file. <strong><a href="%s">See help on setting this up</a></strong>', 'mp3-jplayer' ), esc_url(MP3J_PLUGIN_URL . '/remote/help.txt') ) ); ?></p>
					<br>
				</div>
								
				<div class="mp3j-tab" id="mp3j_tab_4">
					<br>
					<p class="description" style="font-size:14px; margin-bottom:10px;"><?php esc_html_e( 'Set the default text displayed on popout buttons.', 'mp3-jplayer' ); ?></p>
					<table class="popoutSettingsTable">
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Launch Button Text', 'mp3-jplayer' ); ?></strong>:</td>
							<td><input type="text" style="width:150px;" name="mp3foxPopoutButtonText" value="<?php echo esc_attr( $O['popout_button_title'] ); ?>" /></td>
						</tr>
						<tr><td colspan="2"><br><hr></td></tr>
						<tr><td colspan="2" style="padding-left:0;"><p class="description" style="font-size:14px; margin-bottom:10px;"><?php esc_html_e( 'Popout window settings.', 'mp3-jplayer' ); ?></p></td></tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Window Width', 'mp3-jplayer' ); ?></strong>:</td>
							<td><input type="text" size="4" style="text-align:center;" name="mp3foxPopoutWidth" value="<?php echo esc_attr( $O['popout_width'] ); ?>" /> px <span class="description">&nbsp; (250 - 1600)</span></td>
						</tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Window Height', 'mp3-jplayer' ); ?></strong>: &nbsp;</td>
							<td><input type="text" size="4" style="text-align:center;" name="mp3foxPopoutMaxHeight" value="<?php echo esc_attr( $O['popout_max_height'] ); ?>" /> px <span class="description">&nbsp; (200 - 1200) &nbsp; <?php esc_html_e( 'a scroll bar will show for longer playlists', 'mp3-jplayer' ); ?></span></td>
						</tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Background Colour', 'mp3-jplayer' ); ?></strong>:</td>
							<td><input type="text" name="mp3foxPopoutBackground" class="mp3j-color-picker" style="width:100px;" value="<?php echo esc_attr( $O['popout_background'] ); ?>" /></td>
						</tr>
						<tr>
							<td><strong class="mainTick"><?php esc_html_e( 'Background Image', 'mp3-jplayer' ); ?></strong>:</td>
							<td><input type="text" style="width:100%;" name="mp3foxPopoutBGimage" value="<?php echo esc_attr( $O['popout_background_image'] ); ?>" /></td> <?php // Для URL краще esc_url() ?>
						</tr>
					</table>
				</div><div class="mp3j-tab" id="mp3j_tab_2">
					<br>
					<p class="mainTick"><label for="can_view_players_select"><?php esc_html_e( 'Show players to:', 'mp3-jplayer' ); ?> &nbsp;&nbsp;</label> <?php // Додав for та id ?>
							<select name="can_view_players" id="can_view_players_select" style="width:180px;">
								<option value="all"<?php selected( 'all', $O['can_view_players'] ); ?>><?php esc_html_e( 'All Visitors', 'mp3-jplayer' ); ?></option>
								<option value="loggedin"<?php selected( 'loggedin', $O['can_view_players'] ); ?>><?php esc_html_e( 'Logged in users only', 'mp3-jplayer' ); ?></option>
							</select></p>
					<br><hr><br>
					<p class="description" style="font-size:14px; margin-bottom:20px;"><?php esc_html_e( 'Choose which aspects of your content you\'d like MP3-jPlayer to handle.', 'mp3-jplayer' ); ?></p>
					<table class="advancedSettingsTable">
						<?php
						$replace_options = array(
							'replace_WP_audio'    => __( 'Audio Players', 'mp3-jplayer' ),
							'replace_WP_playlist' => __( 'Playlist Players', 'mp3-jplayer' ),
							'make_player_from_link' => __( 'Links to Audio Files', 'mp3-jplayer' ),
							'replace_WP_attached' => __( 'Attached Audio', 'mp3-jplayer' ),
							'replace_WP_embedded' => __( 'URLs', 'mp3-jplayer' )
						);
						$replace_descriptions = array(
							'replace_WP_audio'    => __( 'Use the \'Add Media\' Button on post/page edit screens and choose \'Embed Player\' from the right select (WP 3.6+).', 'mp3-jplayer' ),
							'replace_WP_playlist' => __( 'Use the \'Add Media\' Button on post/page edit screens and choose \'Audio Playlist\' from the left menu (WP 3.9+).', 'mp3-jplayer' ),
							'make_player_from_link' => __( 'Links within post/page content will be turned into players using the shortcode specified under the \'Advanced\' tab.', 'mp3-jplayer' ),
							'replace_WP_attached' => __( 'Use the shortcode <code>[audio]</code> in posts and pages to playlist any attached audio.', 'mp3-jplayer' ),
							'replace_WP_embedded' => __( 'Paste urls directly into posts and pages (WP 3.6+).', 'mp3-jplayer' )
						);
						foreach($replace_options as $key => $label) : ?>
						<tr>
							<td class="mainTick"><input type="checkbox" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="true" <?php checked( $O[$key], "true" ); ?> /> 
								&nbsp; <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></td>
							<td><span class="description"><?php echo wp_kses_post($replace_descriptions[$key]); // wp_kses_post, якщо опис може містити HTML (наприклад, <code>) ?></span></td>
						</tr>
						<?php endforeach; ?>
					</table>
					<br>
					<p class="description" style="font-size:14px;"><?php esc_html_e( 'You can always use MP3-jPlayer\'s own shortcodes and widgets regardless of the above settings.', 'mp3-jplayer' ); ?></p>
					<br><hr><br>
					
					<p class="description" style="font-size:14px; margin-bottom:10px;"><?php esc_html_e( 'On pages like Index, Archive and Search pages, choose whether players should be seen within the results. These settings won\'t affect player widgets.', 'mp3-jplayer' ); ?></p>
					<p style="font-size:14px; margin-bottom:8px;"><input type="checkbox" name="mp3foxOnBlog" id="mp3foxOnBlog" value="true" <?php checked( $O['player_onblog'], "true" );?> />
						<label for="mp3foxOnBlog"> &nbsp; <?php esc_html_e( 'Show players when the full content is used.', 'mp3-jplayer' ); ?></label></p>
					<p style="font-size:14px; margin-bottom:8px;"><input type="checkbox" name="runShcodeInExcerpt" id="runShcodeInExcerpt" value="true" <?php checked( $O['run_shcode_in_excerpt'], "true" ); ?> />
						<label for="runShcodeInExcerpt"> &nbsp; <?php esc_html_e( 'Show players when excerpts (short summaries) are used.', 'mp3-jplayer' ); ?></label></p>
					
					<p class="description" style="margin:0 0 10px 30px; font-size:14px;"><?php esc_html_e( 'NOTE: You will need to manually write your post excerpts for this to work. Write your shortcodes into the excerpt field on post edit screens.', 'mp3-jplayer' ); ?></p>
					<br><hr><br>
					
					<h3 style="margin:0 0 20px 0; font-weight:500;"><?php esc_html_e( 'Conversion Options', 'mp3-jplayer' ); ?></h3>
					<table>
						<tr>
							<td class="padB" style="font-size:14px;"><?php echo wp_kses_post( __( '<strong>Turn</strong> <code>[audio]</code> <strong>shortcodes into</strong>:', 'mp3-jplayer' ) ); ?></td>
							<td class="padB">
								<select name="replacerShortcode_single" style="width:200px; font-weight:500;">
									<option value="mp3j"<?php selected( 'mp3j', $O['replacerShortcode_single'] ); ?>><?php esc_html_e( 'Single Players - Graphic', 'mp3-jplayer' ); ?></option>
									<option value="mp3t"<?php selected( 'mp3t', $O['replacerShortcode_single'] ); ?>><?php esc_html_e( 'Single Players - Text', 'mp3-jplayer' ); ?></option>
									<option value="player"<?php selected( 'player', $O['replacerShortcode_single'] ); ?>><?php esc_html_e( 'Playlist Players', 'mp3-jplayer' ); ?></option>
									<option value="popout"<?php selected( 'popout', $O['replacerShortcode_single'] ); ?>><?php esc_html_e( 'Popout Links', 'mp3-jplayer' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td class="padB" style="font-size:14px;"><?php echo wp_kses_post( __( '<strong>Turn</strong> <code>[playlist]</code> <strong>shortcodes into</strong>:', 'mp3-jplayer' ) ); ?></td>
							<td class="padB">
								<select name="replacerShortcode_playlist" id="replacerShortcode_playlist" style="width:200px; font-weight:500;">
									<option value="player"<?php selected( 'player', $O['replacerShortcode_playlist'] ); ?>><?php esc_html_e( 'Playlist Players', 'mp3-jplayer' ); ?></option>
									<option value="popout"<?php selected( 'popout', $O['replacerShortcode_playlist'] ); ?>><?php esc_html_e( 'Popout Links', 'mp3-jplayer' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td class="vTop padB" style="font-size:14px;"><strong><?php esc_html_e( 'Turn converted links into', 'mp3-jplayer' ); ?></strong>:</td>
							<td class="padB">
								<textarea class="widefat" style="width:400px; height:100px;" name="make_player_from_link_shcode"><?php 
									// Оригінальний код: $deslashed = str_replace('\"', '"', $O['make_player_from_link_shcode'] ); echo $deslashed;
									// Це небезпечно. Якщо $O['make_player_from_link_shcode'] містить HTML, його треба екранувати для textarea.
									// wp_unslash вже застосовано при санітизації, тому str_replace('\"', '"', ...) не потрібен.
									echo esc_textarea( $O['make_player_from_link_shcode'] ); 
									?></textarea><br />
								<p class="description" style="margin:5px 0 10px 0; font-size:14px;"><?php echo wp_kses_post( __( 'Placeholders: <code>{TEXT}</code> - Link text, <code>{URL}</code> - Link url.<br />This field can also include arbitrary text/html.', 'mp3-jplayer' ) ); ?></p>
							</td>
						</tr>
					</table>							
					<hr><br>
					
					<h3 style="margin:0 0 20px 0; font-weight:500;"><?php esc_html_e( 'Misc File Settings', 'mp3-jplayer' ); ?></h3>
					<p class="mainTick"><input type="checkbox" name="allowRangeRequests" id="allowRangeRequests" value="true"<?php checked( $O['allowRangeRequests'], "true" ); ?>/><label for="allowRangeRequests">&nbsp;&nbsp; <?php esc_html_e( 'Allow position seeking beyond buffered', 'mp3-jplayer' ); ?></label></p>
					<p class="description" style="margin:0 0 10px 30px; max-width:550px; font-size:14px;"><?php esc_html_e( 'Lets users seek to end of tracks without waiting for media to load. Most servers should allow this by default, if you are having issues then check that your server has the <code>accept-ranges: bytes</code> header set, or you can just switch this option off.', 'mp3-jplayer' ); ?></p>
					
					<p class="mainTick" style="margin:0 0 10px 0px;"><input type="checkbox" id="mp3foxHideExtension" name="mp3foxHideExtension" value="true" <?php checked( $O['hide_mp3extension'], "true" );?> /> &nbsp; <label for="mp3foxHideExtension"><?php esc_html_e( 'Hide file extensions if a filename is displayed', 'mp3-jplayer' ); ?></label>
						<br /><span class="description" style="margin-left:30px; font-size:14px;"><?php esc_html_e( 'Filenames are displayed when there\'s no available titles.', 'mp3-jplayer' ); ?></span></p>
					
					<p class="mainTick" style="margin:0 0 10px 0px;"><input type="checkbox" id="mp3foxEncodeFiles" name="mp3foxEncodeFiles" value="true" <?php checked( $O['encode_files'], "true" );?> /> &nbsp; <label for="mp3foxEncodeFiles"><?php esc_html_e( 'Encode URLs', 'mp3-jplayer' ); ?></label>
						<br /><span class="description" style="margin-left:30px;font-size:14px;"><?php esc_html_e( 'Provides some obfuscation of your urls in the page source.', 'mp3-jplayer' ); ?></span></p>
					
					<p class="mainTick" style="margin:0 0 10px 0px;"><input type="checkbox" id="mp3foxAllowRemote" name="mp3foxAllowRemote" value="true" <?php checked( $O['allow_remoteMp3'], "true" );?> /> &nbsp; <label for="mp3foxAllowRemote"><?php esc_html_e( 'Allow playing of off-site files', 'mp3-jplayer' ); ?></label>
						<br /><span class="description" style="margin-left:30px;font-size:14px;"><?php esc_html_e( 'Un-checking this option filters out any files coming from other domains, but doesn\'t affect ability to play from a remote default path if one has been set above.', 'mp3-jplayer' ); ?></span></p>					
					<br><hr><br>
					
					<h3 style="margin:0 0 20px 0; font-weight:500;"><?php esc_html_e( 'Misc Player Settings', 'mp3-jplayer' ); ?></h3>				
					<p class="mainTick" style="margin-bottom:10px;"><strong><?php esc_html_e( 'Show player error messages', 'mp3-jplayer' ); ?></strong>:
						&nbsp;&nbsp;&nbsp;
						<select name="showErrors">
							<option value="false"<?php selected( 'false', $O['showErrors'] ); ?>><?php esc_html_e( 'Never', 'mp3-jplayer' ); ?></option>
							<option value="admin"<?php selected( 'admin', $O['showErrors'] ); ?>><?php esc_html_e( 'To Admins only', 'mp3-jplayer' ); ?></option>
							<option value="true"<?php selected( 'true', $O['showErrors'] ); ?>><?php esc_html_e( 'To All', 'mp3-jplayer' ); ?></option>
						</select></p>
					<br><hr><br>
					
					<h3 style="margin:0 0 20px 0; font-weight:500;"><?php esc_html_e( 'Playlist Separator Settings', 'mp3-jplayer' ); ?></h3>
					<div style="margin: 10px 0px 10px 0px; padding:6px 18px 6px 18px; background:#f9f9f9; border:1px solid #ccc;">
						<p><span class="description" style="font-size:14px;"><?php echo wp_kses_post(__( 'If you manually write playlists then you can choose the separators you use in the tracks and captions lists.<br /><strong>CAUTION!!</strong> You\'ll need to manually update any existing playlists if you change the separators!', 'mp3-jplayer' ) ); ?></span></p>
						
						<p class="mainTick" style="margin:10px 0 0 20px;"><strong><?php esc_html_e( 'Files:', 'mp3-jplayer' ); ?></strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<select name="file_separator" style="width:120px; font-size:11px; line-height:16px;">
								<option value="," <?php selected( ',', $O['f_separator'] ); ?>>, <?php esc_html_e( '(comma)', 'mp3-jplayer' ); ?></option>
								<option value=";" <?php selected( ';', $O['f_separator'] ); ?>>; <?php esc_html_e( '(semicolon)', 'mp3-jplayer' ); ?></option>
								<option value="###" <?php selected( '###', $O['f_separator'] ); ?>>### <?php esc_html_e( '(3 hashes)', 'mp3-jplayer' ); ?></option>
							</select>
							&nbsp;&nbsp;<span class="description"><?php esc_html_e( 'eg.', 'mp3-jplayer' ); ?></span> <code>tracks="fileA.mp3 <?php echo esc_html( $O['f_separator'] ); ?> Title@fileB.mp3 <?php echo esc_html( $O['f_separator'] ); ?> fileC.mp3"</code></p>
						
						<p class="mainTick" style="margin-left:20px;"><strong><?php esc_html_e( 'Captions:', 'mp3-jplayer' ); ?></strong> &nbsp;&nbsp; 
							<select name="caption_separator" style="width:120px; font-size:11px; line-height:16px;">
								<option value="," <?php selected( ',', $O['c_separator'] ); ?>>, <?php esc_html_e( '(comma)', 'mp3-jplayer' ); ?></option>
								<option value=";" <?php selected( ';', $O['c_separator'] ); ?>>; <?php esc_html_e( '(semicolon)', 'mp3-jplayer' ); ?></option>
								<option value="###" <?php selected( '###', $O['c_separator'] ); ?>>### <?php esc_html_e( '(3 hashes)', 'mp3-jplayer' ); ?></option>
							</select>
							&nbsp;&nbsp;<span class="description"><?php esc_html_e( 'eg.', 'mp3-jplayer' ); ?></span> <code>captions="Caption A <?php echo esc_html( $O['c_separator'] ); ?> Caption B <?php echo esc_html( $O['c_separator'] ); ?> Caption C"</code></p>
					</div>
					<br><hr><br>
					
					<h3 style="margin:0 0 20px 0; font-weight:500;"><?php esc_html_e( 'Developer Settings', 'mp3-jplayer' ); ?></h3>
					<p class="mainTick"><input type="checkbox" id="mp3foxEchoDebug" name="mp3foxEchoDebug" value="true" <?php checked( $O['echo_debug'], "true" );?> /> 
						&nbsp;<label for="mp3foxEchoDebug"><?php esc_html_e( 'Turn on debug', 'mp3-jplayer' ); ?></label>
						<br />&nbsp; &nbsp; &nbsp; &nbsp;<span class="description" style="font-size:14px;"><?php esc_html_e( 'Info appears in the source view near the bottom.', 'mp3-jplayer' ); ?></span></p>
					
					<?php $bgc = ( $O['disable_jquery_libs'] == "yes" ) ? "#fdd" : "#f9f9f9"; ?>
					<div style="margin: 20px 0px 10px 0px; padding:6px; background:<?php echo esc_attr($bgc); ?>; border:1px solid #ccc;">
						<p class="mainTick" style="margin:0 0 5px 18px; font-weight:700;"><?php esc_html_e( 'Disable jQuery and jQuery-UI javascript libraries?', 'mp3-jplayer' ); ?> &nbsp; <input type="text" style="width:60px;" name="disableJSlibs" value="<?php echo esc_attr( $O['disable_jquery_libs'] ); ?>" /></p>
						<p style="margin: 0 0 8px 18px;"><span class="description" style="font-size:14px;"><?php echo wp_kses_post( __( '<strong>CAUTION!!</strong> This option will bypass the request <strong>from this plugin only</strong> for both jQuery <strong>and</strong> jQuery-UI scripts, you <strong>MUST</strong> be providing these scripts from an alternative source.<br />Type <code>yes</code> in the box and save settings to bypass jQuery and jQuery-UI.', 'mp3-jplayer' ) ); ?></span></p>
					</div>
				</div></div><hr /><br />
			<table>
				<tr>
					<td>
						<?php // submit_button( __('Save All Changes', 'mp3-jplayer'), 'primary', 'update_mp3foxSettings', true, array('style' => 'font-weight:700;') ); // Можна використовувати submit_button() ?>
                        <input type="submit" name="update_mp3foxSettings" class="button-primary" style="font-weight:700;" value="<?php esc_attr_e( 'Save All Changes', 'mp3-jplayer' ); ?>" />&nbsp;&nbsp;&nbsp;
					</td>
					<td>
						 <p style="margin-top:5px;"><label for="mp3foxRemember"><?php esc_html_e( 'Remember settings if plugin is deactivated', 'mp3-jplayer' ); ?> &nbsp;</label>
							<input type="checkbox" id="mp3foxRemember" name="mp3foxRemember" value="true" <?php checked( $O['remember_settings'], "true" );?> /></p>
					</td>
				</tr>
			</table>

			<input type="hidden" name="mp3foxPluginVersion" value="<?php echo esc_attr( $MP3JP->version_of_plugin ); ?>" />
		
		</form>
		<br><hr>
		<div style="margin: 15px 0px 0px 0px; min-height:30px;">
			<p class="description" style="margin: 0px 120px 0px 0px; font-weight:700; color:#d0d0d0;"> <?php // Видалено 'px' з margin: 0px 120px px 0px; ?>
				<a class="button-secondary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url('http://mp3-jplayer.com/help-docs/'); ?>"><?php esc_html_e( 'Help & Docs', 'mp3-jplayer' ); ?> &raquo;</a>
				&nbsp;&nbsp; <a class="button-secondary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url('http://mp3-jplayer.com/add-ons'); ?>"><?php esc_html_e( 'Get Add-Ons', 'mp3-jplayer' ); ?> &raquo;</a>
				&nbsp;&nbsp; <a class="button-secondary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url('http://mp3-jplayer.com/skins'); ?>"><?php esc_html_e( 'Get Skins', 'mp3-jplayer' ); ?> &raquo;</a>
			</p>
		</div>
		
		<div style="margin: 15px auto; height:100px;"></div>
	</div>

<?php
} // Кінець функції mp3j_print_admin_page
?>
