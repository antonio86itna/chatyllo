<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Content Indexer — crawls WordPress content and builds the knowledge base.
 *
 * Indexes: posts, pages, products (WooCommerce), custom post types,
 * site settings, menus, widgets. Supports Elementor content.
 * Auto-updates on save_post and via scheduled cron.
 */
final class Indexer {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Premium: set up indexer hooks (function is in premium-only file).
        if ( function_exists( 'chatyllo_premium_setup_indexer_hooks' ) ) {
            chatyllo_premium_setup_indexer_hooks( $this );
        }
    }

    /* ── Full reindex (Starter+ — only available in premium build) ── */
    public function full_reindex() {
        if ( ! function_exists( 'chatyllo_can_index' ) || ! chatyllo_can_index() ) {
            return;
        }

        $settings = Settings::instance();

        // Clear old knowledge.
        DB::truncate( 'knowledge' );

        // 1. Site information.
        if ( $settings->get( 'index_site_info' ) ) {
            $this->index_site_info();
        }

        // 2. Posts.
        if ( $settings->get( 'index_posts' ) ) {
            $this->index_post_type( 'post' );
        }

        // 3. Pages.
        if ( $settings->get( 'index_pages' ) ) {
            $this->index_post_type( 'page' );
        }

        // 4. WooCommerce products.
        if ( $settings->get( 'index_products' ) && class_exists( 'WooCommerce' ) ) {
            $this->index_post_type( 'product' );
        }

        // 5. Custom post types (Business+ — delegated to premium helper).
        $custom = function_exists( 'chatyllo_premium_get_custom_cpt_types' )
            ? chatyllo_premium_get_custom_cpt_types()
            : '';
        if ( ! empty( $custom ) ) {
            $types = array_map( 'trim', explode( ',', $custom ) );
            foreach ( $types as $type ) {
                if ( post_type_exists( $type ) ) {
                    $this->index_post_type( $type );
                }
            }
        }

        update_option( 'chatyllo_last_indexed', current_time( 'mysql' ) );
    }

    /* ── Index a post type ─────────────────────────────────────────── */
    private function index_post_type( $post_type ) {
        $settings   = Settings::instance();
        $exclude    = $settings->get( 'exclude_ids' );
        $exclude_ids = ! empty( $exclude ) ? array_map( 'absint', explode( ',', $exclude ) ) : array();

        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__not_in'   => $exclude_ids,
            'fields'         => 'ids',
        );

        $post_ids = get_posts( $args );

        foreach ( $post_ids as $post_id ) {
            $this->index_single_post( $post_id );
        }
    }

    /* ── Index a single post ───────────────────────────────────────── */
    public function index_single_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return;
        }

        $content = $this->extract_content( $post );
        $hash    = md5( $content );

        // Skip if content hasn't changed.
        global $wpdb;
        $table    = DB::table( 'knowledge' );
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT content_hash FROM {$table} WHERE source_type = %s AND source_id = %d LIMIT 1",
            $post->post_type,
            $post_id
        ) );

        if ( $existing === $hash ) {
            return; // Content unchanged.
        }

        // Remove old entries for this post.
        DB::delete( 'knowledge', array(
            'source_type' => $post->post_type,
            'source_id'   => $post_id,
        ) );

        // Chunk content for large posts.
        $chunks = $this->chunk_content( $content, Settings::instance()->get( 'max_chunk_size' ) );
        $title  = $post->post_title;

        // Extract keywords.
        $keywords = $this->extract_keywords( $title . ' ' . $content );

        // Add WooCommerce product data.
        $extra = '';
        if ( $post->post_type === 'product' && class_exists( 'WooCommerce' ) ) {
            $extra = $this->extract_product_data( $post_id );
        }

        foreach ( $chunks as $i => $chunk ) {
            $full_chunk = $chunk;
            if ( $i === 0 && ! empty( $extra ) ) {
                $full_chunk = $extra . "\n\n" . $chunk;
            }

            DB::insert( 'knowledge', array(
                'source_type'  => $post->post_type,
                'source_id'    => $post_id,
                'title'        => $title,
                'content'      => $full_chunk,
                'keywords'     => $keywords,
                'content_hash' => $hash,
                'tokens_approx'=> $this->estimate_tokens( $full_chunk ),
                'language'     => '',
                'indexed_at'   => current_time( 'mysql' ),
            ) );
        }
    }

    /* ── Extract content from a post ───────────────────────────────── */
    private function extract_content( $post ) {
        $content = $post->post_content;

        // Elementor: extract from meta if available.
        $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
        if ( ! empty( $elementor_data ) ) {
            $content = $this->parse_elementor_data( $elementor_data );
        }

        // Strip shortcodes, tags, extra whitespace.
        $content = do_shortcode( $content );
        $content = wp_strip_all_tags( $content );
        $content = preg_replace( '/\s+/', ' ', $content );
        $content = trim( $content );

        // Add excerpt if exists.
        if ( ! empty( $post->post_excerpt ) ) {
            $content = $post->post_excerpt . "\n\n" . $content;
        }

        // Add categories and tags.
        $terms = $this->get_post_terms( $post->ID );
        if ( ! empty( $terms ) ) {
            $content .= "\n\nCategories/Tags: " . $terms;
        }

        return $content;
    }

    /* ── Parse Elementor JSON data ─────────────────────────────────── */
    private function parse_elementor_data( $data ) {
        if ( is_string( $data ) ) {
            $data = json_decode( $data, true );
        }
        if ( ! is_array( $data ) ) {
            return '';
        }

        $text = '';
        foreach ( $data as $element ) {
            if ( ! empty( $element['settings'] ) ) {
                foreach ( $element['settings'] as $key => $value ) {
                    if ( is_string( $value ) && strlen( $value ) > 5 ) {
                        $stripped = wp_strip_all_tags( $value );
                        if ( ! empty( $stripped ) && strlen( $stripped ) > 5 ) {
                            $text .= $stripped . ' ';
                        }
                    }
                }
            }
            if ( ! empty( $element['elements'] ) ) {
                $text .= $this->parse_elementor_data( $element['elements'] );
            }
        }

        return trim( $text );
    }

    /* ── Extract WooCommerce product data ──────────────────────────── */
    private function extract_product_data( $post_id ) {
        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return '';
        }

        $data = array();
        $data[] = 'Product: ' . $product->get_name();
        $data[] = 'Type: ' . $product->get_type();

        // Price — handle variable products with plain text (not HTML).
        if ( $product->is_type( 'variable' ) ) {
            $prices = $product->get_variation_prices( true );
            if ( ! empty( $prices['price'] ) ) {
                $min = min( $prices['price'] );
                $max = max( $prices['price'] );
                $data[] = 'Price: ' . wc_price( $min ) . ' - ' . wc_price( $max );
            }
        } else {
            $data[] = 'Price: ' . wp_strip_all_tags( $product->get_price_html() );
        }

        $sku = $product->get_sku();
        if ( $sku ) {
            $data[] = 'SKU: ' . $sku;
        }
        $data[] = 'Stock: ' . ( $product->is_in_stock() ? 'In stock' : 'Out of stock' );

        $short_desc = wp_strip_all_tags( $product->get_short_description() );
        if ( $short_desc ) {
            $data[] = 'Short description: ' . $short_desc;
        }

        $categories = wc_get_product_category_list( $post_id );
        if ( $categories ) {
            $data[] = 'Categories: ' . wp_strip_all_tags( $categories );
        }

        $tags = wc_get_product_tag_list( $post_id );
        if ( $tags ) {
            $data[] = 'Tags: ' . wp_strip_all_tags( $tags );
        }

        // Product attributes — get labels, not IDs.
        $attributes = $product->get_attributes();
        if ( ! empty( $attributes ) ) {
            foreach ( $attributes as $attr ) {
                if ( is_a( $attr, 'WC_Product_Attribute' ) ) {
                    $name = wc_attribute_label( $attr->get_name() );
                    if ( $attr->is_taxonomy() ) {
                        $terms = array();
                        foreach ( $attr->get_terms() as $term ) {
                            $terms[] = $term->name;
                        }
                        $value = implode( ', ', $terms );
                    } else {
                        $value = implode( ', ', $attr->get_options() );
                    }
                    $data[] = $name . ': ' . $value;
                }
            }
        }

        // Weight and dimensions if available.
        if ( $product->has_weight() ) {
            $data[] = 'Weight: ' . $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit' );
        }
        if ( $product->has_dimensions() ) {
            $data[] = 'Dimensions: ' . wc_format_dimensions( $product->get_dimensions( false ) );
        }

        return implode( "\n", $data );
    }

    /* ── Index site information ─────────────────────────────────────── */
    private function index_site_info() {
        $info = array();
        $info[] = 'Website name: ' . get_bloginfo( 'name' );
        $info[] = 'Tagline: ' . get_bloginfo( 'description' );
        $info[] = 'URL: ' . home_url();
        $info[] = 'Language: ' . get_bloginfo( 'language' );

        // Contact info from customizer / options.
        $admin_email = get_option( 'admin_email' );
        if ( $admin_email ) {
            $info[] = 'Contact email: ' . $admin_email;
        }

        // WooCommerce store info.
        if ( class_exists( 'WooCommerce' ) ) {
            $info[] = 'Store type: WooCommerce enabled';
            $info[] = 'Currency: ' . get_woocommerce_currency();
            $address = array(
                get_option( 'woocommerce_store_address' ),
                get_option( 'woocommerce_store_city' ),
                get_option( 'woocommerce_store_postcode' ),
                WC()->countries->get_base_country(),
            );
            $address = array_filter( $address );
            if ( ! empty( $address ) ) {
                $info[] = 'Store address: ' . implode( ', ', $address );
            }
        }

        // Navigation menus.
        $menus = wp_get_nav_menus();
        foreach ( $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id );
            if ( $items ) {
                $titles = array_map( function( $item ) {
                    return $item->title;
                }, $items );
                $info[] = 'Menu "' . $menu->name . '": ' . implode( ', ', $titles );
            }
        }

        $content = implode( "\n", $info );

        DB::insert( 'knowledge', array(
            'source_type'  => 'site_info',
            'source_id'    => 0,
            'title'        => 'Site Information',
            'content'      => $content,
            'keywords'     => 'site info contact about store',
            'content_hash' => md5( $content ),
            'tokens_approx'=> $this->estimate_tokens( $content ),
            'language'     => '',
            'indexed_at'   => current_time( 'mysql' ),
        ) );
    }

    /* ── Hooks (only registered in premium via chatyllo_premium_setup_indexer_hooks) */
    public function on_save_post( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        $settings     = Settings::instance();
        $allowed_types = array();
        if ( $settings->get( 'index_posts' ) ) $allowed_types[] = 'post';
        if ( $settings->get( 'index_pages' ) ) $allowed_types[] = 'page';
        if ( $settings->get( 'index_products' ) ) $allowed_types[] = 'product';

        $custom = function_exists( 'chatyllo_premium_get_custom_cpt_types' )
            ? chatyllo_premium_get_custom_cpt_types()
            : '';
        if ( ! empty( $custom ) ) {
            $allowed_types = array_merge( $allowed_types, array_map( 'trim', explode( ',', $custom ) ) );
        }

        if ( in_array( $post->post_type, $allowed_types, true ) ) {
            if ( $post->post_status === 'publish' ) {
                $this->index_single_post( $post_id );
            } else {
                DB::delete( 'knowledge', array(
                    'source_type' => $post->post_type,
                    'source_id'   => $post_id,
                ) );
            }
        }
    }

    public function on_delete_post( $post_id ) {
        global $wpdb;
        $table = DB::table( 'knowledge' );
        $wpdb->delete( $table, array( 'source_id' => $post_id ) );
    }

    public function on_save_product( $product_id ) {
        if ( Settings::instance()->get( 'index_products' ) ) {
            $this->index_single_post( $product_id );
        }
    }

    /* ── Search knowledge base (RAG-like retrieval) ────────────────── */
    public function search( $query, $limit = 5 ) {
        global $wpdb;
        $table = DB::table( 'knowledge' );

        // Extract search terms.
        $terms = $this->extract_search_terms( $query );
        if ( empty( $terms ) ) {
            return array();
        }

        // Build FULLTEXT-like search with LIKE fallback.
        $where_parts = array();
        $values      = array();
        foreach ( $terms as $term ) {
            $like          = '%' . $wpdb->esc_like( $term ) . '%';
            $where_parts[] = '(content LIKE %s OR title LIKE %s OR keywords LIKE %s)';
            $values[]      = $like;
            $values[]      = $like;
            $values[]      = $like;
        }

        $where = implode( ' OR ', $where_parts );

        // Score by number of matching terms.
        $score_parts = array();
        foreach ( $terms as $term ) {
            $like          = '%' . $wpdb->esc_like( $term ) . '%';
            $score_parts[] = "(CASE WHEN content LIKE %s THEN 2 ELSE 0 END)";
            $score_parts[] = "(CASE WHEN title LIKE %s THEN 3 ELSE 0 END)";
            $score_parts[] = "(CASE WHEN keywords LIKE %s THEN 1 ELSE 0 END)";
            $values[]      = $like;
            $values[]      = $like;
            $values[]      = $like;
        }
        $score = implode( ' + ', $score_parts );

        $sql = "SELECT *, ({$score}) as relevance
                FROM {$table}
                WHERE {$where}
                ORDER BY relevance DESC
                LIMIT %d";

        $values[] = $limit;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

        return $results ?: array();
    }

    /* ── Get full context string for AI ─────────────────────────────── */
    public function build_context( $query, $max_tokens = 3000 ) {
        $results = $this->search( $query, 10 );
        if ( empty( $results ) ) {
            return '';
        }

        $context      = '';
        $total_tokens = 0;

        foreach ( $results as $row ) {
            if ( $total_tokens + $row->tokens_approx > $max_tokens ) {
                break;
            }
            $context      .= "--- {$row->title} ---\n{$row->content}\n\n";
            $total_tokens += $row->tokens_approx;
        }

        return trim( $context );
    }

    /* ── Stats ─────────────────────────────────────────────────────── */
    public function get_stats() {
        global $wpdb;
        $table = DB::table( 'knowledge' );

        return array(
            'total_entries'  => DB::count( 'knowledge' ),
            'total_tokens'   => (int) $wpdb->get_var( "SELECT SUM(tokens_approx) FROM {$table}" ),
            'post_count'     => DB::count( 'knowledge', "source_type = 'post'" ),
            'page_count'     => DB::count( 'knowledge', "source_type = 'page'" ),
            'product_count'  => DB::count( 'knowledge', "source_type = 'product'" ),
            'last_indexed'   => get_option( 'chatyllo_last_indexed', __( 'Never', 'chatyllo' ) ),
        );
    }

    /* ── Utility ───────────────────────────────────────────────────── */
    private function chunk_content( $text, $max_words = 800 ) {
        $words  = explode( ' ', $text );
        $chunks = array();
        $chunk  = array();

        foreach ( $words as $word ) {
            $chunk[] = $word;
            if ( count( $chunk ) >= $max_words ) {
                $chunks[] = implode( ' ', $chunk );
                $chunk    = array();
            }
        }
        if ( ! empty( $chunk ) ) {
            $chunks[] = implode( ' ', $chunk );
        }

        return $chunks;
    }

    private function extract_keywords( $text ) {
        $text  = strtolower( $text );
        $text  = preg_replace( '/[^a-z0-9\s]/', '', $text );
        $words = explode( ' ', $text );

        // Remove stop words.
        $stop  = array( 'the','a','an','and','or','but','in','on','at','to','for','of','with','by','is','are','was','were','be','been','has','have','had','do','does','did','will','would','could','should','can','may','might','shall','it','its','this','that','these','those','i','me','my','we','our','you','your','he','she','they','them','their','not','no','from','as','if','so','than' );
        $words = array_diff( $words, $stop );
        $words = array_filter( $words, function( $w ) { return strlen( $w ) > 2; } );

        // Count and take top 20.
        $freq = array_count_values( $words );
        arsort( $freq );
        $top = array_slice( array_keys( $freq ), 0, 20 );

        return implode( ' ', $top );
    }

    private function extract_search_terms( $query ) {
        $query = strtolower( $query );
        $query = preg_replace( '/[^a-z0-9àèìòùáéíóú\s]/', '', $query );
        $words = explode( ' ', $query );
        $stop  = array( 'the','a','an','and','or','but','in','on','at','to','for','of','with','by','is','are','what','how','when','where','who','why','can','do','does','i','me','my','your','this','that','please','tell','about','it' );
        $words = array_diff( $words, $stop );
        $words = array_filter( $words, function( $w ) { return strlen( $w ) > 2; } );
        return array_values( $words );
    }

    private function get_post_terms( $post_id ) {
        $taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
        $all_terms  = array();
        foreach ( $taxonomies as $tax ) {
            $terms = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $terms ) ) {
                $all_terms = array_merge( $all_terms, $terms );
            }
        }
        return implode( ', ', $all_terms );
    }

    private function estimate_tokens( $text ) {
        // Rough: ~1 token per 4 chars for English, ~1 per 3 for other languages.
        return (int) ceil( strlen( $text ) / 3.5 );
    }
}
