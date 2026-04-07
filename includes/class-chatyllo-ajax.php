<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler — all frontend and admin AJAX endpoints.
 */
final class Ajax {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Frontend (public) — both logged-in and guests.
        add_action( 'wp_ajax_chatyllo_chat', array( $this, 'handle_chat' ) );
        add_action( 'wp_ajax_nopriv_chatyllo_chat', array( $this, 'handle_chat' ) );
        add_action( 'wp_ajax_chatyllo_status', array( $this, 'handle_status' ) );
        add_action( 'wp_ajax_nopriv_chatyllo_status', array( $this, 'handle_status' ) );

        // Admin only.
        add_action( 'wp_ajax_chatyllo_save_settings', array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_chatyllo_reindex', array( $this, 'reindex' ) );
        add_action( 'wp_ajax_chatyllo_clear_cache', array( $this, 'clear_cache' ) );
        add_action( 'wp_ajax_chatyllo_save_faq', array( $this, 'save_faq' ) );
        add_action( 'wp_ajax_chatyllo_delete_faq', array( $this, 'delete_faq' ) );
        add_action( 'wp_ajax_chatyllo_get_faqs', array( $this, 'get_faqs' ) );
        add_action( 'wp_ajax_chatyllo_get_stats', array( $this, 'get_stats' ) );
        add_action( 'wp_ajax_chatyllo_refresh_ai_status', array( $this, 'refresh_ai_status' ) );
        add_action( 'wp_ajax_chatyllo_get_chat_logs', array( $this, 'get_chat_logs' ) );
        add_action( 'wp_ajax_chatyllo_generate_faqs', array( $this, 'generate_faqs' ) );
        add_action( 'wp_ajax_chatyllo_get_session', array( $this, 'get_session' ) );
        add_action( 'wp_ajax_chatyllo_search_content', array( $this, 'search_content' ) );
        add_action( 'wp_ajax_chatyllo_get_roles', array( $this, 'get_roles' ) );
        add_action( 'wp_ajax_chatyllo_get_post_types', array( $this, 'get_post_types' ) );
        add_action( 'wp_ajax_chatyllo_get_service_status', array( $this, 'get_service_status' ) );
        add_action( 'wp_ajax_chatyllo_get_detailed_stats', array( $this, 'get_detailed_stats' ) );
        add_action( 'wp_ajax_chatyllo_export_data', array( $this, 'export_data' ) );
        add_action( 'wp_ajax_chatyllo_run_maintenance', array( $this, 'run_maintenance' ) );
    }

    /* ═══════════════════════════════════════════════════════════════
     * FRONTEND ENDPOINTS
     * ═══════════════════════════════════════════════════════════════ */

