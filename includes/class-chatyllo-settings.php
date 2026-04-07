<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings manager — all plugin options stored as a single serialized array.
 */
final class Settings {

    private static $instance = null;
    private $options = array();
    const OPTION_KEY = 'chatyllo_settings';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = wp_parse_args(
            get_option( self::OPTION_KEY, array() ),
            self::get_defaults()
        );

        // Migration: remove proxy_secret from stored options (v1.1.0+).
        if ( isset( $this->options['proxy_secret'] ) ) {
            unset( $this->options['proxy_secret'] );
            update_option( self::OPTION_KEY, $this->options );
        }
    }

    /* ── Defaults ──────────────────────────────────────────────────── */
    public static function get_defaults() {
        return array(
            // General.
            'enabled'                => true,
            'proxy_url'              => 'https://wpezo.com/wp-api/chatyllo/',

            // Widget appearance.
            'widget_position'        => 'bottom-right', // bottom-right | bottom-left
            'widget_primary_color'   => '#4F46E5',
            'widget_text_color'      => '#FFFFFF',
            'widget_icon'            => 'chat-bubble', // chat-bubble | robot | headset | custom
            'widget_custom_icon_url' => '',
            'widget_size'            => 'medium', // small | medium | large
            'widget_show_on_mobile'  => true,
            'widget_z_index'         => 99999,
            'widget_open_delay'      => 0, // ms, 0 = no auto open
            'widget_sound'           => true,

            // Chat behavior.
            'bot_name'               => 'Chatyllo',
            'bot_avatar_url'         => '',
            'welcome_message'        => '',
            'placeholder_text'       => '',
            'chat_max_history'       => 10,
            'typing_indicator'       => true,
            'show_powered_by'        => true,

            // AI behavior.
            'ai_tone'                => 'professional', // professional | friendly | casual
            'ai_max_response_length' => 500,
            'ai_temperature'         => 0.7,
            'off_topic_message'      => '',

            // Knowledge indexing.
            'index_posts'            => true,
            'index_pages'            => true,
            'index_products'         => true,
            'index_custom_types'     => '',
            'index_site_info'        => true,
            'exclude_ids'            => '',
            'max_chunk_size'         => 800,

            // Fallback / Offline mode.
            'fallback_message'       => '',
            'no_match_message'       => '',

            // Branding.
            'branding_mode'          => 'powered_by', // powered_by | hidden | custom
            'custom_branding_text'   => '',

            // Analytics.
            'log_chats'              => true,
            'log_retention_days'     => 30,

            // Display rules.
            'show_on_all_pages'      => true,
            'show_on_pages'          => '', // comma-separated IDs
            'hide_on_pages'          => '',
            'show_for_roles'         => '', // empty = everyone

            // Privacy & GDPR.
            'privacy_policy_url'     => '',
            'require_chat_consent'   => true,
            'anonymize_ip'           => true,
            'anonymize_user_agent'   => false,

            // Advanced.
            'keep_data_on_uninstall' => false,
        );
    }

    /* ── Set defaults on activation ────────────────────────────────── */
    public static function set_defaults() {
        if ( ! get_option( self::OPTION_KEY ) ) {
            update_option( self::OPTION_KEY, self::get_defaults() );
        }
    }

    /* ── Getters / Setters ─────────────────────────────────────────── */
    public function get( $key, $default = null ) {
        if ( isset( $this->options[ $key ] ) ) {
            return $this->options[ $key ];
        }
        $defaults = self::get_defaults();
        return $default !== null ? $default : ( $defaults[ $key ] ?? null );
    }

    public function get_all() {
        return $this->options;
    }

    public function set( $key, $value ) {
        $this->options[ $key ] = $value;
        return update_option( self::OPTION_KEY, $this->options );
    }

    public function save( $data ) {
        $sanitized = self::sanitize( $data );
        $this->options = wp_parse_args( $sanitized, $this->options );
        $this->enforce_plan_limits();
        return update_option( self::OPTION_KEY, $this->options );
    }

    /**
     * Enforce plan-based limits on settings values.
     * Called automatically on save() to prevent exceeding plan capabilities.
     */
    private function enforce_plan_limits() {
        // Premium: use plan-aware enforcement (function in premium-only file).
        if ( function_exists( 'chatyllo_premium_enforce_plan_limits' ) ) {
            chatyllo_premium_enforce_plan_limits( $this->options );
            return;
        }

        // Free version defaults: 30 days log, forced branding, no custom CPT.
        $this->options['log_retention_days'] = min( (int) $this->options['log_retention_days'], 30 );
        $this->options['branding_mode']      = 'powered_by';
        $this->options['index_custom_types'] = '';
    }

    /* ── Sanitization ──────────────────────────────────────────────── */
    public static function sanitize( $data ) {
        $clean = array();
        $booleans = array(
            'enabled', 'widget_show_on_mobile', 'widget_sound', 'typing_indicator',
            'show_powered_by', 'index_posts', 'index_pages', 'index_products',
            'index_site_info', 'log_chats', 'show_on_all_pages', 'keep_data_on_uninstall',
            'require_chat_consent', 'anonymize_ip', 'anonymize_user_agent',
        );
        $integers = array(
            'widget_z_index', 'widget_open_delay', 'chat_max_history',
            'ai_max_response_length', 'max_chunk_size', 'log_retention_days',
        );
        $floats = array( 'ai_temperature' );

        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $booleans, true ) ) {
                $clean[ $key ] = (bool) $value;
            } elseif ( in_array( $key, $integers, true ) ) {
                $clean[ $key ] = absint( $value );
            } elseif ( in_array( $key, $floats, true ) ) {
                $clean[ $key ] = max( 0, min( 2, (float) $value ) );
            } else {
                $clean[ $key ] = sanitize_text_field( $value );
            }
        }

        // Hard cap on max response length to prevent API abuse.
        if ( isset( $clean['ai_max_response_length'] ) ) {
            $clean['ai_max_response_length'] = max( 100, min( 800, (int) $clean['ai_max_response_length'] ) );
        }

        return $clean;
    }

    /* ── Translated defaults ───────────────────────────────────────── */
    public function get_welcome_message() {
        $msg = $this->get( 'welcome_message' );
        if ( ! empty( $msg ) ) {
            return $msg;
        }
        return __( 'Hi there! 👋 How can I help you today?', 'chatyllo' );
    }

    public function get_placeholder_text() {
        $msg = $this->get( 'placeholder_text' );
        if ( ! empty( $msg ) ) {
            return $msg;
        }
        return __( 'Type your message...', 'chatyllo' );
    }

    public function get_off_topic_message() {
        $msg = $this->get( 'off_topic_message' );
        if ( ! empty( $msg ) ) {
            return $msg;
        }
        return __( "I appreciate your question, but I'm here to help specifically with topics related to this website. Could you please ask something about our products, services, or content?", 'chatyllo' );
    }

    public function get_fallback_message() {
        $msg = $this->get( 'fallback_message' );
        if ( ! empty( $msg ) ) {
            return $msg;
        }
        return __( 'Our AI assistant is currently unavailable. You can still browse our FAQ below or try again later.', 'chatyllo' );
    }

    public function get_no_match_message() {
        $msg = $this->get( 'no_match_message' );
        if ( ! empty( $msg ) ) {
            return $msg;
        }
        return __( "I'm sorry, I don't have an answer for that right now. Please try rephrasing your question or contact us directly.", 'chatyllo' );
    }
}
