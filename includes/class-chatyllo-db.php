<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database manager — tables for knowledge base, FAQ, chat logs, cache.
 */
final class DB {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /* ── Table names ───────────────────────────────────────────────── */
    public static function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'chatyllo_' . $name;
    }

    /* ── Create tables (instance) ──────────────────────────────────── */
    public function create_tables() {
        self::create_tables_static();
    }

    /* ── Create tables (static — for activation hook) ──────────────── */
    public static function create_tables_static() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'chatyllo_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /* Knowledge base — indexed site content chunks */
        $sql = "CREATE TABLE {$prefix}knowledge (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source_type VARCHAR(32) NOT NULL DEFAULT 'post',
            source_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(500) NOT NULL DEFAULT '',
            content LONGTEXT NOT NULL,
            keywords TEXT NOT NULL,
            content_hash CHAR(32) NOT NULL DEFAULT '',
            tokens_approx INT UNSIGNED NOT NULL DEFAULT 0,
            language VARCHAR(10) NOT NULL DEFAULT '',
            indexed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_idx (source_type, source_id),
            KEY hash_idx (content_hash)
        ) $charset;";
        dbDelta( $sql );

        /* FAQ — manual + AI-generated Q&A pairs */
        $sql = "CREATE TABLE {$prefix}faq (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            answer LONGTEXT NOT NULL,
            keywords VARCHAR(1000) NOT NULL DEFAULT '',
            category VARCHAR(200) NOT NULL DEFAULT '',
            language VARCHAR(10) NOT NULL DEFAULT '',
            source VARCHAR(20) NOT NULL DEFAULT 'manual',
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY active_idx (is_active, sort_order),
            KEY source_idx (source, is_active)
        ) $charset;";
        dbDelta( $sql );

        /* Chat logs */
        $sql = "CREATE TABLE {$prefix}chat_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            visitor_ip VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(500) NOT NULL DEFAULT '',
            page_url VARCHAR(500) NOT NULL DEFAULT '',
            referrer_url VARCHAR(500) NOT NULL DEFAULT '',
            user_message TEXT NOT NULL,
            bot_response LONGTEXT NOT NULL,
            response_mode VARCHAR(16) NOT NULL DEFAULT 'ai',
            tokens_used INT UNSIGNED NOT NULL DEFAULT 0,
            response_time_ms INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_idx (session_id),
            KEY date_idx (created_at)
        ) $charset;";
        dbDelta( $sql );

        /* Cache table for proxy responses */
        $sql = "CREATE TABLE {$prefix}cache (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key VARCHAR(64) NOT NULL,
            cache_value LONGTEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY key_idx (cache_key),
            KEY expires_idx (expires_at)
        ) $charset;";
        dbDelta( $sql );
    }

    /* ── Helpers ───────────────────────────────────────────────────── */
    public static function insert( $table_short, $data, $format = null ) {
        global $wpdb;
        return $wpdb->insert( self::table( $table_short ), $data, $format );
    }

    public static function update( $table_short, $data, $where, $format = null, $where_format = null ) {
        global $wpdb;
        return $wpdb->update( self::table( $table_short ), $data, $where, $format, $where_format );
    }

    public static function delete( $table_short, $where, $where_format = null ) {
        global $wpdb;
        return $wpdb->delete( self::table( $table_short ), $where, $where_format );
    }

    public static function get_row( $table_short, $where_clause = '1=1', $args = array() ) {
        global $wpdb;
        $table = self::table( $table_short );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_clause}", ...$args ) );
    }

    public static function get_results( $table_short, $where_clause = '1=1', $args = array() ) {
        global $wpdb;
        $table = self::table( $table_short );
        if ( empty( $args ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where_clause}" );
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_clause}", ...$args ) );
    }

    public static function count( $table_short, $where_clause = '1=1', $args = array() ) {
        global $wpdb;
        $table = self::table( $table_short );
        if ( empty( $args ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}" );
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", ...$args ) );
    }

    public static function truncate( $table_short ) {
        global $wpdb;
        $table = self::table( $table_short );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "TRUNCATE TABLE {$table}" );
    }
}
