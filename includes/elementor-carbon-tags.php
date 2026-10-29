<?php

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

abstract class Elementor_CF_Checkbox_Base_Tag extends Tag {

    abstract protected function meta_key(): string;

    public function get_group() {
        return 'carbon_fields'; // наша группа
    }

    public function get_categories() {
        // Для условий достаточно TEXT_CATEGORY
        return [ Module::TEXT_CATEGORY ];
    }

    public function render() {
        echo esc_html( $this->get_value() );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        if (!$post_id) return '';

        // Carbon Fields: предпочтительно через carbon_get_post_meta
        if (function_exists('carbon_get_post_meta')) {
            $val = carbon_get_post_meta($post_id, $this->meta_key());
        } else {
            // fallback: как обычное post_meta
            $val = get_post_meta($post_id, $this->meta_key(), true);
        }

        // Чекбокс CF обычно возвращает 'yes' / '' (или 1 / 0 в зависимости от настройки)
        if (is_bool($val)) return $val ? 'yes' : '';
        if (is_numeric($val)) return ((int)$val === 1) ? 'yes' : '';
        if (is_string($val)) return trim($val);

        return '';
    }
}

class Elementor_CF_Crown_Display_Tag extends Elementor_CF_Checkbox_Base_Tag {
    public function get_name() { return 'cf_crown_display'; }
    public function get_title() { return 'Crown display (CF)'; }
    protected function meta_key(): string { return 'crown_display'; }
}

class Elementor_CF_Promo_Display_Tag extends Elementor_CF_Checkbox_Base_Tag {
    public function get_name() { return 'cf_promo_display'; }
    public function get_title() { return 'Promo display (CF)'; }
    protected function meta_key(): string { return 'promo_display'; }
}
