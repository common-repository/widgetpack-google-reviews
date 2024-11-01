<?php
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field() {}
}

if (!current_user_can('activate_plugins')) {
    die('The account you\'re logged in to doesn\'t have permission to access this page.');
}

function wpgrev_has_valid_nonce() {
    $nonce_actions = array('wpgrev_reset', 'wpgrev_install', 'wpgrev_settings', 'wpgrev_active');
    $nonce_form_prefix = 'wpgrev-form_nonce_';
    $nonce_action_prefix = 'wpgrev-wpnonce_';
    foreach ($nonce_actions as $key => $value) {
        if (isset($_POST[$nonce_form_prefix.$value])) {
            check_admin_referer($nonce_action_prefix.$value, $nonce_form_prefix.$value);
            return true;
        }
    }
    return false;
}

if (!empty($_POST)) {
    $nonce_result_check = wpgrev_has_valid_nonce();
    if ($nonce_result_check === false) {
        die('Unable to save changes. Make sure you are accessing this page from the Wordpress dashboard.');
    }
}

// Reset
if (isset($_POST['reset'])) {
    foreach (wpgrev_options() as $opt) {
        delete_option($opt);
    }
    unset($_POST);
?>
<div class="wrap">
    <h3><?php echo wpgrev_i('WidgetPack Google Reviews Reset'); ?></h3>
    <form method="POST" action="?page=wpgrev">
        <?php wp_nonce_field('wpgrev-wpnonce_wpgrev_reset', 'wpgrev-form_nonce_wpgrev_reset'); ?>
        <p><?php echo wpgrev_i('WidgetPack Google Reviews has been reset successfully.') ?></p>
        <ul style="list-style: circle;padding-left:20px;">
            <li><?php echo wpgrev_i('Local settings for the plugin were removed.') ?></li>
        </ul>
        <p>
            <?php echo wpgrev_i('If you wish to reinstall, you can do that now.') ?>
            <a href="?page=wpgrev">&nbsp;<?php echo wpgrev_i('Reinstall') ?></a>
        </p>
    </form>
</div>
<?php
die();
}

// Post fields that require verification.
$valid_fields = array(
    'wpac_site_data' => array(
        'key_name' => 'wpac_site_data',
        'regexp' => '/^\d+:[0-9a-zA-Z]+$/'
    ),
    'wpgrev_site_id' => array(
        'key_name' => 'wpgrev_site_id',
        'type' => 'int'
    ),
    'wpgrev_api_key' => array(
        'key_name' => 'wpgrev_api_key',
        'regexp' => '/^[0-9a-zA-Z]{64,64}+$/'
    ),
    'wpgrev_active' => array(
        'key_name' => 'wpgrev_active',
        'values' => array('Disable', 'Enable')
    ));

// Check POST fields and remove bad input.
foreach ($valid_fields as $key) {

    if (isset($_POST[$key['key_name']]) ) {

        // SANITIZE first
        $_POST[$key['key_name']] = trim(sanitize_text_field($_POST[$key['key_name']]));

        // Validate
        if ($key['regexp']) {
            if (!preg_match($key['regexp'], $_POST[$key['key_name']])) {
                unset($_POST[$key['key_name']]);
            }

        } else if ($key['type'] == 'int') {
            if (!intval($_POST[$key['key_name']])) {
                unset($_POST[$key['key_name']]);
            }

        } else {
            $valid = false;
            $vals = $key['values'];
            foreach ($vals as $val) {
                if ($_POST[$key['key_name']] == $val) {
                    $valid = true;
                }
            }
            if (!$valid) {
                unset($_POST[$key['key_name']]);
            }
        }
    }
}

if (isset($_POST['wpgrev_active']) && isset($_GET['wpgrev_active'])) {
    update_option('wpgrev_active', ($_GET['wpgrev_active'] == '1' ? '1' : '0'));
}

if (isset($_POST['wpgrev_install']) && isset($_POST['wpac_site_data'])) {
    list($wpgrev_site_id, $wpgrev_api_key) = explode(':', $_POST['wpac_site_data']);
    update_option('wpgrev_site_id', $wpgrev_site_id);
    update_option('wpgrev_api_key', $wpgrev_api_key);
    update_option('wpgrev_active', '1');
    update_option('wpgrev_version', WPGREV_VERSION);
}

