<?php
/*
Plugin Name: reviewmenow
Description: how Google Places Reviews on your WordPress website which are register with http://reviewmenow.com
Version: 1.0.0
Author: reviewmenow
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class Reviewmenow {

    var $plugin_version = '1.0.0';
    protected $pluginPath;
    protected $pluginUrl;
    protected $curlUrl = 'https://reviewmenow.ca/getreviewme';

    public function __construct()
    {
        // Set Plugin Path
        $this->pluginPath = dirname(__FILE__);

        // Set Plugin URL
        $this->pluginUrl = WP_PLUGIN_URL . '/reviewmenow';

        add_action('wp_enqueue_scripts', array($this,'enqueue'));
        add_action('admin_menu', array($this, 'add_options_menu'));
        register_activation_hook(__FILE__, array($this, 'activate_handler'));
        add_shortcode('reviewmenow', array($this, 'shortcode'));
        register_uninstall_hook(__FILE__, array('Reviewmenow', 'uninstall_handler'));

    }

    public function shortcode()
    {
      /*  $placeId = 'ChIJhfRKhLKMfVMReISc8OIQs68';
        if ( $this->script_registered )
            return;

        $this->script_registered = TRUE;

        wp_register_script(
            'google_review_script', $this->pluginUrl.'/assets/js/google-review.js', array ( 'jquery'), 'v1', TRUE
        );

        wp_enqueue_script( 'google_review_script' );
        $data = array (
            // URL address for AJAX request
            'ajaxUrl'   => 'http://skyralstudio.com/google_review/api/clients/getreview',
            // action to trigger our callback
            'action'    => 'gogle_review',
            // selector for jQuery
            'democlass' => $this->shortcode_class
        );

        wp_localize_script( $this->shortcode_class, 'AjaxDemo', $data );*/

        $result = wp_remote_post($this->curlUrl,array('method'=>'POST','body' => array(
            'placeid' => get_option('google_placeId')
        )));
        $html = '<div id="google-reviews">';
        if($result['response']['code'] == 200){
            $response = json_decode($result['body']);

            if($response->result){
                if(count($response->review) > 0){
                    foreach ($response->review as $item) {
if($item->rating >= 4){
                        $html .= '<div class="clearfix">';
                        $html .= '<div class="profile-circle">';
                        $html .= '<img class="profile-image" src="'.$item->profile_photo_url.'">';
                        $html .= '<a href="'.$item->author_url.'" class="profile">'.$item->author_name.'></a>';
                        $html .= '</div>';
                        $html .= '    <div class="review-item">';
                        $html .= '        <div class="review-meta">';
                        $html .= '            <span class="review-author">'.$item->author_name.'</span>';
                        $html .= '           <span class="review-sep">, </span>';
                        $html .= '             <span class="review-date">'.date('d F, Y', $item->time).'</span>';
                        $html .= '         </div>';
                        $html .= '        <div class="review-stars">';
                        $html .= '            <ul>';

                                        for ($j = 1; $j <= 5; $j++) {
                                            if ($j <= $item->rating) {
                                                $html .=  '<li><i class="star"></i></li>';
                                            }
                                            else {
                                                $html .= '<li><i class="star grey-star"></i></li>';
                                            }

                                        }

                        $html .= '</ul>';
                        $html .= '        </div>';
                        $html .= '        <p class="review-text">'.$item->text .'</p>';
                        $html .= '    </div>';
                        $html .= '</div>';
			}

                    }
                }
            }
            else{
                $html .=  '<div class="clearfix"><h4 style="text-align: center">'.$response->message.'</h4></div>';
            }

            $html .= '<div style="clear:both"></div>';

            if($response->result && $response->reviewlink){
                $html .= '<h4 class="text_center"><a style="text-decoration: none" target="_blank" href="'. $response->reviewlink.'">See All Reviews</a></h4>';
            }

            if(get_option('reviewme_logo_display')) {
                $html .= '<p style="text-align: center; margin-bottom:20px;"><b>Powered by</b> <a href="http://www.reviewmenow.com/" target="_blank" class="reviewmenowimg"><img src="'.plugins_url( 'assets/images/reviewmenow.png', __FILE__ ) . '" style="width: 150px;vertical-align: middle;" ></a></p>';
            }
        }
        $html .= '</div>';
        echo $html;
    }

    function activate_handler() {
        add_option('google_placeId', '');
        add_option('google_review_clientId', '');
        add_option('reviewme_logo_display', 1);
    }

    public static function uninstall_handler(){
        delete_option('google_placeId');
        delete_option('google_review_clientId');
        delete_option('reviewme_logo_display');
    }

    public function enqueue() {


        wp_enqueue_style('google_review_style', plugin_dir_url( __FILE__ ) .'assets/css/review_me_now.css', null);
        wp_enqueue_style('google_font', '//fonts.googleapis.com/css?family=Roboto', null);

    }
    function add_options_menu() {
        add_options_page('Review Me Now Setting', 'Review Me Now', 'manage_options', 'reviewmenow', array($this,'reviewme_options_page'));
    }
    function reviewme_options_page(){
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if (isset($_POST['reviewmenow_settings'])) {
        $nonce = $_REQUEST['_wpnonce'];
        if (!wp_verify_nonce($nonce, 'reviewme_settings')) {
            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
        }
        $googlepalceid = isset($_POST["google_placeid"]) ? sanitize_text_field($_POST["google_placeid"]) : '';
        $powerby = (isset($_POST["powerby"]) && intval($_POST["powerby"]) == 1) ? 1 : 0;
        update_option('google_placeId', $googlepalceid);
        update_option('reviewme_logo_display', $powerby);

            echo '<div id="message" class="updated fade"><p><strong>';
                    echo __('Settings Saved', 'reviewmenow').'!';
                    echo '</strong></p></div>';
        }
        ?>
        <h2>Review Me Now Setting</h2>
        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
            <?php wp_nonce_field('reviewme_settings'); ?>

            <table class="form-table">

                <tbody>
                <tr valign="top">
                    <th scope="row"><label for="google_placeid"><?Php _e('Google Place ID', 'reviewmenow');?></label></th>
                    <td><input name="google_placeid" type="text" id="google_placeid" value="<?php echo esc_attr(get_option('google_placeId')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="powerby"><?Php _e('Display Power by Logo', 'reviewmenow');?></label></th>
                    <td><input name="powerby" type="checkbox" id="powerby" <?php if (esc_attr(get_option('reviewme_logo_display')) == '1') echo ' checked="checked"'; ?> value="1" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <td colspan="2"><?Php _e('To show your Google reviews, place the following shortcode anywhere you want on any page: [reviewmenow]', 'reviewmenow');?></td>
                </tr>
                </tbody>

            </table>

            <p class="submit"><input type="submit" name="reviewmenow_settings" id="reviewmenow_settings" class="button button-primary" value="<?Php _e('Save Changes', 'reviewmenow');?>"></p></form>
         <?php
    }

}

$reviewmenow = new Reviewmenow();