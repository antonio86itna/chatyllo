<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Chat engine — orchestrates AI mode and fallback (manual Q&A) mode.
 */
final class Chat {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Process a user message and return a response.
     */
    public function process( $message, $history = array() ) {
        $settings = Settings::instance();
        $proxy    = Proxy::instance();
        $start    = microtime( true );

        $message = sanitize_textarea_field( $message );
        $message = mb_substr( $message, 0, 2000 );

        if ( empty( trim( $message ) ) ) {
            return array(
                'success' => false,
                'reply'   => __( 'Please type a message.', 'chatyllo' ),
                'mode'    => 'error',
            );
        }

        // Check cache first.
        $cache_key = 'chat_' . md5( strtolower( trim( $message ) ) );
        $cached    = Cache::instance()->get( $cache_key );
        if ( $cached !== false ) {
            return array(
                'success'     => true,
                'reply'       => $cached,
                'mode'        => 'cached',
                'tokens_used' => 0,
            );
        }

        // Determine mode: AI (premium) or FAQ Fallback (free).
        $result = null;
        if ( function_exists( 'chatyllo_premium_process_ai' )
             && chatyllo_can_use_ai()
             && $proxy->is_ai_active()
             && $settings->get( 'enabled' )
        ) {
            $result = chatyllo_premium_process_ai( $message, $history );
        }

        // Fallback to FAQ if no AI result.
        if ( null === $result ) {
            $result = $this->process_fallback( $message );
        }

        // Log the interaction.
        $elapsed_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

        // Log the interaction (respects GDPR consent and anonymization settings).
        // Nonce verified in Ajax::handle_chat() before calling this method.
        $consent_given = ! empty( $_POST['consent'] ) || ! $settings->get( 'require_chat_consent' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( $settings->get( 'log_chats' ) && $consent_given ) {
            $session_id = $this->get_session_id();
            DB::insert( 'chat_logs', array(
                'session_id'      => $session_id,
                'visitor_ip'      => $settings->get( 'anonymize_ip' ) ? '' : $this->get_visitor_ip(),
                'user_agent'      => $settings->get( 'anonymize_user_agent' ) ? '' : $this->get_user_agent(),
                'page_url'        => $this->get_page_url(),
                'referrer_url'    => $this->get_referrer_url(),
                'user_message'    => $message,
                'bot_response'    => $result['reply'],
                'response_mode'   => $result['mode'],
                'tokens_used'     => $result['tokens_used'] ?? 0,
                'response_time_ms'=> $elapsed_ms,
            ) );
        }

        // Cache successful AI responses (1 hour).
        if ( $result['success'] && $result['mode'] === 'ai' && ! empty( $result['reply'] ) ) {
            Cache::instance()->set( $cache_key, $result['reply'], HOUR_IN_SECONDS );
        }

        return $result;
    }

    /* ── Fallback Mode (Manual Q&A) ────────────────────────────────── */
    private function process_fallback( $message ) {
        $faq    = FAQ::instance();
        $result = $faq->find_best_match( $message );

        if ( $result ) {
            return array(
                'success'     => true,
                'reply'       => $result['answer'],
                'mode'        => 'faq',
                'off_topic'   => false,
                'tokens_used' => 0,
                'faq_id'      => $result['id'],
            );
        }

        return array(
            'success'     => true,
            'reply'       => Settings::instance()->get_no_match_message(),
            'mode'        => 'no_match',
            'off_topic'   => false,
            'tokens_used' => 0,
        );
    }

    /* ── Helpers ───────────────────────────────────────────────────── */
    private function get_session_id() {
        // Prefer session_id from POST (sent by widget JS).
        if ( ! empty( $_POST['session_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return sanitize_text_field( wp_unslash( $_POST['session_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
        // Fallback to cookie (legacy).
        if ( ! empty( $_COOKIE['chatyllo_session'] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE['chatyllo_session'] ) );
        }
        return wp_generate_uuid4();
    }

    private function get_visitor_ip() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip  = trim( $ips[0] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        return wp_hash( $ip );
    }

    private function get_user_agent() {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        return mb_substr( $ua, 0, 500 );
    }

    private function get_page_url() {
        // HTTP_REFERER is the page where the AJAX was triggered from.
        $url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
        return mb_substr( $url, 0, 500 );
    }

    private function get_referrer_url() {
        // Original referrer sent from the frontend JS.
        $ref = isset( $_POST['page_referrer'] ) ? esc_url_raw( wp_unslash( $_POST['page_referrer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return mb_substr( $ref, 0, 500 );
    }

    /* ── Chat statistics ───────────────────────────────────────────── */
    public function get_stats( $days = 30 ) {
        global $wpdb;
        $table = DB::table( 'chat_logs' );
        $since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        return array(
            'total_chats'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $since ) ),
            'ai_chats'         => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE response_mode = 'ai' AND created_at >= %s", $since ) ),
            'faq_chats'        => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE response_mode = 'faq' AND created_at >= %s", $since ) ),
            'total_tokens'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(tokens_used) FROM {$table} WHERE created_at >= %s", $since ) ),
            'avg_response_ms'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT AVG(response_time_ms) FROM {$table} WHERE created_at >= %s", $since ) ),
            'unique_sessions'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at >= %s", $since ) ),
        );
    }

    /* ── Cleanup old logs ──────────────────────────────────────────── */
    public function cleanup_old_logs() {
        // Premium: plan-aware retention (function in premium-only file).
        if ( function_exists( 'chatyllo_premium_get_log_retention_days' ) ) {
            $days = chatyllo_premium_get_log_retention_days();
            if ( $days < 0 ) {
                return; // Unlimited (Agency).
            }
        } else {
            $days = 30; // Free plan default.
        }

        global $wpdb;
        $table  = DB::table( 'chat_logs' );
        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );
    }
}
