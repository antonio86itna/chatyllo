<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$stats              = \Chatyllo\Indexer::instance()->get_stats();
$can_index          = chatyllo_can_index();
$can_manual_reindex = chatyllo_can_manual_reindex();
$plan_name          = chatyllo_get_plan_name();
$plan_limits        = chatyllo_get_plan_limits();
?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header"><div class="chatyllo-header__left"><h1 class="chatyllo-header__title"><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Knowledge Base', 'chatyllo' ); ?></h1></div></div>
    <div class="chatyllo-toast" id="chatyllo-toast"></div>
    <p class="chatyllo-desc"><?php esc_html_e( 'The knowledge base is auto-generated from your site content. Chatyllo reads your pages, posts, and products to understand your business.', 'chatyllo' ); ?></p>
    <div class="chatyllo-stats-grid">
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--blue"><span class="dashicons dashicons-media-text"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number"><?php echo esc_html( $stats['total_entries'] ); ?></div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Total Entries', 'chatyllo' ); ?></div></div></div>
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--green"><span class="dashicons dashicons-admin-page"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number"><?php echo esc_html( $stats['page_count'] ); ?></div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Pages', 'chatyllo' ); ?></div></div></div>
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--purple"><span class="dashicons dashicons-admin-post"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number"><?php echo esc_html( $stats['post_count'] ); ?></div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Posts', 'chatyllo' ); ?></div></div></div>
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--orange"><span class="dashicons dashicons-cart"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number"><?php echo esc_html( $stats['product_count'] ); ?></div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Products', 'chatyllo' ); ?></div></div></div>
    </div>
    <div class="chatyllo-card">
        <div class="chatyllo-card__body">
            <p><strong><?php esc_html_e( 'Last indexed:', 'chatyllo' ); ?></strong> <?php echo esc_html( $stats['last_indexed'] ); ?></p>
            <p><strong><?php esc_html_e( 'Approx. tokens:', 'chatyllo' ); ?></strong> <?php echo esc_html( number_format_i18n( $stats['total_tokens'] ) ); ?></p>

            <?php if ( $can_index ) : ?>
                <p style="margin-top:12px"><strong><?php esc_html_e( 'Auto-index schedule:', 'chatyllo' ); ?></strong>
                <?php
                    if ( $can_manual_reindex ) {
                        esc_html_e( 'Every 12 hours + manual anytime', 'chatyllo' );
                    } else {
                        esc_html_e( 'Once every 7 days (automatic)', 'chatyllo' );
                    }
                ?>
                </p>
            <?php endif; ?>

            <br/>

            <?php if ( $can_manual_reindex ) : ?>
                <!-- Business/Agency: manual reindex button -->
                <button type="button" id="chatyllo-btn-reindex" class="button button-primary button-hero"><span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Rebuild Knowledge Base', 'chatyllo' ); ?></button>
                <p class="chatyllo-desc" style="margin-top:12px"><?php esc_html_e( 'This will re-scan all your content. Changes to posts/pages are also detected automatically.', 'chatyllo' ); ?></p>

            <?php elseif ( $can_index ) : ?>
                <!-- Starter: auto-index only, upgrade prompt -->
                <div class="chatyllo-upgrade-banner" style="margin-top:0;border-left:4px solid #8B5CF6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
                    <div class="chatyllo-upgrade-banner__icon">⚡</div>
                    <div class="chatyllo-upgrade-banner__content">
                        <h3 style="margin:0 0 4px"><?php esc_html_e( 'Auto-indexing weekly', 'chatyllo' ); ?></h3>
                        <p style="margin:0"><?php esc_html_e( 'Your Starter plan auto-indexes content every 7 days. Upgrade to Business for manual reindex anytime and 12-hour auto-updates.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-upgrade-banner__action">
                        <a href="<?php echo esc_url( cha_fs()->get_upgrade_url() ); ?>" class="button button-primary chatyllo-upgrade-btn" style="background:#8B5CF6;border-color:#8B5CF6;"><?php esc_html_e( 'Upgrade to Business', 'chatyllo' ); ?></a>
                    </div>
                </div>

            <?php else : ?>
                <!-- Free: no indexing -->
                <div class="chatyllo-upgrade-banner" style="margin-top:0">
                    <div class="chatyllo-upgrade-banner__icon">🔒</div>
                    <div class="chatyllo-upgrade-banner__content">
                        <h3><?php esc_html_e( 'AI Knowledge Base — Paid Plan Required', 'chatyllo' ); ?></h3>
                        <p><?php esc_html_e( 'Upgrade to any paid plan to unlock automatic content indexing and AI-powered responses based on your site content.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-upgrade-banner__action">
                        <a href="<?php echo esc_url( cha_fs()->get_upgrade_url() ); ?>" class="button button-primary chatyllo-upgrade-btn"><?php esc_html_e( 'Upgrade Now', 'chatyllo' ); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
