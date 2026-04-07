<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * FAQ Manager — manual Q&A pairs with intelligent keyword matching.
 */
final class FAQ {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /* ── CRUD ──────────────────────────────────────────────────────── */
    public function create( $data ) {
        $data = $this->sanitize_faq( $data );
        if ( empty( $data['keywords'] ) ) {
            $data['keywords'] = $this->auto_keywords( $data['question'] . ' ' . $data['answer'] );
        }
        DB::insert( 'faq', $data );
        return DB::table( 'faq' );
    }

    public function update( $id, $data ) {
        $data = $this->sanitize_faq( $data );
        if ( empty( $data['keywords'] ) ) {
            $data['keywords'] = $this->auto_keywords( $data['question'] . ' ' . $data['answer'] );
        }
        return DB::update( 'faq', $data, array( 'id' => $id ) );
    }

    public function delete( $id ) {
        return DB::delete( 'faq', array( 'id' => absint( $id ) ) );
    }

    public function get( $id ) {
        global $wpdb;
        $table = DB::table( 'faq' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
    }

    public function get_all( $active_only = false ) {
        global $wpdb;
        $table = DB::table( 'faq' );
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, id ASC", ARRAY_A );
    }

    /* ── Find best matching answer ─────────────────────────────────── */
    public function find_best_match( $query ) {
        $faqs = $this->get_all( true );
        if ( empty( $faqs ) ) {
            return null;
        }

        $query_lower  = mb_strtolower( $query );
        $query_words  = $this->tokenize( $query_lower );
        $best_match   = null;
        $best_score   = 0;

        foreach ( $faqs as $faq ) {
            $score = 0;

            // 1. Exact match (high score).
            $q_lower = mb_strtolower( $faq['question'] );
            if ( $query_lower === $q_lower ) {
                return $faq;
            }

            // 2. Contains check.
            if ( mb_strpos( $q_lower, $query_lower ) !== false || mb_strpos( $query_lower, $q_lower ) !== false ) {
                $score += 10;
            }

            // 3. Keyword matching.
            $keywords = array_filter( explode( ' ', mb_strtolower( $faq['keywords'] ) ) );
            foreach ( $keywords as $kw ) {
                if ( mb_strlen( $kw ) < 3 ) continue;
                foreach ( $query_words as $qw ) {
                    if ( $kw === $qw ) {
                        $score += 3;
                    } elseif ( mb_strpos( $kw, $qw ) !== false || mb_strpos( $qw, $kw ) !== false ) {
                        $score += 1;
                    }
                }
            }

            // 4. Question word overlap.
            $faq_words = $this->tokenize( $q_lower );
            $overlap   = count( array_intersect( $query_words, $faq_words ) );
            $score    += $overlap * 2;

            if ( $score > $best_score ) {
                $best_score = $score;
                $best_match = $faq;
            }
        }

        // Minimum threshold to avoid random matches.
        if ( $best_score < 3 ) {
            return null;
        }

        return $best_match;
    }

    /* ── Helpers ───────────────────────────────────────────────────── */
    private function sanitize_faq( $data ) {
        return array(
            'question'   => sanitize_text_field( $data['question'] ?? '' ),
            'answer'     => wp_kses_post( $data['answer'] ?? '' ),
            'keywords'   => sanitize_text_field( $data['keywords'] ?? '' ),
            'category'   => sanitize_text_field( $data['category'] ?? '' ),
            'language'   => sanitize_text_field( $data['language'] ?? '' ),
            'source'     => in_array( ( $data['source'] ?? 'manual' ), array( 'manual', 'ai' ), true )
                            ? $data['source'] : 'manual',
            'sort_order'  => absint( $data['sort_order'] ?? 0 ),
            'is_active'  => isset( $data['is_active'] ) ? (int) (bool) $data['is_active'] : 1,
        );
    }

    private function tokenize( $text ) {
        $text  = preg_replace( '/[^\p{L}\p{N}\s]/u', '', $text );
        $words = preg_split( '/\s+/', $text );
        $stop  = array( 'the','a','an','and','or','but','in','on','at','to','for','of','with','by','is','are','what','how','when','where','who','why','can','do','does','i','me','my','your','this','that','please','hi','hello','hey' );
        $words = array_diff( $words, $stop );
        return array_values( array_filter( $words, function( $w ) { return mb_strlen( $w ) > 2; } ) );
    }

    private function auto_keywords( $text ) {
        $tokens = $this->tokenize( mb_strtolower( $text ) );
        $freq   = array_count_values( $tokens );
        arsort( $freq );
        return implode( ' ', array_slice( array_keys( $freq ), 0, 15 ) );
    }

    public function get_count() {
        return DB::count( 'faq' );
    }

    public function get_active_count() {
        return DB::count( 'faq', 'is_active = 1' );
    }

    public function get_ai_faq_count() {
        return DB::count( 'faq', "source = 'ai'" );
    }

    /**
     * Get all AI-generated FAQs (for deduplication on re-generation).
     */
    public function get_existing_ai_faqs() {
        global $wpdb;
        $table = DB::table( 'faq' );
        return $wpdb->get_results( "SELECT * FROM {$table} WHERE source = 'ai'", ARRAY_A );
    }

    /**
     * Deactivate all AI-generated FAQs (on downgrade to free).
     */
    public function deactivate_ai_faqs() {
        global $wpdb;
        $table = DB::table( 'faq' );
        return $wpdb->update( $table, array( 'is_active' => 0 ), array( 'source' => 'ai' ) );
    }

    /**
     * Get distinct categories.
     */
    public function get_categories() {
        global $wpdb;
        $table = DB::table( 'faq' );
        return $wpdb->get_col( "SELECT DISTINCT category FROM {$table} WHERE category != '' ORDER BY category ASC" );
    }
}
