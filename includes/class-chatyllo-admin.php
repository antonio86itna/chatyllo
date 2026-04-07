<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Dashboard — menu pages, settings UI, enqueue admin assets.
 */
final class Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'plugin_action_links_' . CHATYLLO_BASENAME, array( $this, 'action_links' ) );
    }

    /* ── Menu ──────────────────────────────────────────────────────── */
    public function register_menu() {
        add_menu_page(
            __( 'Chatyllo', 'chatyllo' ),
            __( 'Chatyllo', 'chatyllo' ),
            'manage_options',
            'chatyllo',
            array( $this, 'render_dashboard' ),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'chatyllo',
            __( 'Dashboard', 'chatyllo' ),
            __( 'Dashboard', 'chatyllo' ),
            'manage_options',
            'chatyllo',
            array( $this, 'render_dashboard' )
        );

        add_submenu_page(
            'chatyllo',
            __( 'Settings', 'chatyllo' ),
            __( 'Settings', 'chatyllo' ),
            'manage_options',
            'chatyllo-settings',
            array( $this, 'render_settings' )
        );

        add_submenu_page(
            'chatyllo',
            __( 'FAQ / Q&A', 'chatyllo' ),
            __( 'FAQ / Q&A', 'chatyllo' ),
            'manage_options',
            'chatyllo-faq',
            array( $this, 'render_faq' )
        );

        add_submenu_page(
            'chatyllo',
            __( 'Knowledge Base', 'chatyllo' ),
            __( 'Knowledge Base', 'chatyllo' ),
            'manage_options',
            'chatyllo-knowledge',
            array( $this, 'render_knowledge' )
        );

        add_submenu_page(
            'chatyllo',
            __( 'Chat Logs', 'chatyllo' ),
            __( 'Chat Logs', 'chatyllo' ),
            'manage_options',
            'chatyllo-logs',
            array( $this, 'render_logs' )
        );

        add_submenu_page(
            'chatyllo',
            __( 'Statistics', 'chatyllo' ),
            __( 'Statistics', 'chatyllo' ),
            'manage_options',
            'chatyllo-stats',
            array( $this, 'render_stats' )
        );

        add_submenu_page(
            'chatyllo',
            __( 'Service Status', 'chatyllo' ),
            __( 'Service Status', 'chatyllo' ),
            'manage_options',
            'chatyllo-status',
            array( $this, 'render_status' )
        );
    }

    /* ── Assets ────────────────────────────────────────────────────── */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'chatyllo' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'chatyllo-admin',
            CHATYLLO_URL . 'admin/css/chatyllo-admin.css',
            array(),
            @filemtime( CHATYLLO_PATH . 'admin/css/chatyllo-admin.css' ) ?: CHATYLLO_VERSION
        );

        wp_enqueue_script(
            'chatyllo-admin',
            CHATYLLO_URL . 'admin/js/chatyllo-admin.js',
            array( 'jquery' ),
            @filemtime( CHATYLLO_PATH . 'admin/js/chatyllo-admin.js' ) ?: CHATYLLO_VERSION,
            true
        );

        // Whitelist: only expose UI-relevant settings to JavaScript.
        // NEVER expose proxy_url, proxy_secret, or any server credentials.
        $all_settings = Settings::instance()->get_all();
        $safe_keys = array(
            'enabled', 'widget_position', 'widget_primary_color', 'widget_text_color',
            'widget_icon', 'widget_custom_icon_url', 'widget_size', 'widget_show_on_mobile',
            'widget_z_index', 'widget_open_delay', 'widget_sound',
            'bot_name', 'bot_avatar_url', 'welcome_message', 'placeholder_text',
            'chat_max_history', 'typing_indicator', 'show_powered_by',
            'ai_tone', 'ai_max_response_length', 'ai_temperature', 'off_topic_message',
            'index_posts', 'index_pages', 'index_products', 'index_custom_types',
            'index_site_info', 'exclude_ids', 'max_chunk_size',
            'fallback_message', 'no_match_message',
            'branding_mode', 'custom_branding_text',
            'log_chats', 'log_retention_days',
            'show_on_all_pages', 'show_on_pages', 'hide_on_pages', 'show_for_roles',
            'privacy_policy_url', 'require_chat_consent', 'anonymize_ip', 'anonymize_user_agent',
            'keep_data_on_uninstall',
        );
        $safe_settings = array_intersect_key( $all_settings, array_flip( $safe_keys ) );

        // Base admin config — always present in both free and premium.
        $admin_config = array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'chatyllo_admin_nonce' ),
            'settings'   => $safe_settings,
            'aiActive'   => false,
            'isPremium'  => false,
            'planName'   => 'free',
            'planLimits' => array( 'faq_limit' => 100, 'log_days' => 30, 'custom_cpt' => false ),
            'canUseAi'   => false,
            'canIndex'   => false,
            'usage'      => array( 'daily_used' => 0, 'daily_limit' => 0, 'monthly_used' => 0, 'monthly_limit' => 0 ),
            'canManualReindex' => false,
            'canHideBrand' => false,
            'canCustomBrand' => false,
            'upgradeUrl' => function_exists( 'cha_fs' ) ? cha_fs()->get_upgrade_url() : '',
            'pluginUrl'  => CHATYLLO_URL,
            'i18n'       => array(
                'saved'        => __( 'Settings saved successfully!', 'chatyllo' ),
                'error'        => __( 'An error occurred. Please try again.', 'chatyllo' ),
                'confirm'      => __( 'Are you sure?', 'chatyllo' ),
                'reindexing'   => __( 'Rebuilding knowledge base...', 'chatyllo' ),
                'reindexDone'  => __( 'Knowledge base rebuilt!', 'chatyllo' ),
                'aiOn'         => __( 'AI Active', 'chatyllo' ),
                'aiOff'        => __( 'AI Offline', 'chatyllo' ),
                'deleting'     => __( 'Deleting...', 'chatyllo' ),
                'deleted'      => __( 'Deleted successfully.', 'chatyllo' ),
            ),
        );

        // Premium: override config with plan-aware values.
        if ( function_exists( 'chatyllo_premium_admin_config' ) ) {
            chatyllo_premium_admin_config( $admin_config );
        }

        wp_localize_script( 'chatyllo-admin', 'chatylloAdmin', $admin_config );

        // Color picker.
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_media();
    }

    /* ── Page renders ──────────────────────────────────────────────── */
    public function render_dashboard() {
        include CHATYLLO_PATH . 'admin/views/dashboard.php';
    }

    public function render_settings() {
        include CHATYLLO_PATH . 'admin/views/settings.php';
    }

    public function render_faq() {
        include CHATYLLO_PATH . 'admin/views/faq.php';
    }

    public function render_knowledge() {
        include CHATYLLO_PATH . 'admin/views/knowledge.php';
    }

    public function render_logs() {
        include CHATYLLO_PATH . 'admin/views/logs.php';
    }

    public function render_stats() {
        include CHATYLLO_PATH . 'admin/views/stats.php';
    }

    public function render_status() {
        include CHATYLLO_PATH . 'admin/views/status.php';
    }

    /* ── Plugin action links ───────────────────────────────────────── */
    public function action_links( $links ) {
        $custom = array(
            '<a href="' . admin_url( 'admin.php?page=chatyllo' ) . '">' . __( 'Dashboard', 'chatyllo' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=chatyllo-settings' ) . '">' . __( 'Settings', 'chatyllo' ) . '</a>',
        );
        return array_merge( $custom, $links );
    }
}
