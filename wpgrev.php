<?php
/*
Plugin Name: WidgetPack Google Reviews
Plugin URI: https://richplugins.com/google-reviews-pro-wordpress-plugin
Description: The WidgetPack Google Reviews display Google places rating and reviews on your website to increase user confidence and search engine optimization.
Author: WidgetPack <contact@widgetpack.com>
Version: 1.93
Author URI: https://richplugins.com/google-reviews-pro-wordpress-plugin
*/

require(ABSPATH . 'wp-includes/version.php');

define('WPGREV_VERSION',      '1.93');
define('WPGREV_EMBED_HOST',   'embed.widgetpack.com');
define('WPGREV_PLUGIN_URL',   plugins_url(basename(plugin_dir_path(__FILE__ )), basename(__FILE__)));

function wpgrev_options() {
    return array(
        'wpgrev_site_id',
        'wpgrev_api_key',
        'wpgrev_version',
        'wpgrev_active',
        'wpgrev_google_api_key',
    );
}

/*-------------------------------- Widget --------------------------------*/
function wpgrev_init_widget() {
    if (!class_exists('WPac_Google_Reviews' ) ) {
        require 'wpgrev-widget.php';
    }
}

add_action('widgets_init', 'wpgrev_init_widget');
add_action('widgets_init', create_function('', 'register_widget("WPac_Google_Reviews");'));

/*-------------------------------- Menu --------------------------------*/
function wpgrev_setting_menu() {
     add_submenu_page(
         'options-general.php',
         'WidgetPack Google Reviews',
         'Google Reviews',
         'moderate_comments',
         'wpgrev',
         'wpgrev_setting'
     );
}
add_action('admin_menu', 'wpgrev_setting_menu', 10);

function wpgrev_setting() {
    include_once(dirname(__FILE__) . '/wpgrev-setting.php');
}

