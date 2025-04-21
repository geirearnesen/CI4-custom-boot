<?php

defined('VERSION_LABEL')      || define('VERSION_LABEL', '5.0');
defined('REV')      || define('REV', 1.0);
defined('UPLOADS') || define('UPLOADS', 'public');


/*
 | --------------------------------------------------------------------
 | Theme Configuration variable
 | --------------------------------------------------------------------
 |
 | This defines the variable of theme configuration of template
 | you can read the the document to understand different value of
 | this variables, and change according as you need.
 |
 | --------------------------------------------------------------------
 */
$theme_contrast = 'false';  // [ false, true ]
$caption_show = 'true';     // [ false, true ]
$preset_theme = 'preset-1'; // [ preset-1 to preset-10 ]
$dark_layout = 'false';     // [ false, true, default ]
$rtl_layout = 'false';      // [ false, true ]
$box_container = 'false';   // [ false, true ]
$version = 'v2.0';
// --------------------------------------------------------------------


$rtltemp = '';
$contrasttemp = '';
$darklayouttemp = '';

if ( $rtl_layout == 'true' ) {
    $rtltemp = 'rtl';
} else {
    $rtltemp = 'ltr';
}

if ( $theme_contrast == 'true' ) {
    $contrasttemp = 'true';
} else {
    $contrasttemp = '';
}

if ( $dark_layout == 'true' ) {
    $darklayouttemp = 'dark';
} else {
    $darklayouttemp = 'light';
}
$bodySetup = 'data-pc-preset="' . $preset_theme . '" data-pc-sidebar-caption="' . $caption_show . '" data-pc-direction="' . $rtltemp . '" data-pc-theme_contrast="'. $contrasttemp .'" data-pc-theme="' . $darklayouttemp . '"';

defined('BODY_SETUP') || define('BODY_SETUP', $bodySetup);
defined('PRESET_THEME') || define('PRESET_THEME', $preset_theme);
defined('RTL_LAYOUT') || define('RTL_LAYOUT', $rtl_layout);
defined('CAPTION_SHOW') || define('CAPTION_SHOW', $caption_show);
defined('BOX_CONTAINER') || define('BOX_CONTAINER', $box_container);
defined('THEME_CONTRAST') || define('THEME_CONTRAST', $theme_contrast);
defined('DARK_LAYOUT') || define('DARK_LAYOUT', $dark_layout);

defined('VERSION') || define('VERSION', $version);
defined('UNDER_MAINTENANCE') || define('UNDER_MAINTENANCE', false);
defined('CACHE_TTL') || define('CACHE_TTL', [
    'point_system' => 3600,
    'closed_rounds' => 60,
    'jury_result' => 60,
    'summary_result_list' => 300,
    'summary_result_details' => 300,
    'jury_status_types' => 3600,
    'images_list' => 60,
    'result_detail_list' => 60,
    ]);
defined('ENABLE_CACHE') || define('ENABLE_CACHE', 1);
defined('APP_LANGUAGE') || define('APP_LANGUAGE', 'no');


