<?php
namespace Chatyllo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend Widget — renders the chat bubble and panel on the site.
 */
final class Widget {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_widget' ), 100 );
    }

    /**
     * Get the effective branding mode, enforced by plan.
     *
     * Free/Starter → always 'powered_by' (forced).
     * Business     → 'powered_by' or 'hidden'.
     * Agency       → 'powered_by', 'hidden', or 'custom'.
     */
    private function get_effective_branding_mode() {
        if ( function_exists( 'chatyllo_premium_get_branding_mode' ) ) {
            return chatyllo_premium_get_branding_mode();
        }
        return 'powered_by';
    }

    /* ── Should widget display? ────────────────────────────────────── */
    private function should_display() {
        $settings = Settings::instance();

        if ( ! $settings->get( 'enabled' ) ) {
            return false;
        }

        if ( is_admin() ) {
            return false;
        }

        // Mobile check.
        if ( ! $settings->get( 'widget_show_on_mobile' ) && wp_is_mobile() ) {
            return false;
        }

        // Page rules.
        if ( ! $settings->get( 'show_on_all_pages' ) ) {
            $show_on = $settings->get( 'show_on_pages' );
            if ( ! empty( $show_on ) ) {
                $ids = array_map( 'absint', explode( ',', $show_on ) );
                if ( ! in_array( get_the_ID(), $ids, true ) ) {
                    return false;
                }
            }
        }

        $hide_on = $settings->get( 'hide_on_pages' );
        if ( ! empty( $hide_on ) ) {
            $ids = array_map( 'absint', explode( ',', $hide_on ) );
            if ( in_array( get_the_ID(), $ids, true ) ) {
                return false;
            }
        }

        // Role check.
        $roles = $settings->get( 'show_for_roles' );
        if ( ! empty( $roles ) ) {
            if ( ! is_user_logged_in() ) {
                return false;
            }
            $allowed = array_map( 'trim', explode( ',', $roles ) );
            $user    = wp_get_current_user();
            if ( empty( array_intersect( $allowed, $user->roles ) ) ) {
                return false;
            }
        }

        return true;
    }

    /* ── Enqueue frontend assets ───────────────────────────────────── */
    public function enqueue_assets() {
        if ( ! $this->should_display() ) {
            return;
        }

        $settings = Settings::instance();

        $css_ver = @filemtime( CHATYLLO_PATH . 'public/css/chatyllo-widget.css' ) ?: CHATYLLO_VERSION;
        $js_ver  = @filemtime( CHATYLLO_PATH . 'public/js/chatyllo-widget.js' ) ?: CHATYLLO_VERSION;

        wp_enqueue_style(
            'chatyllo-widget',
            CHATYLLO_URL . 'public/css/chatyllo-widget.css',
            array(),
            $css_ver
        );

        wp_enqueue_script(
            'chatyllo-widget',
            CHATYLLO_URL . 'public/js/chatyllo-widget.js',
            array(),
            $js_ver,
            true
        );

        // Prevent cache plugins from minifying/concatenating the widget script.
        wp_script_add_data( 'chatyllo-widget', 'async', true );

        $branding_mode = $this->get_effective_branding_mode();

        // Base config — always present in both free and premium.
        $config = array(
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'chatyllo_public_nonce' ),
            'position'         => $settings->get( 'widget_position' ),
            'primaryColor'     => $settings->get( 'widget_primary_color' ),
            'textColor'        => $settings->get( 'widget_text_color' ),
            'size'             => $settings->get( 'widget_size' ),
            'zIndex'           => $settings->get( 'widget_z_index' ),
            'openDelay'        => $settings->get( 'widget_open_delay' ),
            'sound'            => $settings->get( 'widget_sound' ),
            'botName'          => $settings->get( 'bot_name' ),
            'botAvatar'        => $settings->get( 'bot_avatar_url' ),
            'welcomeMessage'   => $settings->get_welcome_message(),
            'placeholderText'  => $settings->get_placeholder_text(),
            'typingIndicator'  => $settings->get( 'typing_indicator' ),
            'showPoweredBy'    => true,
            'maxHistory'       => $settings->get( 'chat_max_history' ),
            'isPremium'        => false,
            'planName'         => 'free',
            'brandingMode'     => 'powered_by',
            'customBrandText'  => '',
            'aiActive'         => false,
            'fallbackMessage'  => $settings->get_fallback_message(),
            'i18n'             => array(
                'send'           => __( 'Send', 'chatyllo' ),
                'close'          => __( 'Close', 'chatyllo' ),
                'minimize'       => __( 'Minimize', 'chatyllo' ),
                'typing'         => __( 'is typing...', 'chatyllo' ),
                'aiMode'         => __( 'AI Powered', 'chatyllo' ),
                'offlineMode'    => __( 'Standard Mode', 'chatyllo' ),
                'poweredBy'      => __( 'Powered by Chatyllo', 'chatyllo' ),
                'errorMessage'   => __( 'Something went wrong. Please try again.', 'chatyllo' ),
                'rateLimited'    => __( 'Please wait a moment before sending another message.', 'chatyllo' ),
                'newConversation'=> __( 'New conversation', 'chatyllo' ),
                'consentText'    => __( 'This chat may store messages to improve our service.', 'chatyllo' ),
                'consentAccept'  => __( 'Accept & Chat', 'chatyllo' ),
                'privacyPolicy'  => __( 'Privacy Policy', 'chatyllo' ),
            ),
            'requireConsent'   => (bool) $settings->get( 'require_chat_consent' ),
            'privacyPolicyUrl' => $settings->get( 'privacy_policy_url' ),
        );

        // Premium: override config with plan-aware values.
        if ( function_exists( 'chatyllo_premium_widget_config' ) ) {
            chatyllo_premium_widget_config( $config );
        }

        wp_localize_script( 'chatyllo-widget', 'chatylloConfig', $config );
    }

    /* ── Render widget HTML shell ──────────────────────────────────── */
    public function render_widget() {
        if ( ! $this->should_display() ) {
            return;
        }

        $settings   = Settings::instance();
        $position   = $settings->get( 'widget_position' );
        $ai_active  = Proxy::instance()->is_ai_active();
        ?>
        <div id="chatyllo-root"
             class="chatyllo-position-<?php echo esc_attr( $position ); ?>"
             data-ai-active="<?php echo $ai_active ? '1' : '0'; ?>"
             style="z-index:<?php echo absint( $settings->get( 'widget_z_index' ) ); ?>">
        </div>
        <?php
    }
}
