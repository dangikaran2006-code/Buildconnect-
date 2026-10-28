<?php
/**
 * BuildConnect Multilingual Internationalization (i18n) Engine
 * Supports English (en), Hindi (hi), and Gujarati (gu).
 * Designed for modular extension with zero framework bloat.
 */

// Supported Languages Registry
global $BC_LANGUAGES;
$BC_LANGUAGES = [
    'en' => [
        'code'    => 'en',
        'name'    => 'English',
        'native'  => 'English',
        'locale'  => 'en_US',
        'flag'    => '🇬🇧',
        'dir'     => 'ltr'
    ],
    'hi' => [
        'code'    => 'hi',
        'name'    => 'Hindi',
        'native'  => 'हिन्दी',
        'locale'  => 'hi_IN',
        'flag'    => '🇮🇳',
        'dir'     => 'ltr'
    ],
    'gu' => [
        'code'    => 'gu',
        'name'    => 'Gujarati',
        'native'  => 'ગુજરાતી',
        'locale'  => 'gu_IN',
        'flag'    => '🇮🇳',
        'dir'     => 'ltr'
    ]
];

// Active Translation Dictionaries Cache
global $BC_TRANSLATIONS, $BC_CURRENT_LANG;
$BC_TRANSLATIONS = [];
$BC_CURRENT_LANG = 'en';

/**
 * Handle Language Switching via Query Parameter, POST, or Stored Preferences
 */
function init_i18n() {
    global $BC_LANGUAGES, $BC_CURRENT_LANG, $BC_TRANSLATIONS;

    // Strict allowlist validation
    $allowed = ['en', 'hi', 'gu'];

    // 1. Check if user explicitly switches language via GET (?lang=code) or POST
    $requested = null;
    if (isset($_GET['lang']) && is_string($_GET['lang'])) {
        $requested = strtolower(trim($_GET['lang']));
    } elseif (isset($_POST['lang']) && is_string($_POST['lang'])) {
        $requested = strtolower(trim($_POST['lang']));
    }

    if ($requested !== null && in_array($requested, $allowed, true)) {
        // Safe server submission & session persistence
        $_SESSION['bc_lang'] = $requested;
        $_SESSION['lang'] = $requested;
        $_COOKIE['bc_lang'] = $requested;
        $_COOKIE['lang'] = $requested;

        $cookie_path = '/';
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        @setcookie('bc_lang', $requested, time() + (86400 * 30), $cookie_path, '', $is_https, false);
        @setcookie('lang', $requested, time() + (86400 * 30), $cookie_path, '', $is_https, false);

        $BC_CURRENT_LANG = $requested;

        // Persist to user record if logged in and column exists
        if (isset($_SESSION['user_id'])) {
            try {
                $db = getDB();
                $chk = $db->query("SHOW COLUMNS FROM users LIKE 'preferred_lang'")->fetch();
                if ($chk) {
                    $up = $db->prepare("UPDATE users SET preferred_lang = ? WHERE id = ?");
                    $up->execute([$requested, $_SESSION['user_id']]);
                }
            } catch (Exception $e) {
                // Fail gracefully
            }
        }
    } else {
        // 2. Resolve Active Language by Priority: Session -> Cookie -> User DB -> Default ('en')
        if (!empty($_SESSION['bc_lang']) && in_array($_SESSION['bc_lang'], $allowed, true)) {
            $BC_CURRENT_LANG = $_SESSION['bc_lang'];
        } elseif (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed, true)) {
            $BC_CURRENT_LANG = $_SESSION['lang'];
            $_SESSION['bc_lang'] = $BC_CURRENT_LANG;
        } elseif (!empty($_COOKIE['bc_lang']) && in_array($_COOKIE['bc_lang'], $allowed, true)) {
            $BC_CURRENT_LANG = $_COOKIE['bc_lang'];
            $_SESSION['bc_lang'] = $BC_CURRENT_LANG;
            $_SESSION['lang'] = $BC_CURRENT_LANG;
        } elseif (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], $allowed, true)) {
            $BC_CURRENT_LANG = $_COOKIE['lang'];
            $_SESSION['bc_lang'] = $BC_CURRENT_LANG;
            $_SESSION['lang'] = $BC_CURRENT_LANG;
        } else {
            // Check User DB preference if logged in
            $db_lang = null;
            if (isset($_SESSION['user_id'])) {
                try {
                    $db = getDB();
                    $chk = $db->prepare("SELECT preferred_lang FROM users WHERE id = ?");
                    $chk->execute([$_SESSION['user_id']]);
                    $plang = $chk->fetchColumn();
                    if ($plang && in_array($plang, $allowed, true)) {
                        $db_lang = $plang;
                    }
                } catch (Exception $e) {
                    // Graceful fallback
                }
            }

            if ($db_lang !== null) {
                $BC_CURRENT_LANG = $db_lang;
                $_SESSION['bc_lang'] = $db_lang;
                $_SESSION['lang'] = $db_lang;
            } else {
                $BC_CURRENT_LANG = 'en';
                $_SESSION['bc_lang'] = 'en';
                $_SESSION['lang'] = 'en';
            }
        }
    }

    // 3. Load English (Default / Fallback) Dictionary
    $en_file = dirname(__DIR__) . '/lang/en.php';
    if (file_exists($en_file)) {
        $BC_TRANSLATIONS['en'] = require $en_file;
    } else {
        $BC_TRANSLATIONS['en'] = [];
    }

    // 4. Load Active Language Dictionary if different from English
    if ($BC_CURRENT_LANG !== 'en') {
        $lang_file = dirname(__DIR__) . "/lang/{$BC_CURRENT_LANG}.php";
        if (file_exists($lang_file)) {
            $BC_TRANSLATIONS[$BC_CURRENT_LANG] = require $lang_file;
        } else {
            $BC_TRANSLATIONS[$BC_CURRENT_LANG] = [];
        }
    }
}

