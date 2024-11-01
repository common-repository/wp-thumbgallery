<?php
/*
Plugin Name: mb.thumbGallery - On the top of WP media gallery
Plugin URI: http://pupunzi.open-lab.com
Description: Use the default Wordpress shortcode to display an awesome smart responsive image-gallery.
Author: Pupunzi (Matteo Bicocchi)
Version: 1.0.7
Author URI: http://pupunzi.com
Text Domain: wpthumbgallery
*/

define("MBTG_VERSION", "1.0.7");


add_action('admin_init', 'mbtg_register_settings');
function mbtg_register_settings()
{
    register_setting('mbtg-settings-group', 'mbtg_version');
//  default settings
    register_setting('mbtg-activate-group', 'mbtg_is_active');
}

register_activation_hook(__FILE__, 'mbtg_install');
function mbtg_install()
{
// add and update our default options upon activation
    add_option('mbtg_is_active', true);
    add_option('mbtg_version', MBTG_VERSION);
    add_option('mbtg_notice_dismiss', false);
}

$mbtg_is_active = get_option('mbtg_is_active');
$mbtg_version = get_option('mbtg_version');

$mbtg_nav_effect = 'slide_horizontal';
$mbtg_gallery_effect = 'fade';
$mbtg_pagination = 3;
$mbtg_speed = 500;
$mbtg_nav_show = "false";
$mbtg_gallery_cover = false;

$plugin_domain = $_SERVER['HTTP_HOST'];
if(!isset($plugin_domain) || empty($plugin_domain))
    $plugin_domain = $_SERVER['SERVER_NAME'];
if(!isset($plugin_domain) || empty($plugin_domain))
    $plugin_domain = get_bloginfo('name');

$tgal_plus_url = "https://pupunzi.com/wpPlus/go-plus.php?locale=".get_locale()."&plugin_prefix=TGAL&plugin_version=".MBTG_VERSION . "&lic_domain=".$plugin_domain. "&lic_theme=".get_template(). "&php=" . phpversion();
$tg_price = 5;

$i = 0;

//mb_notice
/*
require('inc/mb_notice/notice.php');
$mbtg_message = __('<b>WP-THUMB-GALLERY 1.0</b>: <br>To customize the gallery behaviour you have to ', 'wpthumbgallery');
$mbtg_notice = new mb_notice('mbtg', plugin_basename( __FILE__ ));
$mbtg_notice->add_notice($mbtg_message, 'success');

*/

