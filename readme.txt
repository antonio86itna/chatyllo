=== Chatyllo ===
Contributors: wpezo, freemius
Tags: chatbot, ai chatbot, live chat, customer support, faq
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart AI-powered chatbot that auto-learns your website content. Install, activate, done. Zero configuration needed.

== Description ==

**Chatyllo** is the easiest way to add a smart chatbot to your WordPress site. It works right out of the box with zero configuration.

= Free Version =
* Beautiful, animated chat widget
* Manual Q&A system with smart keyword matching (up to 100 FAQ entries)
* Customizable appearance (colors, position, size, bot name)
* Mobile responsive
* Display rules (show/hide on specific pages, user roles)
* Chat logs with session details
* Statistics and analytics dashboard
* Real-time service status monitoring
* GDPR compliant with consent management
* Multi-language ready (20+ languages)

= Premium Version =
* **AI-Powered Responses** — Automatically learns from your pages, posts, and products
* **AI FAQ Auto-Generation** — One-click AI-generated FAQ from your content
* **WooCommerce Deep Integration** — Products, prices, attributes, stock in chat
* **Elementor Compatible** — Extracts content from Elementor pages
* **Knowledge Base Auto-Indexing** — Weekly (Starter) or every 12h (Business/Agency)
* **Context-Aware** — Off-topic detection and smart redirects
* **Conversation Memory** — Multi-turn conversations with AI context
* **Response Caching** — Faster responses, optimized token usage
* **Custom Branding** — Hide or customize "Powered by" text
* **Unlimited FAQs** — No limit on FAQ entries
* **Extended Log Retention** — Up to unlimited chat history
* **Data Export** — Export settings, FAQs, and statistics

= How It Works =

**Free:** Create FAQ pairs in the dashboard. The chatbot matches visitor questions to your answers using a smart keyword matching algorithm. No AI credits consumed.

**Premium:** Chatyllo scans your entire website — pages, posts, WooCommerce products, menus, site info — and builds an AI knowledge base. When visitors ask questions, the AI responds intelligently using your actual content. No API keys needed from you — everything is managed centrally.

When AI responses are unavailable (daily/monthly limits reached or temporary service issues), the chatbot automatically switches to FAQ mode. Your visitors always get helpful answers.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/chatyllo/` or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Chatyllo** in the admin menu to customize settings.
4. Add FAQ pairs for the manual chatbot (free) or upgrade for AI responses.
5. The chat widget appears automatically on your site!

== Frequently Asked Questions ==

= Do I need an API key? =
No! Chatyllo is fully managed. Free users get the manual FAQ chatbot. Premium users get AI responses without any API configuration.

= Does it work with WooCommerce? =
Yes! The premium version automatically indexes your products, prices, categories, tags, attributes, and stock status.

= Does it work with page builders? =
Yes! Chatyllo extracts content from Elementor, Divi, and other page builder content.

= What happens if AI goes offline? =
The chatbot seamlessly falls back to your FAQ answers. Your visitors never see an error. Check the Service Status page for real-time monitoring.

= Can I customize the appearance? =
Yes! Change colors, position, bot name, avatar, messages, widget size, and more from the settings panel.

= Is it GDPR compliant? =
Yes! Chatyllo includes consent management, IP anonymization, browser anonymization, and integrates with WordPress Privacy Tools and major cookie consent plugins (CookieYes, Complianz, CookieBot, iubenda).

= Is it compatible with cache plugins? =
Yes! Fully compatible with WP Rocket, FlyingPress, LiteSpeed Cache, WP Super Cache, and W3 Total Cache.

== External Services ==

This plugin connects to external services for its functionality:

= Chatyllo AI Proxy Server =
* **Service URL:** https://wpezo.com/wp-api/chatyllo/
* **Purpose:** Processes AI chat responses, verifies licenses, manages usage limits, and generates AI-powered FAQ content.
* **Data sent:** Site URL, Freemius install ID, license key (during activation only), chat messages with knowledge base context (for AI responses), and plugin version.
* **Data NOT sent:** Visitor personal information, IP addresses, or browser details.
* **When used:** On every AI chat request (premium plans only) and during plugin activation/license verification.
* **Terms of Service:** https://wpezo.com/terms/
* **Privacy Policy:** https://wpezo.com/privacy-policy/

= Freemius =
* **Service URL:** https://api.freemius.com/
* **Purpose:** License management, plugin updates, user analytics, and payment processing.
* **Privacy Policy:** https://freemius.com/privacy/
* **Terms of Service:** https://freemius.com/terms/

= Google Gemini API (via proxy) =
* **Purpose:** AI language model for generating chat responses and FAQ content.
* **Note:** Requests are proxied through the Chatyllo server — the plugin does NOT connect to Google directly.
* **Privacy Policy:** https://policies.google.com/privacy

== Screenshots ==

1. Chat widget on the frontend
2. Admin dashboard with AI usage meters
3. Settings panel with customization options
4. FAQ management with AI generation
5. Knowledge base overview
6. Chat logs with conversation details
7. Statistics and analytics
8. Service status monitoring

== Changelog ==

= 1.2.0 =
* Added: AI-powered FAQ auto-generation with multi-language support
* Added: Statistics page with detailed analytics and CSV export
* Added: Service Status monitoring page with 30-day timeline
* Added: GDPR consent management with privacy banner
* Added: IP and browser anonymization options
* Added: WordPress Privacy Tools integration (Export/Erase Personal Data)
* Added: Data preservation option on uninstall
* Added: JSON data export for backup/migration
* Added: Automatic weekly maintenance system
* Added: AI Usage meters in dashboard (daily/monthly)
* Added: Multi-language FAQ support (20+ languages)
* Added: Getting Started checklist in dashboard
* Added: Widget Preview in settings
* Added: Session-based chat log grouping with detail modal
* Improved: WooCommerce product indexing (variable products, attribute labels, dimensions)
* Improved: FAQ management with categories, filters, pagination
* Improved: Search-and-pick selectors for content exclusion and display rules
* Improved: Branding options with visual radio cards
* Improved: Cache plugin compatibility (WP Rocket, FlyingPress, LiteSpeed)
* Security: Token-based proxy authentication (Bearer tokens)
* Security: Server-side license verification via Freemius API
* Security: File locking on proxy counters
* Security: Request size limits and history validation
* Security: Hashed token storage in proxy database

= 1.1.0 =
* Security: Complete proxy authentication overhaul (token-based)
* Security: Removed hardcoded proxy secret from plugin
* Security: Server-side Freemius license verification
* Added: SQLite database for proxy installation tracking
* Added: Per-plan usage limits enforced server-side
* Added: Automatic token refresh on expiry

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.2.0 =
Major update with AI FAQ generation, statistics, GDPR compliance, and security improvements. Recommended for all users.
