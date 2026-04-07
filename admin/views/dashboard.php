<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$settings   = \Chatyllo\Settings::instance();
$ai_active  = \Chatyllo\Proxy::instance()->is_ai_active();
$is_premium = chatyllo_is_premium();
$plan_name  = chatyllo_get_plan_name();
$plan_limits = chatyllo_get_plan_limits();
$can_ai             = chatyllo_can_use_ai();
$can_index          = chatyllo_can_index();
$can_manual_reindex = chatyllo_can_manual_reindex();
$k_stats    = \Chatyllo\Indexer::instance()->get_stats();
$c_stats    = \Chatyllo\Chat::instance()->get_stats( 30 );
$faq_count  = \Chatyllo\FAQ::instance()->get_count();

$plan_labels = array(
    'free'     => __( 'Free', 'chatyllo' ),
    'starter'  => __( 'Starter', 'chatyllo' ),
    'business' => __( 'Business', 'chatyllo' ),
    'agency'   => __( 'Agency', 'chatyllo' ),
);
$plan_label  = $plan_labels[ $plan_name ] ?? $plan_labels['free'];
$plan_colors = array(
    'free'     => '#94A3B8',
    'starter'  => '#3B82F6',
    'business' => '#8B5CF6',
    'agency'   => '#F59E0B',
);
$plan_color = $plan_colors[ $plan_name ] ?? '#94A3B8';
?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header">
        <div class="chatyllo-header__left">
            <h1 class="chatyllo-header__title">
                <span class="dashicons dashicons-format-chat"></span>
                <?php esc_html_e( 'Chatyllo Dashboard', 'chatyllo' ); ?>
            </h1>
            <span class="chatyllo-version">v<?php echo esc_html( CHATYLLO_VERSION ); ?></span>
            <span class="chatyllo-plan-badge" style="background:<?php echo esc_attr( $plan_color ); ?>;color:#fff;padding:2px 10px;border-radius:12px;font-size:12px;margin-left:8px;font-weight:600;"><?php echo esc_html( $plan_label ); ?></span>
        </div>
        <div class="chatyllo-header__right">
            <div id="chatyllo-ai-status" class="chatyllo-ai-badge <?php echo ( $can_ai && $ai_active ) ? 'chatyllo-ai-badge--active' : 'chatyllo-ai-badge--inactive'; ?>">
                <span class="chatyllo-ai-badge__dot"></span>
                <span class="chatyllo-ai-badge__text">
                    <?php
                    if ( ! $can_ai ) {
                        esc_html_e( 'Free — FAQ Only', 'chatyllo' );
                    } elseif ( $ai_active ) {
                        esc_html_e( 'AI Active', 'chatyllo' );
                    } else {
                        esc_html_e( 'AI Offline', 'chatyllo' );
                    }
                    ?>
                </span>
            </div>
            <?php if ( $can_ai ) : ?>
            <button type="button" id="chatyllo-refresh-status" class="button button-secondary" title="<?php esc_attr_e( 'Refresh AI Status', 'chatyllo' ); ?>">
                <span class="dashicons dashicons-update"></span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="chatyllo-toast" id="chatyllo-toast"></div>

    <?php if ( 'free' === $plan_name ) : ?>
    <!-- Dismissible AI info for free users -->
    <div id="chatyllo-free-info" class="chatyllo-card" style="margin-bottom:16px;border-left:4px solid var(--chy-primary);background:linear-gradient(135deg,#EEF2FF,#E0E7FF);display:none;">
        <div class="chatyllo-card__body" style="display:flex;align-items:center;gap:14px;padding:14px 18px;">
            <div style="flex:1;">
                <p style="margin:0;font-size:13px;color:#3730A3;line-height:1.6;">
                    <strong><?php esc_html_e( 'No API key needed!', 'chatyllo' ); ?></strong>
                    <?php esc_html_e( 'AI features are automatically included when you upgrade to any paid plan. No personal API key, no setup, just activate and the AI starts working with your content.', 'chatyllo' ); ?>
                </p>
            </div>
            <a href="<?php echo esc_url( cha_fs()->get_upgrade_url() ); ?>" class="button button-primary" style="white-space:nowrap;background:var(--chy-primary);border-color:var(--chy-primary);"><?php esc_html_e( 'See Plans', 'chatyllo' ); ?></a>
            <button type="button" id="chatyllo-dismiss-free-info" style="background:none;border:none;font-size:18px;color:#94A3B8;cursor:pointer;padding:0 4px;" title="<?php esc_attr_e( 'Dismiss', 'chatyllo' ); ?>">&times;</button>
        </div>
    </div>
    <script>
    (function(){
        var key = 'chatyllo_free_info_dismissed';
        var dismissed = localStorage.getItem(key);
        var el = document.getElementById('chatyllo-free-info');
        if (!dismissed || (Date.now() - parseInt(dismissed)) > 172800000) {
            el.style.display = 'block';
        }
        document.getElementById('chatyllo-dismiss-free-info').addEventListener('click', function(){
            localStorage.setItem(key, Date.now().toString());
            el.style.display = 'none';
        });
    })();
    </script>

    <!-- Free → Starter Upgrade Banner -->
    <div class="chatyllo-upgrade-banner">
        <div class="chatyllo-upgrade-banner__icon">🚀</div>
        <div class="chatyllo-upgrade-banner__content">
            <h3><?php esc_html_e( 'Unlock AI-Powered Responses', 'chatyllo' ); ?></h3>
            <p><?php esc_html_e( 'You are using the free version with manual Q&A only. Upgrade to Starter to enable AI chatbot with 100 responses/day.', 'chatyllo' ); ?></p>
            <ul>
                <li>✅ <?php esc_html_e( 'AI chatbot — 100 responses/day', 'chatyllo' ); ?></li>
                <li>✅ <?php esc_html_e( 'Unlimited FAQ entries', 'chatyllo' ); ?></li>
                <li>✅ <?php esc_html_e( '90-day log retention', 'chatyllo' ); ?></li>
            </ul>
        </div>
        <div class="chatyllo-upgrade-banner__action">
            <a href="<?php echo esc_url( cha_fs()->get_upgrade_url() ); ?>" class="button button-primary button-hero chatyllo-upgrade-btn">
                <?php esc_html_e( 'Upgrade to Starter — $4.99/mo', 'chatyllo' ); ?>
            </a>
        </div>
    </div>
    <?php elseif ( 'starter' === $plan_name ) : ?>
    <!-- Starter → Business Upgrade Banner -->
    <div class="chatyllo-upgrade-banner" style="border-left:4px solid #8B5CF6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
        <div class="chatyllo-upgrade-banner__icon">⚡</div>
        <div class="chatyllo-upgrade-banner__content">
            <h3><?php esc_html_e( 'Need more power? Upgrade to Business', 'chatyllo' ); ?></h3>
            <p><?php esc_html_e( 'Get 300 AI responses/day, Knowledge Base indexing, custom post types, and remove Chatyllo branding.', 'chatyllo' ); ?></p>
        </div>
        <div class="chatyllo-upgrade-banner__action">
            <a href="<?php echo esc_url( cha_fs()->get_upgrade_url() ); ?>" class="button button-primary chatyllo-upgrade-btn" style="background:#8B5CF6;border-color:#8B5CF6;">
                <?php esc_html_e( 'Upgrade to Business — $9.99/mo', 'chatyllo' ); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="chatyllo-stats-grid">
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--blue">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $c_stats['total_chats'] ) ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Total Chats (30d)', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--green">
                <span class="dashicons dashicons-superhero-alt"></span>
            </div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $c_stats['ai_chats'] ) ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'AI Responses', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--purple">
                <span class="dashicons dashicons-database"></span>
            </div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $k_stats['total_entries'] ) ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Knowledge Entries', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--orange">
                <span class="dashicons dashicons-editor-help"></span>
            </div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( number_format_i18n( $faq_count ) ); ?><?php if ( $plan_limits['faq_limit'] > 0 ) : ?><small style="font-size:12px;color:#94A3B8"> / <?php echo esc_html( $plan_limits['faq_limit'] ); ?></small><?php endif; ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'FAQ Entries', 'chatyllo' ); ?></div>
            </div>
        </div>
    </div>

    <!-- AI Usage Meters -->
    <div class="chatyllo-card" id="chatyllo-usage-card" style="margin-bottom:20px;">
        <h2 class="chatyllo-card__title" style="padding-bottom:0;">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e( 'AI Usage', 'chatyllo' ); ?>
            <span id="chatyllo-usage-status" style="font-size:12px;font-weight:400;margin-left:8px;"></span>
        </h2>
        <div class="chatyllo-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" id="chatyllo-usage-meters">
                <!-- Daily meter -->
                <div class="chatyllo-meter" id="chatyllo-meter-daily">
                    <div class="chatyllo-meter__header">
                        <span class="chatyllo-meter__label"><?php esc_html_e( 'Daily', 'chatyllo' ); ?></span>
                        <span class="chatyllo-meter__count" id="chatyllo-daily-count">-</span>
                    </div>
                    <div class="chatyllo-meter__bar">
                        <div class="chatyllo-meter__fill" id="chatyllo-daily-fill" style="width:0%"></div>
                    </div>
                    <div class="chatyllo-meter__footer" id="chatyllo-daily-footer"><?php esc_html_e( 'Resets daily', 'chatyllo' ); ?></div>
                </div>
                <!-- Monthly meter -->
                <div class="chatyllo-meter" id="chatyllo-meter-monthly">
                    <div class="chatyllo-meter__header">
                        <span class="chatyllo-meter__label"><?php esc_html_e( 'Monthly', 'chatyllo' ); ?></span>
                        <span class="chatyllo-meter__count" id="chatyllo-monthly-count">-</span>
                    </div>
                    <div class="chatyllo-meter__bar">
                        <div class="chatyllo-meter__fill" id="chatyllo-monthly-fill" style="width:0%"></div>
                    </div>
                    <div class="chatyllo-meter__footer" id="chatyllo-monthly-footer"><?php esc_html_e( 'Resets monthly', 'chatyllo' ); ?></div>
                </div>
            </div>
            <p class="chatyllo-field__desc" id="chatyllo-usage-info" style="margin-top:12px;"></p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="chatyllo-row">
        <div class="chatyllo-col chatyllo-col--8">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php esc_html_e( 'Quick Actions', 'chatyllo' ); ?>
                </h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-actions-grid">
                        <?php if ( $can_manual_reindex ) : ?>
                        <button type="button" id="chatyllo-btn-reindex" class="chatyllo-action-btn chatyllo-action-btn--primary">
                            <span class="dashicons dashicons-update"></span>
                            <span><?php esc_html_e( 'Rebuild Knowledge Base', 'chatyllo' ); ?></span>
                            <small><?php esc_html_e( 'Re-scan all content now', 'chatyllo' ); ?></small>
                        </button>
                        <?php elseif ( $can_index ) : ?>
                        <div class="chatyllo-action-btn chatyllo-action-btn--secondary" style="opacity:.7;cursor:default;">
                            <span class="dashicons dashicons-clock"></span>
                            <span><?php esc_html_e( 'Knowledge Base', 'chatyllo' ); ?></span>
                            <small><?php esc_html_e( 'Auto-indexes weekly', 'chatyllo' ); ?></small>
                        </div>
                        <?php else : ?>
                        <div class="chatyllo-action-btn chatyllo-action-btn--secondary" style="opacity:.6;cursor:not-allowed;">
                            <span class="dashicons dashicons-lock"></span>
                            <span><?php esc_html_e( 'AI Knowledge Base', 'chatyllo' ); ?></span>
                            <small><?php esc_html_e( 'Paid plan required', 'chatyllo' ); ?></small>
                        </div>
                        <?php endif; ?>
                        <?php if ( $can_ai ) : ?>
                        <button type="button" id="chatyllo-btn-clear-cache" class="chatyllo-action-btn chatyllo-action-btn--secondary">
                            <span class="dashicons dashicons-trash"></span>
                            <span><?php esc_html_e( 'Clear Response Cache', 'chatyllo' ); ?></span>
                            <small><?php esc_html_e( 'Force fresh AI responses', 'chatyllo' ); ?></small>
                        </button>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=chatyllo-settings' ) ); ?>" class="chatyllo-action-btn chatyllo-action-btn--outline">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <span><?php esc_html_e( 'Settings', 'chatyllo' ); ?></span>
                            <small><?php esc_html_e( 'Customize appearance & behavior', 'chatyllo' ); ?></small>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=chatyllo-faq' ) ); ?>" class="chatyllo-action-btn chatyllo-action-btn--outline">
                            <span class="dashicons dashicons-editor-help"></span>
                            <span><?php esc_html_e( 'Manage FAQ', 'chatyllo' ); ?></span>
                            <small><?php esc_html_e( 'Add manual Q&A pairs', 'chatyllo' ); ?></small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="chatyllo-col chatyllo-col--4">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php esc_html_e( 'System Info', 'chatyllo' ); ?>
                </h2>
                <div class="chatyllo-card__body">
                    <table class="chatyllo-info-table">
                        <tr>
                            <td><?php esc_html_e( 'Plan', 'chatyllo' ); ?></td>
                            <td>
                                <span class="chatyllo-dot" style="background:<?php echo esc_attr( $plan_color ); ?>"></span>
                                <?php echo esc_html( $plan_label ); ?>
                                <?php if ( 'agency' !== $plan_name ) : ?>
                                    — <a href="<?php echo esc_url( cha_fs()->get_upgrade_url() ); ?>"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Knowledge Base', 'chatyllo' ); ?></td>
                            <td>
                                <?php if ( $can_manual_reindex ) : ?>
                                    <span class="chatyllo-dot chatyllo-dot--green"></span><?php esc_html_e( 'Auto 12h + Manual', 'chatyllo' ); ?>
                                <?php elseif ( $can_index ) : ?>
                                    <span class="chatyllo-dot chatyllo-dot--green"></span><?php esc_html_e( 'Auto weekly', 'chatyllo' ); ?>
                                <?php else : ?>
                                    <span class="chatyllo-dot chatyllo-dot--red"></span><?php esc_html_e( 'Paid plan required', 'chatyllo' ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'AI Status', 'chatyllo' ); ?></td>
                            <td>
                                <?php if ( ! $can_ai ) : ?>
                                    <span class="chatyllo-dot chatyllo-dot--red"></span><?php esc_html_e( 'Starter+ required', 'chatyllo' ); ?>
                                <?php elseif ( $ai_active ) : ?>
                                    <span class="chatyllo-dot chatyllo-dot--green"></span><?php esc_html_e( 'Active', 'chatyllo' ); ?>
                                <?php else : ?>
                                    <span class="chatyllo-dot chatyllo-dot--red"></span><?php esc_html_e( 'Offline', 'chatyllo' ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Mode', 'chatyllo' ); ?></td>
                            <td><?php echo ( $can_ai && $ai_active ) ? esc_html__( 'AI + FAQ', 'chatyllo' ) : esc_html__( 'FAQ Only', 'chatyllo' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'FAQ Limit', 'chatyllo' ); ?></td>
                            <td><?php echo ( $plan_limits['faq_limit'] < 0 ) ? esc_html__( 'Unlimited', 'chatyllo' ) : esc_html( $faq_count . ' / ' . $plan_limits['faq_limit'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Log Retention', 'chatyllo' ); ?></td>
                            <td><?php echo ( $plan_limits['log_days'] < 0 ) ? esc_html__( 'Unlimited', 'chatyllo' ) : esc_html( $plan_limits['log_days'] . ' ' . __( 'days', 'chatyllo' ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Indexed Pages', 'chatyllo' ); ?></td>
                            <td><?php echo esc_html( $k_stats['page_count'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Indexed Posts', 'chatyllo' ); ?></td>
                            <td><?php echo esc_html( $k_stats['post_count'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Indexed Products', 'chatyllo' ); ?></td>
                            <td><?php echo esc_html( $k_stats['product_count'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Last Indexed', 'chatyllo' ); ?></td>
                            <td><?php echo esc_html( $k_stats['last_indexed'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Unique Visitors', 'chatyllo' ); ?></td>
                            <td><?php echo esc_html( $c_stats['unique_sessions'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Avg Response', 'chatyllo' ); ?></td>
                            <td><?php echo esc_html( $c_stats['avg_response_ms'] ); ?>ms</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Getting Started Guide -->
            <div class="chatyllo-card" style="margin-top:20px;">
                <h2 class="chatyllo-card__title">
                    <span class="dashicons dashicons-book-alt" style="color:#8B5CF6"></span>
                    <?php esc_html_e( 'Getting Started', 'chatyllo' ); ?>
                </h2>
                <div class="chatyllo-card__body" style="padding:12px 16px;">
                    <?php
                    $steps = array(
                        array(
                            'done'  => $settings->get( 'enabled' ),
                            'icon'  => 'yes-alt',
                            'title' => __( 'Enable the chatbot', 'chatyllo' ),
                            'desc'  => __( 'Go to Settings → Behavior and enable Chatyllo.', 'chatyllo' ),
                            'link'  => admin_url( 'admin.php?page=chatyllo-settings' ),
                        ),
                        array(
                            'done'  => ! empty( $settings->get( 'welcome_message' ) ),
                            'icon'  => 'format-chat',
                            'title' => __( 'Set a welcome message', 'chatyllo' ),
                            'desc'  => __( 'Greet visitors with a custom message.', 'chatyllo' ),
                            'link'  => admin_url( 'admin.php?page=chatyllo-settings' ),
                        ),
                        array(
                            'done'  => $faq_count > 0,
                            'icon'  => 'editor-help',
                            'title' => __( 'Add FAQ entries', 'chatyllo' ),
                            'desc'  => __( 'Create Q&A pairs for instant answers.', 'chatyllo' ),
                            'link'  => admin_url( 'admin.php?page=chatyllo-faq' ),
                        ),
                        array(
                            'done'  => $k_stats['total_entries'] > 0,
                            'icon'  => 'database',
                            'title' => __( 'Index your content', 'chatyllo' ),
                            'desc'  => __( 'Build the knowledge base for AI responses.', 'chatyllo' ),
                            'link'  => admin_url( 'admin.php?page=chatyllo-knowledge' ),
                        ),
                        array(
                            'done'  => ! empty( $settings->get( 'privacy_policy_url' ) ),
                            'icon'  => 'shield',
                            'title' => __( 'Set privacy policy', 'chatyllo' ),
                            'desc'  => __( 'Add your privacy URL for GDPR compliance.', 'chatyllo' ),
                            'link'  => admin_url( 'admin.php?page=chatyllo-settings' ),
                        ),
                    );
                    $done_count = count( array_filter( $steps, function( $s ) { return $s['done']; } ) );
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <span style="font-size:12px;color:#64748B;"><?php
                            /* translators: %1$d: completed steps, %2$d: total steps */
                            printf( esc_html__( '%1$d of %2$d completed', 'chatyllo' ), (int) $done_count, count( $steps ) );
                        ?></span>
                        <div style="width:60px;height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo esc_attr( ( $done_count / count( $steps ) ) * 100 ); ?>%;background:var(--chy-primary);border-radius:3px;"></div>
                        </div>
                    </div>
                    <?php foreach ( $steps as $step ) : ?>
                    <a href="<?php echo esc_url( $step['link'] ); ?>" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #F1F5F9;text-decoration:none;color:inherit;">
                        <span class="dashicons dashicons-<?php echo esc_attr( $step['done'] ? 'yes-alt' : $step['icon'] ); ?>" style="color:<?php echo $step['done'] ? '#22C55E' : '#CBD5E1'; ?>;font-size:18px;width:18px;height:18px;"></span>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:<?php echo $step['done'] ? '400' : '600'; ?>;color:<?php echo $step['done'] ? '#94A3B8' : '#1E293B'; ?>;<?php echo $step['done'] ? 'text-decoration:line-through;' : ''; ?>"><?php echo esc_html( $step['title'] ); ?></div>
                            <?php if ( ! $step['done'] ) : ?>
                            <div style="font-size:11px;color:#94A3B8;"><?php echo esc_html( $step['desc'] ); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