wp_enqueue_script('jquery');
wp_register_script('wpgrev_bootstrap_js', plugins_url('/static/js/bootstrap.min.js', __FILE__));
wp_enqueue_script('wpgrev_bootstrap_js', plugins_url('/static/js/bootstrap.min.js', __FILE__));
wp_register_style('wpgrev_bootstrap_css', plugins_url('/static/css/bootstrap.min.css', __FILE__));
wp_enqueue_style('wpgrev_bootstrap_css', plugins_url('/static/css/bootstrap.min.css', __FILE__));
wp_register_style('wpgrev_admin_css', plugins_url('/static/css/admin.css', __FILE__));
wp_enqueue_style('wpgrev_admin_css', plugins_url('/static/css/admin.css', __FILE__));
wp_register_style('wpgrev_setting_css', plugins_url('/static/css/wpgrev-setting.css', __FILE__));
wp_enqueue_style('wpgrev_setting_css', plugins_url('/static/css/wpgrev-setting.css', __FILE__));
?>

<?php if (wpgrev_is_installed()) {
$wpgrev_site_id = get_option('wpgrev_site_id');
$wpgrev_enabled = get_option('wpgrev_active') == '1';
$wpgrev_enabled_state = $wpgrev_enabled ? 'enabled' : 'disabled';
$wpgrev_google_api_key = get_option('wpgrev_google_api_key');

wp_enqueue_script('wpgrev_widget_js', plugins_url('/static/js/widget.js', __FILE__));
?>

<span class="version"><?php echo wpgrev_i('Free Version: %s', esc_html(WPGREV_VERSION)); ?></span>
<div class="wpgrev-setting container-fluid">
    <img src="<?php echo WPGREV_PLUGIN_URL . '/static/img/google.png'; ?>" alt="Google">
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#about" aria-controls="about" role="tab" data-toggle="tab"><?php echo wpgrev_i('About'); ?></a>
        </li>
        <li role="presentation">
            <a href="#setting" aria-controls="setting" role="tab" data-toggle="tab"><?php echo wpgrev_i('Setting'); ?></a>
        </li>
        <li role="presentation">
            <a href="#shortcode" aria-controls="shortcode" role="tab" data-toggle="tab"><?php echo wpgrev_i('Shortcode Builder'); ?></a>
        </li>
        <li role="presentation">
            <a href="#mod" aria-controls="mod" role="tab" data-toggle="tab"><?php echo wpgrev_i('Review Moderation'); ?></a>
        </li>
    </ul><br>
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="about">
            <div class="row">
                <div class="col-sm-6">
                    <h4><?php echo wpgrev_i('Welcome to Google Reviews for WordPress'); ?></h4>
                    <p><?php echo wpgrev_i('Google Reviews plugin is an easy and fast way to integrate Google place reviews right into your WordPress website. This plugin can work without a Google Places API key and you can instantly add Google reviews right now. Just go to menu <b>"Appearance" -> "Widgets"</b> and add "Google Reviews" widget to sidebar, then find place and save reviews.'); ?></p>
                    <p><?php echo wpgrev_i('Our development team is very responsive and we will be happy to hear from you suggestions to improve the plugin and features. Feel free to ask your question by email <a href="mailto:contact@widgetpack.com">contact@widgetpack.com</a>.'); ?></p>
                    <p><?php echo wpgrev_i('<b>Like this plugin? Give it a like on social:</b>'); ?></p>
                    <div class="row">
                        <div class="col-sm-4">
                            <div id="fb-root"></div>
                            <script>(function(d, s, id) {
                              var js, fjs = d.getElementsByTagName(s)[0];
                              if (d.getElementById(id)) return;
                              js = d.createElement(s); js.id = id;
                              js.src = "//connect.facebook.net/en_EN/sdk.js#xfbml=1&version=v2.6&appId=1501100486852897";
                              fjs.parentNode.insertBefore(js, fjs);
                            }(document, 'script', 'facebook-jssdk'));</script>
                            <div class="fb-like" data-href="https://widgetpack.com/" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                        </div>
                        <div class="col-sm-4 twitter">
                            <a href="https://twitter.com/widgetpack" class="twitter-follow-button" data-show-count="false">Follow @WidgetPack</a>
                            <script>!function (d, s, id) {
                                    var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
                                    if (!d.getElementById(id)) {
                                        js = d.createElement(s);
                                        js.id = id;
                                        js.src = p + '://platform.twitter.com/widgets.js';
                                        fjs.parentNode.insertBefore(js, fjs);
                                    }
                                }(document, 'script', 'twitter-wjs');</script>
                        </div>
                        <div class="col-sm-4 googleplus">
                            <div class="g-plusone" data-size="medium" data-annotation="inline" data-width="200" data-href="https://plus.google.com/101080686931597182099"></div>
                            <script type="text/javascript">
                                window.___gcfg = { lang: 'en-US' };
                                (function () {
                                    var po = document.createElement('script');
                                    po.type = 'text/javascript';
                                    po.async = true;
                                    po.src = 'https://apis.google.com/js/plusone.js';
                                    var s = document.getElementsByTagName('script')[0];
                                    s.parentNode.insertBefore(po, s);
                                })();
                            </script>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="//www.youtube.com/embed/lmaTBmvDPKk?rel=0" allowfullscreen=""></iframe>
                    </div>
                </div>
            </div>
            <hr>
            <!--b>Feel free to try our other widgets powered by <a href="https://widgetpack.com/">Widget Pack</a>.</b-->
            <h4>Get More Features with Google Reviews Pro!</h4>
            <p><a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank" style="color:#00bf54;font-size:16px;text-decoration:underline;">Upgrade to Google Reviews Pro or Business version</a></p>
            <p>* Collect more than 5 Google reviews</p>
            <p>* Pure self-hosted plugin, keep all reviews in  WordPress database</p>
            <p>* Auto-download new Google reviews daily</p>
            <p>* Supports Google Rich Snippets (schema.org)</p>
            <p>* 'Write a review' button to available leave Google review directly on your website</p>
            <p>* Custom business place photo</p>
            <p>* Minimum rating filter</p>
            <p>* Pagination, Sorting</p>
            <p>* Priority support</p>
        </div>
        <div role="tabpanel" class="tab-pane" id="setting">
            <!-- Enable/disable WidgetPack Google Reviews toggle -->
            <form method="POST" action="?page=wpgrev&amp;wpgrev_active=<?php echo (string)((int)($wpgrev_enabled != true)); ?>#wpgrev-plugin">
                <?php wp_nonce_field('wpgrev-wpnonce_wpgrev_active', 'wpgrev-form_nonce_wpgrev_active'); ?>
                <span class="status">
                    <?php echo wpgrev_i('WidgetPack Google Reviews are currently '); ?>
                    <span class="wpgrev-<?php echo esc_attr($wpgrev_enabled_state); ?>-text"><b><?php echo $wpgrev_enabled_state; ?></b></span>
                </span>
                <input type="submit" name="wpgrev_active" class="button" value="<?php echo $wpgrev_enabled ? wpgrev_i('Disable') : wpgrev_i('Enable'); ?>" />
            </form><br>
            <form action="?page=wpgrev" method="POST">
                <?php wp_nonce_field('wpgrev-wpnonce_wpgrev_reset', 'wpgrev-form_nonce_wpgrev_reset'); ?>
                <p>
                    <input type="submit" value="Reset" name="reset" onclick="return confirm('<?php echo wpgrev_i('Are you sure you want to reset the WidgetPack Google Reviews plugin?'); ?>')" class="button" />
                    <?php echo wpgrev_i('This removes all WidgetPack-specific settings.') ?>
                </p>
            </form>
        </div>
        <div role="tabpanel" class="tab-pane" id="mod">
            <h4><?php echo wpgrev_i('Moderation available in Google Reviews Pro version:'); ?></h4>
            <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank" style="color:#00bf54;font-size:16px;text-decoration:underline;"><?php echo wpgrev_i('Upgrade to Pro'); ?></a>
        </div>
        <div role="tabpanel" class="tab-pane" id="shortcode">
            <h4><?php echo wpgrev_i('Shortcode Builder available in Google Reviews Pro version:'); ?></h4>
            <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank" style="color:#00bf54;font-size:16px;text-decoration:underline;"><?php echo wpgrev_i('Upgrade to Pro'); ?></a>
        </div>
    </div>
</div>

<?php } else { ?>

<form method="POST" action="#wpgrev-plugin">
    <?php wp_nonce_field('wpgrev-wpnonce_wpgrev_install', 'wpgrev-form_nonce_wpgrev_install'); ?>
    <input type="hidden" name="wpgrev_install"/>
    <input type="hidden" id="wpac_site_data" name="wpac_site_data"/>
    <div id="wpac-setup"></div>
</form>
<script type="text/javascript">
    wpac_init = window.wpac_init || [];
    wpac_init.push({widget: 'Sign', tab: 'signup', el: 'wpac-setup', cb: function() {
        WPac.init({widget: 'SiteChosen', el: 'wpac-setup', cb: function(site) {
            console.log(site.id);
            console.log(site.api_key);
            var wpac_site_data = document.getElementById('wpac_site_data');
            wpac_site_data.value = site.id + ':' + site.api_key;
            wpac_site_data.parentNode.submit();
        }});
    }});
    (function() {
        var mc = document.createElement('script');
        mc.type = 'text/javascript';
        mc.async = true;
        mc.src = 'https://<?php echo WPGREV_EMBED_HOST; ?>/widget.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
    })();
</script>

<?php } ?>