add_action('plugins_loaded', 'mbtg_localize');
function mbtg_localize()
{
    load_plugin_textdomain( 'wpthumbgallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action('admin_enqueue_scripts', 'mbtg_load_admin_css');
function mbtg_load_admin_css($hook)
{
    if ($hook != 'mb-ideas_page_wp-thumbGallery/mbThumbGallery' && $hook != 'toplevel_page_mb-ideas-menu')
        return;
    wp_enqueue_style('tg_admin_css', plugins_url('/inc/mb_admin.css', __FILE__), null, MBTG_VERSION);
}

add_filter('admin_body_class', 'mbtg_add_body_classes');
function mbtg_add_body_classes($classes)
{
    $screen = (get_current_screen()->id == "mb-ideas_page_wp-thumbGallery/mbThumbGallery") ? 1 : 0;
    $classes = '';
    if ($screen)
        $classes = 'mb-free';
    return $classes;
}

add_action('wp_enqueue_scripts', 'mbtg_js');
function mbtg_js()
{
    wp_enqueue_style('mbtgCss', plugins_url('/css/jquery.mb.gallery.min.css', __FILE__), null, MBTG_VERSION);
    wp_enqueue_script('mbtgJs', plugins_url('/js/jquery.mb.gallery.js', __FILE__), array(), MBTG_VERSION, true);
    wp_enqueue_script('mbtgInit', plugins_url('/js/thumbGallery-init.js', __FILE__), array(), MBTG_VERSION, true);
}

add_filter('post_gallery', 'mbtg_shortcode', 10, 2);
function mbtg_shortcode($output, $attr)
{
    global $mbtg_is_active,$post, $columns, $mbtg_speed, $order, $orderby, $mbtg_gallery_effect, $mbtg_nav_effect, $mbtg_cover, $mbtg_nav_show, $mbtg_pagination;

    if(!$mbtg_is_active)
        return;

    extract(shortcode_atts(array(
        'captions' => '',
        'columns' => $mbtg_pagination,
        'id' => $post->ID,
        'order' => 'ASC',
        'orderby' => 'menu_order ID',
        'mbtg_speed' => "$mbtg_speed",
        'mbtg_nav_effect' => "$mbtg_nav_effect",
        'mbtg_gallery_effect' => "$mbtg_gallery_effect",
        'mbtg_cover' => "$mbtg_cover",
        'mbtg_nav_show' => "$mbtg_nav_show",
        'type' => ''
    ), $attr));

    if (isset($attr['ids'])) {
        $_attachments = get_posts(array('include' => $attr['ids'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));
        $attachments = array();
        foreach ($_attachments as $key => $val) {
            $attachments[$val->ID] = $_attachments[$key];
        }
    }

    $output .= mbtg_show_thumbs($attachments, $columns, $mbtg_speed, $mbtg_nav_effect, $mbtg_gallery_effect, $mbtg_cover, $mbtg_nav_show );
    return $output;
}

function mbtg_show_thumbs($attachments, $pagination, $mbtg_speed, $mbtg_nav_effect, $mbtg_gallery_effect, $mbtg_cover, $mbtg_nav_show)
{
    global $i;

    $i++;
    $out = "<div id='thumbGallery_" . $i . "'
             class='thumbGallery'

             data-thumbGallery='true'
             data-nav_effect='$mbtg_nav_effect'
             data-nav_delay='200'
             data-nav_timing='$mbtg_speed'
             data-nav_show='$mbtg_nav_show'
             data-nav_delay_inverse='1'
             data-nav_pagination='$pagination'
             data-gallery_effect='$mbtg_gallery_effect'
             data-gallery_fullscreenw='100%'
             data-gallery_fullscreenh='100%'
             data-gallery_cover='$mbtg_cover'

             style='display:none'
             >";

    $img_i = 0;
    foreach ($attachments as $id => $attachment) {
        $img_i ++;
        $medium = wp_get_attachment_image_src($id, 'medium');
        $larg = wp_get_attachment_image_src($id, 'medium_large');
        $full = wp_get_attachment_image_src($id, 'full');

        $caption = mbtg_attachment_caption($attachment);
        $thumb_src = $pagination > 3 ? $medium : $larg;

        $out .= "<img id='tg-img-$img_i' src='" . $thumb_src[0] . "' data-highres='" . $full[0] . "'  data-caption='" . $caption ."'>"; //. " <br>©" . date("Y") . " " . $_SERVER['SERVER_NAME'] . ". All rights reserved

    }

    $out .= "</div>";

    return $out;
}

function mbtg_attachment_caption($attachment)
{
    if (!empty($attachment->post_excerpt)) {
        $caption = wptexturize($attachment->post_excerpt);
    } else {
        $caption = wptexturize($attachment->post_content);
    }
    return $caption;
}


/**
 * --------------------------------------------------------------------------------------------------------- Options page
 * */

add_filter('plugin_action_links', 'mbtg_action_links', 10, 2);
function mbtg_action_links($links, $file)
{
    global $tgal_plus_url;
    // check to make sure we are on the correct plugin
    if ($file == plugin_basename(__FILE__)) {

        // the anchor tag and href to the URL we want. For a "Settings" link, this needs to be the url of your settings page
        $settings_link = '<a style="color: #008000" href="' . $tgal_plus_url . '" target="_blank">Go PLUS</a> | ';
        $settings_link .= '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-thumbgallery/mbThumbGallery.php">Settings</a>';
        // add the link to the list
        array_unshift($links, $settings_link);
    }
    return $links;
}

/**
 * Add root menu
 */
require("inc/mb-admin-menu.php");

add_action('admin_menu', 'mbtg_add_option_page');
function mbtg_add_option_page()
{
    add_submenu_page('mb-ideas-menu', 'ThumbGallery', 'ThumbGallery', 'manage_options', __FILE__, 'mbtg_options_page');
}


/**
---------------------------------------------------------- Settings
 */

add_action('print_media_templates', function(){
    global $tgal_plus_url;

    ?>
    <script type="text/html" id="tmpl-custom-gallery-setting">
        <br style="clear: both">
        <br>
        <img style="margin-top: 30px;" src="<?php echo plugins_url('images/media-gallery-advanced.png', __FILE__); ?>">
        <br><br>
        <a href="<?php echo $tgal_plus_url ?>" target="_blank"><?php _e("Get the PLUS plug-in to customize each single gallery from the Media gallery panel.","wpthumbgallery") ?></a>
    </script>

    <script>
        jQuery(document).ready(function(){
            _.extend(wp.media.gallery.defaults, {
                ds_text: 'no text',
                ds_textarea: 'no more text',
                ds_number: "3",
                ds_select: 'option1',
                ds_bool: false,
                ds_text1: 'dummdideldei'
            });
            wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
                template: function(view){
                    return wp.media.template('gallery-settings')(view)
                        + wp.media.template('custom-gallery-setting')(view);
                }
            });
        });
    </script>

<?php
});

function mbtg_options_page()
{ // Output the options page
    global $tg_price, $tgal_plus_url;
    ?>
    <div class="wrap">
        <a href="http://pupunzi.com"><img style=" width: 350px" src="<?php echo plugins_url('images/logo.png', __FILE__); ?>" alt="Made by Pupunzi"/></a>
        <h2><?php _e('mb.thumbGallery', 'wpthumbgallery'); ?></h2>

        <img style=" width: 200px; position: absolute; right: 0; top: 0; z-index: 100" src="<?php echo plugins_url('images/TGAL.svg', __FILE__); ?>" alt="mb.thumbGallery icon"/>
        <form id="optionsForm" method="post" action="options.php">

            <?php settings_fields('mbtg-activate-group'); ?>
            <?php do_settings_sections('mbtg-activate-group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('activate mb.thumbGallery', 'wpthumbgallery'); ?></th>
                    <td>
                        <div class="onoffswitch">
                            <input class="onoffswitch-checkbox" type="checkbox" id="mbtg_is_active" name="mbtg_is_active" value="true" <?php if (get_option('mbtg_is_active')) { echo ' checked="checked"'; } ?>/>
                            <label class="onoffswitch-label" for="mbtg_is_active"></label>
                        </div>
                    </td>
                </tr>
            </table>
            <br>
            <br>
            <a href="<?php echo $tgal_plus_url ?>" target="_blank">
                <img style="max-width:100%" src="<?php echo plugins_url('images/mbtg-advanced-opt.jpg', __FILE__); ?>">
            </a>
            <!--            <p class="submit">
                <input type="submit" class="button-primary" value="<?php /*_e('Save Changes') */?>"/>
            </p>
-->
        </form>
    </div>
    <div class="rightCol">

        <!-- ---------------------------—---------------------------—---------------------------—---------------------------
        License form box
        ---------------------------—---------------------------—---------------------------—---------------------------— -->
        <div id="getLic" class="box box-success" style="display:block">
            <h3><?php _e('Get your <strong>PLUS</strong> plug-in!', 'wpthumbgallery'); ?></h3>
            <?php _e("With the <strong>mb.thumbGallery PLUS</strong> plug-in you can activate the advanced settings panel and customize each single gallery from the Media gallery panel.","wpthumbgallery") ?>
            <br>
            <br>
            <a target="_blank" href="<?php echo $tgal_plus_url ?>" class="getKey">
                    <span>
                         <?php printf(__('Go <strong>PLUS</strong> For <b>%s EUR</b> Only', 'wpthumbgallery'), $tg_price) ?>
                    </span>
            </a>
        </div>

        <!-- ---------------------------—---------------------------—---------------------------—---------------------------
        ADVs box
        ---------------------------—---------------------------—---------------------------—---------------------------— -->
        <div id="ADVs" class="box"></div>

        <!-- ---------------------------—---------------------------—---------------------------—---------------------------
        Info box
        ---------------------------—---------------------------—---------------------------—---------------------------— -->
        <div class="box">
            <h3><?php _e('Thanks for installing <strong>mb.thumbGallery</strong>!', 'wpthumbgallery'); ?></h3>
            <p>
                <?php printf(__('You\'re using <strong>mb.thumbGallery</strong> v. <b>%s</b>', 'wpthumbgallery'), MBTG_VERSION); ?><br>
                <?php _e('by', 'wpthumbgallery'); ?> <a href="http://pupunzi.com">mb.ideas (Pupunzi)</a>
            </p>
            <hr>
            <p><?php _e('Follow me on twitter', 'wpthumbgallery'); ?>: <a
                    href="https://twitter.com/pupunzi">@pupunzi</a><br>
                <?php _e('Visit my site', 'wpthumbgallery'); ?>: <a href="http://pupunzi.com">http://pupunzi.com</a><br>
                <?php _e('Visit my blog', 'wpthumbgallery'); ?>: <a href="http://pupunzi.open-lab.com">http://pupunzi.open-lab.com</a><br>
                Paypal: <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=V6ZS8JPMZC446&lc=GB&item_name=mb%2eideas&item_number=MBIDEAS&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG_global%2egif%3aNonHosted" target="_blank"><?php _e('donate', 'wpthumbgallery'); ?></a>
            <hr>
            <!-- Begin MailChimp Signup Form -->
            <form action="http://pupunzi.us6.list-manage2.com/subscribe/post?u=4346dc9633&amp;id=91a005172f"
                  method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate"
                  target="_blank" novalidate>
                <label for="mce-EMAIL"><?php _e('Subscribe to my mailing list <br>to stay in touch', 'wpthumbgallery'); ?>
                    :</label>
                <br>
                <br>
                <input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL"
                       placeholder="<?php _e('your email address', 'wpthumbgallery'); ?>" required>
                <input type="submit" value="<?php _e('Subscribe', 'wpthumbgallery'); ?>" name="subscribe"
                       id="mc-embedded-subscribe" class="button">
            </form>
            <!--End mc_embed_signup-->
            <hr>

            <!--SHARE-->

            <div id="share" style="margin-top: 10px">
                <a href="https://twitter.com/share" class="twitter-share-button"
                   data-url="https://wordpress.org/plugins/wp-thumbgallery/"
                   data-text="I'm using the mb.thumbGallery WP plugin" data-via="pupunzi"
                   data-hashtags="HTML5,wordpress,plugin">Tweet</a>
                <script>!function (d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (!d.getElementById(id)) {
                            js = d.createElement(s);
                            js.id = id;
                            js.src = "//platform.twitter.com/widgets.js";
                            fjs.parentNode.insertBefore(js, fjs);
                        }
                    }(document, "script", "twitter-wjs");</script>
                <div id="fb-root"></div>
                <script>(function (d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) return;
                        js = d.createElement(s);
                        js.id = id;
                        js.src = "//connect.facebook.net/it_IT/all.js#xfbml=1";
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));</script>
                <div style="margin-top: 10px" class="fb-like"
                     data-href="https://wordpress.org/plugins/wp-thumbgallery/" data-send="false"
                     data-layout="button_count" data-width="450" data-show-faces="true" data-font="arial"></div>
            </div>
        </div>

    </div>

    <script>
        jQuery(function(){

            var activate = jQuery("#mbtg_is_active");
            activate.on("change", function(){
                var val = this.checked ? true : false;

                console.debug(val);
                jQuery.ajax({
                    type : "post",
                    dataType : "json",
                    url : ajaxurl,
                    data : {action: "mbtg_activate", activate : val},
                    success: function(resp) {
                        console.debug(resp)
                    }
                })

            });

            // Add ADVs
            jQuery.ajax({
                type    : "post",
                dataType: "html",
                url     : "https://pupunzi.com/wpPlus/advs.php",
                data    : {plugin: "TGAL"},
                success : function (resp) {
                    jQuery("#ADVs").html(resp);
                }
            })
        })
    </script>
<?php
}

/**
 *
 */
add_action('wp_ajax_mbtg_activate', 'mbtg_activate');
function mbtg_activate()
{
    $activate = $_POST["activate"] == "true" ? true : false;
    update_option('mbtg_is_active', $activate);
}



/**
 * Watermark
 */
add_action('wp_head', 'mbtg_custom_js');
function mbtg_custom_js() {

    if (!wp_script_is('jquery', 'done')) {
        wp_enqueue_script('jquery');
    }
    $script = 'jQuery(function(){var a=null;setInterval(function(){jQuery(".thumbWrapper, .tg-img-container").each(function(){var b=jQuery(this);jQuery("[class*=tg_wm_]",b).remove();a="tg_wm_"+Math.floor(1E5*Math.random());var c=jQuery("<img/>").attr("src","data:image/png;base64,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");
tg_wm=jQuery("<div/>").addClass(a).html(c);c.attr("style","filter:none!important;-webkit-transform:none!important;transform:none!important;padding:0!important;margin:0!important;height:100%!important; width:auto!important;display:block!important;visibility:visible!important;top:0!important;right:0!important;opacity:1!important;position:absolute!important;margin:auto!important;z-index:10000!important;");tg_wm.attr("style","filter:none!important;-webkit-transform:none!important;transform:none!important;padding:0!important;margin:0!important;display:block!important;position:absolute!important;top:0!important;bottom:0!important;right:0!important;margin:auto!important;z-index:10000!important;width:100%!important;height:100%!important;max-height:280px!important;");
b.prepend(tg_wm)})},5E3)});';
    echo "<script>".$script."</script>";
}

/**
 * Deactivate plugin if PLUS version exist.
 */
add_action('plugins_loaded', 'mbtg_free_deactivate');
function mbtg_free_deactivate()
{
    global $mbtgpro;
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if ($mbtgpro) {
        deactivate_plugins(plugin_basename(__FILE__));

        $dir = plugin_dir_path(__FILE__);
        deleteDir($dir);
    }
}

if(!function_exists("deleteDir")) {

    function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}
