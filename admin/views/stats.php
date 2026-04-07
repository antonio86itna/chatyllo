<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header">
        <div class="chatyllo-header__left">
            <h1 class="chatyllo-header__title"><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'Statistics', 'chatyllo' ); ?></h1>
        </div>
        <div class="chatyllo-header__right">
            <select id="chatyllo-stats-period" style="min-width:120px;">
                <option value="7"><?php esc_html_e( 'Last 7 days', 'chatyllo' ); ?></option>
                <option value="30" selected><?php esc_html_e( 'Last 30 days', 'chatyllo' ); ?></option>
                <option value="90"><?php esc_html_e( 'Last 90 days', 'chatyllo' ); ?></option>
                <option value="0"><?php esc_html_e( 'All time', 'chatyllo' ); ?></option>
            </select>
            <button type="button" class="button" id="chatyllo-export-stats-btn" title="<?php esc_attr_e( 'Export CSV', 'chatyllo' ); ?>">
                <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export', 'chatyllo' ); ?>
            </button>
        </div>
    </div>
    <div class="chatyllo-toast" id="chatyllo-toast"></div>

    <!-- Overview Cards -->
    <div class="chatyllo-stats-grid" id="chatyllo-stats-overview" style="margin-bottom:20px;">
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--blue"><span class="dashicons dashicons-admin-comments"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number" id="chy-stat-total">-</div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Total Messages', 'chatyllo' ); ?></div></div></div>
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--purple"><span class="dashicons dashicons-groups"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number" id="chy-stat-sessions">-</div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Conversations', 'chatyllo' ); ?></div></div></div>
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--orange"><span class="dashicons dashicons-clock"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number" id="chy-stat-avgms">-</div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Avg Response', 'chatyllo' ); ?></div></div></div>
        <div class="chatyllo-stat-card"><div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--green"><span class="dashicons dashicons-database"></span></div><div class="chatyllo-stat-card__content"><div class="chatyllo-stat-card__number" id="chy-stat-tokens">-</div><div class="chatyllo-stat-card__label"><?php esc_html_e( 'Tokens Used', 'chatyllo' ); ?></div></div></div>
    </div>

    <div class="chatyllo-row">
        <!-- Left column -->
        <div class="chatyllo-col chatyllo-col--8">
            <!-- Daily Trend -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Daily Activity', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div id="chatyllo-trend-chart" style="display:flex;align-items:flex-end;gap:2px;height:120px;padding:0 4px;"></div>
                </div>
            </div>

            <!-- Response Mode Breakdown -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Response Modes', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body" id="chatyllo-modes-chart"></div>
            </div>

            <!-- Top Pages -->
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Top Pages', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body" id="chatyllo-top-pages"></div>
            </div>
        </div>

        <!-- Right column -->
        <div class="chatyllo-col chatyllo-col--4">
            <!-- Devices -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-smartphone"></span> <?php esc_html_e( 'Devices', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body" id="chatyllo-devices-chart"></div>
            </div>

            <!-- Browsers -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-desktop"></span> <?php esc_html_e( 'Browsers', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body" id="chatyllo-browsers-chart"></div>
            </div>

            <!-- Session Quality -->
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'Session Quality', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body" id="chatyllo-session-quality"></div>
            </div>
        </div>
    </div>
</div>
