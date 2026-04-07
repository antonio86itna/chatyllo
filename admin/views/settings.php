<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$s  = \Chatyllo\Settings::instance();
$ai = \Chatyllo\Proxy::instance()->is_ai_active();
$is_premium  = chatyllo_is_premium();
$plan_name   = chatyllo_get_plan_name();
$can_ai      = chatyllo_can_use_ai();
$can_index   = chatyllo_can_index();
$can_manual_reindex = chatyllo_can_manual_reindex();
$can_hide    = chatyllo_can_hide_branding();
$can_custom  = chatyllo_can_custom_brand();
$can_cpt     = chatyllo_can_use_custom_cpt();
$plan_limits = chatyllo_get_plan_limits();
$upgrade_url = function_exists( 'cha_fs' ) ? cha_fs()->get_upgrade_url() : '#';
?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header">
        <div class="chatyllo-header__left">
            <h1 class="chatyllo-header__title"><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Chatyllo Settings', 'chatyllo' ); ?></h1>
        </div>
        <div class="chatyllo-header__right">
            <div id="chatyllo-ai-status" class="chatyllo-ai-badge <?php echo ( $can_ai && $ai ) ? 'chatyllo-ai-badge--active' : 'chatyllo-ai-badge--inactive'; ?>">
                <span class="chatyllo-ai-badge__dot"></span>
                <span class="chatyllo-ai-badge__text"><?php
                    if ( ! $can_ai ) { esc_html_e( 'Free — FAQ Only', 'chatyllo' ); }
                    elseif ( $ai ) { esc_html_e( 'AI Active', 'chatyllo' ); }
                    else { esc_html_e( 'AI Offline', 'chatyllo' ); }
                ?></span>
            </div>
        </div>
    </div>
    <div class="chatyllo-toast" id="chatyllo-toast"></div>

    <!-- Tabs -->
    <nav class="chatyllo-tabs" id="chatyllo-settings-tabs">
        <a href="#tab-appearance" class="chatyllo-tab chatyllo-tab--active" data-tab="tab-appearance"><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Appearance', 'chatyllo' ); ?></a>
        <a href="#tab-behavior" class="chatyllo-tab" data-tab="tab-behavior"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Behavior', 'chatyllo' ); ?></a>
        <a href="#tab-knowledge" class="chatyllo-tab" data-tab="tab-knowledge"><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Knowledge', 'chatyllo' ); ?></a>
        <a href="#tab-branding" class="chatyllo-tab" data-tab="tab-branding"><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Branding', 'chatyllo' ); ?></a>
        <a href="#tab-messages" class="chatyllo-tab" data-tab="tab-messages"><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'Messages', 'chatyllo' ); ?></a>
        <a href="#tab-display" class="chatyllo-tab" data-tab="tab-display"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Display Rules', 'chatyllo' ); ?></a>
        <a href="#tab-advanced" class="chatyllo-tab" data-tab="tab-advanced"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Advanced', 'chatyllo' ); ?></a>
    </nav>

    <div class="chatyllo-row">
    <div class="chatyllo-col chatyllo-col--8">
    <form id="chatyllo-settings-form">

        <!-- ═══ APPEARANCE TAB ═══ -->
        <div class="chatyllo-tab-panel chatyllo-tab-panel--active" id="tab-appearance">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Widget Appearance', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Position', 'chatyllo' ); ?></label>
                        <select name="widget_position">
                            <option value="bottom-right" <?php selected( $s->get( 'widget_position' ), 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'chatyllo' ); ?></option>
                            <option value="bottom-left" <?php selected( $s->get( 'widget_position' ), 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'chatyllo' ); ?></option>
                        </select>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Where the chat widget appears on your site.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Primary Color', 'chatyllo' ); ?></label>
                        <input type="text" name="widget_primary_color" value="<?php echo esc_attr( $s->get( 'widget_primary_color' ) ); ?>" class="chatyllo-color-picker" />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Main color for the chat bubble, header, and user messages.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Header & User Message Color', 'chatyllo' ); ?></label>
                        <input type="text" name="widget_text_color" value="<?php echo esc_attr( $s->get( 'widget_text_color' ) ); ?>" class="chatyllo-color-picker" />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Text color in the chat header and user message bubbles.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Widget Size', 'chatyllo' ); ?></label>
                        <select name="widget_size">
                            <option value="small" <?php selected( $s->get( 'widget_size' ), 'small' ); ?>><?php esc_html_e( 'Small', 'chatyllo' ); ?></option>
                            <option value="medium" <?php selected( $s->get( 'widget_size' ), 'medium' ); ?>><?php esc_html_e( 'Medium', 'chatyllo' ); ?></option>
                            <option value="large" <?php selected( $s->get( 'widget_size' ), 'large' ); ?>><?php esc_html_e( 'Large', 'chatyllo' ); ?></option>
                        </select>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Size of the floating chat bubble button.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Bot Name', 'chatyllo' ); ?></label>
                        <input type="text" name="bot_name" value="<?php echo esc_attr( $s->get( 'bot_name' ) ); ?>" />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Display name shown in the chat header.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-avatar-field <?php echo ! $is_premium ? 'chatyllo-plan-lock' : ''; ?>">
                        <label><?php esc_html_e( 'Bot Avatar', 'chatyllo' ); ?></label>
                        <?php if ( ! $is_premium ) : ?>
                            <span class="chatyllo-plan-lock__badge"><?php esc_html_e( 'Starter+', 'chatyllo' ); ?> — <a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a></span>
                        <?php endif; ?>
                        <div class="chatyllo-avatar-picker" id="chatyllo-avatar-picker">
                            <div class="chatyllo-avatar-picker__preview" id="chatyllo-avatar-preview">
                                <?php if ( ! empty( $s->get( 'bot_avatar_url' ) ) ) : ?>
                                    <img src="<?php echo esc_url( $s->get( 'bot_avatar_url' ) ); ?>" alt="Avatar" />
                                <?php else : ?>
                                    <svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="#4F46E5"/><path d="M20 12c-2.2 0-4 1.8-4 4v1c0 2.2 1.8 4 4 4s4-1.8 4-4v-1c0-2.2-1.8-4-4-4zm-7 16c0-3 2-5.5 5-6.3.6-.2 1.3-.2 2-.2s1.4 0 2 .2c3 .8 5 3.3 5 6.3" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="chatyllo-avatar-picker__actions">
                                <button type="button" class="button button-secondary" id="chatyllo-avatar-upload" <?php echo ! $is_premium ? 'disabled' : ''; ?>><?php esc_html_e( 'Upload Image', 'chatyllo' ); ?></button>
                                <button type="button" class="button chatyllo-avatar-remove" id="chatyllo-avatar-remove" style="<?php echo empty( $s->get( 'bot_avatar_url' ) ) ? 'display:none' : ''; ?>" <?php echo ! $is_premium ? 'disabled' : ''; ?>><?php esc_html_e( 'Remove', 'chatyllo' ); ?></button>
                            </div>
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'Recommended: square image, at least 80x80px.', 'chatyllo' ); ?></p>
                        </div>
                        <input type="hidden" name="bot_avatar_url" id="chatyllo-avatar-url" value="<?php echo esc_url( $s->get( 'bot_avatar_url' ) ); ?>" />
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Show on Mobile', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="widget_show_on_mobile" value="1" <?php checked( $s->get( 'widget_show_on_mobile' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Display the chat widget on mobile devices.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Sound Effects', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="widget_sound" value="1" <?php checked( $s->get( 'widget_sound' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Play a sound when new messages arrive.', 'chatyllo' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ BEHAVIOR TAB ═══ -->
        <div class="chatyllo-tab-panel" id="tab-behavior">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'AI Behavior', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <!-- Enable Chatyllo — available to ALL plans -->
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Enable Chatyllo', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="enabled" value="1" <?php checked( $s->get( 'enabled' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Master switch to enable or disable the chatbot on your site.', 'chatyllo' ); ?></p>
                    </div>

                    <!-- AI-only fields — visible but locked for Free -->
                    <div class="chatyllo-field <?php echo ! $can_ai ? 'chatyllo-plan-lock' : ''; ?>">
                        <label><?php esc_html_e( 'AI Tone', 'chatyllo' ); ?>
                            <?php if ( ! $can_ai ) : ?><span class="chatyllo-plan-lock__badge"><?php esc_html_e( 'Starter+', 'chatyllo' ); ?> — <a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a></span><?php endif; ?>
                        </label>
                        <select name="ai_tone" <?php echo ! $can_ai ? 'disabled' : ''; ?>>
                            <option value="professional" <?php selected( $s->get( 'ai_tone' ), 'professional' ); ?>><?php esc_html_e( 'Professional', 'chatyllo' ); ?></option>
                            <option value="friendly" <?php selected( $s->get( 'ai_tone' ), 'friendly' ); ?>><?php esc_html_e( 'Friendly', 'chatyllo' ); ?></option>
                            <option value="casual" <?php selected( $s->get( 'ai_tone' ), 'casual' ); ?>><?php esc_html_e( 'Casual', 'chatyllo' ); ?></option>
                        </select>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'The communication style the AI assistant will use when responding.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field <?php echo ! $can_ai ? 'chatyllo-plan-lock' : ''; ?>">
                        <label><?php esc_html_e( 'Max Response Length', 'chatyllo' ); ?>
                            <?php if ( ! $can_ai ) : ?><span class="chatyllo-plan-lock__badge"><?php esc_html_e( 'Starter+', 'chatyllo' ); ?> — <a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a></span><?php endif; ?>
                        </label>
                        <input type="number" name="ai_max_response_length" value="<?php echo esc_attr( $s->get( 'ai_max_response_length' ) ); ?>" min="100" max="800" <?php echo ! $can_ai ? 'disabled' : ''; ?> />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Maximum length of AI responses (100-800). Higher values give longer, more detailed answers.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field <?php echo ! $can_ai ? 'chatyllo-plan-lock' : ''; ?>">
                        <label><?php esc_html_e( 'Conversation Memory', 'chatyllo' ); ?>
                            <?php if ( ! $can_ai ) : ?><span class="chatyllo-plan-lock__badge"><?php esc_html_e( 'Starter+', 'chatyllo' ); ?> — <a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a></span><?php endif; ?>
                        </label>
                        <input type="number" name="chat_max_history" value="<?php echo esc_attr( $s->get( 'chat_max_history' ) ); ?>" min="2" max="30" <?php echo ! $can_ai ? 'disabled' : ''; ?> />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Number of previous messages the AI remembers in each conversation.', 'chatyllo' ); ?></p>
                    </div>

                    <!-- Typing Indicator — available to ALL plans -->
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Typing Indicator', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="typing_indicator" value="1" <?php checked( $s->get( 'typing_indicator' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Show animated dots while the bot is composing a response.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Log Conversations', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="log_chats" value="1" <?php checked( $s->get( 'log_chats' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Save chat conversations for review in Chat Logs.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Log Retention (days)', 'chatyllo' ); ?></label>
                        <input type="number" name="log_retention_days" value="<?php echo esc_attr( $s->get( 'log_retention_days' ) ); ?>" min="1" max="<?php echo esc_attr( $plan_limits['log_days'] > 0 ? $plan_limits['log_days'] : 9999 ); ?>" />
                        <p class="chatyllo-field__desc"><?php
                            if ( $plan_limits['log_days'] < 0 ) { esc_html_e( 'Unlimited retention (Agency plan).', 'chatyllo' ); }
                            else {
                                /* translators: %d: maximum number of days for log retention */
                                printf( esc_html__( 'Max %d days on your current plan.', 'chatyllo' ), (int) $plan_limits['log_days'] );
                            }
                        ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ KNOWLEDGE TAB ═══ -->
        <div class="chatyllo-tab-panel" id="tab-knowledge">
            <?php if ( ! $can_index ) : ?>
            <div class="chatyllo-upgrade-banner" style="margin-bottom:16px;border-left:4px solid var(--chy-primary);">
                <div class="chatyllo-upgrade-banner__icon">🧠</div>
                <div class="chatyllo-upgrade-banner__content">
                    <h3 style="margin:0 0 4px;"><?php esc_html_e( 'Unlock AI Knowledge Base', 'chatyllo' ); ?></h3>
                    <p style="margin:0;"><?php esc_html_e( 'Upgrade to let AI automatically learn from your pages, posts, and products. No API key needed — everything is managed for you.', 'chatyllo' ); ?></p>
                </div>
                <div class="chatyllo-upgrade-banner__action">
                    <a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary chatyllo-upgrade-btn"><?php esc_html_e( 'Upgrade Now', 'chatyllo' ); ?></a>
                </div>
            </div>
            <?php endif; ?>
            <div class="chatyllo-card" style="<?php echo ! $can_index ? 'opacity:.6;pointer-events:none;' : ''; ?>">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Content Indexing', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <p class="chatyllo-field__desc"><?php esc_html_e( 'Choose which content types the AI should learn from.', 'chatyllo' ); ?></p>

                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Index Posts', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="index_posts" value="1" <?php checked( $s->get( 'index_posts' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Include blog posts in the AI knowledge base.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Index Pages', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="index_pages" value="1" <?php checked( $s->get( 'index_pages' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Include pages in the AI knowledge base.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Index WooCommerce Products', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="index_products" value="1" <?php checked( $s->get( 'index_products' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Include WooCommerce products (if available).', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Index Site Info', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="index_site_info" value="1" <?php checked( $s->get( 'index_site_info' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Include site name, description, menus, and contact info.', 'chatyllo' ); ?></p>
                    </div>

                    <!-- Custom Post Types — Business+ -->
                    <div class="chatyllo-field <?php echo ! $can_cpt ? 'chatyllo-plan-lock' : ''; ?>">
                        <label>
                            <?php esc_html_e( 'Custom Post Types', 'chatyllo' ); ?>
                            <?php if ( ! $can_cpt ) : ?>
                                <span class="chatyllo-plan-lock__badge"><?php esc_html_e( 'Business+', 'chatyllo' ); ?> — <a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a></span>
                            <?php endif; ?>
                        </label>
                        <div class="chatyllo-picker" data-source="post_types">
                            <input type="text" class="chatyllo-picker__input" placeholder="<?php esc_attr_e( 'Search custom post types...', 'chatyllo' ); ?>" <?php echo ! $can_cpt ? 'disabled' : ''; ?> />
                            <div class="chatyllo-picker__dropdown"></div>
                            <div class="chatyllo-picker__tags"></div>
                            <input type="hidden" name="index_custom_types" class="chatyllo-picker__value" value="<?php echo esc_attr( $s->get( 'index_custom_types' ) ); ?>" />
                        </div>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Select custom post types to include in the AI knowledge base.', 'chatyllo' ); ?></p>
                    </div>

                    <!-- Exclude Posts -->
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Exclude Posts', 'chatyllo' ); ?></label>
                        <div class="chatyllo-picker" data-source="content" data-post-type="post">
                            <input type="text" class="chatyllo-picker__input" placeholder="<?php esc_attr_e( 'Type to search posts...', 'chatyllo' ); ?>" />
                            <div class="chatyllo-picker__dropdown"></div>
                            <div class="chatyllo-picker__tags"></div>
                            <input type="hidden" name="exclude_post_ids" class="chatyllo-picker__value" value="<?php echo esc_attr( $s->get( 'exclude_ids' ) ); ?>" />
                        </div>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Search and select posts to exclude from AI indexing.', 'chatyllo' ); ?></p>
                    </div>

                    <!-- Exclude Pages -->
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Exclude Pages', 'chatyllo' ); ?></label>
                        <div class="chatyllo-picker" data-source="content" data-post-type="page">
                            <input type="text" class="chatyllo-picker__input" placeholder="<?php esc_attr_e( 'Type to search pages...', 'chatyllo' ); ?>" />
                            <div class="chatyllo-picker__dropdown"></div>
                            <div class="chatyllo-picker__tags"></div>
                            <input type="hidden" name="exclude_page_ids" class="chatyllo-picker__value" value="" />
                        </div>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Search and select pages to exclude from AI indexing.', 'chatyllo' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ BRANDING TAB ═══ -->
        <div class="chatyllo-tab-panel" id="tab-branding">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Branding', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Footer Branding', 'chatyllo' ); ?></label>
                        <div class="chatyllo-branding-options">
                            <?php
                            $branding_options = array(
                                'powered_by' => array(
                                    'label' => __( 'Powered by Chatyllo', 'chatyllo' ),
                                    'desc'  => __( 'Default branding link', 'chatyllo' ),
                                    'plan'  => '',
                                    'color' => '',
                                    'locked'=> false,
                                ),
                                'hidden' => array(
                                    'label' => __( 'Hidden', 'chatyllo' ),
                                    'desc'  => __( 'No branding shown', 'chatyllo' ),
                                    'plan'  => 'Business+',
                                    'color' => '#8B5CF6',
                                    'locked'=> ! $can_hide,
                                ),
                                'custom' => array(
                                    'label' => __( 'Custom / White-label', 'chatyllo' ),
                                    'desc'  => __( 'Your own branding text', 'chatyllo' ),
                                    'plan'  => 'Agency',
                                    'color' => '#F59E0B',
                                    'locked'=> ! $can_custom,
                                ),
                            );
                            $current_mode = $s->get( 'branding_mode' );
                            foreach ( $branding_options as $value => $opt ) :
                                $is_active = ( $current_mode === $value );
                                $is_locked = $opt['locked'];
                            ?>
                            <label class="chatyllo-branding-option <?php echo $is_active ? 'chatyllo-branding-option--active' : ''; ?> <?php echo $is_locked ? 'chatyllo-branding-option--locked' : ''; ?>"
                                   <?php if ( $is_locked ) : ?>onclick="window.location.href='<?php echo esc_url( $upgrade_url ); ?>'; return false;"<?php endif; ?>>
                                <input type="radio" name="branding_mode" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current_mode, $value ); ?> <?php echo $is_locked ? 'disabled' : ''; ?> />
                                <div class="chatyllo-branding-option__content">
                                    <span class="chatyllo-branding-option__label"><?php echo esc_html( $opt['label'] ); ?></span>
                                    <?php if ( $opt['plan'] ) : ?>
                                        <span class="chatyllo-plan-lock__badge" style="<?php echo $opt['color'] ? 'background:' . esc_attr( $opt['color'] ) . '1a;color:' . esc_attr( $opt['color'] ) : ''; ?>"><?php echo esc_html( $opt['plan'] ); ?><?php if ( $is_locked ) : ?> — <?php esc_html_e( 'Upgrade', 'chatyllo' ); ?><?php endif; ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="chatyllo-branding-option__desc"><?php echo esc_html( $opt['desc'] ); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Controls the branding text shown at the bottom of the chat widget.', 'chatyllo' ); ?></p>
                    </div>

                    <div class="chatyllo-field <?php echo ! $can_custom ? 'chatyllo-plan-lock' : ''; ?>" id="chatyllo-custom-brand-field" style="<?php echo ( $current_mode !== 'custom' ) ? 'display:none' : ''; ?>; margin-top:8px;">
                        <label><?php esc_html_e( 'Custom Branding Text', 'chatyllo' ); ?></label>
                        <input type="text" name="custom_branding_text" value="<?php echo esc_attr( $s->get( 'custom_branding_text' ) ); ?>" placeholder="<?php esc_attr_e( 'Powered by YourBrand', 'chatyllo' ); ?>" <?php echo ! $can_custom ? 'disabled' : ''; ?> />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Your custom text to display in the chat footer.', 'chatyllo' ); ?></p>
                    </div>
                    <input type="hidden" name="show_powered_by" value="<?php echo ( $s->get( 'branding_mode' ) === 'powered_by' ) ? '1' : '0'; ?>" />
                </div>
            </div>
        </div>

        <!-- ═══ MESSAGES TAB ═══ -->
        <div class="chatyllo-tab-panel" id="tab-messages">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Custom Messages', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Welcome Message', 'chatyllo' ); ?></label>
                        <textarea name="welcome_message" rows="3"><?php echo esc_textarea( $s->get( 'welcome_message' ) ); ?></textarea>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'First message shown when the chat opens. Leave empty for default.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Input Placeholder', 'chatyllo' ); ?></label>
                        <input type="text" name="placeholder_text" value="<?php echo esc_attr( $s->get( 'placeholder_text' ) ); ?>" />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Placeholder text in the message input field.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Off-Topic Response', 'chatyllo' ); ?></label>
                        <textarea name="off_topic_message" rows="3"><?php echo esc_textarea( $s->get( 'off_topic_message' ) ); ?></textarea>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Shown when users ask questions unrelated to your website.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'AI Offline Message', 'chatyllo' ); ?></label>
                        <textarea name="fallback_message" rows="3"><?php echo esc_textarea( $s->get( 'fallback_message' ) ); ?></textarea>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Shown when the AI service is temporarily unavailable.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'No FAQ Match Message', 'chatyllo' ); ?></label>
                        <textarea name="no_match_message" rows="3"><?php echo esc_textarea( $s->get( 'no_match_message' ) ); ?></textarea>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Shown when no matching FAQ answer is found.', 'chatyllo' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ DISPLAY RULES TAB ═══ -->
        <div class="chatyllo-tab-panel" id="tab-display">
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Display Rules', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Show on All Pages', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch"><input type="checkbox" name="show_on_all_pages" value="1" <?php checked( $s->get( 'show_on_all_pages' ) ); ?> /><span class="chatyllo-switch__slider"></span></label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'When enabled, the widget shows everywhere. Disable to use the rules below.', 'chatyllo' ); ?></p>
                    </div>

                    <div id="chatyllo-display-conditional">
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Show Only on Pages', 'chatyllo' ); ?></label>
                            <div class="chatyllo-picker" data-source="content" data-post-type="page">
                                <input type="text" class="chatyllo-picker__input" placeholder="<?php esc_attr_e( 'Type to search pages...', 'chatyllo' ); ?>" />
                                <div class="chatyllo-picker__dropdown"></div>
                                <div class="chatyllo-picker__tags"></div>
                                <input type="hidden" name="show_on_pages" class="chatyllo-picker__value" value="<?php echo esc_attr( $s->get( 'show_on_pages' ) ); ?>" />
                            </div>
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'Only show the widget on these specific pages (whitelist). Leave empty to ignore.', 'chatyllo' ); ?></p>
                        </div>
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Hide on Pages', 'chatyllo' ); ?></label>
                            <div class="chatyllo-picker" data-source="content" data-post-type="page">
                                <input type="text" class="chatyllo-picker__input" placeholder="<?php esc_attr_e( 'Type to search pages...', 'chatyllo' ); ?>" />
                                <div class="chatyllo-picker__dropdown"></div>
                                <div class="chatyllo-picker__tags"></div>
                                <input type="hidden" name="hide_on_pages" class="chatyllo-picker__value" value="<?php echo esc_attr( $s->get( 'hide_on_pages' ) ); ?>" />
                            </div>
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'Hide the widget on these specific pages (blacklist).', 'chatyllo' ); ?></p>
                        </div>
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Show for Roles', 'chatyllo' ); ?></label>
                            <div class="chatyllo-picker" data-source="roles">
                                <input type="text" class="chatyllo-picker__input" placeholder="<?php esc_attr_e( 'Click to select roles... (empty = everyone)', 'chatyllo' ); ?>" />
                                <div class="chatyllo-picker__dropdown"></div>
                                <div class="chatyllo-picker__tags"></div>
                                <input type="hidden" name="show_for_roles" class="chatyllo-picker__value" value="<?php echo esc_attr( $s->get( 'show_for_roles' ) ); ?>" />
                            </div>
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'Only show the widget to users with these roles. Leave empty to show to everyone.', 'chatyllo' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ ADVANCED TAB ═══ -->
        <div class="chatyllo-tab-panel" id="tab-advanced">
            <!-- GDPR & Privacy -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><span class="dashicons dashicons-shield" style="color:#22C55E"></span> <?php esc_html_e( 'Privacy & GDPR', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-field">
                        <label><?php esc_html_e( 'Privacy Policy URL', 'chatyllo' ); ?></label>
                        <input type="url" name="privacy_policy_url" value="<?php echo esc_url( $s->get( 'privacy_policy_url' ) ); ?>" placeholder="https://yoursite.com/privacy-policy" />
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Link to your privacy policy. Shown in the chat widget consent notice.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Require consent before logging', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch">
                            <input type="checkbox" name="require_chat_consent" value="1" <?php checked( $s->get( 'require_chat_consent' ) ); ?> />
                            <span class="chatyllo-switch__slider"></span>
                        </label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'When enabled, visitors must accept a privacy notice before their conversations are logged. Chat still works without consent, but no data is stored.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Anonymize IP addresses', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch">
                            <input type="checkbox" name="anonymize_ip" value="1" <?php checked( $s->get( 'anonymize_ip' ) ); ?> />
                            <span class="chatyllo-switch__slider"></span>
                        </label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Do not store visitor IP addresses in chat logs. Recommended for GDPR compliance.', 'chatyllo' ); ?></p>
                    </div>
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Anonymize browser info', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch">
                            <input type="checkbox" name="anonymize_user_agent" value="1" <?php checked( $s->get( 'anonymize_user_agent' ) ); ?> />
                            <span class="chatyllo-switch__slider"></span>
                        </label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Do not store browser/device information in chat logs.', 'chatyllo' ); ?></p>
                    </div>
                    <p class="chatyllo-field__desc" style="margin-top:12px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:6px;padding:10px 14px;color:#166534;">
                        <span class="dashicons dashicons-info" style="font-size:14px;margin-right:4px"></span>
                        <?php esc_html_e( 'Chatyllo is compatible with WordPress Privacy Tools (Tools > Export/Erase Personal Data) and major cookie consent plugins (CookieYes, Complianz, CookieBot, iubenda). Storage used: localStorage keys "chatyllo_sid" (functional, session) and "chatyllo_consent" (functional, persistent).', 'chatyllo' ); ?>
                    </p>
                </div>
            </div>

            <!-- Data Preservation -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Data Preservation', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <div class="chatyllo-field chatyllo-field--toggle">
                        <label><?php esc_html_e( 'Keep data when plugin is deleted', 'chatyllo' ); ?></label>
                        <label class="chatyllo-switch">
                            <input type="checkbox" name="keep_data_on_uninstall" value="1" <?php checked( $s->get( 'keep_data_on_uninstall' ) ); ?> />
                            <span class="chatyllo-switch__slider"></span>
                        </label>
                        <p class="chatyllo-field__desc"><?php esc_html_e( 'Preserve settings, FAQs, knowledge base, and chat logs if you delete and reinstall the plugin.', 'chatyllo' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Export -->
            <div class="chatyllo-card" style="margin-bottom:20px;">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Export / Import', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <p class="chatyllo-field__desc" style="margin-bottom:12px;"><?php esc_html_e( 'Export your plugin configuration, FAQs, and statistics for backup or migration.', 'chatyllo' ); ?></p>
                    <button type="button" class="button button-primary" id="chatyllo-export-data-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Export Data (JSON)', 'chatyllo' ); ?>
                    </button>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="chatyllo-card">
                <h2 class="chatyllo-card__title"><?php esc_html_e( 'Maintenance', 'chatyllo' ); ?></h2>
                <div class="chatyllo-card__body">
                    <p class="chatyllo-field__desc" style="margin-bottom:12px;">
                        <?php esc_html_e( 'Automatic maintenance runs weekly: cleans expired logs and cache, removes orphan data, and optimizes database tables.', 'chatyllo' ); ?>
                    </p>
                    <p style="font-size:13px;color:#64748B;margin-bottom:12px;">
                        <?php esc_html_e( 'Last maintenance:', 'chatyllo' ); ?>
                        <strong><?php echo esc_html( get_option( 'chatyllo_last_maintenance', __( 'Never', 'chatyllo' ) ) ); ?></strong>
                    </p>
                    <button type="button" class="button" id="chatyllo-run-maintenance-btn">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e( 'Run Maintenance Now', 'chatyllo' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="chatyllo-form-footer">
            <button type="submit" class="button button-primary button-hero" id="chatyllo-save-settings">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e( 'Save Settings', 'chatyllo' ); ?>
            </button>
        </div>
    </form>
    </div><!-- .chatyllo-col--8 -->

    <!-- Settings Sidebar -->
    <div class="chatyllo-col chatyllo-col--4">
        <!-- Widget Preview (NOT sticky) -->
        <div class="chatyllo-card">
            <h2 class="chatyllo-card__title">
                <span class="dashicons dashicons-visibility" style="color:#8B5CF6"></span>
                <?php esc_html_e( 'Widget Preview', 'chatyllo' ); ?>
            </h2>
            <div class="chatyllo-card__body" style="padding:16px;">
                <div style="background:#F8FAFC;border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;">
                    <div style="background:<?php echo esc_attr( $s->get( 'widget_primary_color' ) ); ?>;color:<?php echo esc_attr( $s->get( 'widget_text_color' ) ); ?>;padding:14px 16px;display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <?php if ( ! empty( $s->get( 'bot_avatar_url' ) ) ) : ?>
                                <img src="<?php echo esc_url( $s->get( 'bot_avatar_url' ) ); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                            <?php else : ?>
                                <svg viewBox="0 0 120 120" fill="none" style="width:22px;height:22px;"><path d="M95.5 18H24.5C17.6 18 12 23.6 12 30.5v45C12 82.4 17.6 88 24.5 88H36l-8.5 19.2c-.6 1.4.8 2.8 2.2 2.1L58 95h37.5c6.9 0 12.5-5.6 12.5-12.5v-45C108 30.6 102.4 18 95.5 18z" fill="none" stroke="#fff" stroke-width="9" stroke-linecap="round"/><path d="M42 60c6 12 30 12 36 0" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"/></svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-size:14px;font-weight:700;"><?php echo esc_html( $s->get( 'bot_name' ) ); ?></div>
                            <div style="font-size:11px;opacity:.8;">● <?php esc_html_e( 'Online', 'chatyllo' ); ?></div>
                        </div>
                    </div>
                    <div style="padding:14px;min-height:100px;">
                        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px 12px 12px 4px;padding:10px 12px;font-size:12px;color:#1E293B;max-width:85%;margin-bottom:10px;"><?php echo esc_html( $s->get_welcome_message() ); ?></div>
                        <div style="background:<?php echo esc_attr( $s->get( 'widget_primary_color' ) ); ?>;color:<?php echo esc_attr( $s->get( 'widget_text_color' ) ); ?>;border-radius:12px 12px 4px 12px;padding:10px 12px;font-size:12px;max-width:75%;margin-left:auto;margin-bottom:10px;"><?php esc_html_e( 'Hello!', 'chatyllo' ); ?></div>
                        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px 12px 12px 4px;padding:10px 12px;font-size:12px;color:#1E293B;max-width:85%;"><?php esc_html_e( 'How can I help you today?', 'chatyllo' ); ?></div>
                    </div>
                    <div style="padding:8px 14px;border-top:1px solid #E2E8F0;text-align:center;font-size:10px;color:#94A3B8;"><?php esc_html_e( 'Powered by Chatyllo', 'chatyllo' ); ?></div>
                </div>
                <p style="text-align:center;margin:10px 0 0;font-size:11px;color:#94A3B8;"><?php esc_html_e( 'Preview updates when you save settings.', 'chatyllo' ); ?></p>
            </div>
        </div>

        <!-- Quick Help — Accordion FAQ -->
        <div class="chatyllo-card" style="margin-top:16px;">
            <h2 class="chatyllo-card__title">
                <span class="dashicons dashicons-sos" style="color:#F59E0B"></span>
                <?php esc_html_e( 'Quick Help', 'chatyllo' ); ?>
            </h2>
            <div class="chatyllo-card__body" style="padding:0;">
                <?php
                $help_faq = array(
                    array(
                        __( 'How do I customize colors and size?', 'chatyllo' ),
                        __( 'Go to the Appearance tab. You can change the Primary Color (chat bubble, header, user messages), the Header & User Message text color, and the Widget Size (Small, Medium, Large). Changes apply immediately after saving.', 'chatyllo' ),
                    ),
                    array(
                        __( 'How does the AI chatbot work?', 'chatyllo' ),
                        __( 'The AI automatically reads your website content (pages, posts, products) and uses it to answer visitor questions. No API key is needed — everything is managed for you. Set the AI Tone (Professional, Friendly, Casual) and Memory in the Behavior tab. Requires a Starter plan or higher.', 'chatyllo' ),
                    ),
                    array(
                        __( 'What is the Knowledge Base?', 'chatyllo' ),
                        __( 'The Knowledge Base is the AI\'s brain. It indexes your pages, posts, WooCommerce products, and site info. Starter plans auto-index weekly, Business/Agency every 12 hours. Configure what to index in the Knowledge tab.', 'chatyllo' ),
                    ),
                    array(
                        __( 'How do FAQ and AI work together?', 'chatyllo' ),
                        __( 'FAQs use a smart keyword matching algorithm that works on ALL plans — no AI credits consumed. When AI is unavailable (limits reached, maintenance), the chatbot automatically switches to FAQ mode. Keep your FAQs updated for the best fallback experience.', 'chatyllo' ),
                    ),
                    array(
                        __( 'How can I remove the Chatyllo branding?', 'chatyllo' ),
                        __( 'The "Powered by Chatyllo" footer can be hidden on Business plans or replaced with your own text on Agency plans. Go to the Branding tab to configure this.', 'chatyllo' ),
                    ),
                    array(
                        __( 'How do I set up GDPR compliance?', 'chatyllo' ),
                        __( 'Go to the Advanced tab. Enable "Require consent before logging" to show a privacy banner. Enable "Anonymize IP" (recommended). Add your Privacy Policy URL. Chatyllo also integrates with WordPress Privacy Tools and major cookie plugins (CookieYes, Complianz, CookieBot).', 'chatyllo' ),
                    ),
                    array(
                        __( 'How do Display Rules work?', 'chatyllo' ),
                        __( 'By default the widget shows on all pages. Disable "Show on All Pages" to use rules: whitelist specific pages, blacklist pages, or restrict by user role. Use the search-and-pick selectors to easily find pages.', 'chatyllo' ),
                    ),
                    array(
                        __( 'Can I export my data?', 'chatyllo' ),
                        __( 'Yes! Go to Advanced tab → Export Data (JSON) to download your settings, FAQs, and statistics. Statistics can also be exported as CSV from the Statistics page.', 'chatyllo' ),
                    ),
                );
                foreach ( $help_faq as $idx => $faq ) : ?>
                <div class="chatyllo-accordion" style="border-bottom:1px solid #F1F5F9;">
                    <button type="button" class="chatyllo-accordion__toggle" data-idx="<?php echo esc_attr( $idx ); ?>" style="display:flex;align-items:center;gap:8px;width:100%;padding:12px 16px;background:none;border:none;cursor:pointer;text-align:left;font-size:13px;font-weight:600;color:#1E293B;">
                        <span class="dashicons dashicons-arrow-right-alt2 chatyllo-accordion__icon" style="color:#94A3B8;font-size:14px;width:14px;height:14px;transition:transform .2s;"></span>
                        <?php echo esc_html( $faq[0] ); ?>
                    </button>
                    <div class="chatyllo-accordion__content" data-idx="<?php echo esc_attr( $idx ); ?>" style="display:none;padding:0 16px 14px 38px;font-size:13px;color:#64748B;line-height:1.6;">
                        <?php echo esc_html( $faq[1] ); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div><!-- .chatyllo-col--4 -->
    </div><!-- .chatyllo-row -->
</div>
