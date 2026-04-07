<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$current = chatyllo_get_current_status();
$history = get_option( 'chatyllo_status_history', array() );
$cutoff  = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
$history = array_filter( $history, function( $h ) use ( $cutoff ) { return $h['date'] >= $cutoff; } );
$history = array_values( $history );
// Index by date for easy lookup.
$history_map = array();
foreach ( $history as $h ) { $history_map[ $h['date'] ] = $h; }
?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header">
        <div class="chatyllo-header__left">
            <h1 class="chatyllo-header__title"><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'Service Status', 'chatyllo' ); ?></h1>
        </div>
        <div class="chatyllo-header__right">
            <button type="button" class="button" id="chatyllo-status-refresh">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Refresh', 'chatyllo' ); ?>
            </button>
        </div>
    </div>
    <div class="chatyllo-toast" id="chatyllo-toast"></div>

    <!-- Current Status -->
    <div class="chatyllo-card" style="margin-bottom:20px;">
        <div class="chatyllo-card__body" style="padding:24px;">
            <div id="chatyllo-current-status" style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;border-radius:50%;background:<?php echo esc_attr( $current['color'] ); ?>1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span class="dashicons dashicons-<?php echo esc_attr( $current['icon'] ); ?>" style="color:<?php echo esc_attr( $current['color'] ); ?>;font-size:28px;width:28px;height:28px;"></span>
                </div>
                <div>
                    <div style="font-size:20px;font-weight:700;color:<?php echo esc_attr( $current['color'] ); ?>;" id="chatyllo-status-text"><?php echo esc_html( $current['text'] ); ?></div>
                    <div style="font-size:13px;color:#94A3B8;margin-top:2px;" id="chatyllo-status-time">
                        <?php if ( $current['checked_at'] ) : ?>
                            <?php
                                /* translators: %s: date and time of last status check */
                                printf( esc_html__( 'Last checked: %s', 'chatyllo' ), esc_html( $current['checked_at'] ) );
                            ?>
                        <?php else : ?>
                            <?php esc_html_e( 'Checking...', 'chatyllo' ); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 30-Day Timeline -->
    <div class="chatyllo-card" style="margin-bottom:20px;">
        <h2 class="chatyllo-card__title">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e( 'Last 30 Days', 'chatyllo' ); ?>
        </h2>
        <div class="chatyllo-card__body">
            <div style="display:flex;gap:3px;align-items:flex-end;" id="chatyllo-status-timeline">
                <?php
                $status_colors = array(
                    'operational'   => '#22C55E',
                    'degraded'      => '#F59E0B',
                    'maintenance'   => '#F59E0B',
                    'outage'        => '#EF4444',
                    'investigating' => '#F59E0B',
                    'network'       => '#94A3B8',
                );
                for ( $i = 29; $i >= 0; $i-- ) :
                    $day      = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
                    $day_data = $history_map[ $day ] ?? null;
                    $status   = $day_data ? $day_data['status'] : '';
                    $color    = $status ? ( $status_colors[ $status ] ?? '#E2E8F0' ) : '#E2E8F0';
                    $title    = gmdate( 'M j', strtotime( $day ) ) . ( $status ? ' — ' . ucfirst( $status ) : ' — No data' );
                ?>
                <div class="chatyllo-timeline-bar"
                     style="flex:1;height:32px;background:<?php echo esc_attr( $color ); ?>;border-radius:3px;cursor:pointer;transition:opacity .2s;"
                     title="<?php echo esc_attr( $title ); ?>"
                     data-date="<?php echo esc_attr( $day ); ?>"
                     data-status="<?php echo esc_attr( $status ); ?>">
                </div>
                <?php endfor; ?>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:#94A3B8;">
                <span><?php echo esc_html( date_i18n( 'M j', strtotime( '-29 days' ) ) ); ?></span>
                <span><?php esc_html_e( 'Today', 'chatyllo' ); ?></span>
            </div>

            <!-- Legend -->
            <div style="display:flex;gap:16px;margin-top:14px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:4px;font-size:12px;color:#64748B;"><span style="width:10px;height:10px;border-radius:2px;background:#22C55E;"></span> <?php esc_html_e( 'Operational', 'chatyllo' ); ?></div>
                <div style="display:flex;align-items:center;gap:4px;font-size:12px;color:#64748B;"><span style="width:10px;height:10px;border-radius:2px;background:#F59E0B;"></span> <?php esc_html_e( 'Degraded / Maintenance', 'chatyllo' ); ?></div>
                <div style="display:flex;align-items:center;gap:4px;font-size:12px;color:#64748B;"><span style="width:10px;height:10px;border-radius:2px;background:#EF4444;"></span> <?php esc_html_e( 'Outage', 'chatyllo' ); ?></div>
                <div style="display:flex;align-items:center;gap:4px;font-size:12px;color:#64748B;"><span style="width:10px;height:10px;border-radius:2px;background:#E2E8F0;"></span> <?php esc_html_e( 'No data', 'chatyllo' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Info Tips -->
    <div class="chatyllo-card">
        <h2 class="chatyllo-card__title">
            <span class="dashicons dashicons-lightbulb" style="color:#F59E0B"></span>
            <?php esc_html_e( 'Good to Know', 'chatyllo' ); ?>
        </h2>
        <div class="chatyllo-card__body" style="padding:14px 20px;">
            <?php
            $tips = array(
                array( 'yes-alt',  __( 'When AI is operational, your chatbot uses intelligent AI to answer visitor questions based on your website content.', 'chatyllo' ) ),
                array( 'editor-help', __( 'If AI is temporarily unavailable, the chatbot automatically switches to FAQ mode — your visitors still get helpful answers from your manual Q&A pairs.', 'chatyllo' ) ),
                array( 'clock',    __( 'Service status is checked automatically every 5 minutes. You can also refresh manually using the button above.', 'chatyllo' ) ),
                array( 'shield',   __( 'Daily and monthly usage limits reset automatically. Daily limits reset at midnight (server time), monthly limits reset on the 1st of each month.', 'chatyllo' ) ),
                array( 'superhero-alt', __( 'For the best experience, keep your FAQ entries up to date — they serve as a reliable fallback whenever AI is not available.', 'chatyllo' ) ),
            );
            foreach ( $tips as $tip ) : ?>
            <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #F1F5F9;">
                <span class="dashicons dashicons-<?php echo esc_attr( $tip[0] ); ?>" style="color:#94A3B8;font-size:16px;width:16px;height:16px;margin-top:2px;flex-shrink:0;"></span>
                <p style="margin:0;font-size:13px;color:#475569;line-height:1.5;"><?php echo esc_html( $tip[1] ); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
