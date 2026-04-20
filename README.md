# academicwriting

## Upload-ready WordPress website kit

This repository now includes an installable plugin at:

`/home/runner/work/academicwriting/academicwriting/wordpress-plugin/academic-writing-services`

### What it provides

- Frontend homepage layout matching the provided academic writing plan
- Auto-creation of core pages + 27 service pages on plugin activation
- Service page template shortcode
- PHP + JavaScript quote/order form with:
  - level/deadline/word-count pricing calculation
  - minimum 6-hour deadline validation
  - nonce-protected form submission
  - order storage in WordPress (`aws_order` custom post type)
  - admin + customer email notifications
- Admin dashboard menu with:
  - order and writer post types
  - dashboard overview cards
  - settings page for colours, trust messaging, and pricing matrix
- Legal disclaimer output in footer

### How to use in WordPress

1. Zip the folder `wordpress-plugin/academic-writing-services`
2. In WordPress Admin: **Plugins → Add New → Upload Plugin**
3. Upload zip and activate **Academic Writing Services Website Kit**
4. Set homepage to the generated **Home** page (Settings → Reading)
5. Update settings under **Academic Writing → Settings**

### Key shortcodes

- `[aws_homepage]`
- `[aws_service_page service="Dissertation Writing Services"]`
- `[aws_order_form]`
- `[aws_my_orders]`
- `[aws_track_order]`
