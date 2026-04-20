<?php
/**
 * Plugin Name: Academic Writing Services Website Kit
 * Description: Upload-ready WordPress plugin with homepage blocks, service templates, quote/order form, pricing calculator, and admin dashboard.
 * Version: 1.0.0
 * Author: Academic Writing
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWS_Website_Kit
{
    private const OPTION_KEY = 'aws_settings';
    private const URGENT_THRESHOLD_HOURS = 72;
    private const EXPRESS_THRESHOLD_HOURS = 144;

    public function __construct()
    {
        register_activation_hook(__FILE__, [self::class, 'activate']);

        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_shortcodes']);

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('admin_post_aws_submit_order', [$this, 'handle_order_submission']);
        add_action('admin_post_nopriv_aws_submit_order', [$this, 'handle_order_submission']);
    }

    public static function activate(): void
    {
        $instance = new self();
        $instance->register_post_types();
        flush_rewrite_rules();

        if (! get_option(self::OPTION_KEY)) {
            update_option(self::OPTION_KEY, $instance->default_settings());
        }

        $instance->create_core_pages();
    }

    private function default_settings(): array
    {
        return [
            'announcement' => '15% Off First Order — Use code: FIRST15',
            'trust_line'   => 'Trusted by 50,000+ UK students | PhD-qualified writers | 100% original',
            'primary_color' => '#185FA5',
            'secondary_color' => '#0D3F75',
            'accent_color' => '#F59E0B',
            'disclaimer'   => 'All work is intended for academic reference and study purposes only.',
            'pricing'      => $this->default_pricing(),
        ];
    }

    private function default_pricing(): array
    {
        return [
            'undergraduate' => ['standard' => 9.99, 'express' => 14.99, 'urgent' => 19.99],
            'masters'       => ['standard' => 12.99, 'express' => 17.99, 'urgent' => 24.99],
            'phd'           => ['standard' => 16.99, 'express' => 22.99, 'urgent' => 29.99],
            'mba'           => ['standard' => 14.99, 'express' => 19.99, 'urgent' => 26.99],
        ];
    }

    private function service_pages(): array
    {
        return [
            'dissertation-writing-services' => 'Dissertation Writing Services',
            'proposal-writing-services' => 'Dissertation Proposal Writing',
            'phd-dissertation-writing' => 'PhD Dissertation Writing',
            'mba-dissertation-help' => 'MBA Dissertation Help',
            'masters-dissertation-help' => 'Masters Dissertation Help',
            'literature-review-writing' => 'Literature Review Writing',
            'dissertation-editing-services' => 'Dissertation Editing Services',
            'dissertation-topics-help' => 'Dissertation Topics Help',
            'college-essay-writing' => 'College & Uni Essays',
            'cheap-essay-writing-uk' => 'Cheap Essay Writing UK',
            'admission-essay-writing' => 'Admission Essay Writing',
            'psychology-essay-help' => 'Psychology Essay Help',
            'essay-editing-services-uk' => 'Essay Editing Services UK',
            'buy-essay-online' => 'Buy Essay Online',
            'buy-assignment-online' => 'Buy Assignment Online',
            'buy-law-assignment-uk' => 'Buy Law Assignment UK',
            'coursework-writing-services' => 'Coursework Writing Services',
            'case-study-help' => 'Case Study Help',
            'case-study-writing-services-uk' => 'Case Study Writing Services UK',
            'research-paper-help' => 'Research Paper Help',
            'thesis-writing-services' => 'Thesis Writing Services',
            'research-proposal-help' => 'Research Proposal Help',
            'term-paper-writing-service' => 'Term Paper Writing Service',
            'nursing-dissertation-writing' => 'Nursing Dissertation Writing',
            'academic-writing-services' => 'Academic Writing Services',
            'cv-writing-services-uk' => 'CV Writing Services UK',
            'copywriting-services-uk' => 'Copywriting Services UK',
        ];
    }

    private function create_core_pages(): void
    {
        $core_pages = [
            'home' => ['title' => 'Home', 'content' => '[aws_homepage]'],
            'about-us' => ['title' => 'About Us', 'content' => '<h2>About Us</h2><p>We support UK students and academics with reference-model writing support.</p>'],
            'contact' => ['title' => 'Contact', 'content' => '<h2>Contact</h2><p>Email: support@example.co.uk</p>'],
            'blog' => ['title' => 'Blog', 'content' => '<h2>Latest Academic Writing Guides</h2>'],
            'pricing' => ['title' => 'Pricing', 'content' => '[aws_order_form]'],
            'reviews' => ['title' => 'Reviews', 'content' => '<h2>Student Reviews</h2><p>Verified testimonials are displayed across service pages and homepage sections.</p>'],
            'privacy-policy' => ['title' => 'Privacy Policy', 'content' => '<h2>Privacy Policy</h2><p>We process customer data in line with GDPR and UK data protection law. We collect only data required for service delivery, support, and payment processing. Payment data is handled by third-party processors (such as Stripe/PayPal). Customers may request access, correction, or deletion of their personal data by contacting support.</p>'],
            'terms-and-conditions' => ['title' => 'Terms & Conditions', 'content' => '<h2>Terms & Conditions</h2><p>By using this service, you agree that all materials provided are for academic reference and study support only. Delivery timelines, revision windows, and payment conditions are defined at order confirmation. Misuse of reference materials is the customer’s sole responsibility.</p>'],
            'refund-policy' => ['title' => 'Refund Policy', 'content' => '<h2>Refund Policy</h2><p>Refund requests are assessed based on delivery stage, scope completed, and documented quality concerns. Partial or full refunds may be issued where applicable. Please refer to support for case-specific review within the stated refund window.</p>'],
            'anti-plagiarism-policy' => ['title' => 'Anti-Plagiarism Policy', 'content' => 'All work is checked for originality before delivery.'],
            'academic-integrity-disclaimer' => ['title' => 'Academic Integrity Disclaimer', 'content' => '<h2>Academic Integrity Disclaimer</h2><p>All delivered material is intended strictly as a model answer, study reference, or research aid. Customers must use all content responsibly and in accordance with institutional policies. Submission of purchased content as original assessed work is prohibited and remains the customer’s responsibility.</p>'],
            'register' => ['title' => 'Register', 'content' => '[woocommerce_my_account]'],
            'login' => ['title' => 'Login', 'content' => '[woocommerce_my_account]'],
            'my-account' => ['title' => 'My Account', 'content' => '[woocommerce_my_account]'],
            'my-orders' => ['title' => 'My Orders', 'content' => '[aws_my_orders]'],
            'track-order' => ['title' => 'Track Order', 'content' => '[aws_track_order]'],
            'sitemap' => ['title' => 'Sitemap', 'content' => '[wp_sitemap_page]'],
        ];

        foreach ($core_pages as $slug => $page) {
            if (! get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title'   => $page['title'],
                    'post_name'    => $slug,
                    'post_content' => $page['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);
            }
        }

        foreach ($this->service_pages() as $slug => $title) {
            if (! get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title'   => $title,
                    'post_name'    => $slug,
                    'post_content' => '[aws_service_page service="' . esc_attr($title) . '"]',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);
            }
        }
    }

    public function register_post_types(): void
    {
        register_post_type('aws_order', [
            'labels' => [
                'name' => 'Orders',
                'singular_name' => 'Order',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor'],
        ]);

        register_post_type('aws_writer', [
            'labels' => [
                'name' => 'Writers',
                'singular_name' => 'Writer',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail'],
        ]);
    }

    public function register_shortcodes(): void
    {
        add_shortcode('aws_homepage', [$this, 'render_homepage']);
        add_shortcode('aws_service_page', [$this, 'render_service_page']);
        add_shortcode('aws_order_form', [$this, 'render_order_form']);
        add_shortcode('aws_my_orders', [$this, 'render_my_orders']);
        add_shortcode('aws_track_order', [$this, 'render_track_order']);
    }

    public function enqueue_assets(): void
    {
        $settings = get_option(self::OPTION_KEY, $this->default_settings());

        wp_enqueue_style('aws-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], '1.0.0');
        wp_enqueue_script('aws-frontend', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', [], '1.0.0', true);

        wp_localize_script('aws-frontend', 'awsConfig', [
            'pricing' => $settings['pricing'] ?? $this->default_pricing(),
            'now' => current_time('timestamp'),
            'servicesCount' => count($this->service_pages()),
            'currency' => '£',
        ]);

        $css_vars = sprintf(
            ':root{--aws-primary:%s;--aws-secondary:%s;--aws-accent:%s;}',
            esc_attr($settings['primary_color'] ?? '#185FA5'),
            esc_attr($settings['secondary_color'] ?? '#0D3F75'),
            esc_attr($settings['accent_color'] ?? '#F59E0B')
        );
        wp_add_inline_style('aws-style', $css_vars);
    }

    public function register_admin_menu(): void
    {
        add_menu_page(
            'Academic Writing Dashboard',
            'Academic Writing',
            'manage_options',
            'aws-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-welcome-learn-more',
            26
        );

        add_submenu_page('aws-dashboard', 'Orders', 'Orders', 'manage_options', 'edit.php?post_type=aws_order');
        add_submenu_page('aws-dashboard', 'Writers', 'Writers', 'manage_options', 'edit.php?post_type=aws_writer');
        add_submenu_page('aws-dashboard', 'Settings', 'Settings', 'manage_options', 'aws-settings', [$this, 'render_settings']);
    }

    public function register_settings(): void
    {
        register_setting('aws_settings_group', self::OPTION_KEY, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings(array $input): array
    {
        $existing = get_option(self::OPTION_KEY, $this->default_settings());

        $output = [
            'announcement' => sanitize_text_field($input['announcement'] ?? $existing['announcement']),
            'trust_line' => sanitize_text_field($input['trust_line'] ?? $existing['trust_line']),
            'primary_color' => sanitize_hex_color($input['primary_color'] ?? $existing['primary_color']) ?: '#185FA5',
            'secondary_color' => sanitize_hex_color($input['secondary_color'] ?? $existing['secondary_color']) ?: '#0D3F75',
            'accent_color' => sanitize_hex_color($input['accent_color'] ?? $existing['accent_color']) ?: '#F59E0B',
            'disclaimer' => sanitize_text_field($input['disclaimer'] ?? $existing['disclaimer']),
            'pricing' => $this->default_pricing(),
        ];

        $levels = ['undergraduate', 'masters', 'phd', 'mba'];
        $tiers = ['standard', 'express', 'urgent'];

        foreach ($levels as $level) {
            foreach ($tiers as $tier) {
                $raw = $input['pricing'][$level][$tier] ?? $existing['pricing'][$level][$tier] ?? 0;
                $output['pricing'][$level][$tier] = max(0, round((float) $raw, 2));
            }
        }

        return $output;
    }

    public function render_dashboard(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $order_counts = wp_count_posts('aws_order');
        $writer_counts = wp_count_posts('aws_writer');
        $recent_orders = get_posts([
            'post_type' => 'aws_order',
            'numberposts' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        echo '<div class="wrap"><h1>Academic Writing Dashboard</h1>';
        echo '<p>Operational control center for orders, writers, and pricing.</p>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;max-width:900px;">';
        echo '<div style="background:#fff;padding:16px;border:1px solid #ddd;border-radius:8px;"><strong>Total Orders</strong><br>' . esc_html((string) ($order_counts->publish ?? 0)) . '</div>';
        echo '<div style="background:#fff;padding:16px;border:1px solid #ddd;border-radius:8px;"><strong>Total Writers</strong><br>' . esc_html((string) ($writer_counts->publish ?? 0)) . '</div>';
        echo '<div style="background:#fff;padding:16px;border:1px solid #ddd;border-radius:8px;"><strong>Active Services</strong><br>' . esc_html((string) count($this->service_pages())) . '</div>';
        echo '</div>';

        echo '<h2 style="margin-top:24px;">Recent Orders</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead><tbody>';

        if (! empty($recent_orders)) {
            foreach ($recent_orders as $order) {
                $total = get_post_meta($order->ID, '_aws_total', true);
                $status = get_post_meta($order->ID, '_aws_status', true) ?: 'Pending';
                echo '<tr>';
                echo '<td><a href="' . esc_url(get_edit_post_link($order->ID)) . '">' . esc_html($order->post_title) . '</a></td>';
                echo '<td>' . esc_html(get_the_date('', $order)) . '</td>';
                echo '<td>' . esc_html($status) . '</td>';
                echo '<td>£' . esc_html(number_format((float) $total, 2)) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">No orders yet.</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    public function render_settings(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = get_option(self::OPTION_KEY, $this->default_settings());
        $levels = ['undergraduate' => 'Undergraduate', 'masters' => 'Masters', 'phd' => 'PhD', 'mba' => 'MBA'];
        $tiers = ['standard' => 'Standard (7+ days)', 'express' => 'Express (3–6 days)', 'urgent' => 'Urgent (24–72 hrs)'];

        echo '<div class="wrap"><h1>Academic Writing Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('aws_settings_group');

        echo '<table class="form-table">';
        echo '<tr><th><label>Announcement Bar Text</label></th><td><input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[announcement]" value="' . esc_attr($settings['announcement']) . '"></td></tr>';
        echo '<tr><th><label>Trust Statement</label></th><td><input type="text" class="large-text" name="' . esc_attr(self::OPTION_KEY) . '[trust_line]" value="' . esc_attr($settings['trust_line']) . '"></td></tr>';
        echo '<tr><th><label>Primary Color</label></th><td><input type="color" name="' . esc_attr(self::OPTION_KEY) . '[primary_color]" value="' . esc_attr($settings['primary_color']) . '"></td></tr>';
        echo '<tr><th><label>Secondary Color</label></th><td><input type="color" name="' . esc_attr(self::OPTION_KEY) . '[secondary_color]" value="' . esc_attr($settings['secondary_color']) . '"></td></tr>';
        echo '<tr><th><label>Accent Color</label></th><td><input type="color" name="' . esc_attr(self::OPTION_KEY) . '[accent_color]" value="' . esc_attr($settings['accent_color']) . '"></td></tr>';
        echo '<tr><th><label>Footer Disclaimer</label></th><td><input type="text" class="large-text" name="' . esc_attr(self::OPTION_KEY) . '[disclaimer]" value="' . esc_attr($settings['disclaimer']) . '"></td></tr>';
        echo '</table>';

        echo '<h2>Pricing Matrix (£ per page)</h2>';
        echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Level</th>';
        foreach ($tiers as $tier_label) {
            echo '<th>' . esc_html($tier_label) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($levels as $key => $label) {
            echo '<tr><td><strong>' . esc_html($label) . '</strong></td>';
            foreach (array_keys($tiers) as $tier) {
                $value = $settings['pricing'][$key][$tier] ?? 0;
                echo '<td><input type="number" step="0.01" min="0" name="' . esc_attr(self::OPTION_KEY) . '[pricing][' . esc_attr($key) . '][' . esc_attr($tier) . ']" value="' . esc_attr((string) $value) . '"></td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';

        submit_button('Save Settings');
        echo '</form></div>';
    }

    public function render_homepage(): string
    {
        $settings = get_option(self::OPTION_KEY, $this->default_settings());
        $services = $this->service_pages();

        ob_start();
        ?>
        <div class="aws-site">
            <div class="aws-announcement"><?php echo esc_html($settings['announcement']); ?></div>
            <header class="aws-nav">
                <div class="aws-logo">Academic Writing UK</div>
                <button class="aws-menu-toggle" type="button" aria-expanded="false" aria-label="Toggle menu">☰</button>
                <nav class="aws-menu">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
                    <a href="<?php echo esc_url(home_url('/pricing')); ?>">Pricing</a>
                    <a href="<?php echo esc_url(home_url('/reviews')); ?>">Reviews</a>
                    <a href="<?php echo esc_url(home_url('/blog')); ?>">Blog</a>
                    <a href="<?php echo esc_url(home_url('/about-us')); ?>">About</a>
                    <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a>
                    <a class="aws-cta" href="<?php echo esc_url(home_url('/pricing')); ?>">Order Now</a>
                </nav>
            </header>

            <section class="aws-hero">
                <h1>Expert UK Dissertation &amp; Essay Writing Services</h1>
                <p><?php echo esc_html($settings['trust_line']); ?></p>
                <?php echo do_shortcode('[aws_order_form compact="1"]'); ?>
                <div class="aws-badges">
                    <span>⭐ 4.9/5 Rating</span>
                    <span>Trustpilot Verified</span>
                    <span>Money-Back Guarantee</span>
                    <span>Plagiarism-Free</span>
                </div>
            </section>

            <section class="aws-section">
                <h2>All Services <span class="aws-pill"><?php echo esc_html((string) count($services)); ?>+</span></h2>
                <div class="aws-grid">
                    <?php foreach ($services as $slug => $service) : ?>
                        <a class="aws-card" href="<?php echo esc_url(home_url('/' . $slug . '/')); ?>">
                            <strong><?php echo esc_html($service); ?></strong>
                            <span>Trusted UK-focused academic support service.</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="aws-section aws-process">
                <h2>How It Works</h2>
                <div class="aws-steps">
                    <div><strong>1. Fill Form</strong><p>Tell us service, level, deadline and instructions.</p></div>
                    <div><strong>2. Make Payment</strong><p>Secure checkout via Stripe or PayPal integration.</p></div>
                    <div><strong>3. Writer Assigned</strong><p>Qualified writer assigned based on expertise.</p></div>
                    <div><strong>4. Receive Work</strong><p>Review your completed model paper and request revisions.</p></div>
                </div>
            </section>

            <section class="aws-section aws-stats">
                <div><strong>50,000+</strong><span>Students Helped</span></div>
                <div><strong>350+</strong><span>Writers</span></div>
                <div><strong>98%</strong><span>On-time Delivery</span></div>
                <div><strong>4.9/5</strong><span>Average Rating</span></div>
            </section>

            <section class="aws-section">
                <h2>Why Choose Us</h2>
                <div class="aws-features">
                    <article><strong>PhD Writers</strong><p>Qualified experts with academic credentials.</p></article>
                    <article><strong>Plagiarism Free</strong><p>Original writing with internal quality checks.</p></article>
                    <article><strong>On-Time Delivery</strong><p>Structured workflows and deadline tracking.</p></article>
                    <article><strong>24/7 Support</strong><p>Live chat and support desk for all queries.</p></article>
                    <article><strong>Free Revisions</strong><p>Revision window included after delivery.</p></article>
                    <article><strong>Money-Back Guarantee</strong><p>Transparent policy and secure processing.</p></article>
                </div>
            </section>

            <section class="aws-section">
                <h2>Featured Services with Pricing Preview</h2>
                <div class="aws-tabs" role="tablist" aria-label="Featured services">
                    <button type="button" class="active" data-tab="dissertation" role="tab" aria-selected="true" tabindex="0">Dissertation</button>
                    <button type="button" data-tab="essay" role="tab" aria-selected="false" tabindex="-1">Essay</button>
                    <button type="button" data-tab="thesis" role="tab" aria-selected="false" tabindex="-1">Thesis</button>
                    <button type="button" data-tab="cv" role="tab" aria-selected="false" tabindex="-1">CV</button>
                </div>
                <div class="aws-pricing-preview">
                    <div><strong>Standard</strong><span>From £9.99/page</span></div>
                    <div><strong>Express</strong><span>From £14.99/page</span></div>
                    <div><strong>Premium</strong><span>From £19.99/page</span></div>
                </div>
                <p><a class="aws-cta" href="<?php echo esc_url(home_url('/pricing')); ?>">Order This Service</a></p>
            </section>

            <section class="aws-section">
                <h2>Testimonials</h2>
                <div class="aws-testimonials">
                    <blockquote>“Delivered before deadline. Excellent structure and referencing.” — Sarah, University of Manchester</blockquote>
                    <blockquote>“Great communication and useful model dissertation chapter.” — Ahmed, University of Leeds</blockquote>
                    <blockquote>“Clear and professional writing, helped me understand my topic better.” — Chloe, UCL</blockquote>
                </div>
            </section>

            <section class="aws-section aws-guarantees">
                <h2>Our Guarantees</h2>
                <ul>
                    <li>100% Plagiarism-Free</li>
                    <li>Money-Back Guarantee</li>
                    <li>Free Revisions</li>
                    <li>On-Time Delivery</li>
                    <li>Confidentiality</li>
                </ul>
            </section>

            <section class="aws-section">
                <h2>From the Blog</h2>
                <div class="aws-blog-cards">
                    <article><strong>How to Write a Dissertation Methodology</strong><p>Step-by-step UK-focused guide.</p></article>
                    <article><strong>Harvard Referencing Guide UK</strong><p>Practical examples for students.</p></article>
                    <article><strong>Dissertation vs Thesis: UK Differences</strong><p>Know what examiners expect.</p></article>
                </div>
            </section>

            <footer class="aws-footer">
                <div>
                    <strong>Academic Writing UK</strong>
                    <p>Dissertation, Essay, Thesis, CV &amp; Copywriting services.</p>
                </div>
                <div>
                    <strong>Quick Links</strong>
                    <a href="<?php echo esc_url(home_url('/privacy-policy')); ?>">Privacy Policy</a>
                    <a href="<?php echo esc_url(home_url('/terms-and-conditions')); ?>">Terms &amp; Conditions</a>
                    <a href="<?php echo esc_url(home_url('/academic-integrity-disclaimer')); ?>">Disclaimer</a>
                </div>
                <div>
                    <strong>Payments</strong>
                    <p>Visa · Mastercard · PayPal · Stripe</p>
                </div>
                <div>
                    <strong>Notice</strong>
                    <p><?php echo esc_html($settings['disclaimer']); ?></p>
                </div>
            </footer>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_service_page(array $atts): string
    {
        $atts = shortcode_atts(['service' => 'Academic Writing Service'], $atts);
        $service = sanitize_text_field($atts['service']);

        ob_start();
        ?>
        <div class="aws-site aws-service-page">
            <section class="aws-hero">
                <h1><?php echo esc_html($service); ?> UK</h1>
                <p>Professional, confidential and deadline-focused writing support for UK students.</p>
                <?php echo do_shortcode('[aws_order_form]'); ?>
            </section>

            <section class="aws-section">
                <h2>What We Offer</h2>
                <ul>
                    <li>Tailored model paper aligned to your brief and marking criteria</li>
                    <li>Referencing support (Harvard, APA, MLA, Oxford, Chicago)</li>
                    <li>Editing and proofreading quality check before delivery</li>
                    <li>Revision support after first draft delivery</li>
                </ul>
            </section>

            <section class="aws-section">
                <h2>Why Choose Us</h2>
                <div class="aws-features">
                    <article><strong>Subject Specialists</strong><p>Matched to relevant discipline expertise.</p></article>
                    <article><strong>Transparent Pricing</strong><p>Live calculator by level, urgency, and word count.</p></article>
                    <article><strong>Quality Assured</strong><p>Checked before delivery and revision-ready.</p></article>
                    <article><strong>Confidential Service</strong><p>Secure handling of files and communication.</p></article>
                </div>
            </section>

            <section class="aws-section">
                <h2>Frequently Asked Questions</h2>
                <details><summary>Is the content plagiarism free?</summary><p>Yes, originality checks are part of the delivery process.</p></details>
                <details><summary>Can I request revisions?</summary><p>Yes, free revisions are available within the stated revision window.</p></details>
                <details><summary>How are writers selected?</summary><p>Writers are assigned based on subject expertise and deadline availability.</p></details>
                <details><summary>Is this service confidential?</summary><p>Yes, customer data and order files are managed securely.</p></details>
            </section>

            <section class="aws-section">
                <h2>Final CTA</h2>
                <p><a class="aws-cta" href="#aws-order-form">Order Now</a></p>
            </section>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_order_form(array $atts = []): string
    {
        $atts = shortcode_atts(['compact' => '0'], $atts);
        $compact = $atts['compact'] === '1';

        $success = isset($_GET['aws_order']) && $_GET['aws_order'] === 'submitted';
        $message_token = isset($_GET['aws_msg']) ? sanitize_key(wp_unslash($_GET['aws_msg'])) : '';
        $error = '';
        if (! empty($message_token)) {
            $stored_message = get_transient('aws_msg_' . $message_token);
            if (is_string($stored_message) && $stored_message !== '') {
                $error = sanitize_text_field($stored_message);
            }
            delete_transient('aws_msg_' . $message_token);
        }

        ob_start();
        ?>
        <div class="aws-order-wrap" id="aws-order-form">
            <?php if ($success) : ?>
                <div class="aws-alert aws-success">Thank you. Your order has been submitted successfully.</div>
            <?php endif; ?>
            <?php if (! empty($error)) : ?>
                <div class="aws-alert aws-error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <form class="aws-order-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="aws_submit_order">
                <?php wp_nonce_field('aws_submit_order_nonce', 'aws_nonce'); ?>

                <div class="aws-form-grid<?php echo $compact ? ' aws-compact' : ''; ?>">
                    <label>Service Type
                        <select name="service_type" required>
                            <?php foreach ($this->service_pages() as $service) : ?>
                                <option value="<?php echo esc_attr($service); ?>"><?php echo esc_html($service); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Academic Level
                        <select name="academic_level" required>
                            <option value="undergraduate">Undergraduate</option>
                            <option value="masters">Masters</option>
                            <option value="phd">PhD</option>
                            <option value="mba">MBA</option>
                        </select>
                    </label>

                    <label>Subject / Discipline
                        <input type="text" name="subject" required>
                    </label>

                    <label>Word Count
                        <input type="number" name="word_count" min="250" max="20000" step="50" value="1000" required>
                    </label>

                    <label>Deadline
                        <input type="datetime-local" name="deadline" required>
                    </label>

                    <label>Paper Format
                        <select name="paper_format" required>
                            <option value="Harvard">Harvard</option>
                            <option value="APA">APA</option>
                            <option value="MLA">MLA</option>
                            <option value="Oxford">Oxford</option>
                            <option value="Chicago">Chicago</option>
                            <option value="Other">Other</option>
                        </select>
                    </label>

                    <?php if (! $compact) : ?>
                        <label>Upload Brief
                            <input type="file" name="brief_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small>Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG (max 20MB).</small>
                        </label>
                    <?php endif; ?>

                    <label class="aws-full">Paper Instructions
                        <textarea name="instructions" minlength="50" required></textarea>
                    </label>

                    <label>Additional Comments
                        <textarea name="comments"></textarea>
                    </label>

                    <label>Coupon Code
                        <input type="text" name="coupon_code" placeholder="FIRST15">
                    </label>

                    <label class="aws-price">Estimated Price
                        <output class="aws-price-value">£0.00</output>
                    </label>
                </div>

                <div class="aws-form-actions">
                    <label>Name <input type="text" name="customer_name" required></label>
                    <label>Email <input type="email" name="customer_email" required></label>
                    <button type="submit">Get Price &amp; Submit</button>
                </div>
            </form>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function get_urgency_tier(string $deadline): string
    {
        $deadline_ts = strtotime($deadline);
        $now_ts = current_time('timestamp');
        $urgent_cutoff = $now_ts + self::URGENT_THRESHOLD_HOURS * HOUR_IN_SECONDS;
        $express_cutoff = $now_ts + self::EXPRESS_THRESHOLD_HOURS * HOUR_IN_SECONDS;

        if ($deadline_ts <= $urgent_cutoff) {
            return 'urgent';
        }

        if ($deadline_ts <= $express_cutoff) {
            return 'express';
        }

        return 'standard';
    }

    private function calculate_total(string $level, int $word_count, string $deadline): float
    {
        $settings = get_option(self::OPTION_KEY, $this->default_settings());
        $pricing = $settings['pricing'] ?? $this->default_pricing();

        $tier = $this->get_urgency_tier($deadline);
        $per_page = isset($pricing[$level][$tier]) ? (float) $pricing[$level][$tier] : 0;
        $pages = max(1, (int) ceil($word_count / 250));

        return round($per_page * $pages, 2);
    }

    private function apply_coupon_discount(float $total, string $coupon_code): float
    {
        if (empty($coupon_code) || ! function_exists('wc_get_coupon_id_by_code') || ! class_exists('WC_Coupon')) {
            return $total;
        }

        $coupon_id = wc_get_coupon_id_by_code($coupon_code);
        if (! $coupon_id) {
            return $total;
        }

        $coupon = new WC_Coupon($coupon_id);
        if (! $coupon->get_id()) {
            return $total;
        }

        $discount_type = $coupon->get_discount_type();
        $amount = (float) $coupon->get_amount();

        if ($discount_type === 'percent') {
            $total -= ($total * ($amount / 100));
        } elseif (in_array($discount_type, ['fixed_cart', 'fixed_product'], true)) {
            $total -= $amount;
        }

        return max(0, round($total, 2));
    }

    public function handle_order_submission(): void
    {
        if (! isset($_POST['aws_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aws_nonce'])), 'aws_submit_order_nonce')) {
            wp_die('Invalid request.');
        }

        $service_type = sanitize_text_field(wp_unslash($_POST['service_type'] ?? ''));
        $academic_level = sanitize_key(wp_unslash($_POST['academic_level'] ?? ''));
        $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
        $word_count = (int) ($_POST['word_count'] ?? 0);
        $deadline = sanitize_text_field(wp_unslash($_POST['deadline'] ?? ''));
        $paper_format = sanitize_text_field(wp_unslash($_POST['paper_format'] ?? ''));
        $instructions = sanitize_textarea_field(wp_unslash($_POST['instructions'] ?? ''));
        $comments = sanitize_textarea_field(wp_unslash($_POST['comments'] ?? ''));
        $coupon_code = sanitize_text_field(wp_unslash($_POST['coupon_code'] ?? ''));
        $customer_name = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
        $customer_email = strtolower(trim(sanitize_email(wp_unslash($_POST['customer_email'] ?? ''))));

        if (
            empty($service_type) ||
            empty($academic_level) ||
            empty($subject) ||
            $word_count < 250 ||
            empty($deadline) ||
            empty($paper_format) ||
            strlen($instructions) < 50 ||
            empty($customer_name) ||
            ! is_email($customer_email)
        ) {
            $this->redirect_with_error('Please complete all required fields correctly.');
        }

        if (! preg_match('/^(?=.*\\p{L})[\\p{L}\\s\\-\'\\.]{2,100}$/u', $customer_name)) {
            $this->redirect_with_error('Please enter a valid name.');
        }

        if (strtotime($deadline) < (current_time('timestamp') + 6 * HOUR_IN_SECONDS)) {
            $this->redirect_with_error('Deadline must be at least 6 hours from now.');
        }

        $total = $this->calculate_total($academic_level, $word_count, $deadline);
        $total = $this->apply_coupon_discount($total, $coupon_code);

        $brief_url = '';
        if (! empty($_FILES['brief_file']['name'])) {
            $allowed_mimes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
            ];
            $filename = sanitize_file_name(wp_unslash($_FILES['brief_file']['name']));
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $filetype = wp_check_filetype_and_ext($_FILES['brief_file']['tmp_name'], $filename, $allowed_mimes);
            $size = (int) $_FILES['brief_file']['size'];

            if (preg_match('/\\.(php|phtml|phar|js|sh|exe|bat)$/i', $filename) || empty($extension) || ! array_key_exists($extension, $allowed_mimes)) {
                $this->redirect_with_error('Invalid file name or extension.');
            }

            if (empty($filetype['ext']) || empty($filetype['type'])) {
                $this->redirect_with_error('Invalid file type. Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG.');
            }

            if ($size > 20 * 1024 * 1024) {
                $this->redirect_with_error('File too large. Max size is 20MB.');
            }

            if (! function_exists('wp_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            $upload = wp_handle_upload($_FILES['brief_file'], ['test_form' => false]);
            if (! empty($upload['error'])) {
                $this->redirect_with_error('Brief upload failed. Please try again.');
            }

            $brief_url = ! empty($upload['url']) ? esc_url_raw($upload['url']) : '';
        }

        $order_id = wp_insert_post([
            'post_type' => 'aws_order',
            'post_status' => 'publish',
            'post_title' => sprintf('Order - %s - %s', $customer_name, current_time('mysql')),
            'post_content' => wp_kses_post($instructions),
            'post_author' => (int) get_current_user_id(),
        ]);

        if (is_wp_error($order_id) || ! $order_id) {
            $this->redirect_with_error('Order could not be saved. Please try again.');
        }

        update_post_meta($order_id, '_aws_service_type', $service_type);
        update_post_meta($order_id, '_aws_academic_level', $academic_level);
        update_post_meta($order_id, '_aws_subject', $subject);
        update_post_meta($order_id, '_aws_word_count', $word_count);
        update_post_meta($order_id, '_aws_deadline', $deadline);
        update_post_meta($order_id, '_aws_paper_format', $paper_format);
        update_post_meta($order_id, '_aws_comments', $comments);
        update_post_meta($order_id, '_aws_coupon_code', $coupon_code);
        update_post_meta($order_id, '_aws_brief_url', $brief_url);
        update_post_meta($order_id, '_aws_customer_name', $customer_name);
        update_post_meta($order_id, '_aws_customer_email', $customer_email);
        update_post_meta($order_id, '_aws_customer_user_id', (int) get_current_user_id());
        update_post_meta($order_id, '_aws_total', $total);
        update_post_meta($order_id, '_aws_status', 'Pending');

        $admin_email = get_option('admin_email');
        $subject_line = 'New Academic Writing Order #' . $order_id;
        $safe_customer_name = wp_strip_all_tags($customer_name);
        $safe_service_type = wp_strip_all_tags($service_type);
        $body = "A new order has been submitted.\n\n"
            . "Order ID: {$order_id}\n"
            . "Customer: {$safe_customer_name} ({$customer_email})\n"
            . "Service: {$safe_service_type}\n"
            . "Level: {$academic_level}\n"
            . "Word Count: {$word_count}\n"
            . "Deadline: {$deadline}\n"
            . "Estimated Total: £" . number_format($total, 2);

        $admin_mail_sent = wp_mail($admin_email, $subject_line, $body);
        update_post_meta($order_id, '_aws_admin_mail_sent', $admin_mail_sent ? '1' : '0');

        $customer_mail_sent = wp_mail(
            $customer_email,
            'Order Received - Academic Writing Services',
            "Hi {$safe_customer_name},\n\nWe have received your request for {$safe_service_type}.\nEstimated total: £" . number_format($total, 2) . "\nOur team will contact you shortly."
        );
        update_post_meta($order_id, '_aws_customer_mail_sent', $customer_mail_sent ? '1' : '0');

        $redirect_url = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg('aws_order', 'submitted', $redirect_url));
        exit;
    }

    private function redirect_with_error(string $message): void
    {
        $redirect_url = wp_get_referer() ?: home_url('/');
        $token = wp_generate_password(12, false, false);
        set_transient('aws_msg_' . $token, sanitize_text_field($message), MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg('aws_msg', $token, $redirect_url));
        exit;
    }

    public function render_my_orders(): string
    {
        if (! is_user_logged_in()) {
            return '<p>Please login to view your orders.</p>';
        }

        $user_id = get_current_user_id();
        $orders = get_posts([
            'post_type' => 'aws_order',
            'numberposts' => 20,
            'author' => $user_id,
        ]);

        if (empty($orders)) {
            return '<p>No orders found.</p>';
        }

        ob_start();
        echo '<table class="aws-table"><thead><tr><th>Order</th><th>Service</th><th>Status</th><th>Total</th></tr></thead><tbody>';

        foreach ($orders as $order) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $order->ID) . '</td>';
            echo '<td>' . esc_html((string) get_post_meta($order->ID, '_aws_service_type', true)) . '</td>';
            echo '<td>' . esc_html((string) get_post_meta($order->ID, '_aws_status', true)) . '</td>';
            echo '<td>£' . esc_html(number_format((float) get_post_meta($order->ID, '_aws_total', true), 2)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        return (string) ob_get_clean();
    }

    public function render_track_order(): string
    {
        return '<p>Track order from your account dashboard. For a custom tracking view, map this page to your CRM or WooCommerce order IDs.</p>';
    }
}

new AWS_Website_Kit();
