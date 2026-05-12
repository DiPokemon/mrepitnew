# MRepit Theme Documentation

## 1. Overview
- Type: child theme for `hello-elementor`.
- Goal: school website + custom admin module for managing users (`teacher`, `student`, `parent`) and related content.
- Entry points: `functions.php`, `includes/crb_fields/init.php`, `includes/users_controls/users-controls.php`.

## 2. Project Structure
- `functions.php`: theme bootstrap, assets enqueue, SVG mime support, Elementor dynamic tags registration.
- `includes/crb_fields/`: Carbon Fields boot + CPT and meta definitions.
- `includes/users_controls/`: custom admin module (roles/capabilities/menu/CRUD/list tables/linking).
- `includes/telegram_form.php`: REST webhook endpoint for Elementor form -> Telegram.
- `includes/elementor-carbon-tags.php`: custom Elementor dynamic tags for CF meta.
- `assets/js/phone-mask.js`: phone masking + E.164 normalization for Elementor form.
- `assets/css/style.scss`: theme custom styles.

## 3. Dependencies
- Composer package: `htmlburger/carbon-fields` (`^3.6`).
- Parent theme: `hello-elementor`.
- Frontend CDN: `imask@7.6.1`.

## 4. Bootstrap & Hooks
- `functions.php` loads:
  - `includes/crb_fields/init.php`
  - `includes/telegram_form.php`
  - `includes/users_controls/users-controls.php`
- Hooks used:
  - `after_setup_theme` for `Carbon_Fields::boot()`
  - `wp_enqueue_scripts` for CSS/JS
  - `rest_api_init` for Telegram endpoint
  - `elementor/dynamic_tags/register` for custom dynamic tags

## 5. Content Model (CPT)
Defined in `includes/crb_fields/cpt.php`:
- `service`
- `review`
- `teacher`

All are public, `show_in_rest = true`, and hidden from default WP menu (`show_in_menu = false`) because access is provided from custom "School" admin menu.

## 6. Carbon Fields: Meta
### Theme options (`includes/crb_fields/theme_options.php`)
- `tg_chat_id`
- `tg_bot_token`
- `tg_webhook_secret`

### Review post meta (`includes/crb_fields/review_fields.php`)
- `review_name`
- `review_age`
- `review_class`
- `review_screenshot` (image ID)

### Service post meta (`includes/crb_fields/service_fields.php`)
- `service_price`
- `service_anounce`
- `crown_display`
- `promo_display`

### Teacher post meta
- See `includes/crb_fields/teacher_fields.php`.

## 7. Users Controls Module
Main loader: `includes/users_controls/users-controls.php`.

### Common layer (`includes/users_controls/common`)
- `capabilities.php`: roles and custom caps.
- `security.php`: protects relationship meta from unauthorized writes.
- `helpers.php`: permission helpers and role labels.
- `admin-menu.php`: "School" dashboard/menu pages.
- `carbon-fields.php`: user-level profile fields.
- `sync.php`: parent-student two-way link synchronization.
- `teacher-link.php`: two-way link between teacher user and teacher post.

### Role-specific CRUD
- Parents: `parents_controls/*`
- Students: `students_controls/*`
- Teachers: `teachers_controls/*`

Each role has:
- list page (`WP_List_Table`)
- add/edit form page
- `admin-post.php` handlers for create/update

## 8. Access Control Summary
- Admin can manage all.
- Manager can manage only school roles (`teacher/student/parent`) and family links.
- Manager cannot edit admins/managers (enforced via `map_meta_cap`).
- Relationship meta keys (`parent_children`, `student_parents`) are protected by `pre_update_user_meta` filter.

## 9. Teacher Linking Model
Two-way binding between WP User (`teacher`) and CPT post (`teacher`):
- user meta key: `school_teacher_post_id`
- post meta key: `school_teacher_user_id`

Implemented in `includes/users_controls/common/teacher-link.php` with safe relinking and unlinking on user/post deletion.

## 10. Telegram Integration
File: `includes/telegram_form.php`.
- Route: `POST /wp-json/its/v1/elementor-telegram`
- Secret verification via query param `?secret=` and Carbon option `tg_webhook_secret`.
- Flexible payload parsing for Elementor forms.
- Optional honeypot field `website`.
- Sends message through Telegram Bot API `sendMessage`.

## 11. Frontend Form Phone Mask
File: `assets/js/phone-mask.js`.
- IMask format: `+7 (000) 000-00-00`
- Converts value to E.164 and stores into hidden field `form_fields[phone_e164]`
- Supports dynamic Elementor form rendering via frontend hook.

## 12. Known Risks / Technical Debt
- Some files contain mojibake in comments/UI strings (encoding issue).
- Telegram route uses open permission callback and relies on secret only.
- Payload logging may include personal data (`error_log`).
- There are empty placeholder files (`form.php`, `menu.php`, `list-table-base.php`) in some modules.

## 13. Local Development Notes
1. Ensure parent theme `hello-elementor` is installed and active.
2. Run `composer install` in theme root if vendor is missing.
3. Configure Telegram settings in Theme Options before testing form webhook.
4. After switching theme, verify custom roles/caps are present.

## 14. Quick Change Map
- Add/modify CPT: `includes/crb_fields/cpt.php`
- Add/modify post meta fields: `includes/crb_fields/*_fields.php` + include in `includes/crb_fields/init.php`
- User role logic: `includes/users_controls/common/capabilities.php`
- School admin UI: `includes/users_controls/common/admin-menu.php`
- User CRUD logic: `includes/users_controls/*_controls/handlers.php`
- Telegram webhook: `includes/telegram_form.php`