/*-------------------------------- Links --------------------------------*/
function wpgrev_plugin_action_links($links, $file) {
    $plugin_file = basename(__FILE__);
    if (basename($file) == $plugin_file) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=wpgrev') . '#wpgrev-plugin">'.wpgrev_i('Settings').'</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'wpgrev_plugin_action_links', 10, 2);

/*-------------------------------- Shortcode --------------------------------*/
function wpgrev_shortcode($atts) {
    if (!wpgrev_enabled()) return '';

    $wpgrev_site_id = get_option('wpgrev_site_id');
    if (!$wpgrev_site_id) {
        return wpgrev_i('<b>Google Reviews:</b> plugin is not activated, first active plugin into <b>"Setting"</b> -> <b>"Google Reviews"</b>');
    }

    $a = shortcode_atts(array(
        'max_width' => 'auto',
        'el' => 'wpac-google-review',
        'place_id' => '',
        'place_photo' => '',
        'text_size' => 250,
        'dark_theme' => 'false',
        'view_mode' => 'list',
        'star_head_size' => '16',
        'star_head_color' => 'e7711b',
        'star_size' => '14',
        'star_color' => 'e7711b',
        'lang' => '',
    ), $atts);

    if (!$a['place_id']) {
        return wpgrev_i('<b>Google Reviews:</b> the required attribute place_id is not setted');
    }

    return "<div id=\"wpac-google-review\" style=\"max-width:". $a['max_width'] ."\"></div>
           <script type=\"text/javascript\">
             wpac_init = window.wpac_init || [];
             wpac_init.push({
               widget: 'GoogleReview',
               el: '". $a['el'] ."',
               id: ". $wpgrev_site_id .",
               place_id: '". $a['place_id'] ."',
               place_photo: '". $a['place_photo'] ."',
               text_size: ". $a['text_size'] .",
               dark: ". $a['dark_theme'] .",
               star_head_size: ". $a['star_head_size'] .",
               star_head_color: '". $a['star_head_color'] ."',
               star_size: ". $a['star_size'] .",
               star_color: '". $a['star_color'] ."',
               view_mode: 'list',
               lang: '". $a['lang'] ."'
             });
             (function() {
               if ('WIDGETPACK_LOADED' in window) return;
               WIDGETPACK_LOADED = true;
               var mc = document.createElement('script');
               mc.type = 'text/javascript';
               mc.async = true;
               mc.src = 'https://". WPGREV_EMBED_HOST ."/widget.js';
               var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
             })();
           </script>";
}
add_shortcode("google-reviews", "wpgrev_shortcode");

/*-------------------------------- Request --------------------------------*/
function wpgrev_request_handler() {
    global $wpgrev_api;

    if (!empty($_GET['cf_action'])) {
        switch ($_GET['cf_action']) {
            case 'wpgrev_signature':
                if (current_user_can('manage_options')) {
                    if (isset($_GET['_wpgrevsign_wpnonce']) === false) {
                        $error = wpgrev_i('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.');
                        $response = compact('error');
                    } else {
                        check_admin_referer('wpgrev-wpnonce_wpgrev_signature', '_wpgrevsign_wpnonce');
                        $api_key = get_option('wpgrev_api_key');
                        $signature = md5($_GET['data'].$api_key);
                        $response = compact('signature');
                    }
                    header('Content-type: text/javascript');
                    echo cf_json_encode($response);
                    die();
                }
            break;
            case 'wpgrev_google_api_key':
                if (current_user_can('manage_options')) {
                    if (isset($_POST['_wpgrevsign_wpnonce']) === false) {
                        $error = wpgrev_i('Unable to call request. Make sure you are accessing this page from the Wordpress dashboard.');
                        $response = compact('error');
                    } else {
                        check_admin_referer('wpgrev-wpnonce_wpgrev_signature', '_wpgrevsign_wpnonce');
                        update_option('wpgrev_google_api_key', $_POST['key']);
                        $response = '{"status":"success"}';
                    }
                    header('Content-type: text/javascript');
                    echo cf_json_encode($response);
                    die();
                }
            break;
        }
    }
}
add_action('init', 'wpgrev_request_handler');

/*-------------------------------- Helpers --------------------------------*/
function wpgrev_is_installed() {
    $wpgrev_site_id = get_option('wpgrev_site_id');
    $wpgrev_api_key = get_option('wpgrev_api_key');
    if (is_numeric($wpgrev_site_id) > 0 && strlen($wpgrev_api_key) > 0) {
        return true;
    } else {
        return false;
    }
}

function wpgrev_enabled() {
    global $id, $post;

    if (get_option('wpgrev_active') === '0'){ return false; }
    if (!get_option('wpgrev_site_id'))      { return false; }

    return true;
}

function wpgrev_i($text, $params=null) {
    if (!is_array($params)) {
        $params = func_get_args();
        $params = array_slice($params, 1);
    }
    return vsprintf(__($text, 'wpgrev'), $params);
}

if (!function_exists('esc_html')) {
function esc_html( $text ) {
    $safe_text = wp_check_invalid_utf8( $text );
    $safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
    return apply_filters( 'esc_html', $safe_text, $text );
}
}

if (!function_exists('esc_attr')) {
function esc_attr( $text ) {
    $safe_text = wp_check_invalid_utf8( $text );
    $safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
    return apply_filters( 'attribute_escape', $safe_text, $text );
}
}

/**
 * JSON ENCODE for PHP < 5.2.0
 * Checks if json_encode is not available and defines json_encode
 * to use php_json_encode in its stead
 * Works on iteratable objects as well - stdClass is iteratable, so all WP objects are gonna be iteratable
 */
if(!function_exists('cf_json_encode')) {
    function cf_json_encode($data) {

        // json_encode is sending an application/x-javascript header on Joyent servers
        // for some unknown reason.
        return cfjson_encode($data);
    }

    function cfjson_encode_string($str) {
        if(is_bool($str)) {
            return $str ? 'true' : 'false';
        }

        return str_replace(
            array(
                '\\'
                , '"'
                //, '/'
                , "\n"
                , "\r"
            )
            , array(
                '\\\\'
                , '\"'
                //, '\/'
                , '\n'
                , '\r'
            )
            , $str
        );
    }

    function cfjson_encode($arr) {
        $json_str = '';
        if (is_array($arr)) {
            $pure_array = true;
            $array_length = count($arr);
            for ( $i = 0; $i < $array_length ; $i++) {
                if (!isset($arr[$i])) {
                    $pure_array = false;
                    break;
                }
            }
            if ($pure_array) {
                $json_str = '[';
                $temp = array();
                for ($i=0; $i < $array_length; $i++) {
                    $temp[] = sprintf("%s", cfjson_encode($arr[$i]));
                }
                $json_str .= implode(',', $temp);
                $json_str .="]";
            }
            else {
                $json_str = '{';
                $temp = array();
                foreach ($arr as $key => $value) {
                    $temp[] = sprintf("\"%s\":%s", $key, cfjson_encode($value));
                }
                $json_str .= implode(',', $temp);
                $json_str .= '}';
            }
        }
        else if (is_object($arr)) {
            $json_str = '{';
            $temp = array();
            foreach ($arr as $k => $v) {
                $temp[] = '"'.$k.'":'.cfjson_encode($v);
            }
            $json_str .= implode(',', $temp);
            $json_str .= '}';
        }
        else if (is_string($arr)) {
            $json_str = '"'. cfjson_encode_string($arr) . '"';
        }
        else if (is_numeric($arr)) {
            $json_str = $arr;
        }
        else if (is_bool($arr)) {
            $json_str = $arr ? 'true' : 'false';
        }
        else {
            $json_str = '"'. cfjson_encode_string($arr) . '"';
        }
        return $json_str;
    }
}
?>