<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Proxy client — communicates with the remote Chatyllo proxy server.
 */
final class Proxy {

    private static $instance = null;
    private $ai_status = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Always register the cron interval so WP knows about it.
        add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

        // Premium: set up heartbeat cron (function is in premium-only file).
        if ( function_exists( 'chatyllo_premium_setup_heartbeat' ) ) {
            chatyllo_premium_setup_heartbeat();
        } else {
            // Free version: clear any leftover heartbeat cron.
            wp_clear_scheduled_hook( 'chatyllo_heartbeat' );
        }
    }

    public function add_cron_interval( $schedules ) {
        $schedules['chatyllo_5min'] = array(
            'interval' => 300,
            'display'  => __( 'Every 5 minutes', 'chatyllo' ),
        );
        return $schedules;
    }

    public function heartbeat() {
        if ( function_exists( 'chatyllo_premium_heartbeat' ) ) {
            chatyllo_premium_heartbeat();
        }
    }

    public function is_ai_active() {
        if ( function_exists( 'chatyllo_premium_is_ai_active' ) ) {
            return chatyllo_premium_is_ai_active();
        }
        return false;
    }

    public function chat( $user_message, $context = '', $history = array(), $site_info = array() ) {
        if ( function_exists( 'chatyllo_premium_proxy_chat' ) ) {
            return chatyllo_premium_proxy_chat( $user_message, $context, $history, $site_info );
        }
        return array(
            'success' => false,
            'mode'    => 'error',
            'reply'   => '',
            'error'   => __( 'AI chat requires a paid plan.', 'chatyllo' ),
        );
    }

    public function refresh_status() {
        if ( function_exists( 'chatyllo_premium_refresh_status' ) ) {
            $this->ai_status = null;
            return chatyllo_premium_refresh_status();
        }
        return false;
    }
}