/**
 * Programmatically switch active language and update session, cookies, and user DB
 */
function switch_lang($lang_code) {
    global $BC_LANGUAGES, $BC_CURRENT_LANG, $BC_TRANSLATIONS;
    $allowed = ['en', 'hi', 'gu'];
    $lang = strtolower(trim((string)$lang_code));
    if (!in_array($lang, $allowed, true)) {
        return false;
    }

    $BC_CURRENT_LANG = $lang;
    $_SESSION['bc_lang'] = $lang;
    $_SESSION['lang'] = $lang;

    $cookie_path = '/';
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    @setcookie('bc_lang', $lang, time() + (86400 * 30), $cookie_path, '', $is_https, false);
    @setcookie('lang', $lang, time() + (86400 * 30), $cookie_path, '', $is_https, false);

    // Load dictionary if not already loaded
    if (!isset($BC_TRANSLATIONS[$lang])) {
        $lang_file = dirname(__DIR__) . "/lang/{$lang}.php";
        if (file_exists($lang_file)) {
            $BC_TRANSLATIONS[$lang] = require $lang_file;
        }
    }

    if (isset($_SESSION['user_id'])) {
        try {
            $db = getDB();
            $up = $db->prepare("UPDATE users SET preferred_lang = ? WHERE id = ?");
            $up->execute([$lang, $_SESSION['user_id']]);
        } catch (Exception $e) {}
    }

    return true;
}

/**
 * Get all loaded translations for a given language (or current language)
 */
function get_all_translations($lang = null) {
    global $BC_TRANSLATIONS, $BC_CURRENT_LANG;
    $target_lang = $lang ?: ($BC_CURRENT_LANG ?? 'en');
    if (!isset($BC_TRANSLATIONS[$target_lang])) {
        $lang_file = dirname(__DIR__) . "/lang/{$target_lang}.php";
        if (file_exists($lang_file)) {
            $BC_TRANSLATIONS[$target_lang] = require $lang_file;
        } else {
            $BC_TRANSLATIONS[$target_lang] = [];
        }
    }
    return $BC_TRANSLATIONS[$target_lang] ?? [];
}

