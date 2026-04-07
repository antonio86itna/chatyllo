<?php

/**
 * Plugin Name: Chatyllo
 * Plugin URI: https://wpezo.com/plugins/chatyllo
 * Description: Smart AI-powered chatbot that auto-learns your website content and provides intelligent, context-aware responses to your visitors. Zero configuration needed — install, activate, and it works.
 * Version: 1.2.0
 * Author: WPezo
 * Author URI: https://wpezo.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: chatyllo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * WC requires at least: 7.0
 * WC tested up to: 9.8
 *
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Freemius free/premium auto-deactivation mechanism.
 * DO NOT REMOVE THIS IF — it is essential for the
 * `function_exists` call to properly work.
 */
if ( function_exists( 'cha_fs' ) ) {
    cha_fs()->set_basename( false, __FILE__ );
} else {
    /* ─── Constants ────────────────────────────────────────────── */
    define( 'CHATYLLO_VERSION', '1.2.0' );
    define( 'CHATYLLO_FILE', __FILE__ );
    define( 'CHATYLLO_PATH', plugin_dir_path( __FILE__ ) );
    define( 'CHATYLLO_URL', plugin_dir_url( __FILE__ ) );
    define( 'CHATYLLO_BASENAME', plugin_basename( __FILE__ ) );
    define( 'CHATYLLO_DB_VERSION', '1.0.0' );
    /* ─── Freemius SDK ─────────────────────────────────────────── */
    if ( !function_exists( 'cha_fs' ) ) {
        /**
         * Create a helper function for easy SDK access.
         *
         * @return \Freemius
         */
        function cha_fs() {
            global $cha_fs;
            if ( !isset( $cha_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $cha_fs = fs_dynamic_init( array(
                    'id'               => '26781',
                    'slug'             => 'chatyllo',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_c5731db10aa875eed43305a8942d7',
                    'is_premium'       => false,
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => true,
                    'menu'             => array(
                        'slug' => 'chatyllo',
                    ),
                    'is_live'          => true,
                ) );
            }
            return $cha_fs;
        }

        // Init Freemius.
        cha_fs();
        // Signal that SDK was initiated.
        do_action( 'cha_fs_loaded' );
    }
    /* ─── Helper: is current site on a premium plan? ───────────── */
    function chatyllo_is_premium() {
        return function_exists( 'cha_fs' ) && cha_fs()->is_paying();
    }

    /* ─── Multi-plan helpers ──────────────────────────────────── */
    /** Freemius Plan ID constants. */
    define( 'CHATYLLO_PLAN_FREE', 44343 );
    define( 'CHATYLLO_PLAN_STARTER', 44344 );
    define( 'CHATYLLO_PLAN_BUSINESS', 44351 );
    define( 'CHATYLLO_PLAN_AGENCY', 44352 );
    /**
     * Load premium helpers if available.
     *
     * The file chatyllo-premium__premium_only.php is physically
     * EXCLUDED from the free build by Freemius. In the free version,
     * it simply doesn't exist and the fallbacks below are used.
     */
    $premium_file = CHATYLLO_PATH . 'includes/chatyllo-premium__premium_only.php';
    if ( file_exists( $premium_file ) ) {
        require_once $premium_file;
    }
    /**
     * Free-build fallbacks — always return free-plan values.
     * Only used when the premium file is absent (free version).
     */
    if ( !function_exists( 'chatyllo_get_plan_name' ) ) {
        function chatyllo_get_plan_name() {
            return 'free';
        }

    }
    if ( !function_exists( 'chatyllo_is_plan' ) ) {
        function chatyllo_is_plan(  $plan  ) {
            return 'free' === $plan;
        }

    }
    if ( !function_exists( 'chatyllo_can_use_ai' ) ) {
        function chatyllo_can_use_ai() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_can_index' ) ) {
        function chatyllo_can_index() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_can_manual_reindex' ) ) {
        function chatyllo_can_manual_reindex() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_can_generate_faqs' ) ) {
        function chatyllo_can_generate_faqs() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_can_hide_branding' ) ) {
        function chatyllo_can_hide_branding() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_can_custom_brand' ) ) {
        function chatyllo_can_custom_brand() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_can_use_custom_cpt' ) ) {
        function chatyllo_can_use_custom_cpt() {
            return false;
        }

    }
    if ( !function_exists( 'chatyllo_get_plan_limits' ) ) {
        function chatyllo_get_plan_limits() {
            return array(
                'faq_limit'        => 100,
                'log_days'         => 30,
                'custom_cpt'       => false,
                'reindex_interval' => 0,
                'faq_gen_limit'    => 0,
            );
        }

    }
    /* ─── Autoloader ───────────────────────────────────────────── */
    spl_autoload_register( function ( $class ) {
        $prefix = 'Chatyllo\\';
        if ( strpos( $class, $prefix ) !== 0 ) {
            return;
        }
        $relative = strtolower( str_replace( $prefix, '', $class ) );
        $relative = str_replace( '_', '-', $relative );
        $file = CHATYLLO_PATH . 'includes/class-chatyllo-' . $relative . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    } );
    /* ─── Service Status Helper ───────────────────────────────── */
    function chatyllo_get_current_status() {
        $ai_active = (bool) get_option( 'chatyllo_ai_active', false );
        $usage = get_transient( 'chatyllo_usage' );
        $last_check = get_option( 'chatyllo_last_status_check', '' );
        // No heartbeat data yet.
        if ( empty( $last_check ) && !$ai_active ) {
            return array(
                'code'       => 'network',
                'text'       => __( 'Unable to connect to AI service — checking...', 'chatyllo' ),
                'color'      => '#94A3B8',
                'icon'       => 'admin-site-alt3',
                'checked_at' => '',
            );
        }
        // AI is active and working.
        if ( $ai_active ) {
            return array(
                'code'       => 'operational',
                'text'       => __( 'All systems operational', 'chatyllo' ),
                'color'      => '#22C55E',
                'icon'       => 'yes-alt',
                'checked_at' => $last_check,
            );
        }
        // AI was checked but is not active — determine why.
        // If usage data has limits but ai_active is false → maintenance/outage.
        if ( $usage && isset( $usage['daily_limit'] ) && $usage['daily_limit'] > 0 ) {
            // User has a paid plan but AI is off → maintenance.
            return array(
                'code'       => 'maintenance',
                'text'       => __( 'Scheduled maintenance in progress — our team is working on improvements', 'chatyllo' ),
                'color'      => '#F59E0B',
                'icon'       => 'admin-tools',
                'checked_at' => $last_check,
            );
        }
        // Default: service issue.
        return array(
            'code'       => 'outage',
            'text'       => __( 'AI service temporarily unavailable — FAQ mode is active as fallback', 'chatyllo' ),
            'color'      => '#EF4444',
            'icon'       => 'warning',
            'checked_at' => $last_check,
        );
    }

    /* ─── Activation / Deactivation ────────────────────────────── */
    register_activation_hook( __FILE__, array('Chatyllo\\Core', 'activate') );
    register_deactivation_hook( __FILE__, array('Chatyllo\\Core', 'deactivate') );
    /* ─── Freemius Uninstall Hook ──────────────────────────────── */
    // Not like register_uninstall_hook(), you do NOT have to use a static function.
    cha_fs()->add_action( 'after_uninstall', 'chatyllo_uninstall_cleanup' );
    function chatyllo_uninstall_cleanup() {
        // Check if user wants to keep data on uninstall.
        $settings = get_option( 'chatyllo_settings', array() );
        if ( !empty( $settings['keep_data_on_uninstall'] ) ) {
            // Only clear crons, keep all data.
            wp_clear_scheduled_hook( 'chatyllo_reindex_content' );
            wp_clear_scheduled_hook( 'chatyllo_heartbeat' );
            return;
        }
        global $wpdb;
        $prefix = $wpdb->prefix . 'chatyllo_';
        $wpdb->query( "DROP TABLE IF EXISTS {$prefix}knowledge" );
        $wpdb->query( "DROP TABLE IF EXISTS {$prefix}faq" );
        $wpdb->query( "DROP TABLE IF EXISTS {$prefix}chat_logs" );
        $wpdb->query( "DROP TABLE IF EXISTS {$prefix}cache" );
        delete_option( 'chatyllo_settings' );
        delete_option( 'chatyllo_version' );
        delete_option( 'chatyllo_activated' );
        delete_option( 'chatyllo_ai_active' );
        delete_option( 'chatyllo_last_indexed' );
        wp_clear_scheduled_hook( 'chatyllo_reindex_content' );
        wp_clear_scheduled_hook( 'chatyllo_heartbeat' );
        wp_clear_scheduled_hook( 'chatyllo_maintenance' );
        delete_option( 'chatyllo_last_maintenance' );
    }

    /* ─── Boot ─────────────────────────────────────────────────── */
    require_once CHATYLLO_PATH . 'includes/class-chatyllo-core.php';
    add_action( 'plugins_loaded', function () {
        Chatyllo\Core::instance();
    } );
    // WP 6.7+ requires translations loaded at 'init' or later.
    add_action( 'init', function () {
        load_plugin_textdomain( 'chatyllo', false, dirname( CHATYLLO_BASENAME ) . '/languages' );
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Kept for custom translation loading support.
    } );
}
// End of function_exists('cha_fs') else block.