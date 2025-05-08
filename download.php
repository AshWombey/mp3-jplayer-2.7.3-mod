<?php
/*
*	Local Downloader
*	MP3-jPlayer 2.7.3 (Mod) //
*	http://mp3-jplayer.com
*	---
*/

// === Початок змін для захисту від CSRF та WordPress інтеграції ===

// Завантажуємо WordPress середовище, якщо скрипт викликається напряму
// Це потрібно, щоб функції WordPress (nonces, sanitization) були доступні.
// Шлях може потребувати коригування залежно від структури виклику.
if ( ! defined( 'ABSPATH' ) ) {
    $wp_load_path = false;
    // Спроба знайти wp-load.php, рухаючись вгору по директоріях
    $current_dir = __DIR__;
    for ( $i = 0; $i < 5; $i++ ) { // Обмеження на 5 рівнів вгору
        if ( file_exists( $current_dir . '/wp-load.php' ) ) {
            $wp_load_path = $current_dir . '/wp-load.php';
            break;
        }
        if ( file_exists( $current_dir . '/../../../wp-load.php' ) ) { // Стандартне розміщення для плагінів
             $wp_load_path = $current_dir . '/../../../wp-load.php';
             break;
        }
        $parent_dir = dirname( $current_dir );
        if ( $parent_dir === $current_dir ) { // Досягли кореня файлової системи
            break;
        }
        $current_dir = $parent_dir;
    }

    if ( $wp_load_path ) {
        require_once( $wp_load_path );
    } else {
        // Якщо wp-load.php не знайдено, функції WordPress будуть недоступні.
        // Це критично для безпеки. Розгляньте альтернативний механізм або переконайтеся,
        // що скрипт завжди викликається з завантаженим WordPress.
        die( 'WordPress environment not loaded.' );
    }
}

// Отримуємо pID та створюємо відповідну дію для nonce
// Важливо: pID має бути однаковим при генерації та перевірці nonce.
// Якщо pID може містити спецсимволи, його також потрібно очистити перед використанням в nonce_action.
$playerID_for_nonce = isset($_GET['pID']) ? sanitize_text_field(wp_unslash($_GET['pID'])) : '';
// Створюємо більш унікальну дію для nonce
$mp3_param_for_nonce = isset($_GET['mp3']) ? basename(sanitize_text_field(wp_unslash($_GET['mp3']))) : ''; // Використовуємо basename для унікальності
$nonce_action = 'mp3j_download_track_' . $playerID_for_nonce . '_' . $mp3_param_for_nonce;

if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
    status_header(403); // Надсилаємо відповідний HTTP статус
    wp_die('Security check failed: Invalid nonce or action.', 'Nonce Verification Failed', array('response' => 403));
}
// === Кінець змін для захисту від CSRF ===

//~~~ Flag
function mp3j_check_chars_ok ( $string ) { // Перейменував, щоб уникнути конфліктів
	$badChars = 0;
	$charArray = str_split( $string );

	if ( is_array( $charArray ) ) {
		foreach ( $charArray as $char ) {
			$ascii = ord( $char );
			if ( 0 === $ascii ) { //null bytes
				$badChars += 1;
			}
		}
	} else {
		$badChars += 1; // Якщо $string не рядок, str_split поверне null (в PHP 8) або false і warning
	}
	return ( $badChars === 0 );
}


//~~~ Clean
function mp3j_strip_scripts ( $field ) { // Перейменував
    // Покращена версія для видалення скриптів та небезпечних тегів, використовуючи wp_kses_post для полів, де очікується HTML,
    // або sanitize_text_field для простих текстових полів.
    // Для шляхів файлів або URL краще використовувати специфічні функції.
    // Поточна реалізація stripScripts може бути недостатньою.
    // Для URL, що використовуються в file operations, потрібне особливе очищення.
    // Нижче залишено оригінальну логіку, але рекомендується переглянути її на користь функцій WordPress.
	$search = array(
		'@<script[^>]*?>.*?</script>@si',	// Javascript
		'@<style[^>]*?>.*?</style>@siU',    // Style tags
		'@<![\s\S]*?--[ \t\n\r]*>@',        // Multi-line comments including CDATA
        // Null bytes та traversals краще обробляти окремо і більш суворо для шляхів файлів.
		// '@%00@', // Null byte stripping - PHP має вбудований захист від null byte injection в файлових функціях з 5.3.4
		// '@\.\.@' // Traversals - ця перевірка має бути контекстною для шляхів
	);
	$text = preg_replace( $search, '', $field );
    $text = str_replace(array("\0", '%00'), '', $text); // Явне видалення null bytes
	return $text;
}