/**
 * Translate database status / enum value
 */
function translate_status($status) {
    if ($status === null || $status === '') return '';
    $key = 'status.' . strtolower(trim((string)$status));
    $trans = __($key);
    if ($trans !== $key) {
        return $trans;
    }
    // Fallback: replace underscores with spaces and capitalize
    return ucwords(str_replace('_', ' ', (string)$status));
}

/**
 * Get current active language code (e.g. 'en', 'hi', 'gu')
 */
function current_lang() {
    global $BC_CURRENT_LANG;
    return $BC_CURRENT_LANG ?? 'en';
}

/**
 * Get current active language details array
 */
function current_lang_info() {
    global $BC_LANGUAGES, $BC_CURRENT_LANG;
    $code = current_lang();
    return $BC_LANGUAGES[$code] ?? $BC_LANGUAGES['en'];
}

/**
 * Get all supported languages array
 */
function get_supported_languages() {
    global $BC_LANGUAGES;
    return $BC_LANGUAGES;
}

/**
 * Build language switcher URL preserving all existing query parameters
 */
function get_lang_switch_url($lang_code) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $url_parts = parse_url($uri);
    $path = $url_parts['path'] ?? '/';
    $query = [];
    if (!empty($url_parts['query'])) {
        parse_str($url_parts['query'], $query);
    }
    $query['lang'] = $lang_code;
    return $path . '?' . http_build_query($query);
}

/**
 * Primary Translation Helper: __($key, $replacements = [], $lang = null)
 * 
 * Supports:
 * - Direct key: __('nav.home')
 * - Parameter substitution: __('welcome_user', ['name' => 'Karan'])
 * - Fallback: active lang -> en -> original key
 */
function __($key, $replacements = [], $lang = null) {
    global $BC_TRANSLATIONS, $BC_CURRENT_LANG, $BC_LANGUAGES;

    $target_lang = $lang ?: ($BC_CURRENT_LANG ?? 'en');

    // 0. Ensure target language dictionary is loaded
    if (!isset($BC_TRANSLATIONS[$target_lang])) {
        if (isset($BC_LANGUAGES[$target_lang])) {
            $lang_file = dirname(__DIR__) . "/lang/{$target_lang}.php";
            if (file_exists($lang_file)) {
                $BC_TRANSLATIONS[$target_lang] = require $lang_file;
            } else {
                $BC_TRANSLATIONS[$target_lang] = [];
            }
        } else {
            $BC_TRANSLATIONS[$target_lang] = [];
        }
    }

    // 1. Check in target language
    $text = null;
    if (isset($BC_TRANSLATIONS[$target_lang][$key])) {
        $text = $BC_TRANSLATIONS[$target_lang][$key];
    } elseif (isset($BC_TRANSLATIONS['en'][$key])) {
        // Fallback to English
        $text = $BC_TRANSLATIONS['en'][$key];
    } else {
        // Fallback to formatted key
        $text = $key;
    }

    // 2. Perform replacements (e.g. :name, {name})
    if (!empty($replacements) && is_array($replacements)) {
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace(
                [':' . $placeholder, '{' . $placeholder . '}'],
                (string)$value,
                $text
            );
        }
    }

    return $text;
}

/**
 * Echo escaped translated string
 */
function _e($key, $replacements = [], $lang = null) {
    echo htmlspecialchars(__($key, $replacements, $lang), ENT_QUOTES, 'UTF-8');
}

/**
 * Alias for __()
 */
function trans($key, $replacements = [], $lang = null) {
    return __($key, $replacements, $lang);
}

// Automatically initialize i18n
init_i18n();