    public function handle_chat() {
        check_ajax_referer( 'chatyllo_public_nonce', 'nonce' );

        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
        $history = isset( $_POST['history'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['history'] ) ), true ) : array();

        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Empty message', 'chatyllo' ) ) );
        }

        // Rate limit: max 20 requests per minute per IP.
        if ( $this->is_rate_limited() ) {
            wp_send_json_error( array(
                'message' => __( 'Too many requests. Please slow down.', 'chatyllo' ),
            ) );
        }

        $result = Chat::instance()->process( $message, $history ?: array() );

        wp_send_json_success( $result );
    }

    public function handle_status() {
        check_ajax_referer( 'chatyllo_public_nonce', 'nonce' );
        wp_send_json_success( array(
            'ai_active' => Proxy::instance()->is_ai_active(),
        ) );
    }

    /* ═══════════════════════════════════════════════════════════════
     * ADMIN ENDPOINTS
     * ═══════════════════════════════════════════════════════════════ */

    public function save_settings() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        $data = isset( $_POST['settings'] ) ? (array) json_decode( sanitize_text_field( wp_unslash( $_POST['settings'] ) ), true ) : array();
        Settings::instance()->save( $data );
        wp_send_json_success( array( 'message' => __( 'Settings saved.', 'chatyllo' ) ) );
    }

    public function reindex() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        if ( ! chatyllo_can_manual_reindex() ) {
            wp_send_json_error( array( 'message' => __( 'Manual reindex requires a Business plan or higher.', 'chatyllo' ) ) );
        }

        Indexer::instance()->full_reindex();
        $stats = Indexer::instance()->get_stats();

        wp_send_json_success( array(
            'message' => __( 'Knowledge base rebuilt.', 'chatyllo' ),
            'stats'   => $stats,
        ) );
    }

    public function clear_cache() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }
        Cache::instance()->flush();
        wp_send_json_success( array( 'message' => __( 'Cache cleared.', 'chatyllo' ) ) );
    }

    public function save_faq() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        $faq_data = isset( $_POST['faq'] ) ? (array) json_decode( sanitize_text_field( wp_unslash( $_POST['faq'] ) ), true ) : array();
        $id       = isset( $faq_data['id'] ) ? absint( $faq_data['id'] ) : 0;

        // Enforce FAQ limit per plan (only for new FAQs, not updates).
        if ( 0 === $id ) {
            $limits    = chatyllo_get_plan_limits();
            $faq_limit = $limits['faq_limit'];

            if ( $faq_limit > 0 ) {
                $current_count = FAQ::instance()->get_count();
                if ( $current_count >= $faq_limit ) {
                    wp_send_json_error( array(
                        'message' => sprintf(
                            /* translators: %d: max FAQ entries allowed */
                            __( 'FAQ limit reached (%d). Upgrade your plan for unlimited FAQs.', 'chatyllo' ),
                            $faq_limit
                        ),
                    ) );
                }
            }
        }

        if ( $id > 0 ) {
            FAQ::instance()->update( $id, $faq_data );
            $msg = __( 'FAQ updated.', 'chatyllo' );
        } else {
            FAQ::instance()->create( $faq_data );
            $msg = __( 'FAQ created.', 'chatyllo' );
        }

        wp_send_json_success( array( 'message' => $msg ) );
    }

    public function delete_faq() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( $id > 0 ) {
            FAQ::instance()->delete( $id );
        }
        wp_send_json_success( array( 'message' => __( 'FAQ deleted.', 'chatyllo' ) ) );
    }

    public function get_faqs() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }
        wp_send_json_success( array( 'faqs' => FAQ::instance()->get_all() ) );
    }

    public function get_stats() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        $days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
        wp_send_json_success( array(
            'chat_stats'     => Chat::instance()->get_stats( $days ),
            'knowledge_stats'=> Indexer::instance()->get_stats(),
            'faq_count'      => FAQ::instance()->get_count(),
            'ai_active'      => Proxy::instance()->is_ai_active(),
        ) );
    }

    public function refresh_ai_status() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        $active = Proxy::instance()->refresh_status();
        $usage  = get_transient( 'chatyllo_usage' ) ?: array(
            'daily_used' => 0, 'daily_limit' => 0, 'monthly_used' => 0, 'monthly_limit' => 0,
        );
        wp_send_json_success( array(
            'ai_active'    => $active,
            'faq_gen_used' => (int) get_transient( 'chatyllo_faq_gen_used' ),
            'usage'        => $usage,
        ) );
    }

    public function get_chat_logs() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        global $wpdb;
        $table  = DB::table( 'chat_logs' );
        $page   = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
        $per    = 20;
        $offset = ( $page - 1 ) * $per;

        // Group by session — one row per conversation.
        $total = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table}" );

        $sessions = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                session_id,
                MIN(created_at) AS started_at,
                MAX(created_at) AS ended_at,
                COUNT(*) AS message_count,
                SUM(tokens_used) AS total_tokens,
                ROUND(AVG(response_time_ms)) AS avg_response_ms,
                GROUP_CONCAT(DISTINCT response_mode) AS modes,
                SUBSTRING_INDEX(GROUP_CONCAT(user_message ORDER BY created_at ASC SEPARATOR '|||'), '|||', 1) AS first_message,
                SUBSTRING_INDEX(GROUP_CONCAT(page_url ORDER BY created_at ASC SEPARATOR '|||'), '|||', 1) AS page_url
            FROM {$table}
            GROUP BY session_id
            ORDER BY MAX(created_at) DESC
            LIMIT %d OFFSET %d",
            $per,
            $offset
        ), ARRAY_A );

        wp_send_json_success( array(
            'sessions'   => $sessions,
            'total'      => $total,
            'page'       => $page,
            'total_pages'=> (int) ceil( $total / $per ),
        ) );
    }

    /* ═══════════════════════════════════════════════════════════════
     * SESSION DETAILS (for chat log modal)
     * ═══════════════════════════════════════════════════════════════ */

    public function get_session() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
        if ( empty( $session_id ) ) {
            wp_send_json_error( array( 'message' => 'Missing session_id' ) );
        }

        global $wpdb;
        $table = DB::table( 'chat_logs' );
        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE session_id = %s ORDER BY created_at ASC",
            $session_id
        ), ARRAY_A );

        if ( empty( $messages ) ) {
            wp_send_json_error( array( 'message' => 'Session not found' ) );
        }

        // Extract session metadata from first message.
        $first = $messages[0];
        $last  = end( $messages );

        // Parse user agent for browser/OS info.
        $ua_info = $this->parse_user_agent( $first['user_agent'] ?? '' );

        $session_data = array(
            'session_id'    => $session_id,
            'messages'      => $messages,
            'message_count' => count( $messages ),
            'started_at'    => $first['created_at'],
            'ended_at'      => $last['created_at'],
            'page_url'      => $first['page_url'] ?? '',
            'referrer_url'  => $first['referrer_url'] ?? '',
            'visitor_ip'    => $first['visitor_ip'],
            'browser'       => $ua_info['browser'],
            'os'            => $ua_info['os'],
            'device'        => $ua_info['device'],
            'total_tokens'  => array_sum( array_column( $messages, 'tokens_used' ) ),
            'avg_response'  => (int) ( array_sum( array_column( $messages, 'response_time_ms' ) ) / count( $messages ) ),
            'modes_used'    => array_unique( array_column( $messages, 'response_mode' ) ),
        );

        wp_send_json_success( $session_data );
    }

    /**
     * Parse user agent string into browser, OS, device info.
     */
    private function parse_user_agent( $ua ) {
        $browser = 'Unknown';
        $os      = 'Unknown';
        $device  = 'Desktop';

        if ( empty( $ua ) ) {
            return compact( 'browser', 'os', 'device' );
        }

        // Browser detection.
        if ( preg_match( '/Edg\//i', $ua ) )           $browser = 'Edge';
        elseif ( preg_match( '/OPR|Opera/i', $ua ) )   $browser = 'Opera';
        elseif ( preg_match( '/Chrome/i', $ua ) )       $browser = 'Chrome';
        elseif ( preg_match( '/Safari/i', $ua ) && ! preg_match( '/Chrome/i', $ua ) ) $browser = 'Safari';
        elseif ( preg_match( '/Firefox/i', $ua ) )      $browser = 'Firefox';

        // OS detection.
        if ( preg_match( '/Windows/i', $ua ) )      $os = 'Windows';
        elseif ( preg_match( '/Mac OS/i', $ua ) )   $os = 'macOS';
        elseif ( preg_match( '/Linux/i', $ua ) )    $os = 'Linux';
        elseif ( preg_match( '/Android/i', $ua ) )  $os = 'Android';
        elseif ( preg_match( '/iPhone|iPad/i', $ua ) ) $os = 'iOS';

        // Device detection.
        if ( preg_match( '/Mobile|Android|iPhone/i', $ua ) ) $device = 'Mobile';
        elseif ( preg_match( '/iPad|Tablet/i', $ua ) )       $device = 'Tablet';

        return compact( 'browser', 'os', 'device' );
    }

    /* ═══════════════════════════════════════════════════════════════
     * AI FAQ GENERATION
     * ═══════════════════════════════════════════════════════════════ */

    public function generate_faqs() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'chatyllo' ) ) );
        }

        if ( ! function_exists( 'chatyllo_can_generate_faqs' ) || ! chatyllo_can_generate_faqs() ) {
            wp_send_json_error( array( 'message' => __( 'AI FAQ generation requires a Starter plan or higher.', 'chatyllo' ) ) );
        }

        $language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : get_locale();

        // Build full context from knowledge base (max 6000 tokens).
        $context = $this->build_faq_context();
        if ( empty( $context ) ) {
            wp_send_json_error( array( 'message' => __( 'Knowledge base is empty. Please index your content first from the Knowledge Base page.', 'chatyllo' ) ) );
        }

        $site_info = array(
            'name' => get_bloginfo( 'name' ),
            'url'  => home_url(),
        );

        $response = chatyllo_premium_generate_faqs( $context, $language, $site_info );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        if ( ! empty( $response['error'] ) ) {
            wp_send_json_error( array( 'message' => $response['error'] ) );
        }

        $faqs = $response['faqs'] ?? array();
        if ( empty( $faqs ) ) {
            $debug = ! empty( $response['ai_reply'] ) ? ' AI response: ' . substr( $response['ai_reply'], 0, 200 ) : '';
            $error_msg = ! empty( $response['error'] ) ? $response['error'] : __( 'AI did not generate any FAQs.', 'chatyllo' );
            wp_send_json_error( array( 'message' => $error_msg . $debug ) );
        }

        // Save FAQs with deduplication.
        $saved       = 0;
        $updated     = 0;
        $faq_manager = FAQ::instance();
        $existing_ai = $faq_manager->get_existing_ai_faqs();

        foreach ( $faqs as $faq_data ) {
            $question = trim( $faq_data['question'] ?? '' );
            if ( empty( $question ) ) continue;

            // Check for duplicate (same question, case-insensitive).
            $duplicate = false;
            foreach ( $existing_ai as $existing ) {
                if ( mb_strtolower( trim( $existing['question'] ) ) === mb_strtolower( $question ) ) {
                    $faq_manager->update( $existing['id'], array(
                        'question' => $question,
                        'answer'   => $faq_data['answer'] ?? '',
                        'keywords' => $faq_data['keywords'] ?? '',
                        'category' => $faq_data['category'] ?? '',
                        'language' => $language,
                        'source'   => 'ai',
                        'is_active'=> 1,
                    ) );
                    $duplicate = true;
                    $updated++;
                    break;
                }
            }

            if ( ! $duplicate ) {
                $faq_manager->create( array(
                    'question'   => $question,
                    'answer'     => $faq_data['answer'] ?? '',
                    'keywords'   => $faq_data['keywords'] ?? '',
                    'category'   => $faq_data['category'] ?? '',
                    'sort_order' => 0,
                    'is_active'  => 1,
                    'language'   => $language,
                    'source'     => 'ai',
                ) );
                $saved++;
            }
        }

        wp_send_json_success( array(
            'message'       => sprintf(
                /* translators: %1$d: number of new FAQs created, %2$d: number of FAQs updated */
                __( 'Done! %1$d new FAQs created, %2$d updated.', 'chatyllo' ),
                $saved, $updated
            ),
            'created'       => $saved,
            'updated'       => $updated,
            'faq_gen_used'  => $response['faq_gen_used'] ?? 0,
            'faq_gen_limit' => $response['faq_gen_limit'] ?? 0,
        ) );
    }

    /**
     * Build full knowledge base context for FAQ generation (up to 6000 tokens).
     */
    private function build_faq_context() {
        global $wpdb;
        $table = DB::table( 'knowledge' );
        $rows  = $wpdb->get_results( "SELECT title, content, source_type FROM {$table} ORDER BY source_type, id ASC" );

        if ( empty( $rows ) ) {
            return '';
        }

        $context      = '';
        $total_tokens = 0;
        $max_tokens   = 6000;

        foreach ( $rows as $row ) {
            $tokens_approx = (int) ceil( strlen( $row->content ) / 3.5 );
            if ( $total_tokens + $tokens_approx > $max_tokens ) break;
            $context      .= "--- {$row->title} [{$row->source_type}] ---\n{$row->content}\n\n";
            $total_tokens += $tokens_approx;
        }

        return trim( $context );
    }

    /* ═══════════════════════════════════════════════════════════════
     * DYNAMIC SELECTOR ENDPOINTS
     * ═══════════════════════════════════════════════════════════════ */

    public function search_content() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $search    = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'any';
        $ids       = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';

        $args = array(
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( 'any' !== $post_type ) {
            $args['post_type'] = $post_type;
        } else {
            $args['post_type'] = array( 'post', 'page' );
        }

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        // If specific IDs requested (for loading saved selections).
        if ( ! empty( $ids ) ) {
            $args['post__in']       = array_map( 'absint', explode( ',', $ids ) );
            $args['posts_per_page'] = -1;
            $args['post_type']      = 'any';
            unset( $args['s'] );
        }

        $posts   = get_posts( $args );
        $results = array();

        foreach ( $posts as $p ) {
            $results[] = array(
                'id'    => $p->ID,
                'title' => $p->post_title ?: __( '(no title)', 'chatyllo' ),
                'type'  => $p->post_type,
            );
        }

        wp_send_json_success( array( 'items' => $results ) );
    }

    public function get_roles() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $wp_roles = wp_roles();
        $results  = array();
        foreach ( $wp_roles->role_names as $slug => $name ) {
            $results[] = array( 'slug' => $slug, 'name' => $name );
        }

        wp_send_json_success( array( 'items' => $results ) );
    }

    public function get_post_types() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $types   = get_post_types( array( 'public' => true ), 'objects' );
        $exclude = array( 'post', 'page', 'attachment' );
        $results = array();

        foreach ( $types as $slug => $obj ) {
            if ( in_array( $slug, $exclude, true ) ) {
                continue;
            }
            $results[] = array( 'slug' => $slug, 'label' => $obj->label );
        }

        wp_send_json_success( array( 'items' => $results ) );
    }

    /* ═══════════════════════════════════════════════════════════════
     * SERVICE STATUS
     * ═══════════════════════════════════════════════════════════════ */

    public function get_service_status() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $refresh = ! empty( $_POST['refresh'] );

        // Force fresh check if requested.
        if ( $refresh && function_exists( 'chatyllo_premium_refresh_status' ) ) {
            chatyllo_premium_refresh_status();
        }

        $current = chatyllo_get_current_status();
        $history = get_option( 'chatyllo_status_history', array() );

        // Keep only last 30 days.
        $cutoff = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
        $history = array_filter( $history, function( $h ) use ( $cutoff ) {
            return $h['date'] >= $cutoff;
        } );

        wp_send_json_success( array(
            'current' => $current,
            'history' => array_values( $history ),
        ) );
    }

    /* ═══════════════════════════════════════════════════════════════
     * DETAILED STATISTICS
     * ═══════════════════════════════════════════════════════════════ */

    public function get_detailed_stats() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        global $wpdb;
        $table = DB::table( 'chat_logs' );
        $days  = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
        $since = ( $days > 0 ) ? gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) ) : '1970-01-01';

        // Response mode breakdown.
        $modes = $wpdb->get_results( $wpdb->prepare(
            "SELECT response_mode, COUNT(*) AS cnt FROM {$table} WHERE created_at >= %s GROUP BY response_mode",
            $since
        ), ARRAY_A );

        // Daily trend (last N days).
        $daily_trend = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM {$table} WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY day ASC",
            $since
        ), ARRAY_A );

        // Session stats.
        $session_stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) AS total_sessions,
                    AVG(msg_count) AS avg_msgs_per_session
             FROM (SELECT session_id, COUNT(*) AS msg_count FROM {$table} WHERE created_at >= %s GROUP BY session_id) sub",
            $since
        ), ARRAY_A );

        // Browser breakdown (parse user_agent).
        $user_agents = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_agent FROM {$table} WHERE created_at >= %s AND user_agent != ''",
            $since
        ) );

        $browsers = array( 'Chrome' => 0, 'Safari' => 0, 'Firefox' => 0, 'Edge' => 0, 'Opera' => 0, 'Other' => 0 );
        $devices  = array( 'Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0 );

        foreach ( $user_agents as $ua ) {
            if ( preg_match( '/Edg\//i', $ua ) )           $browsers['Edge']++;
            elseif ( preg_match( '/OPR|Opera/i', $ua ) )   $browsers['Opera']++;
            elseif ( preg_match( '/Chrome/i', $ua ) )       $browsers['Chrome']++;
            elseif ( preg_match( '/Safari/i', $ua ) && ! preg_match( '/Chrome/i', $ua ) ) $browsers['Safari']++;
            elseif ( preg_match( '/Firefox/i', $ua ) )      $browsers['Firefox']++;
            else                                             $browsers['Other']++;

            if ( preg_match( '/Mobile|Android|iPhone/i', $ua ) )      $devices['Mobile']++;
            elseif ( preg_match( '/iPad|Tablet/i', $ua ) )             $devices['Tablet']++;
            else                                                        $devices['Desktop']++;
        }

        // Performance.
        $perf = $wpdb->get_row( $wpdb->prepare(
            "SELECT AVG(response_time_ms) AS avg_ms, SUM(tokens_used) AS total_tokens, COUNT(*) AS total_msgs FROM {$table} WHERE created_at >= %s",
            $since
        ), ARRAY_A );

        // Top pages.
        $top_pages = $wpdb->get_results( $wpdb->prepare(
            "SELECT page_url, COUNT(*) AS cnt FROM {$table} WHERE created_at >= %s AND page_url != '' GROUP BY page_url ORDER BY cnt DESC LIMIT 5",
            $since
        ), ARRAY_A );

        wp_send_json_success( array(
            'modes'         => $modes,
            'daily_trend'   => $daily_trend,
            'sessions'      => $session_stats,
            'browsers'      => $browsers,
            'devices'       => $devices,
            'performance'   => $perf,
            'top_pages'     => $top_pages,
        ) );
    }

    /* ═══════════════════════════════════════════════════════════════
     * DATA EXPORT
     * ═══════════════════════════════════════════════════════════════ */

    public function export_data() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        $type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'all';

        $export = array(
            '_chatyllo_export' => true,
            '_version'         => CHATYLLO_VERSION,
            '_site'            => home_url(),
            '_date'            => current_time( 'mysql' ),
            '_watermark'       => 'Generated by Chatyllo - https://wpezo.com/plugins/chatyllo',
        );

        if ( in_array( $type, array( 'all', 'settings' ), true ) ) {
            $export['settings'] = Settings::instance()->get_all();
        }

        if ( in_array( $type, array( 'all', 'faq' ), true ) ) {
            $export['faqs'] = FAQ::instance()->get_all();
        }

        if ( in_array( $type, array( 'all', 'stats' ), true ) ) {
            $export['chat_stats'] = Chat::instance()->get_stats( 9999 );
            $export['knowledge_stats'] = Indexer::instance()->get_stats();
        }

        wp_send_json_success( $export );
    }

    /* ═══════════════════════════════════════════════════════════════
     * MAINTENANCE
     * ═══════════════════════════════════════════════════════════════ */

    public function run_maintenance() {
        check_ajax_referer( 'chatyllo_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        \Chatyllo\Core::instance()->run_maintenance();

        wp_send_json_success( array(
            'message'        => __( 'Maintenance completed successfully.', 'chatyllo' ),
            'last_maintenance' => get_option( 'chatyllo_last_maintenance', __( 'Never', 'chatyllo' ) ),
        ) );
    }

    /* ── Rate limiter ──────────────────────────────────────────────── */
    private function is_rate_limited() {
        $ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $key = 'chatyllo_rl_' . md5( $ip );
        $count = (int) get_transient( $key );
        if ( $count >= 20 ) {
            return true;
        }
        set_transient( $key, $count + 1, 60 );
        return false;
    }
}
