<?php
/**
 * Frontend rendering utilities.
 */

namespace WP_BetterForms\Rendering;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\Form_Repository;

/**
 * Class Renderer
 */
final class Renderer {
private static bool $enqueue_frontend = false;

public static function render_shortcode( array $atts ): string {
$atts = shortcode_atts( [
'id'     => 0,
'preset' => 'default',
], $atts, 'betterform' );

$form_id = absint( $atts['id'] );
if ( ! $form_id ) {
return '';
}

$form = Form_Repository::get_form( $form_id );
if ( ! $form ) {
return '';
}

self::$enqueue_frontend = true;

return self::render_form_markup( $form, [ 'preset' => $atts['preset'] ] );
}

public static function render_block( array $attributes, string $content ): string {
$form_id = absint( $attributes['formId'] ?? 0 );

if ( ! $form_id ) {
return '';
}

$form = Form_Repository::get_form( $form_id );
if ( ! $form ) {
return '';
}

self::$enqueue_frontend = true;

return self::render_form_markup( $form, $attributes );
}

public static function should_enqueue_frontend_assets(): bool {
return self::$enqueue_frontend;
}

private static function render_form_markup( array $form, array $context = [] ): string {
$schema = $form['schema'] ?? [];
$fields = $schema['fields'] ?? [];
$styles = $form['styles'] ?? [];

$form_id     = (int) $form['id'];
$wrapper_id  = 'bf-form-' . $form_id;
$style_block = self::generate_style_block( $form_id, $styles, $context['preset'] ?? '' );

$nonce = wp_create_nonce( 'wp_rest' );

$fields_markup = array_map( [ self::class, 'render_field' ], $fields );

$honeypot       = '<div class="bf-hp" aria-hidden="true"><label>' . esc_html__( 'Leave this field empty', 'wp-betterforms' ) . '</label><input type="text" name="bf_hp" tabindex="-1" autocomplete="off" value="" /></div>';
$timing_inputs  = '<input type="hidden" name="bf_rendered_at" value="' . esc_attr( (string) time() ) . '" />';
$timing_inputs .= '<input type="hidden" name="bf_elapsed" value="" />';

$markup  = $style_block;
$markup .= '<form class="bf-form bf-form--' . esc_attr( $form_id ) . '" id="' . esc_attr( $wrapper_id ) . '" data-form-id="' . esc_attr( $form_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
$markup .= implode( '', $fields_markup );
$markup .= $honeypot . $timing_inputs;
$markup .= '<div class="bf-actions"><button type="submit" class="bf-submit">' . esc_html__( 'Submit', 'wp-betterforms' ) . '</button></div>';
$markup .= '<div class="bf-messages" aria-live="polite" role="status"></div>';
$markup .= '</form>';

return apply_filters( 'wp_betterforms/render_markup', $markup, $form, $context );
}

private static function generate_style_block( int $form_id, array $styles, string $preset ): string {
$variables = array_merge( self::preset_tokens( $preset ), $styles['tokens'] ?? [] );
$lines     = [];

foreach ( $variables as $token => $value ) {
$lines[] = '--bf-' . sanitize_key( $token ) . ':' . esc_attr( $value ) . ';';
}

if ( empty( $lines ) ) {
return '';
}

return '<style>.bf-form--' . esc_attr( $form_id ) . '{' . implode( '', $lines ) . '}</style>';
}

private static function preset_tokens( string $preset ): array {
$presets = [
'outlined' => [
'bg'          => '#ffffff',
'primary'     => '#2f6fef',
'border'      => '#d0d7e3',
'focus-ring'  => '0 0 0 2px rgba(47,111,239,0.3)',
'label-color' => '#1c1f2b',
],
'dark'     => [
'bg'          => '#121318',
'primary'     => '#4fc3f7',
'label-color' => '#f5f5f5',
'text'        => '#f5f5f5',
],
'default'  => [
'bg'          => '#ffffff',
'primary'     => '#2563eb',
'focus-ring'  => '0 0 0 3px rgba(37, 99, 235, 0.35)',
'label-color' => '#1f2933',
],
];

return $presets[ $preset ] ?? $presets['default'];
}

    private static function render_field( array $field ): string {
        $type        = $field['type'] ?? 'text';
        $key         = sanitize_key( $field['key'] ?? wp_unique_id( 'bf_field_' ) );
        $label       = esc_html( $field['label'] ?? ucfirst( $key ) );
        $required    = ! empty( $field['required'] );
$required_attr = $required ? ' required' : '';
$aria_required = $required ? ' aria-required="true"' : '';
$description   = isset( $field['description'] ) ? '<p class="bf-description">' . esc_html( $field['description'] ) . '</p>' : '';

$input = '';

        switch ( $type ) {
            case 'repeater':
                return self::render_repeater( $field );
            case 'textarea':
                $input = '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"' . $required_attr . $aria_required . '></textarea>';
                break;
case 'select':
$options = array_map(
static fn( $choice ): string => '<option value="' . esc_attr( $choice['value'] ?? '' ) . '">' . esc_html( $choice['label'] ?? '' ) . '</option>',
$field['choices'] ?? []
);
$input = '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"' . $required_attr . $aria_required . '>' . implode( '', $options ) . '</select>';
break;
case 'checkbox':
$input = '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1"' . $aria_required . $required_attr . ' />';
break;
case 'number':
$input = '<input type="number" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"' . $required_attr . $aria_required . ' />';
break;
case 'email':
$input = '<input type="email" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" autocomplete="email"' . $required_attr . $aria_required . ' />';
break;
default:
$input = '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '"' . $required_attr . $aria_required . ' />';
}

        return '<div class="bf-field bf-field--' . esc_attr( $type ) . '"><label for="' . esc_attr( $key ) . '">' . $label . '</label>' . $description . $input . '</div>';
    }

    private static function render_repeater( array $field ): string {
        $key      = sanitize_key( $field['key'] ?? wp_unique_id( 'bf_repeater_' ) );
        $label    = esc_html( $field['label'] ?? ucfirst( $key ) );
        $children = is_array( $field['fields'] ?? null ) ? $field['fields'] : [];
        $rows     = is_array( $field['rows'] ?? null ) ? $field['rows'] : [];

        if ( empty( $rows ) ) {
            $rows[] = [];
        }

        $template_markup = self::render_repeater_row( $key, $children, '{{index}}', [], true );
        $rows_markup     = '';

        foreach ( $rows as $index => $values ) {
            $rows_markup .= self::render_repeater_row( $key, $children, (string) $index, is_array( $values ) ? $values : [], false );
        }

        $template_attribute = esc_attr( $template_markup );

        return '<div class="bf-field bf-field--repeater" data-bf-repeater="' . esc_attr( $key ) . '">' .
            '<label>' . $label . '</label>' .
            '<div class="bf-repeater__rows" data-bf-template="' . $template_attribute . '">' . $rows_markup . '</div>' .
            '<button type="button" class="bf-repeater__add">' . esc_html__( 'Add row', 'wp-betterforms' ) . '</button>' .
            '</div>';
    }

    private static function render_repeater_row( string $repeater_key, array $children, string $index, array $values, bool $is_template ): string {
        $row_attributes = 'class="bf-repeater__row" data-bf-template-index="{{index}}"';

        if ( ! $is_template ) {
            $row_attributes .= ' data-bf-index="' . esc_attr( $index ) . '"';
        }

        $fields_markup = '';

        foreach ( $children as $child ) {
            $child_key    = sanitize_key( $child['key'] ?? wp_unique_id( 'bf_field_' ) );
            $type         = $child['type'] ?? 'text';
            $label        = esc_html( $child['label'] ?? ucfirst( $child_key ) );
            $description  = isset( $child['description'] ) ? '<p class="bf-description">' . esc_html( $child['description'] ) . '</p>' : '';
            $required     = ! empty( $child['required'] );
            $required_attr = $required ? ' required' : '';
            $aria_required = $required ? ' aria-required="true"' : '';

            $id_template   = $repeater_key . '-{{index}}-' . $child_key;
            $name_template = $repeater_key . '[{{index}}][' . $child_key . ']';

            $id   = str_replace( '{{index}}', $index, $id_template );
            $name = str_replace( '{{index}}', $index, $name_template );

            $template_attributes = ' data-bf-template-id="' . esc_attr( $id_template ) . '" data-bf-template-name="' . esc_attr( $name_template ) . '"';
            $label_attributes    = ' for="' . esc_attr( $id ) . '" data-bf-template-for="' . esc_attr( $id_template ) . '"';

            $value = $values[ $child_key ] ?? '';

            switch ( $type ) {
                case 'textarea':
                    $input = '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $template_attributes . $required_attr . $aria_required . '>' . esc_textarea( (string) $value ) . '</textarea>';
                    break;
                case 'select':
                    $options = array_map(
                        static function ( $choice ) use ( $value ): string {
                            $choice_value = $choice['value'] ?? '';
                            $selected     = (string) $choice_value === (string) $value ? ' selected' : '';

                            return '<option value="' . esc_attr( $choice_value ) . '"' . $selected . '>' . esc_html( $choice['label'] ?? '' ) . '</option>';
                        },
                        $child['choices'] ?? []
                    );
                    $input = '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $template_attributes . $required_attr . $aria_required . '>' . implode( '', $options ) . '</select>';
                    break;
                case 'checkbox':
                    $checked = ! empty( $value ) ? ' checked' : '';
                    $input   = '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . $template_attributes . $aria_required . $required_attr . $checked . ' />';
                    break;
                case 'number':
                    $input = '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $template_attributes . $required_attr . $aria_required . ' />';
                    break;
                case 'email':
                    $input = '<input type="email" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" autocomplete="email"' . $template_attributes . $required_attr . $aria_required . ' />';
                    break;
                default:
                    $input = '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $template_attributes . $required_attr . $aria_required . ' />';
            }

            $fields_markup .= '<div class="bf-field bf-field--' . esc_attr( $type ) . '"><label' . $label_attributes . '>' . $label . '</label>' . $description . $input . '</div>';
        }

        $remove_button = '<button type="button" class="bf-repeater__remove">' . esc_html__( 'Remove', 'wp-betterforms' ) . '</button>';

        return '<div ' . $row_attributes . '>' . $fields_markup . $remove_button . '</div>';
    }

public static function store_submission( array $form, array $data ): int {
global $wpdb;

$table_entries = $wpdb->prefix . 'bf_entries';
$table_meta    = $wpdb->prefix . 'bf_entry_meta';

$wpdb->insert(
$table_entries,
[
'form_id'    => (int) $form['id'],
'user_id'    => get_current_user_id() ?: null,
'ip'         => inet_pton( $_SERVER['REMOTE_ADDR'] ?? '' ) ?: null,
'ua'         => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
'created_at' => current_time( 'mysql' ),
'status'     => 'submitted',
]
);

$entry_id = (int) $wpdb->insert_id;

foreach ( $data as $key => $value ) {
$wpdb->insert(
$table_meta,
[
'entry_id'       => $entry_id,
'field_key'      => sanitize_key( $key ),
'value_longtext' => maybe_serialize( $value ),
'value_indexed'  => is_scalar( $value ) ? substr( (string) $value, 0, 191 ) : null,
'created_at'     => current_time( 'mysql' ),
]
);
}

do_action( 'wp_betterforms/entry_created', $entry_id, $form, $data );

return $entry_id;
}
}
