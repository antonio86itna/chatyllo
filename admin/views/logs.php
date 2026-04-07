<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$c_stats     = \Chatyllo\Chat::instance()->get_stats( 30 );
$plan_limits = chatyllo_get_plan_limits();
?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header">
        <div class="chatyllo-header__left">
            <h1 class="chatyllo-header__title"><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'Chat Logs', 'chatyllo' ); ?></h1>
        </div>
    </div>
    <div class="chatyllo-toast" id="chatyllo-toast"></div>

    <!-- Stats -->
    <div class="chatyllo-stats-grid" style="margin-bottom:20px;">
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--blue"><span class="dashicons dashicons-admin-comments"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $c_stats['total_chats'] ) ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Total Chats (30d)', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--green"><span class="dashicons dashicons-superhero-alt"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $c_stats['ai_chats'] ) ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'AI Responses', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--purple"><span class="dashicons dashicons-groups"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $c_stats['unique_sessions'] ) ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Unique Visitors', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--orange"><span class="dashicons dashicons-clock"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( $c_stats['avg_response_ms'] ); ?>ms</div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Avg Response', 'chatyllo' ); ?></div>
            </div>
        </div>
    </div>

    <p class="chatyllo-field__desc" style="margin-bottom:12px;">
        <?php
        if ( $plan_limits['log_days'] < 0 ) {
            esc_html_e( 'Unlimited log retention (Agency plan).', 'chatyllo' );
        } else {
            /* translators: %d: number of days for log retention */
            printf( esc_html__( 'Logs retained for %d days on your current plan.', 'chatyllo' ), (int) $plan_limits['log_days'] );
        }
        ?>
    </p>

    <!-- Log Table -->
    <div class="chatyllo-card">
        <div class="chatyllo-card__body" style="padding:0;">
            <table class="widefat striped" id="chatyllo-logs-table" style="border:none;">
                <thead>
                    <tr>
                        <th style="width:140px"><?php esc_html_e( 'Started', 'chatyllo' ); ?></th>
                        <th style="width:80px"><?php esc_html_e( 'Session', 'chatyllo' ); ?></th>
                        <th><?php esc_html_e( 'First Message', 'chatyllo' ); ?></th>
                        <th style="width:60px;text-align:center"><?php esc_html_e( 'Msgs', 'chatyllo' ); ?></th>
                        <th style="width:55px;text-align:center"><?php esc_html_e( 'Mode', 'chatyllo' ); ?></th>
                        <th style="width:65px;text-align:center"><?php esc_html_e( 'Tokens', 'chatyllo' ); ?></th>
                        <th style="width:55px;text-align:center"><?php esc_html_e( 'Duration', 'chatyllo' ); ?></th>
                    </tr>
                </thead>
                <tbody id="chatyllo-logs-body">
                    <tr><td colspan="7" style="text-align:center;padding:24px;color:#94A3B8;"><?php esc_html_e( 'Loading...', 'chatyllo' ); ?></td></tr>
                </tbody>
            </table>
            <div id="chatyllo-logs-pagination" style="display:flex;align-items:center;gap:6px;padding:12px 16px;flex-wrap:wrap;"></div>
        </div>
    </div>
</div>

<!-- Session Detail Modal -->
<div id="chatyllo-session-modal" class="chatyllo-modal" style="display:none;">
    <div class="chatyllo-modal__overlay" id="chatyllo-modal-overlay"></div>
    <div class="chatyllo-modal__dialog">
        <div class="chatyllo-modal__header">
            <h3 style="margin:0;display:flex;align-items:center;gap:8px;">
                <span class="dashicons dashicons-format-chat" style="color:var(--chy-primary)"></span>
                <span id="chatyllo-modal-title"><?php esc_html_e( 'Conversation Details', 'chatyllo' ); ?></span>
            </h3>
            <button type="button" class="chatyllo-modal__close" id="chatyllo-modal-close">&times;</button>
        </div>
        <div class="chatyllo-modal__body" id="chatyllo-modal-body">
            <p style="text-align:center;color:#94A3B8;padding:40px;"><?php esc_html_e( 'Loading conversation...', 'chatyllo' ); ?></p>
        </div>
    </div>
</div>