// Start ~~~~~~~~~~~~~~~~~~~~~~

$dbug_messages = array(); // Використовуємо масив для дебаг-повідомлень
$sent 		= "";
$mp3_raw 	= ""; // Використовуємо _raw для необроблених вхідних даних
$playerID 	= "";
$file_path_segment = ""; // Для частини шляху, отриманої з $mp3
$file_name 	= "";
$http_host  = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
$document_root = رealpath(isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : ABSPATH); // Використовуємо ABSPATH як fallback

if ( ! $document_root ) {
    $dbug_messages[] = "Error: Document root could not be determined.";
    wp_die('Configuration error: Document root not found. Debug: ' . esc_html(implode("; ", $dbug_messages)));
}


if ( isset($_GET['mp3']) ) {
	$mp3_raw = wp_unslash($_GET['mp3']); // wp_unslash для обробки слешів, доданих WordPress

	// Очищення URL/шляху
    // Спочатку декодуємо URL, якщо він закодований
    $mp3_decoded = rawurldecode($mp3_raw);

    // Базове очищення від тегів та потенційно небезпечних скриптів
    // Для шляхів файлів це може бути недостатньо, потрібна валідація структури шляху.
    $mp3_path_candidate = mp3j_strip_scripts($mp3_decoded); // Ваша функція очищення
    $mp3_path_candidate = sanitize_text_field($mp3_path_candidate); // Додаткове очищення WordPress

    // Перевірка на null bytes після всіх очищень
	if ( ! mp3j_check_chars_ok( $mp3_path_candidate ) ) {
        $dbug_messages[] = "Error: Invalid characters (null bytes) in mp3 parameter.";
		wp_die('Invalid input: Null bytes detected in file path. Debug: ' . esc_html(implode("; ", $dbug_messages)));
	}

    // Логіка отримання $sent (частина шляху)
    // Оригінальна логіка: $sent = substr($mp3, 3);
    // Ця логіка (видалення перших 3 символів) виглядає дуже специфічною і потенційно небезпечною.
    // Потрібно розуміти, чому вона тут. Якщо це для видалення '://' або '../', то це не надійний спосіб.
    // Припускаючи, що $mp3_path_candidate - це вже відносний шлях або URL,
    // що починається з домену, ця логіка потребує перегляду.
    // Для прикладу, залишимо її, але з попередженням.
    // WARNING: The logic substr($mp3, 3) is suspicious and should be reviewed for security.
    $file_path_segment = $mp3_path_candidate; // Поки що використовуємо повний очищений шлях

	// Очищення ID гравця (вже використовувався для nonce, але тут отримуємо знову для логіки файлу)
	$playerID = isset($_GET['pID']) ? sanitize_text_field(wp_unslash($_GET['pID'])) : ''; // Використовуємо той самий pID
	$playerID = preg_replace( '![^0-9a-zA-Z_-]!', '', $playerID ); // Дозволимо також букви, _, - для ID, якщо потрібно

	if ( empty($playerID) ) {
        $dbug_messages[] = "Error: Player ID is missing or invalid.";
		wp_die('Invalid input: Player ID is required. Debug: ' . esc_html(implode("; ", $dbug_messages)));
	}

	// Перевірка типу файлу
	$matches = array();
    // Додамо ^ і $ для більш точного співпадіння розширень
	if ( preg_match("!\.(mp3|mp4|m4a|ogg|oga|wav|webm)$!i", $file_path_segment, $matches) ) {
		$fileExtension = strtolower($matches[1]); // $matches[1] містить розширення без крапки
		$mimeType = wp_get_mime_types()[$fileExtension] ?? 'application/octet-stream'; // Використовуємо стандартні MIME типи WordPress

		// Отримання імені файлу
		$file_name = basename( $file_path_segment );
        // Додатково очистимо ім'я файлу
        $file_name = sanitize_file_name($file_name);

		// Перевірка, чи файл розміщений локально
        // Видаляємо схему та хост з URL, якщо вони є, щоб отримати відносний шлях
        $path_to_check = $file_path_segment;
        if (strpos($path_to_check, '://') !== false) {
            $parsed_url = wp_parse_url($path_to_check);
            if ($parsed_url && isset($parsed_url['host']) && strtolower($parsed_url['host']) === strtolower($http_host)) {
                $path_to_check = isset($parsed_url['path']) ? $parsed_url['path'] : '';
            } else if ($parsed_url && isset($parsed_url['host'])) {
                // Файл на іншому хості - заборонено
                $dbug_messages[] = "Error: File is not hosted locally (external host specified: " . esc_html($parsed_url['host']) . ").";
                wp_die('Access denied: External files are not permitted. Debug: ' . esc_html(implode("; ", $dbug_messages)));
            }
            // Якщо схема є, але хоста немає (наприклад, file://), це також може бути проблемою
        }

        // Нормалізуємо шлях, видаляючи можливі зайві слеші або крапки
        $path_to_check = '/' . ltrim(trim($path_to_check), '/'); // Переконуємося, що починається з одного слеша
        $full_file_path_unsafe = $document_root . $path_to_check;

        // Валідація шляху та запобігання обходу директорій
        $real_file_path = realpath( $full_file_path_unsafe );

        // Перевіряємо, чи $real_file_path існує, знаходиться всередині $document_root
        // і чи не містить небезпечних конструкцій (наприклад, обхід директорій, що не був виявлений realpath)
        if ( $real_file_path === false || strpos($real_file_path, $document_root) !== 0 ) {
            $dbug_messages[] = "Error: File path is not valid, file does not exist, or access is denied. Path tried: " . esc_html($full_file_path_unsafe);
            wp_die('File access error: The requested file could not be accessed. Debug: ' . esc_html(implode("; ", $dbug_messages)));
        }

        // Додаткова перевірка на '.php' або інші скриптові розширення, якщо не очікуються
        if (preg_match('/\.(php|phtml|phar|pl|py|cgi|asp|aspx)(\.|$)/i', $real_file_path)) {
            $dbug_messages[] = "Error: Access to script files is not permitted through this interface.";
            wp_die('Access denied: Script files are not allowed. Debug: ' . esc_html(implode("; ", $dbug_messages)));
        }

		// Спроба відправити файл
		if ( is_file($real_file_path) && is_readable($real_file_path) ) {
            $fsize = filesize( $real_file_path );
            if ( $fsize !== false ) {

                if (headers_sent($hs_file, $hs_line)) {
                    $dbug_messages[] = "Error: Headers already sent in " . esc_html($hs_file) . " on line " . esc_html($hs_line);
                    // Не можемо надіслати файл, якщо заголовки вже відправлені
                    wp_die('Internal error: Cannot send file, headers already sent. Debug: ' . esc_html(implode("; ", $dbug_messages)));
                }

				// Встановлення заголовків
                status_header(200);
				header('Content-Type: ' . $mimeType);
				// $cookiename = 'mp3Download' . $playerID; // Кукі для відстеження завантажень можуть бути CSRF-вразливими, якщо їх встановлення має побічні ефекти
				// setcookie($cookiename, "true", 0, '/', '', false, true); // Додано httponly, якщо кукі не потрібні JS
                // Якщо ці кукі критичні, їх встановлення має бути захищене nonce або іншим чином.

				header('Accept-Ranges: bytes');
				header('Content-Disposition: attachment; filename="' . $file_name . '"'); // Використовуємо очищене ім'я файлу
				header('Content-Length: ' . $fsize);
                header('X-Content-Type-Options: nosniff');
                header('Cache-Control: private');
                header('Pragma: private');
                // Expires у минулому для запобігання кешуванню на проксі
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');


				// Очищення буферів виводу перед відправкою файлу
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

				// Відправка файлу
				$bytes_sent = readfile( $real_file_path );

                if ($bytes_sent === false || $bytes_sent != $fsize) {
                    // Логування помилки читання файлу, якщо потрібно
                    // $dbug_messages[] = "Error: readfile() failed or sent incomplete data.";
                    // Не надсилаємо wp_die тут, оскільки частина файлу могла бути відправлена
                }
				exit; // Завершуємо скрипт після відправки файлу

			} else {
				$dbug_messages[] = "Error: File not found or cannot be sized at " . esc_html($real_file_path);
			}
        } else {
            $dbug_messages[] = "Error: File is not readable or is not a file at " . esc_html($real_file_path);
        }
	} else {
		$dbug_messages[] = "Error: Unsupported file format for parameter: " . esc_html($file_path_segment);
	}
} else {
	$dbug_messages[] = "Error: Required GET parameter 'mp3' is missing.";
}

// Якщо дійшли сюди, значить сталася помилка перед exit
status_header(400); // Bad Request або інший відповідний статус помилки
wp_die('File download failed. Debug: ' . esc_html(implode("; ", $dbug_messages)), 'Download Error', array('response' => 400));

?>
// HTML частина, що була в оригінальному файлі, тут не потрібна,
// оскільки скрипт або успішно віддає файл і завершується (exit),
// або завершується через wp_die() у випадку помилки.
