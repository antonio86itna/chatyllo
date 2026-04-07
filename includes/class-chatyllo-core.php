<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core plugin orchestrator — singleton.
 */
final class Core {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /* ── Dependencies ──────────────────────────────────────────────── */
    private function load_dependencies() {
        $files = array(
            'class-chatyllo-db',
            'class-chatyllo-settings',
            'class-chatyllo-proxy',
            'class-chatyllo-indexer',
            'class-chatyllo-chat',
            'class-chatyllo-faq',
            'class-chatyllo-cache',
            'class-chatyllo-ajax',
            'class-chatyllo-widget',
        );

        if ( is_admin() ) {
            $files[] = 'class-chatyllo-admin';
        }

        foreach ( $files as $file ) {
            require_once CHATYLLO_PATH . 'includes/' . $file . '.php';
        }
    }

    /* ── Hooks ─────────────────────────────────────────────────────── */
    private function init_hooks() {
        // Register 'weekly' cron schedule (not built into WP core).
        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

        // Deactivate AI-generated FAQs if user is on free plan.
        add_action( 'admin_init', array( $this, 'check_ai_faq_status' ) );

        // GDPR: WP Privacy tools integration.
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_data_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_data_eraser' ) );
        add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );

        // Maintenance cron.
        add_action( 'chatyllo_maintenance', array( $this, 'run_maintenance' ) );
        if ( ! wp_next_scheduled( 'chatyllo_maintenance' ) ) {
            wp_schedule_event( time(), 'weekly', 'chatyllo_maintenance' );
        }

        DB::instance();
        Settings::instance();
        Proxy::instance();
        // Indexer is loaded always (for stats display) but only hooks content for paid plans.
        Indexer::instance();

        Chat::instance();
        FAQ::instance();
        Cache::instance();
        Ajax::instance();
        Widget::instance();

        if ( is_admin() ) {
            Admin::instance();
        }

