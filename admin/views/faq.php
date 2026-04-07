<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$ai           = \Chatyllo\Proxy::instance()->is_ai_active();
$plan_name    = chatyllo_get_plan_name();
$plan_limits  = chatyllo_get_plan_limits();
$faq_count    = \Chatyllo\FAQ::instance()->get_count();
$faq_active   = \Chatyllo\FAQ::instance()->get_active_count();
$faq_ai_count = \Chatyllo\FAQ::instance()->get_ai_faq_count();
$faq_limit    = $plan_limits['faq_limit'];
$can_gen      = function_exists( 'chatyllo_can_generate_faqs' ) && chatyllo_can_generate_faqs();
$faq_gen_limit = $plan_limits['faq_gen_limit'] ?? 0;
$upgrade_url  = function_exists( 'cha_fs' ) ? cha_fs()->get_upgrade_url() : '#';
$wp_locale    = get_locale();
?>
<div class="wrap chatyllo-wrap">
    <div class="chatyllo-header">
        <div class="chatyllo-header__left">
            <h1 class="chatyllo-header__title"><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'FAQ / Smart Q&A', 'chatyllo' ); ?></h1>
        </div>
        <div class="chatyllo-header__right">
            <div id="chatyllo-ai-status" class="chatyllo-ai-badge <?php echo $ai ? 'chatyllo-ai-badge--active' : 'chatyllo-ai-badge--inactive'; ?>">
                <span class="chatyllo-ai-badge__dot"></span>
                <span class="chatyllo-ai-badge__text"><?php echo $ai ? esc_html__( 'AI Active', 'chatyllo' ) : esc_html__( 'FAQ Only Mode', 'chatyllo' ); ?></span>
            </div>
        </div>
    </div>
    <div class="chatyllo-toast" id="chatyllo-toast"></div>

    <!-- Info banner -->
    <div style="background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:1px solid #C7D2FE;border-radius:10px;padding:18px 22px;margin-bottom:20px;">
        <p style="margin:0 0 8px;font-size:14px;font-weight:600;color:#3730A3;"><?php esc_html_e( 'How FAQ / Smart Q&A works', 'chatyllo' ); ?></p>
        <p style="margin:0;font-size:13px;color:#4338CA;line-height:1.6;">
            <?php esc_html_e( 'FAQs are powered by Chatyllo\'s built-in smart matching algorithm — no AI credits are consumed. They work on all plans, including Free. When AI responses are unavailable (daily/monthly limits reached, temporary service issues, or network errors), the chatbot automatically switches to FAQ mode. This ensures your visitors always get helpful answers.', 'chatyllo' ); ?>
        </p>
        <?php if ( $can_gen ) : ?>
        <p style="margin:8px 0 0;font-size:13px;color:#4338CA;line-height:1.6;">
            <strong><?php esc_html_e( 'AI Generation:', 'chatyllo' ); ?></strong>
            <?php esc_html_e( 'You can generate FAQs multiple times. Each generation analyzes your knowledge base and adds new Q&A pairs. Existing AI-generated FAQs are updated if needed — no duplicates are created. The more content you have indexed, the better and more complete the generated FAQs will be.', 'chatyllo' ); ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- AI FAQ Generation Card -->
    <div class="chatyllo-card" style="border-left:4px solid #8B5CF6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
        <div class="chatyllo-card__body">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <h3 style="margin:0 0 4px;font-size:15px;color:#4C1D95;">
                        <span class="dashicons dashicons-superhero-alt" style="color:#8B5CF6;margin-right:4px;"></span>
                        <?php esc_html_e( 'Generate FAQs with AI', 'chatyllo' ); ?>
                    </h3>
                    <p style="margin:0;font-size:13px;color:#6D28D9;">
                        <?php esc_html_e( 'AI analyzes your indexed content and creates relevant Q&A pairs automatically.', 'chatyllo' ); ?>
                    </p>
                </div>
                <?php if ( $can_gen ) : ?>
                <select id="chatyllo-ai-faq-language" style="min-width:160px;border-radius:6px;border:1px solid #C4B5FD;padding:6px 10px;font-size:13px;">
                </select>
                <button type="button" class="button button-primary" id="chatyllo-generate-faqs-btn" style="background:#8B5CF6;border-color:#7C3AED;">
                    <span class="dashicons dashicons-superhero-alt"></span>
                    <?php esc_html_e( 'Generate', 'chatyllo' ); ?>
                </button>
                <span id="chatyllo-faq-gen-usage" style="font-size:12px;color:#6D28D9;white-space:nowrap;background:#EDE9FE;padding:4px 10px;border-radius:12px;font-weight:500;"></span>
                <?php else : ?>
                <a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary chatyllo-upgrade-btn" style="background:#8B5CF6;border-color:#7C3AED;">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e( 'Upgrade to Starter', 'chatyllo' ); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Progress overlay -->
            <div id="chatyllo-faq-gen-progress" style="display:none;margin-top:16px;">
                <div class="chatyllo-faq-gen-bar"><div class="chatyllo-faq-gen-bar__fill" id="chatyllo-faq-gen-fill"></div></div>
                <p style="margin:6px 0 0;font-size:13px;font-weight:600;color:#4C1D95;" id="chatyllo-faq-gen-text"><?php esc_html_e( 'AI is analyzing your content...', 'chatyllo' ); ?></p>
                <p style="margin:2px 0 0;font-size:12px;color:#DC2626;"><?php esc_html_e( 'Please don\'t reload or navigate away from this page.', 'chatyllo' ); ?></p>
            </div>
        </div>
    </div>

    <?php if ( $faq_ai_count > 0 && 'free' === $plan_name ) : ?>
    <div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#92400E;">
        <strong><?php esc_html_e( 'Note:', 'chatyllo' ); ?></strong>
        <?php
            /* translators: %d: number of AI-generated FAQ entries */
            printf( esc_html__( 'You have %d AI-generated FAQs that are currently inactive because you are on the Free plan. Upgrade to any paid plan to reactivate them.', 'chatyllo' ), (int) $faq_ai_count );
        ?>
        <a href="<?php echo esc_url( $upgrade_url ); ?>" style="color:#92400E;font-weight:600;"><?php esc_html_e( 'Upgrade', 'chatyllo' ); ?></a>
    </div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="chatyllo-stats-grid" style="margin-bottom:20px;">
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--blue"><span class="dashicons dashicons-editor-help"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( $faq_count ); ?><?php if ( $faq_limit > 0 ) : ?><small style="font-size:12px;color:#94A3B8"> / <?php echo esc_html( $faq_limit ); ?></small><?php endif; ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Total FAQs', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--green"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo esc_html( $faq_active ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Active', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon chatyllo-stat-card__icon--purple"><span class="dashicons dashicons-category"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number" id="chatyllo-faq-cat-count">-</div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Categories', 'chatyllo' ); ?></div>
            </div>
        </div>
        <div class="chatyllo-stat-card">
            <div class="chatyllo-stat-card__icon <?php echo $ai ? 'chatyllo-stat-card__icon--green' : 'chatyllo-stat-card__icon--orange'; ?>"><span class="dashicons <?php echo $ai ? 'dashicons-superhero-alt' : 'dashicons-editor-help'; ?>"></span></div>
            <div class="chatyllo-stat-card__content">
                <div class="chatyllo-stat-card__number"><?php echo $ai ? esc_html__( 'AI + FAQ', 'chatyllo' ) : esc_html__( 'FAQ Only', 'chatyllo' ); ?></div>
                <div class="chatyllo-stat-card__label"><?php esc_html_e( 'Current Mode', 'chatyllo' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Add/Edit FAQ Form -->
    <div class="chatyllo-card">
        <h2 class="chatyllo-card__title" id="chatyllo-faq-form-title">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'Add New FAQ', 'chatyllo' ); ?>
        </h2>
        <div class="chatyllo-card__body">
            <form id="chatyllo-faq-form">
                <input type="hidden" name="faq_id" id="chatyllo-faq-id" value="0" />
                <div class="chatyllo-field">
                    <label><?php esc_html_e( 'Question', 'chatyllo' ); ?></label>
                    <input type="text" id="chatyllo-faq-question" required placeholder="<?php esc_attr_e( 'e.g. What are your shipping options?', 'chatyllo' ); ?>" />
                    <p class="chatyllo-field__desc"><?php esc_html_e( 'The question visitors might ask. Write it naturally.', 'chatyllo' ); ?></p>
                </div>
                <div class="chatyllo-field">
                    <label><?php esc_html_e( 'Answer', 'chatyllo' ); ?></label>
                    <textarea id="chatyllo-faq-answer" rows="4" required placeholder="<?php esc_attr_e( 'Write a clear, helpful answer...', 'chatyllo' ); ?>"></textarea>
                    <p class="chatyllo-field__desc"><?php esc_html_e( 'The response shown to visitors. Supports **bold** and *italic* formatting.', 'chatyllo' ); ?></p>
                </div>
                <div class="chatyllo-row">
                    <div class="chatyllo-col chatyllo-col--4">
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Keywords', 'chatyllo' ); ?></label>
                            <input type="text" id="chatyllo-faq-keywords" placeholder="<?php esc_attr_e( 'Auto-generated if empty', 'chatyllo' ); ?>" />
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'Words that trigger this answer.', 'chatyllo' ); ?></p>
                        </div>
                    </div>
                    <div class="chatyllo-col chatyllo-col--3">
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Category', 'chatyllo' ); ?></label>
                            <div class="chatyllo-picker" data-source="faq_categories" style="position:relative;">
                                <input type="text" class="chatyllo-picker__input" id="chatyllo-faq-category" placeholder="<?php esc_attr_e( 'Select or type new...', 'chatyllo' ); ?>" autocomplete="off" />
                                <div class="chatyllo-picker__dropdown" id="chatyllo-faq-cat-dropdown"></div>
                            </div>
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'Group related FAQs.', 'chatyllo' ); ?></p>
                        </div>
                    </div>
                    <div class="chatyllo-col chatyllo-col--3">
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Language', 'chatyllo' ); ?></label>
                            <select id="chatyllo-faq-language" class="chatyllo-faq-lang-select"></select>
                            <p class="chatyllo-field__desc"><?php esc_html_e( 'FAQ language.', 'chatyllo' ); ?></p>
                        </div>
                    </div>
                    <div class="chatyllo-col chatyllo-col--2">
                        <div class="chatyllo-field">
                            <label><?php esc_html_e( 'Priority', 'chatyllo' ); ?></label>
                            <input type="number" id="chatyllo-faq-order" value="0" min="0" />
                            <p class="chatyllo-field__desc"><?php esc_html_e( '0 = default.', 'chatyllo' ); ?></p>
                        </div>
                    </div>
                </div>
                <div class="chatyllo-field chatyllo-field--toggle" id="chatyllo-faq-active-field" style="display:none;">
                    <label><?php esc_html_e( 'Active', 'chatyllo' ); ?></label>
                    <label class="chatyllo-switch">
                        <input type="checkbox" id="chatyllo-faq-active" value="1" checked />
                        <span class="chatyllo-switch__slider"></span>
                    </label>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="button button-primary" id="chatyllo-faq-submit"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Save FAQ', 'chatyllo' ); ?></button>
                    <button type="button" class="button" id="chatyllo-faq-cancel" style="display:none"><?php esc_html_e( 'Cancel Edit', 'chatyllo' ); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category filter + FAQ List -->
    <div class="chatyllo-card">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px 0;flex-wrap:wrap;gap:8px;">
            <h2 class="chatyllo-card__title" style="margin:0;padding:0;border:none;"><?php esc_html_e( 'FAQ List', 'chatyllo' ); ?></h2>
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="chatyllo-faq-filter-cat" style="min-width:140px;font-size:13px;">
                    <option value=""><?php esc_html_e( 'All Categories', 'chatyllo' ); ?></option>
                </select>
                <select id="chatyllo-faq-filter-source" style="min-width:120px;font-size:13px;">
                    <option value=""><?php esc_html_e( 'All Sources', 'chatyllo' ); ?></option>
                    <option value="manual"><?php esc_html_e( 'Manual', 'chatyllo' ); ?></option>
                    <option value="ai"><?php esc_html_e( 'AI Generated', 'chatyllo' ); ?></option>
                </select>
            </div>
        </div>
        <div class="chatyllo-card__body">
            <table class="widefat striped" id="chatyllo-faq-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th><?php esc_html_e( 'Question', 'chatyllo' ); ?></th>
                        <th style="width:110px"><?php esc_html_e( 'Category', 'chatyllo' ); ?></th>
                        <th style="width:50px;text-align:center"><?php esc_html_e( 'Lang', 'chatyllo' ); ?></th>
                        <th style="width:70px;text-align:center"><?php esc_html_e( 'Source', 'chatyllo' ); ?></th>
                        <th style="width:50px;text-align:center"><?php esc_html_e( 'Active', 'chatyllo' ); ?></th>
                        <th style="width:130px"><?php esc_html_e( 'Actions', 'chatyllo' ); ?></th>
                    </tr>
                </thead>
                <tbody id="chatyllo-faq-list"><tr><td colspan="7"><?php esc_html_e( 'Loading...', 'chatyllo' ); ?></td></tr></tbody>
            </table>
            <div id="chatyllo-faq-pagination" style="display:flex;align-items:center;gap:6px;padding:12px 0;flex-wrap:wrap;"></div>
        </div>
    </div>
</div>
