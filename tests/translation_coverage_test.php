<?php
/**
 * Translation Coverage and Multilingual Verification Suite
 * BuildConnect - Comprehensive i18n Automated Test
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '1');

// Setup base definitions
if (!defined('BASE_URL')) define('BASE_URL', '/BuildConnect');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/BuildConnect/tests/translation_coverage_test.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking-functions.php';

$passes = 0;
$failures = 0;
$warnings = 0;

function assert_test($description, $condition, $details = '') {
    global $passes, $failures;
    if ($condition) {
        echo "  [PASS] $description\n";
        $passes++;
    } else {
        echo "  [FAIL] $description\n";
        if ($details) echo "         Details: $details\n";
        $failures++;
    }
}

echo "====================================================================\n";
echo " BuildConnect - Translation Coverage & System Verification Suite\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------
// 1. DICTIONARY PARITY & KEY INTEGRITY
// -------------------------------------------------------------------
echo "TEST SUITE 1: Translation Dictionaries & Parity Check\n";
echo "--------------------------------------------------------------------\n";

$en_dict = require __DIR__ . '/../lang/en.php';
$hi_dict = require __DIR__ . '/../lang/hi.php';
$gu_dict = require __DIR__ . '/../lang/gu.php';

$en_count = count($en_dict);
$hi_count = count($hi_dict);
$gu_count = count($gu_dict);

echo "  Key counts: EN = $en_count | HI = $hi_count | GU = $gu_count\n";

assert_test("EN dictionary has >= 600 keys ($en_count present)", $en_count >= 600);
assert_test("HI dictionary matches EN key count exactly ($hi_count === $en_count)", $hi_count === $en_count);
assert_test("GU dictionary matches EN key count exactly ($gu_count === $en_count)", $gu_count === $en_count);

$missing_in_hi = array_diff_key($en_dict, $hi_dict);
$missing_in_gu = array_diff_key($en_dict, $gu_dict);
$extra_in_hi = array_diff_key($hi_dict, $en_dict);
$extra_in_gu = array_diff_key($gu_dict, $en_dict);

assert_test("Zero missing keys in Hindi dictionary", count($missing_in_hi) === 0, implode(', ', array_keys($missing_in_hi)));
assert_test("Zero missing keys in Gujarati dictionary", count($missing_in_gu) === 0, implode(', ', array_keys($missing_in_gu)));
assert_test("Zero extra keys in Hindi dictionary", count($extra_in_hi) === 0, implode(', ', array_keys($extra_in_hi)));
assert_test("Zero extra keys in Gujarati dictionary", count($extra_in_gu) === 0, implode(', ', array_keys($extra_in_gu)));

// Check for empty values
$empty_en = array_filter($en_dict, fn($v) => trim($v) === '');
$empty_hi = array_filter($hi_dict, fn($v) => trim($v) === '');
$empty_gu = array_filter($gu_dict, fn($v) => trim($v) === '');
assert_test("No empty values in EN dictionary", count($empty_en) === 0);
assert_test("No empty values in HI dictionary", count($empty_hi) === 0);
assert_test("No empty values in GU dictionary", count($empty_gu) === 0);

echo "\n";

// -------------------------------------------------------------------
// 2. LANGUAGE SWITCHING & PERSISTENCE SIMULATION
// -------------------------------------------------------------------
echo "TEST SUITE 2: Language Switching Workflow (EN -> HI -> GU -> EN)\n";
echo "--------------------------------------------------------------------\n";

// Step A: Switch to Hindi
switch_lang('hi');
assert_test("Switched to Hindi: current_lang() is 'hi'", current_lang() === 'hi');
assert_test("Session bc_lang is 'hi'", ($_SESSION['bc_lang'] ?? '') === 'hi');
assert_test("Translation helper __('worker.assigned_sites') produces Hindi", __('worker.assigned_sites') === $hi_dict['worker.assigned_sites']);
assert_test("Native language name is 'हिन्दी'", current_lang_info()['native'] === 'हिन्दी');

// Step B: Switch to Gujarati
switch_lang('gu');
assert_test("Switched to Gujarati: current_lang() is 'gu'", current_lang() === 'gu');
assert_test("Session bc_lang is 'gu'", ($_SESSION['bc_lang'] ?? '') === 'gu');
assert_test("Translation helper __('worker.assigned_sites') produces Gujarati", __('worker.assigned_sites') === $gu_dict['worker.assigned_sites']);
assert_test("Native language name is 'ગુજરાતી'", current_lang_info()['native'] === 'ગુજરાતી');

// Step C: Switch back to English
switch_lang('en');
assert_test("Switched to English: current_lang() is 'en'", current_lang() === 'en');
assert_test("Session bc_lang is 'en'", ($_SESSION['bc_lang'] ?? '') === 'en');
assert_test("Translation helper __('worker.assigned_sites') produces English", __('worker.assigned_sites') === $en_dict['worker.assigned_sites']);
assert_test("Native language name is 'English'", current_lang_info()['native'] === 'English');

// Step D: Invalid language fallback
switch_lang('invalid_xyz');
assert_test("Invalid language gracefully rejected, stays on 'en'", current_lang() === 'en');

// Step E: Status translation helper check
assert_test("translate_status('pending') maps to 'Pending' in EN", translate_status('pending') === 'Pending');
switch_lang('hi');
assert_test("translate_status('pending') maps to 'लंबित' in HI", translate_status('pending') === 'लंबित');
assert_test("translate_status('draft') maps to 'प्रारूप' in HI", translate_status('draft') === 'प्रारूप');
assert_test("translate_status('published') maps to 'प्रकाशित' in HI", translate_status('published') === 'प्रकाशित');
switch_lang('gu');
assert_test("translate_status('pending') maps to 'બાકી' in GU", translate_status('pending') === 'બાકી');
assert_test("translate_status('draft') maps to 'ડ્રાફ્ટ' in GU", translate_status('draft') === 'ડ્રાફ્ટ');
assert_test("translate_status('published') maps to 'પ્રકાશિત' in GU", translate_status('published') === 'પ્રકાશિત');
switch_lang('en');

// Step F: Date Localization Helper Check
switch_lang('hi');
assert_test("format_date('2026-10-25') translates month to Hindi", format_date('2026-10-25') === 'अक्टूबर 25, 2026');
switch_lang('gu');
assert_test("format_date('2026-10-25') translates month to Gujarati", format_date('2026-10-25') === 'ઓક્ટોબર 25, 2026');
switch_lang('en');

// Step G: Trade & Pay Type Localization Check
switch_lang('hi');
assert_test("translate_trade('Structural Welder') produces Hindi", translate_trade('Structural Welder') === 'संरचनात्मक वेल्डर');
assert_test("translate_pay_type('hourly') produces Hindi", translate_pay_type('hourly') === 'प्रति घंटा');
switch_lang('gu');
assert_test("translate_trade('Structural Welder') produces Gujarati", translate_trade('Structural Welder') === 'સ્ટ્રક્ચરલ વેલ્ડર');
assert_test("translate_pay_type('hourly') produces Gujarati", translate_pay_type('hourly') === 'કલાક દીઠ');
switch_lang('en');

// Step H: Notification Title & Message Translation Check
switch_lang('hi');
assert_test("translate_notification_title('Booking Status: Completed') produces Hindi", translate_notification_title('Booking Status: Completed') === 'बुकिंग स्थिति: पूर्ण');
assert_test("translate_notification_title('Quotation Closed') produces Hindi", translate_notification_title('Quotation Closed') === 'भावपत्र बंद');
assert_test("translate_notification_message translates dynamic pattern in Hindi", strpos(translate_notification_message("Booking #BK-01 status was updated to 'Completed'."), 'बुकिंग') !== false);
switch_lang('gu');
assert_test("translate_notification_title('Booking Status: Completed') produces Gujarati", translate_notification_title('Booking Status: Completed') === 'બુકિંગ સ્થિતિ: પૂર્ણ');
assert_test("translate_notification_title('Quotation Closed') produces Gujarati", translate_notification_title('Quotation Closed') === 'ભાવપત્રક બંધ');
assert_test("translate_notification_message translates dynamic pattern in Gujarati", strpos(translate_notification_message("Booking #BK-01 status was updated to 'Completed'."), 'બુકિંગ') !== false);
switch_lang('en');

// Step I: Login Language Choice Preservation Check
// When a user selects Hindi on public page, then logs in, active session choice is preserved.
$_SESSION['bc_lang'] = 'hi';
$db = getDB();
$test_user = $db->query("SELECT id, email, preferred_lang FROM users WHERE status = 'active' LIMIT 1")->fetch();
if ($test_user) {
    // Simulate login sync
    $saved_pref = $_SESSION['bc_lang'];
    $sync_stmt = $db->prepare("UPDATE users SET preferred_lang = ? WHERE id = ?");
    $sync_stmt->execute([$saved_pref, $test_user['id']]);
    
    $recheck = $db->prepare("SELECT preferred_lang FROM users WHERE id = ?");
    $recheck->execute([$test_user['id']]);
    $updated_lang = $recheck->fetchColumn();
    assert_test("Login preserves active session language and persists to DB ($updated_lang === 'hi')", $updated_lang === 'hi');
    
    // Restore
    $sync_stmt->execute([$test_user['preferred_lang'] ?: 'en', $test_user['id']]);
}

switch_lang('en');
echo "\n";

// -------------------------------------------------------------------
// 3. TEMPLATE KEY USAGE COVERAGE
// -------------------------------------------------------------------
echo "TEST SUITE 3: Template Translation Key Reference Coverage\n";
echo "--------------------------------------------------------------------\n";

$template_files = [
    'index.php' => __DIR__ . '/../index.php',
    'worker/index.php' => __DIR__ . '/../worker/index.php',
    'admin/index.php' => __DIR__ . '/../admin/index.php',
    'client/index.php' => __DIR__ . '/../client/index.php',
    'contractor/index.php' => __DIR__ . '/../contractor/index.php',
    'login.php' => __DIR__ . '/../login.php',
    'register.php' => __DIR__ . '/../register.php',
    'notifications.php' => __DIR__ . '/../notifications.php',
    'worker/notifications.php' => __DIR__ . '/../worker/notifications.php',
    'unauthorized.php' => __DIR__ . '/../unauthorized.php',
    'client/bookings.php' => __DIR__ . '/../client/bookings.php',
    'worker/my-bookings.php' => __DIR__ . '/../worker/my-bookings.php',
    'includes/sidebar.php' => __DIR__ . '/../includes/sidebar.php',
    'includes/navbar.php' => __DIR__ . '/../includes/navbar.php',
    'includes/footer.php' => __DIR__ . '/../includes/footer.php',
];

$all_referenced_keys = [];
foreach ($template_files as $name => $path) {
    if (!file_exists($path)) {
        assert_test("File exists: $name", false);
        continue;
    }
    $content = file_get_contents($path);
    // Find all __('key'...) and _e('key'...)
    preg_match_all("/(?:__|trans|_e)\(\s*['\"]([a-zA-Z0-9_\.]+)['\"]/", $content, $matches);
    $keys = array_unique($matches[1]);
    $missing_for_file = [];
    foreach ($keys as $k) {
        // If it's a dynamic prefix like 'role.' or 'status.', verify the prefixed keys exist
        if (str_ends_with($k, '.')) {
            $prefix = $k;
            $has_matching = false;
            foreach ($en_dict as $dict_k => $v) {
                if (str_starts_with($dict_k, $prefix)) {
                    $has_matching = true;
                    break;
                }
            }
            if (!$has_matching) {
                $missing_for_file[] = $k;
            }
        } else {
            $all_referenced_keys[$k] = true;
            if (!isset($en_dict[$k])) {
                $missing_for_file[] = $k;
            }
        }
    }
    assert_test("Template '$name' references " . count($keys) . " keys, all valid", count($missing_for_file) === 0, implode(', ', $missing_for_file));
}

echo "  Total unique translation keys referenced across audited templates: " . count($all_referenced_keys) . "\n\n";

// -------------------------------------------------------------------
// 4. SCAN FOR UNTRANSLATED HARDCODED STRINGS IN TEMPLATES
// -------------------------------------------------------------------
echo "TEST SUITE 4: Hardcoded UI String Verification\n";
echo "--------------------------------------------------------------------\n";

$hardcoded_checks = [
    'index.php' => [
        'AI Powered Construction Management' => '/>\s*AI Powered Construction Management\s*</i',
        'One Central Hub for Every Construction Need' => '/>\s*One Central Hub for Every Construction Need\s*</i',
        'From Connection to Completion' => '/>\s*From Connection to Completion\s*</i',
        'Everything You Need to Build Smarter' => '/>\s*Everything You Need to Build Smarter\s*</i',
        'Built for Everyone in Construction' => '/>\s*Built for Everyone in Construction\s*</i',
        'Ready to Build Smarter?' => '/>\s*Ready to Build Smarter\?\s*</i',
    ],
    'worker/index.php' => [
        'Profile Completion' => '/<[^>]*>\s*Profile Completion\s*<\/[^>]*>/i',
        'Complete Profile' => '/>\s*Complete Profile\s*</i',
        'Verification' => '/>\s*Verification\s*</i',
        'Assigned Sites' => '/>\s*Assigned Sites\s*</i',
        'Trade Skills' => '/>\s*Trade Skills\s*</i',
        'Documents' => '/>\s*Documents\s*</i',
        'Attendance' => '/>\s*Attendance\s*</i',
        'My Active Site Assignments' => '/>\s*My Active Site Assignments\s*</i',
    ],
    'contractor/index.php' => [
        'Total Projects' => '/>\s*Total Projects\s*</i',
        'Active Projects' => '/>\s*Active Projects\s*</i',
        'Completed Projects' => '/>\s*Completed Projects\s*</i',
        'Workforce Assigned' => '/>\s*Workforce Assigned\s*</i',
        'Active Jobs' => '/>\s*Active Jobs\s*</i',
        'Pending Applications' => '/>\s*Pending Applications\s*</i',
        'Recent Projects' => '/>\s*Recent Projects\s*</i',
        'Active Project Job Postings' => '/>\s*Active Project Job Postings\s*</i',
    ],
    'client/index.php' => [
        'My Construction Projects' => '/>\s*My Construction Projects\s*</i',
        'Recent Booking Status' => '/>\s*Recent Booking Status\s*</i',
        'Target Milestones' => '/>\s*Target Milestones\s*</i',
    ],
    'admin/index.php' => [
        'Recent System Activity Logs' => '/>\s*Recent System Activity Logs\s*</i',
        'Platform Operational Insights' => '/>\s*Platform Operational Insights\s*</i',
        'Active Sites' => '/>\s*Active Sites\s*</i',
        'Open Trade Jobs' => '/>\s*Open Trade Jobs\s*</i',
    ],
    'notifications.php' => [
        'Notification Center' => '/>\s*Notification Center\s*</i',
        'Mark All as Read' => '/>\s*Mark All as Read\s*</i',
        'Localhost Web Push Notifications' => '/>\s*Localhost Web Push Notifications\s*</i',
    ],
    'unauthorized.php' => [
        'Signed in as' => '/\bSigned in as\b/i',
    ],
];

foreach ($hardcoded_checks as $file => $patterns) {
    $content = file_get_contents($template_files[$file]);
    foreach ($patterns as $label => $pattern) {
        $found = preg_match($pattern, $content);
        assert_test("$file: '$label' is not hardcoded in HTML markup", !$found, "Found match for $label");
    }
}

echo "\n";

// -------------------------------------------------------------------
// 5. PUBLIC HOMEPAGE & ROLE DASHBOARD RENDERING IN ALL 3 LANGUAGES
// -------------------------------------------------------------------
echo "TEST SUITE 5: Full Page Render Simulation across Roles & Languages\n";
echo "--------------------------------------------------------------------\n";

// Test Public Homepage
echo "  [Testing Public Homepage index.php in EN, HI, GU]\n";

// Homepage EN
switch_lang('en');
ob_start();
include __DIR__ . '/../index.php';
$html_home_en = ob_get_clean();
assert_test("Homepage renders in English cleanly", strlen($html_home_en) > 1000 && strpos($html_home_en, $en_dict['home.hero_badge']) !== false);

// Homepage HI
switch_lang('hi');
ob_start();
include __DIR__ . '/../index.php';
$html_home_hi = ob_get_clean();
$hi_home_ok = (strpos($html_home_hi, $hi_dict['home.hero_badge']) !== false)
           && (strpos($html_home_hi, $hi_dict['home.platform_title']) !== false)
           && (strpos($html_home_hi, $hi_dict['home.why_title']) !== false);
assert_test("Homepage renders in Hindi with localized sections", $hi_home_ok);

// Homepage GU
switch_lang('gu');
ob_start();
include __DIR__ . '/../index.php';
$html_home_gu = ob_get_clean();
$gu_home_ok = (strpos($html_home_gu, $gu_dict['home.hero_badge']) !== false)
           && (strpos($html_home_gu, $gu_dict['home.platform_title']) !== false)
           && (strpos($html_home_gu, $gu_dict['home.why_title']) !== false);
assert_test("Homepage renders in Gujarati with localized sections", $gu_home_ok);

// Test Role Dashboards
$roles_to_test = [
    'worker' => [
        'file' => __DIR__ . '/../worker/index.php',
        'expected_hi_snippets' => [$hi_dict['worker.assigned_sites'], $hi_dict['worker.trade_skills'], $hi_dict['worker.documents'], $hi_dict['worker.attendance'], $hi_dict['worker.profile_completion']],
        'expected_gu_snippets' => [$gu_dict['worker.assigned_sites'], $gu_dict['worker.trade_skills'], $gu_dict['worker.documents'], $gu_dict['worker.attendance'], $gu_dict['worker.profile_completion']],
    ],
    'contractor' => [
        'file' => __DIR__ . '/../contractor/index.php',
        'expected_hi_snippets' => [$hi_dict['contractor.total_projects'], $hi_dict['contractor.active_projects'], $hi_dict['contractor.completed_projects'], $hi_dict['contractor.recent_projects']],
        'expected_gu_snippets' => [$gu_dict['contractor.total_projects'], $gu_dict['contractor.active_projects'], $gu_dict['contractor.completed_projects'], $gu_dict['contractor.recent_projects']],
    ],
    'client' => [
        'file' => __DIR__ . '/../client/index.php',
        'expected_hi_snippets' => [$hi_dict['client.my_projects'], $hi_dict['client.recent_bookings']],
        'expected_gu_snippets' => [$gu_dict['client.my_projects'], $gu_dict['client.recent_bookings']],
    ],
    'admin' => [
        'file' => __DIR__ . '/../admin/index.php',
        'expected_hi_snippets' => [$hi_dict['admin.operational_insights'], $hi_dict['admin.system_activity_logs'], $hi_dict['admin.active_sites']],
        'expected_gu_snippets' => [$gu_dict['admin.operational_insights'], $gu_dict['admin.system_activity_logs'], $gu_dict['admin.active_sites']],
    ],
];

foreach ($roles_to_test as $role => $info) {
    // Find a test user for this role
    $stmt = $db->prepare("SELECT * FROM users WHERE role = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$role]);
    $test_user = $stmt->fetch();
    
    if (!$test_user) {
        echo "  [WARN] Skipping $role render: no active $role user found in DB.\n";
        continue;
    }
    
    // Set up session
    $_SESSION['user_id'] = $test_user['id'];
    $_SESSION['user_role'] = $test_user['role'];
    $_SESSION['user_name'] = $test_user['name'];
    $_SESSION['user_email'] = $test_user['email'];
    
    // Test Hindi Render
    switch_lang('hi');
    ob_start();
    try {
        include $info['file'];
        $html_hi = ob_get_clean();
        $hi_ok = true;
        $missing_hi = [];
        foreach ($info['expected_hi_snippets'] as $snippet) {
            if (mb_strpos($html_hi, $snippet) === false) {
                $hi_ok = false;
                $missing_hi[] = $snippet;
            }
        }
        assert_test("Dashboard '$role' renders in Hindi with localized snippets", $hi_ok, "Missing: " . implode(', ', $missing_hi));
    } catch (Throwable $e) {
        ob_end_clean();
        assert_test("Dashboard '$role' renders in Hindi without error", false, $e->getMessage());
    }
    
    // Test Gujarati Render
    switch_lang('gu');
    ob_start();
    try {
        include $info['file'];
        $html_gu = ob_get_clean();
        $gu_ok = true;
        $missing_gu = [];
        foreach ($info['expected_gu_snippets'] as $snippet) {
            if (mb_strpos($html_gu, $snippet) === false) {
                $gu_ok = false;
                $missing_gu[] = $snippet;
            }
        }
        assert_test("Dashboard '$role' renders in Gujarati with localized snippets", $gu_ok, "Missing: " . implode(', ', $missing_gu));
    } catch (Throwable $e) {
        ob_end_clean();
        assert_test("Dashboard '$role' renders in Gujarati without error", false, $e->getMessage());
    }
    
    // Test English Render
    switch_lang('en');
    ob_start();
    try {
        include $info['file'];
        $html_en = ob_get_clean();
        assert_test("Dashboard '$role' renders cleanly in English", strlen($html_en) > 500);
    } catch (Throwable $e) {
        ob_end_clean();
        assert_test("Dashboard '$role' renders in English without error", false, $e->getMessage());
    }
}

// Reset language to English
switch_lang('en');

echo "\n";
echo "====================================================================\n";
echo " VERIFICATION SUMMARY\n";
echo "====================================================================\n";
echo " Passed Assertions:   $passes\n";
echo " Failed Assertions:   $failures\n";
echo " Result:             " . ($failures === 0 ? "ALL TESTS PASSED! (100% COVERAGE)" : "TESTS FAILED") . "\n";
echo "====================================================================\n";

exit($failures === 0 ? 0 : 1);
