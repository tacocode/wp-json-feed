<?php
/*
Plugin Name: WP JSON Feed
Plugin URI:  https://github.com/tacocode/wp-json-feed
Description: Add a custom JSON feed
Version:     0.0.2
Author:      TacoCode
Author URI:  https://github.com/tacocode
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-json-feed
Domain Path: /languages
*/

namespace TacoCode\WordPress\Plugins\WPJSONFeed;

/**
 * Class WPJSONFeed
 */
class WPJSONFeed
{
    /**
     * WPJSONFeed constructor.
     */
    public function __construct()
    {
        defined('ABSPATH') or die();
        add_action('admin_menu', array($this, 'optionsPageMenu'));
        add_action('admin_init', array($this, 'initSettings'));
        add_action('wp_ajax_wp_json_feed', array($this, 'getData'));
        add_action('wp_ajax_nopriv_wp_json_feed', array($this, 'getData'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('wp_footer', array($this, 'getFooterJS'));
        add_shortcode('wp_json_feed', array($this, 'shortcode'));
    }

    /**
     * Admin Tools menu entry
     */
    public function optionsPageMenu()
    {
        add_submenu_page(
            'tools.php',
            __('WP JSON Feed', 'wp-json-feed'),
            __('WP JSON Feed', 'wp-json-feed'),
            'manage_options',
            'wp_json_feed',
            array($this, 'optionsPageTemplate')
        );
    }

    /**
     * The option page HTML template
     */
    public function optionsPageTemplate()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once 'includes/options_page_template.php';
    }

    /**
     * Enqueue scripts
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-json-feed', plugins_url('css/wp-json-feed.css', __FILE__));
        wp_localize_script(
            'jquery',
            'wp_json_feed',
            array(
                'ajax_url' => admin_url('admin-ajax.php')
            )
        );
    }

    /**
     * Initialize all plugin sections, settings & fields
     */
    public function initSettings()
    {
        register_setting(
            'wp_json_feed',
            'display_content'
        );

        add_settings_field(
            'display_content',
            __('Display Content', 'wp-json-feed'),
            array($this, 'addContentSettingsField'),
            'wp_json_feed',
            'wp_json_feed_settings_section',
            array(
                'label_for' => 'display_content',
            )
        );

        register_setting(
            'wp_json_feed',
            'limit_content'
        );

        add_settings_field(
            'limit_content',
            __('Limit Content', 'wp-json-feed'),
            array($this, 'addLimitContentField'),
            'wp_json_feed',
            'wp_json_feed_settings_section',
            array(
                'label_for' => 'limit_content',
            )
        );

        add_settings_section(
            'wp_json_feed_settings_section',
            __('Settings', 'wp-json-feed'),
            array($this,'addSettingsSection'),
            'wp_json_feed'
        );
    }

    /**
     * Boilerplate
     */
    public function addSettingsSection()
    {
        // Tutu
    }

    public function addContentSettingsField($args)
    {
        $option = get_option('display_content');
        ?>
        <input type="radio" name="display_content" value="content" <?php checked('content', $option, true); ?>> Full
        <input type="radio" name="display_content" value="excerpt" <?php checked('excerpt', $option, true); ?>> Excerpt
        <?php
    }


    public function addLimitContentField($args)
    {
        $option = get_option('limit_content');
        ?>
        <input type="number" name="limit_content" value="<?php echo $option; ?>"> Letters
        <?php
    }

    /**
     * @param $atts
     *
     * @return false|string
     */
    public function shortcode($atts)
    {
        $attributes = shortcode_atts(array(
            'url' => null,
            'categories' => null,
            'replace' => null,
            'featured_image' => false,
            'excerpt_only' => false,
        ), $atts);

        $categories = empty($attributes['categories']) ? '' : 'categories=' . $attributes['categories'];
        $url = $attributes['url'] . (parse_url($attributes['url'], PHP_URL_QUERY) ? '&' : '?') . $categories;

        $display_content = get_option('display_content');
        $display_content = empty($display_content) ? 'content' : $display_content;
        $display_content = (bool)$attributes['excerpt_only'] ? 'excerpt' : $display_content;

        $limit_content = get_option('limit_content');
        $limit_content = empty($limit_content) ? 0 : $limit_content;

        ob_start();
        require_once plugin_dir_path(__FILE__) . 'includes/template.php';
        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getData()
    {
        if (empty($_REQUEST['url'])) {
            wp_send_json_error();
        }

        if (false !== $response = $this->get($_REQUEST['url'])) {
            $json = json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return "Could not get JSON data.";
            }
            unset($json);
        }

        $response = $this->replace($response, "\[(\\\?)\/?et_pb_(text|column|row)?.*?\]");
        $response = $this->replace($response, "\[monarch_share\]");

        wp_send_json_success(array(
            'json' => json_decode($response)
        ));
    }

    /**
     * Boilerplate
     */
    public function getFooterJS()
    {
        // Tutu
    }

    /**
     * @param $uri
     *
     * @return bool|string
     */
    private function get($uri)
    {
        $result = false;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if (WP_DEBUG) {
            $fp = fopen(dirname(__FILE__).'/debug_curl.txt', 'w');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $fp);
        }

        $response = curl_exec($ch);

        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 200:
                    $result = $response;
            }
        }

        curl_close($ch);

        return $result;
    }

    /**
     * @param $string
     * @param $regex
     *
     * @return string|string[]|null
     */
    private function replace($string, $regex)
    {
        return preg_replace('%' . $regex . '%i', '', $string);
    }
}

return new WPJSONFeed();
