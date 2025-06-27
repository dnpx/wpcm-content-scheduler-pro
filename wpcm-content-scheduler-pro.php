<?php
/**
 * Plugin Name: WP Content Scheduler Pro
 * Plugin URI: https://rd5.com.br/plugins/wp-content-scheduler-pro
 * Description: Plugin profissional para publicação automática de postagens agendadas perdidas com melhorias de segurança e performance.
 * Version: 4.0.0
 * Author: Daniel Oliveira da Paixão
 * Author URI: https://rd5.com.br/dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-content-scheduler-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package WPContentSchedulerPro
 * @author Daniel Oliveira da Paixão
 * @since 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
define('WP_CONTENT_SCHEDULER_PRO_VERSION', '4.0.0');
define('WP_CONTENT_SCHEDULER_PRO_PLUGIN_FILE', __FILE__);
define('WP_CONTENT_SCHEDULER_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_CONTENT_SCHEDULER_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN', 'wp-content-scheduler-pro');

/**
 * Main plugin class implementing Singleton pattern
 *
 * @since 4.0.0
 */
final class WP_Content_Scheduler_Pro {
    
    /**
     * Plugin instance
     *
     * @var WP_Content_Scheduler_Pro|null
     * @since 4.0.0
     */
    private static $instance = null;
    
    /**
     * Transient key for caching
     *
     * @var string
     * @since 4.0.0
     */
    private const CACHE_KEY = 'wp_content_scheduler_pro_last_check';
    
    /**
     * Cache duration in seconds (5 minutes)
     *
     * @var int
     * @since 4.0.0
     */
    private const CACHE_DURATION = 300;
    
    /**
     * Maximum posts to process per execution
     *
     * @var int
     * @since 4.0.0
     */
    private const MAX_POSTS_PER_EXECUTION = 10;
    
    /**
     * Get plugin instance (Singleton)
     *
     * @return WP_Content_Scheduler_Pro
     * @since 4.0.0
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 4.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Prevent cloning
     *
     * @since 4.0.0
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     *
     * @since 4.0.0
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
    
    /**
     * Initialize WordPress hooks
     *
     * @return void
     * @since 4.0.0
     */
    private function init_hooks(): void {
        // Hook only on frontend to avoid admin performance impact
        add_action('wp_head', [$this, 'process_scheduled_posts']);
        
        // Add admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add activation/deactivation hooks
        register_activation_hook(WP_CONTENT_SCHEDULER_PRO_PLUGIN_FILE, [$this, 'activate_plugin']);
        register_deactivation_hook(WP_CONTENT_SCHEDULER_PRO_PLUGIN_FILE, [$this, 'deactivate_plugin']);
        
        // Add custom cron event
        add_action('wp_content_scheduler_pro_cron', [$this, 'process_scheduled_posts_cron']);
        
        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }
    
    /**
     * Process scheduled posts that should have been published
     *
     * @return void
     * @since 4.0.0
     */
    public function process_scheduled_posts(): void {
        // Only run on frontend pages that typically get high traffic
        if (!$this->should_process_posts()) {
            return;
        }
        
        // Check cache to avoid excessive database queries
        $last_check = get_transient(self::CACHE_KEY);
        if (false !== $last_check) {
            return;
        }
        
        // Set cache to prevent multiple executions
        set_transient(self::CACHE_KEY, time(), self::CACHE_DURATION);
        
        try {
            $this->publish_missed_posts();
        } catch (Exception $e) {
            error_log(
                sprintf(
                    '[WP Content Scheduler Pro] Error processing scheduled posts: %s',
                    $e->getMessage()
                )
            );
        }
    }
    
    /**
     * Process scheduled posts via cron (more reliable method)
     *
     * @return void
     * @since 4.0.0
     */
    public function process_scheduled_posts_cron(): void {
        try {
            $this->publish_missed_posts();
        } catch (Exception $e) {
            error_log(
                sprintf(
                    '[WP Content Scheduler Pro] Cron error processing scheduled posts: %s',
                    $e->getMessage()
                )
            );
        }
    }
    
    /**
     * Determine if posts should be processed on current request
     *
     * @return bool
     * @since 4.0.0
     */
    private function should_process_posts(): bool {
        // Only process on frontend
        if (is_admin()) {
            return false;
        }
        
        // Only on main query
        if (!is_main_query()) {
            return false;
        }
        
        // Only on specific page types to limit resource usage
        return is_front_page() || is_single() || is_page();
    }
    
    /**
     * Publish missed scheduled posts
     *
     * @return int Number of posts published
     * @since 4.0.0
     * @throws Exception If database error occurs
     */
    private function publish_missed_posts(): int {
        global $wpdb;
        
        if (!$wpdb instanceof wpdb) {
            throw new Exception('WordPress database connection not available');
        }
        
        $current_time = current_time('mysql', true);
        $post_types = $this->get_allowed_post_types();
        
        if (empty($post_types)) {
            return 0;
        }
        
        // Prepare secure query with placeholders
        $post_types_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type IN ($post_types_placeholders) 
             AND post_status = %s 
             AND post_date_gmt < %s 
             AND post_date_gmt != '0000-00-00 00:00:00'
             ORDER BY post_date_gmt ASC 
             LIMIT %d",
            array_merge($post_types, ['future', $current_time, self::MAX_POSTS_PER_EXECUTION])
        );
        
        $results = $wpdb->get_results($query);
        