        add_action( 'init', array( $this, 'maybe_upgrade' ) );
    }

    /* ── Custom cron schedules ────────────────────────────────────── */
    public function add_cron_schedules( $schedules ) {
        if ( ! isset( $schedules['weekly'] ) ) {
            $schedules['weekly'] = array(
                'interval' => 604800, // 7 days in seconds.
                'display'  => __( 'Once Weekly', 'chatyllo' ),
            );
        }
        return $schedules;
    }

    /* ── Service Status Tracking ──────────────────────────────────── */
    public static function update_status_history( $status_code ) {
        $history = get_option( 'chatyllo_status_history', array() );
        $today   = gmdate( 'Y-m-d' );
        $now     = current_time( 'mysql' );

        // Find today's entry.
        $found = false;
        foreach ( $history as &$entry ) {
            if ( $entry['date'] === $today ) {
                // Only update if status changed.
                if ( $entry['status'] !== $status_code ) {
                    $entry['status']     = $status_code;
                    $entry['changed_at'] = $now;
                }
                $entry['checked_at'] = $now;
                $found = true;
                break;
            }
        }
        unset( $entry );

        if ( ! $found ) {
            $history[] = array(
                'date'       => $today,
                'status'     => $status_code,
                'checked_at' => $now,
                'changed_at' => $now,
            );
        }

        // Keep only 30 days.
        $cutoff  = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
        $history = array_filter( $history, function( $h ) use ( $cutoff ) {
            return $h['date'] >= $cutoff;
        } );

        update_option( 'chatyllo_status_history', array_values( $history ), false );
    }

    /* ── AI FAQ downgrade protection ──────────────────────────────── */
    public function check_ai_faq_status() {
        $plan = function_exists( 'chatyllo_get_plan_name' ) ? chatyllo_get_plan_name() : 'free';
        if ( 'free' === $plan ) {
            $ai_count = FAQ::instance()->get_ai_faq_count();
            if ( $ai_count > 0 ) {
                FAQ::instance()->deactivate_ai_faqs();
            }
        }
    }

    /* ── GDPR: WP Privacy Data Exporter ───────────────────────────── */
    public function register_data_exporter( $exporters ) {
        $exporters['chatyllo'] = array(
            'exporter_friendly_name' => __( 'Chatyllo Chat Logs', 'chatyllo' ),
            'callback'               => array( $this, 'privacy_exporter' ),
        );
        return $exporters;
    }

    public function privacy_exporter( $email, $page = 1 ) {
        // We hash IPs, so we search by hashed email domain IP (best effort).
        // In practice, admins use this for manual review.
        global $wpdb;
        $table = DB::table( 'chat_logs' );
        $data  = array();

        // Get recent logs (can't match by email since we only have hashed IPs).
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            50, ( $page - 1 ) * 50
        ), ARRAY_A );

        foreach ( $logs as $log ) {
            $data[] = array(
                'group_id'    => 'chatyllo_chats',
                'group_label' => __( 'Chatyllo Chat Conversations', 'chatyllo' ),
                'item_id'     => 'chat-' . $log['id'],
                'data'        => array(
                    array( 'name' => __( 'Date', 'chatyllo' ), 'value' => $log['created_at'] ),
                    array( 'name' => __( 'User Message', 'chatyllo' ), 'value' => $log['user_message'] ),
                    array( 'name' => __( 'Bot Response', 'chatyllo' ), 'value' => $log['bot_response'] ),
                    array( 'name' => __( 'Mode', 'chatyllo' ), 'value' => $log['response_mode'] ),
                ),
            );
        }

        return array( 'data' => $data, 'done' => count( $logs ) < 50 );
    }

    public function register_data_eraser( $erasers ) {
        $erasers['chatyllo'] = array(
            'eraser_friendly_name' => __( 'Chatyllo Chat Logs', 'chatyllo' ),
            'callback'             => array( $this, 'privacy_eraser' ),
        );
        return $erasers;
    }

    public function privacy_eraser( $email, $page = 1 ) {
        // Erase all chat logs (since we can't match by email — IPs are hashed).
        // Admin should confirm this action manually.
        global $wpdb;
        $table   = DB::table( 'chat_logs' );
        $deleted = $wpdb->query( "DELETE FROM {$table} WHERE created_at < NOW()" );

        return array(
            'items_removed'  => (int) $deleted,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => true,
        );
    }

    /* ── Privacy Policy Content ────────────────────────────────────── */
    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        $content = '<h2>' . __( 'Chatyllo Chat Assistant', 'chatyllo' ) . '</h2>';
        $content .= '<p>' . __( 'When visitors use the Chatyllo chat widget on this site, the following data may be collected:', 'chatyllo' ) . '</p>';
        $content .= '<ul>';
        $content .= '<li>' . __( '<strong>Chat messages:</strong> The text of conversations between visitors and the chatbot is stored in the site database for quality improvement and support purposes.', 'chatyllo' ) . '</li>';
        $content .= '<li>' . __( '<strong>Session identifier:</strong> A randomly generated session ID is stored in the browser\'s localStorage to maintain conversation continuity. This is not linked to any personal account.', 'chatyllo' ) . '</li>';
        $content .= '<li>' . __( '<strong>IP address:</strong> Visitor IP addresses may be collected in hashed (anonymized) form, or not collected at all if the site administrator has enabled IP anonymization.', 'chatyllo' ) . '</li>';
        $content .= '<li>' . __( '<strong>Browser information:</strong> Browser type and device information may be collected for analytics purposes, unless the site administrator has enabled browser anonymization.', 'chatyllo' ) . '</li>';
        $content .= '<li>' . __( '<strong>Page URL:</strong> The page where the chat conversation took place is recorded.', 'chatyllo' ) . '</li>';
        $content .= '</ul>';
        $content .= '<p>' . __( '<strong>External service:</strong> For AI-powered responses (premium plans only), chat messages and website content context are sent to the Chatyllo AI proxy server (wpezo.com) for processing. No visitor personal data (IP, browser info) is sent to this service.', 'chatyllo' ) . '</p>';
        $content .= '<p>' . __( '<strong>Data retention:</strong> Chat logs are automatically deleted based on the site\'s configured retention period (30 to 180 days, depending on the plan). Site administrators can manually delete all chat data at any time.', 'chatyllo' ) . '</p>';
        $content .= '<p>' . __( '<strong>Consent:</strong> If the site administrator has enabled the consent requirement, a privacy notice is displayed in the chat widget before any data is logged. Visitors can use the chat without accepting, but their conversations will not be stored.', 'chatyllo' ) . '</p>';

        wp_add_privacy_policy_content( 'Chatyllo', $content );
    }

    /* ── Maintenance System ────────────────────────────────────────── */
    public function run_maintenance() {
        global $wpdb;

        // 1. Cleanup expired chat logs (retention policy).
        Chat::instance()->cleanup_old_logs();

        // 2. Cleanup expired cache entries.
        $cache_table = DB::table( 'cache' );
        $wpdb->query( "DELETE FROM {$cache_table} WHERE expires_at < NOW() AND expires_at != '0000-00-00 00:00:00'" );

        // 3. Cleanup orphan knowledge entries (posts that no longer exist).
        $kb_table = DB::table( 'knowledge' );
        $wpdb->query( "
            DELETE k FROM {$kb_table} k
            LEFT JOIN {$wpdb->posts} p ON k.source_id = p.ID AND k.source_type != 'site_info'
            WHERE k.source_type != 'site_info' AND k.source_id > 0 AND p.ID IS NULL
        " );

        // 4. Optimize tables.
        $tables = array( 'knowledge', 'faq', 'chat_logs', 'cache' );
        foreach ( $tables as $t ) {
            $full = DB::table( $t );
            $wpdb->query( "OPTIMIZE TABLE {$full}" );
        }

        // 5. Save maintenance timestamp.
        update_option( 'chatyllo_last_maintenance', current_time( 'mysql' ) );
    }

    /* ── Upgrade routine ───────────────────────────────────────────── */
    public function maybe_upgrade() {
        $stored = get_option( 'chatyllo_version', '0' );
        if ( version_compare( $stored, CHATYLLO_VERSION, '<' ) ) {
            DB::instance()->create_tables();

            // v1.1.0 migration: remove proxy_secret, clear old tokens.
            if ( version_compare( $stored, '1.1.0', '<' ) ) {
                $opts = get_option( Settings::OPTION_KEY, array() );
                if ( isset( $opts['proxy_secret'] ) ) {
                    unset( $opts['proxy_secret'] );
                    update_option( Settings::OPTION_KEY, $opts );
                }
                // Clear old auth data so fresh activation happens.
                delete_transient( 'chatyllo_api_token' );
                delete_transient( 'chatyllo_token_expires' );
            }

            update_option( 'chatyllo_version', CHATYLLO_VERSION );
        }
    }

    /* ── Activation ────────────────────────────────────────────────── */
    public static function activate() {
        DB::create_tables_static();
        update_option( 'chatyllo_version', CHATYLLO_VERSION );
        update_option( 'chatyllo_activated', time() );

        // Reindex cron is now set up per-plan in Indexer::__construct().
        // No hardcoded cron here — the Indexer handles scheduling.

        // Set default settings.
        Settings::set_defaults();

        flush_rewrite_rules();
    }

    /* ── Deactivation ──────────────────────────────────────────────── */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'chatyllo_reindex_content' );
        wp_clear_scheduled_hook( 'chatyllo_maintenance' );
        flush_rewrite_rules();
    }
}
