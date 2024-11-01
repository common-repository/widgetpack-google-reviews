<?php

/**
 * WidgetPack Google Reviews
 *
 * @description: The Google Reviews
 * @since      : 1.0
 */
class WPac_Google_Reviews extends WP_Widget {

    public $options;
    public $api_key;

    public $widget_fields = array(
        'title'                => '',
        'place_name'           => '',
        'place_id'             => '',
        'place_photo'          => '',
        'auto_load'            => '',
        'rating_snippet'       => '',
        'pagination'           => '',
        'sort'                 => '',
        'min_filter'           => '',
        'text_size'            => '',
        'dark_theme'           => '',
        'view_mode'            => '',
        'leave_review_link'    => '',
        'open_link'            => '',
        'nofollow_link'        => '',
        'lang'                 => '',
    );

    public function __construct() {
        parent::__construct(
            'wpgrev_widget', // Base ID
            'Google Reviews', // Name
            array(
                'classname'   => 'wpac-google-reviews',
                'description' => wpgrev_i('Display Google Places Reviews on your website.', 'wpgrev')
            )
        );

        add_action('admin_enqueue_scripts', array($this, 'wpgrev_wpac_widget_scripts'));
    }

    function wpgrev_wpac_widget_scripts($hook) {
        if ($hook == 'widgets.php' || ($hook == 'customize.php' && defined('SITEORIGIN_PANELS_VERSION'))) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('wpgrev_widget_js', plugins_url('/static/js/widget.js', __FILE__));
            wp_register_style('wpgrev_install_css', plugins_url('/static/css/wpgrev-install.css', __FILE__));
            wp_enqueue_style('wpgrev_install_css', plugins_url('/static/css/wpgrev-install.css', __FILE__));
        }
    }

    function widget($args, $instance) {
        if (wpgrev_enabled()) {
            extract($args);
            foreach ( $instance as $variable => $value ) {
                ${$variable} = !isset($instance[$variable]) ? $this->widget_fields[$variable] : esc_attr($instance[$variable]);
            }
            $wpgrev_site_id = get_option('wpgrev_site_id');
            echo $before_widget;
            if ($place_id) {
                if ($title) { ?><h2 class="wpgrev-widget-title widget-title"><?php echo $title; ?></h2><?php } ?>
                <div id="<?php echo $this->id; ?>-wpac-google-review"></div>
                <script type="text/javascript">
                wpac_init = window.wpac_init || [];
                wpac_init.push({
                    widget: 'GoogleReview',
                    el: '<?php echo $this->id; ?>-wpac-google-review',
                    id: <?php echo $wpgrev_site_id; ?>,
                    place_id: '<?php echo $place_id; ?>',
                    view_mode: '<?php echo $view_mode; ?>',
                    <?php if ($place_photo) { ?>place_photo: '<?php echo $place_photo; ?>',<?php } ?>
                    <?php if ($text_size) { ?>text_size: '<?php echo $text_size; ?>',<?php } ?>
                    <?php if ($dark_theme) { ?>dark: true,<?php } ?>
                    <?php if ($lang) { ?>lang: '<?php echo $lang; ?>',<?php } ?>
                });
                (function() {
                    if ('WIDGETPACK_LOADED' in window) return;
                    WIDGETPACK_LOADED = true;
                    var mc = document.createElement('script');
                    mc.type = 'text/javascript';
                    mc.async = true;
                    mc.src = 'https://<?php echo WPGREV_EMBED_HOST; ?>/widget.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(mc, s.nextSibling);
                })();
                <?php if ($view_mode == 'badge') { ?>
                var el = document.getElementById('<?php echo $this->id; ?>');
                if (el) {
                    el.style.display = 'none';
                }
                <?php } ?>
                </script>
            <?php
            } else {
            ?>
                <div class="wpgrev-error" style="padding:10px;color:#B94A48;background-color:#F2DEDE;border-color:#EED3D7;">
                    <?php echo wpgrev_i('Please check that this widget <b>Google Reviews</b> has a Google Place ID set.'); ?>
                </div>
            <?php
            }
            echo $after_widget;
        }
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        foreach ($this->widget_fields as $field => $value) {
            $instance[$field] = strip_tags(stripslashes($new_instance[$field]));
        }
        return $instance;
    }

    //TODO: star - color && size
    function form($instance) {
        global $wp_version;
        foreach ($this->widget_fields as $field => $value) {
            ${$field} = !isset($instance[$field]) ? $value : esc_attr($instance[$field]);
        }
        $wpgrev_site_id = get_option('wpgrev_site_id');
        $wpgrev_google_api_key = get_option('wpgrev_google_api_key');
        if ($wpgrev_site_id) { ?>
            <div class="form-group">
                <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" placeholder="<?php echo wpgrev_i('Widget title'); ?>" />
            </div>

            <?php wp_nonce_field('wpgrev-wpnonce_wpgrev_signature', 'wpgrev-form_nonce_wpgrev_signature'); ?>

            <div id="<?php echo $this->id; ?>"></div>
            <script type="text/javascript">
            function wpgrev_sidebar_widget(widgetData) {

                var widgetId = widgetData.widgetId,
                    placeId = widgetData.placeId,
                    placeName = widgetData.placeName,
                    placePhoto = widgetData.placePhoto,
                    placePhotoImg = widgetData.placePhotoImg,
                    placePhotoBtn = widgetData.placePhotoBtn;

                function wpgrev_param2data(params) {
                    var data = '';
                    for (p in params) {
                        if (Object.prototype.hasOwnProperty.call(params, p) && params[p]) {
                            data += p + '=' + params[p];
                        }
                    }
                    return data;
                }

                function wpgrev_set_fields(place) {
                    var place_id_el = document.getElementById(placeId);
                    var place_name_el = document.getElementById(placeName);
                    var place_photo_el = document.getElementById(placePhoto);
                    var place_photo_img = document.getElementById(placePhotoImg);
                    place_id_el.value = place.place_id;
                    place_name_el.value = place.name;
                    if (place_photo_el.type == 'hidden') {
                        place_photo_el.value = place.icon;
                    }
                    place_photo_img.src = place.icon;
                    place_photo_img.style.display = 'inline-block';
                }

                function wpgrev_show_tooltip() {
                    var el = document.getElementById(widgetId);
                    var insideEl = WPac.Fastjs.parents(el, 'widget-inside');
                    if (insideEl) {
                        var controlEl = insideEl.querySelector('.widget-control-actions');
                        if (controlEl) {
                            var tooltip = WPac.Fastjs.create('div', 'wpgrev-tooltip');
                            tooltip.innerHTML = '<div class="wpgrev-corn1"></div>' +
                                                '<div class="wpgrev-corn2"></div>' +
                                                '<div class="wpgrev-text">Well, now you can <b>Save</b> the widget.</div>';
                            controlEl.appendChild(tooltip);
                            setTimeout(function() {
                                WPac.Fastjs.addcl(tooltip, 'wpgrev-tooltip-visible');
                            }, 100);
                        }
                    }
                }

                function wpgrev_add(params, place) {
                    var req = new XMLHttpRequest(),
                        host = 'https://api.widgetpack.com',
                        url = host + '/1.0/google-review/add?' + jQuery.param(params);

                    req.open('POST', url, true);
                    req.setRequestHeader('Content-Type', 'application/json');
                    req.onreadystatechange = function(res) {
                        if (req.readyState === 4) {
                            if (req.status === 200) {
                                var result = JSON.parse(req.responseText),
                                    gkey_cnt = document.querySelector('#' + widgetId + ' .wp-gkey').parentNode.parentNode;
                                if (result.error == 'api_limit') {
                                    WPac.Fastjs.show2(gkey_cnt);
                                    WPac.Fastjs.addcl(gkey_cnt, 'has-error');
                                    return;
                                } else {
                                    WPac.Fastjs.remcl(gkey_cnt, 'has-error');
                                }
                                wpgrev_set_fields(place);
                                wpgrev_show_tooltip();
                            }
                        }
                    };
                    req.send();
                }

                function wpgrev_signature(params, cb) {
                    jQuery.get('<?php echo admin_url('options-general.php?page=wpgrev'); ?>', {
                        cf_action: 'wpgrev_signature',
                        data: wpgrev_param2data(params),
                        _wpgrevsign_wpnonce: jQuery('#wpgrev-form_nonce_wpgrev_signature').val()
                    }, function(response) {
                        if (response.error) {
                            return console.log('Error:', response.error);
                        }
                        cb(response.signature);
                    }, 'json');
                }

                function wpgrev_google_key_save_listener(params, cb) {
                    var gkey = document.querySelector('#' + widgetId + ' .wp-gkey');
                    if (gkey) {
                        WPac.Fastjs.on(gkey, 'change', function() {
                            jQuery.post('<?php echo admin_url('options-general.php?page=wpgrev&cf_action=wpgrev_google_api_key'); ?>', {
                                key: this.value,
                                _wpgrevsign_wpnonce: jQuery('#wpgrev-form_nonce_wpgrev_signature').val()
                            });
                        });
                    }
                }

                <?php if (!$place_id) { ?>
                wpac_init = window.wpac_init || [];
                WPac.init({
                    widget: 'GreviewInstall',
                    id: <?php echo $wpgrev_site_id; ?>,
                    el: widgetId,
                    inline: true,
                    google_api_key: '<?php echo $wpgrev_google_api_key; ?>',
                    rating_filter_off: true,
                    callback: {
                        add: [function(arg) {
                            var params = arg.params, place = arg.place;
                            wpgrev_signature(params, function(signature) {
                                params.signature = signature;
                                wpgrev_add(params, place);
                            });
                        }],
                        ready: [function(arg) {
                            var placeInput = document.querySelector('#' + widgetId + ' .wp-place');
                            if (placeInput) {
                                placeInput.focus();
                            }
                            wpgrev_google_key_save_listener();
                        }]
                    }
                });
                <?php } else { ?>
                wpac_init = window.wpac_init || [];
                WPac.init({
                    widget: 'GoogleReview',
                    id: <?php echo $wpgrev_site_id; ?>,
                    el: widgetId,
                    place_id: '<?php echo $place_id; ?>',
                    <?php if ($place_photo) { ?>place_photo: '<?php echo $place_photo; ?>',<?php } ?>
                    view_mode: 'list',
                    text_size: 50,
                    callback: {
                        del: [function(arg) {
                            var params = arg.params, review_cnt = arg.review_cnt;
                            wpgrev_signature(params, function(signature) {
                                params.signature = signature;
                                var req = new XMLHttpRequest(),
                                    host = 'https://api.widgetpack.com',
                                    url = host + '/1.0/google-review/del?' + jQuery.param(params);

                                req.open('POST', url, true);
                                req.setRequestHeader('Content-Type', 'application/json');
                                req.onreadystatechange = function(res) {
                                    if (req.readyState === 4) {
                                        if (req.status === 200) {
                                            WPac.Fastjs.rm(review_cnt);
                                        }
                                    }
                                };
                                req.send();
                            });
                        }]
                    }
                });
                var tooltips = document.querySelectorAll('.wpgrev-tooltip');
                if (tooltips && tooltips.length > 0) {
                    for (var i = 0; i < tooltips.length; i++) {
                        tooltips[i].parentNode.removeChild(tooltips[i]);
                    }
                }
                <?php } ?>

                jQuery(document).ready(function($) {
                    var file_frame;
                    $('#' + placePhotoBtn).on('click', function(e) {
                        e.preventDefault();
                        if (file_frame) {
                            file_frame.open();
                            return;
                        }

                        file_frame = wp.media.frames.file_frame = wp.media({
                            title: $(this).data('uploader_title'),
                            button: {text: $(this).data('uploader_button_text')},
                            multiple: false
                        });

                        file_frame.on('select', function() {
                            var place_photo_hidden = $('#' + placePhoto),
                                place_photo_img = $('#' + placePhotoImg);
                            attachment = file_frame.state().get('selection').first().toJSON();
                            place_photo_hidden.val(attachment.url);
                            place_photo_img.attr('src', attachment.url);
                            place_photo_img.show();
                        });
                        file_frame.open();
                        return false;
                    });

                    var $widgetContent = $('#' + widgetId).parent();
                    $('.wpgrev-options-toggle', $widgetContent).click(function () {
                        $(this).toggleClass('toggled');
                        $(this).next().slideToggle();
                    });
                });
            }
            </script>
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
              data-widget-id="<?php echo $this->id; ?>"
              data-place-id="<?php echo $this->get_field_id("place_id"); ?>"
              data-place-name="<?php echo $this->get_field_id("place_name"); ?>"
              data-place-photo="<?php echo $this->get_field_id("place_photo"); ?>"
              data-place-photo-img="<?php echo $this->get_field_id("place_photo_img"); ?>"
              data-place-photo-btn="<?php echo $this->get_field_id("place_photo_btn"); ?>"
              onload="wpgrev_sidebar_widget({
                  widgetId: this.getAttribute('data-widget-id'),
                  placeId: this.getAttribute('data-place-id'),
                  placeName: this.getAttribute('data-place-name'),
                  placePhoto: this.getAttribute('data-place-photo'),
                  placePhotoImg: this.getAttribute('data-place-photo-img'),
                  placePhotoBtn: this.getAttribute('data-place-photo-btn')
              })">

            <div class="form-group">
                <input type="text" id="<?php echo $this->get_field_id('place_name'); ?>" name="<?php echo $this->get_field_name('place_name'); ?>" value="<?php echo $place_name; ?>" placeholder="<?php echo wpgrev_i('Google Place Name'); ?>" readonly />
            </div>

            <div class="form-group">
                <input type="text" id="<?php echo $this->get_field_id('place_id'); ?>" name="<?php echo $this->get_field_name('place_id'); ?>" value="<?php echo $place_id; ?>" placeholder="<?php echo wpgrev_i('Google Place ID'); ?>" readonly />
            </div>

            <h4 class="wpgrev-options-toggle"><?php echo wpgrev_i('Review Options'); ?></h4>
            <div class="wpgrev-options" style="display:none">
                <div class="form-group wpgrev-disabled">
                    <input type="checkbox" disabled />
                    <label ><?php echo wpgrev_i('Save Google reviews to my WordPress database'); ?></label>
                </div>
                <div class="form-group wpgrev-disabled">
                    <input id="<?php echo $this->get_field_id('auto_load'); ?>" name="<?php echo $this->get_field_name('auto_load'); ?>" type="checkbox" value="true" <?php checked('true', $auto_load); ?> disabled />
                    <label for="<?php echo $this->get_field_id('auto_load'); ?>"><?php echo wpgrev_i('Auto-download new reviews from Google'); ?></label>
                </div>
                <div class="form-group wpgrev-disabled">
                    <input id="<?php echo $this->get_field_id('rating_snippet'); ?>" name="<?php echo $this->get_field_name('rating_snippet'); ?>" type="checkbox" value="true" <?php checked('true', $rating_snippet); ?> disabled />
                    <label for="<?php echo $this->get_field_id('rating_snippet'); ?>"><?php echo wpgrev_i('Enable Google Rich Snippets (schema.org)'); ?></label>
                </div>
                <div class="form-group">
                    <?php echo wpgrev_i('Pagination'); ?>
                    <select id="<?php echo $this->get_field_id('pagination'); ?>" name="<?php echo $this->get_field_name('pagination'); ?>" disabled>
                        <option value="" <?php selected('', $pagination); ?>><?php echo wpgrev_i('Show all reviews'); ?></option>
                        <option value="10" <?php selected('10', $pagination); ?>><?php echo wpgrev_i('10'); ?></option>
                        <option value="5" <?php selected('5', $pagination); ?>><?php echo wpgrev_i('5'); ?></option>
                        <option value="4" <?php selected('4', $pagination); ?>><?php echo wpgrev_i('4'); ?></option>
                        <option value="3" <?php selected('3', $pagination); ?>><?php echo wpgrev_i('3'); ?></option>
                        <option value="2" <?php selected('2', $pagination); ?>><?php echo wpgrev_i('2'); ?></option>
                        <option value="1" <?php selected('1', $pagination); ?>><?php echo wpgrev_i('1'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <?php echo wpgrev_i('Sorting'); ?>
                    <select id="<?php echo $this->get_field_id('sort'); ?>" name="<?php echo $this->get_field_name('sort'); ?>" disabled>
                        <option value="" <?php selected('', $sort); ?>><?php echo wpgrev_i('Default'); ?></option>
                        <option value="1" <?php selected('1', $sort); ?>><?php echo wpgrev_i('Most recent'); ?></option>
                        <option value="2" <?php selected('2', $sort); ?>><?php echo wpgrev_i('Most oldest'); ?></option>
                        <option value="3" <?php selected('3', $sort); ?>><?php echo wpgrev_i('Highest score'); ?></option>
                        <option value="4" <?php selected('4', $sort); ?>><?php echo wpgrev_i('Lowest score'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <?php echo wpgrev_i('Minimum Review Rating'); ?>
                    <select id="<?php echo $this->get_field_id('min_filter'); ?>" name="<?php echo $this->get_field_name('min_filter'); ?>" disabled>
                        <option value="" <?php selected('', $min_filter); ?>><?php echo wpgrev_i('No filter'); ?></option>
                        <option value="5" <?php selected('5', $min_filter); ?>><?php echo wpgrev_i('5 Stars'); ?></option>
                        <option value="4" <?php selected('4', $min_filter); ?>><?php echo wpgrev_i('4 Stars'); ?></option>
                        <option value="3" <?php selected('3', $min_filter); ?>><?php echo wpgrev_i('3 Stars'); ?></option>
                        <option value="2" <?php selected('2', $min_filter); ?>><?php echo wpgrev_i('2 Stars'); ?></option>
                        <option value="1" <?php selected('1', $min_filter); ?>><?php echo wpgrev_i('1 Star'); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <div class="wpgrev-pro"><?php echo wpgrev_i('These features available in Google Reviews Pro plugin: '); ?> <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank"><?php echo wpgrev_i('Upgrade to Pro'); ?></a></div>
                </div>
            </div>

            <h4 class="wpgrev-options-toggle"><?php echo wpgrev_i('Display Options'); ?></h4>
            <div class="wpgrev-options" style="display:none">
                <div class="form-group wpgrev-disabled">
                    <label><?php echo wpgrev_i('Change place photo'); ?></label>
                </div>
                <div class="form-group">
                    <div class="wpgrev-pro"><?php echo wpgrev_i('Custom photo available in Pro version: '); ?> <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank"><?php echo wpgrev_i('Upgrade to Pro'); ?></a></div>
                </div>
                <div class="form-group">
                    <input type="text" id="<?php echo $this->get_field_id('text_size'); ?>" name="<?php echo $this->get_field_name('text_size'); ?>" value="<?php echo $text_size; ?>" placeholder="<?php echo wpgrev_i('Review char limit before \'read more\' link: 200'); ?>" />
                </div>
                <div class="form-group">
                    <input id="<?php echo $this->get_field_id('dark_theme'); ?>" name="<?php echo $this->get_field_name('dark_theme'); ?>" type="checkbox" value="1" <?php checked('1', $dark_theme); ?> />
                    <label for="<?php echo $this->get_field_id('dark_theme'); ?>"><?php echo wpgrev_i('Dark theme'); ?></label>
                </div>
                <div class="form-group">
                    <?php echo wpgrev_i('Widget theme'); ?>
                    <select id="<?php echo $this->get_field_id('view_mode'); ?>" name="<?php echo $this->get_field_name('view_mode'); ?>">
                        <option value="list" <?php selected('list', $view_mode); ?>><?php echo wpgrev_i('Review list'); ?></option>
                        <option value="badge" <?php selected('badge', $view_mode); ?>><?php echo wpgrev_i('Google badge'); ?></option>
                        <option value="badge_inner" <?php selected('badge_inner', $view_mode); ?>><?php echo wpgrev_i('Inner badge'); ?></option>
                    </select>
                </div>
            </div>

            <h4 class="wpgrev-options-toggle"><?php echo wpgrev_i('Advance Options'); ?></h4>
            <div class="wpgrev-options" style="display:none">
                <div class="form-group wpgrev-disabled">
                    <input type="text" id="<?php echo $this->get_field_id('leave_review_link'); ?>" name="<?php echo $this->get_field_name('leave_review_link'); ?>" value="<?php echo $leave_review_link; ?>" placeholder="<?php echo wpgrev_i('Leave Google review link'); ?>" disabled />
                    <small><?php echo wpgrev_i('Show "Leave Review" link and open popup where user can leave your Google review'); ?></small>
                </div>
                <div class="form-group wpgrev-disabled">
                    <input type="checkbox" disabled />
                    <label><?php echo wpgrev_i('Disable G+ profile links'); ?></label>
                </div>
                <div class="form-group wpgrev-disabled">
                    <input id="<?php echo $this->get_field_id('open_link'); ?>" name="<?php echo $this->get_field_name('open_link'); ?>" type="checkbox" value="1" <?php checked('1', $open_link); ?> disabled />
                    <label for="<?php echo $this->get_field_id('open_link'); ?>"><?php echo wpgrev_i('Open links in new Window'); ?></label>
                </div>
                <div class="form-group wpgrev-disabled">
                    <input id="<?php echo $this->get_field_id('nofollow_link'); ?>" name="<?php echo $this->get_field_name('nofollow_link'); ?>" type="checkbox" value="1" <?php checked('1', $nofollow_link); ?> disabled />
                    <label for="<?php echo $this->get_field_id('nofollow_link'); ?>"><?php echo wpgrev_i('User no follow links'); ?></label>
                </div>
                <div class="form-group">
                    <div class="wpgrev-pro"><?php echo wpgrev_i('These features available in Google Reviews Pro plugin: '); ?> <a href="https://richplugins.com/google-reviews-pro-wordpress-plugin" target="_blank"><?php echo wpgrev_i('Upgrade to Pro'); ?></a></div>
                </div>
                <div class="form-group">
                    <?php echo wpgrev_i('Language'); ?>
                    <select id="<?php echo $this->get_field_id('lang'); ?>" name="<?php echo $this->get_field_name('lang'); ?>">
                        <option value="" <?php selected('', $lang); ?>><?php echo wpgrev_i('Browser preference'); ?></option>
                        <option value="en" <?php selected('en', $lang); ?>><?php echo wpgrev_i('English'); ?></option>
                        <option value="de" <?php selected('de', $lang); ?>><?php echo wpgrev_i('German'); ?></option>
                        <option value="ru" <?php selected('ru', $lang); ?>><?php echo wpgrev_i('Russian'); ?></option>
                    </select>
                </div>
            </div>

            <br>
        <?php
        } else {
        ?>
            <div class="wpgrev-error" style="padding:10px;color:#B94A48;background-color:#F2DEDE;border-color:#EED3D7;">
                <?php echo wpgrev_i('First please register your website.'); ?>
                <a href="<?php echo admin_url('options-general.php?page=wpgrev'); ?>"><?php echo wpgrev_i('Register Site'); ?></a>
            </div>
        <?php
        }
    }
}
?>