        if (!$results || $wpdb->last_error) {
            if ($wpdb->last_error) {
                throw new Exception("Database error: " . $wpdb->last_error);
            }
            return 0;
        }
        
        $published_count = 0;
        
        foreach ($results as $post) {
            $post_id = absint($post->ID);
            
            if ($post_id <= 0) {
                continue;
            }
            
            // Verify post still needs publishing (double-check to prevent race conditions)
            $post_object = get_post($post_id);
            if (!$post_object || $post_object->post_status !== 'future') {
                continue;
            }
            
            // Use WordPress native function for proper hooks execution
            $result = wp_publish_post($post_id);
            
            if ($result) {
                $published_count++;
                
                // Log successful publication
                error_log(
                    sprintf(
                        '[WP Content Scheduler Pro] Successfully published post ID: %d, Title: "%s"',
                        $post_id,
                        get_the_title($post_id)
                    )
                );
            }
        }
        
        return $published_count;
    }
    
    /**
     * Get allowed post types for scheduling
     *
     * @return array
     * @since 4.0.0
     */
    private function get_allowed_post_types(): array {
        $default_types = ['post', 'page'];
        
        // Get public custom post types
        $custom_post_types = get_post_types([
            'public' => true,
            '_builtin' => false,
        ], 'names');
        
        $all_types = array_merge($default_types, $custom_post_types);
        
        // Allow filtering of post types
        return apply_filters('wp_content_scheduler_pro_post_types', $all_types);
    }
    
    /**
     * Add admin menu
     *
     * @return void
     * @since 4.0.0
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('WP Content Scheduler Pro', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN),
            __('Content Scheduler', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN),
            'manage_options',
            'wp-content-scheduler-pro',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Register plugin settings
     *
     * @return void
     * @since 4.0.0
     */
    public function register_settings(): void {
        register_setting(
            'wp_content_scheduler_pro_settings',
            'wp_content_scheduler_pro_enable_cron',
            [
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]
        );
    }
    
    /**
     * Render admin page
     *
     * @return void
     * @since 4.0.0
     */
    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'wp_content_scheduler_pro_settings')) {
            $enable_cron = isset($_POST['enable_cron']) ? 1 : 0;
            update_option('wp_content_scheduler_pro_enable_cron', $enable_cron);
            
            if ($enable_cron) {
                $this->schedule_cron_event();
            } else {
                $this->unschedule_cron_event();
            }
            
            echo '<div class="notice notice-success"><p>' . 
                 __('Settings saved successfully!', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN) . 
                 '</p></div>';
        }
        
        $enable_cron = get_option('wp_content_scheduler_pro_enable_cron', false);
        
        ?>
        <div class="wrap">
            <h1><?php _e('WP Content Scheduler Pro Settings', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wp_content_scheduler_pro_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php _e('Enable Cron Processing', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_cron" value="1" <?php checked($enable_cron); ?> />
                                <?php _e('Use WordPress cron for more reliable scheduled post processing', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?>
                            </label>
                            <p class="description">
                                <?php _e('Recommended for sites with low traffic or when frontend processing is not reliable.', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2><?php _e('Plugin Information', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?></h2>
            <table class="widefat">
                <tr>
                    <td><strong><?php _e('Version:', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?></strong></td>
                    <td><?php echo esc_html(WP_CONTENT_SCHEDULER_PRO_VERSION); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Supported Post Types:', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?></strong></td>
                    <td><?php echo esc_html(implode(', ', $this->get_allowed_post_types())); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Cache Duration:', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?></strong></td>
                    <td><?php echo esc_html(self::CACHE_DURATION); ?> <?php _e('seconds', WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Schedule cron event
     *
     * @return void
     * @since 4.0.0
     */
    private function schedule_cron_event(): void {
        if (!wp_next_scheduled('wp_content_scheduler_pro_cron')) {
            wp_schedule_event(time(), 'hourly', 'wp_content_scheduler_pro_cron');
        }
    }
    
    /**
     * Unschedule cron event
     *
     * @return void
     * @since 4.0.0
     */
    private function unschedule_cron_event(): void {
        $timestamp = wp_next_scheduled('wp_content_scheduler_pro_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wp_content_scheduler_pro_cron');
        }
    }
    
    /**
     * Load plugin text domain
     *
     * @return void
     * @since 4.0.0
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            WP_CONTENT_SCHEDULER_PRO_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(WP_CONTENT_SCHEDULER_PRO_PLUGIN_FILE)) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     *
     * @return void
     * @since 4.0.0
     */
    public function activate_plugin(): void {
        // Set default options
        add_option('wp_content_scheduler_pro_enable_cron', false);
        
        // Clear any existing cache
        delete_transient(self::CACHE_KEY);
        
        // Log activation
        error_log('[WP Content Scheduler Pro] Plugin activated successfully');
    }
    
    /**
     * Plugin deactivation
     *
     * @return void
     * @since 4.0.0
     */
    public function deactivate_plugin(): void {
        // Clean up cron events
        $this->unschedule_cron_event();
        
        // Clear cache
        delete_transient(self::CACHE_KEY);
        
        // Log deactivation
        error_log('[WP Content Scheduler Pro] Plugin deactivated');
    }
}

// Initialize the plugin
WP_Content_Scheduler_Pro::get_instance();

/**
 * Get plugin instance (for external access if needed)
 *
 * @return WP_Content_Scheduler_Pro
 * @since 4.0.0
 */
function wp_content_scheduler_pro(): WP_Content_Scheduler_Pro {
    return WP_Content_Scheduler_Pro::get_instance();
}
