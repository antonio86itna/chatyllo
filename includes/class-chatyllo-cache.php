<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cache layer — DB-based caching for AI responses.
 */
final class Cache {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Purge expired entries daily.
        add_action( 'chatyllo_reindex_content', array( $this, 'purge_expired' ) );
    }

    public function get( $key ) {
        global $wpdb;
        $table   = DB::table( 'cache' );
        $cache_key = $this->hash_key( $key );
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT cache_value, expires_at FROM {$table} WHERE cache_key = %s LIMIT 1",
            $cache_key
        ) );
        if ( ! $row ) {
            return false;
        }
        if ( strtotime( $row->expires_at ) < time() ) {
            $this->delete( $key );
            return false;
        }
        return maybe_unserialize( $row->cache_value );
    }

    public function set( $key, $value, $ttl = 3600 ) {
        global $wpdb;
        $table     = DB::table( 'cache' );
        $cache_key = $this->hash_key( $key );
        $expires   = gmdate( 'Y-m-d H:i:s', time() + $ttl );

        $wpdb->replace( $table, array(
            'cache_key'   => $cache_key,
            'cache_value' => maybe_serialize( $value ),
            'expires_at'  => $expires,
        ) );
    }

    public function delete( $key ) {
        global $wpdb;
        $table = DB::table( 'cache' );
        $wpdb->delete( $table, array( 'cache_key' => $this->hash_key( $key ) ) );
    }

    public function flush() {
        DB::truncate( 'cache' );
    }

    public function purge_expired() {
        global $wpdb;
        $table = DB::table( 'cache' );
        $now   = current_time( 'mysql', true );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %s", $now ) );
    }

    private function hash_key( $key ) {
        return md5( 'chatyllo_' . $key );
    }
}
