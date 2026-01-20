<?php
/**
 * Plugin Name: Custom Form Builder
 * Description: Create and manage custom forms with an intuitive admin interface
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class Custom_Form_Builder {
    
    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_shortcode('custom_form', [$this, 'render_form']);
        add_action('wp_ajax_cfb_save_form', [$this, 'save_form']);
        add_action('wp_ajax_cfb_get_form', [$this, 'get_form']);
        add_action('wp_ajax_cfb_delete_form', [$this, 'delete_form']);
        add_action('wp_ajax_cfb_submit_form', [$this, 'handle_submission']);
        add_action('wp_ajax_nopriv_cfb_submit_form', [$this, 'handle_submission']);
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $forms_table = $wpdb->prefix . 'cfb_forms';
        $submissions_table = $wpdb->prefix . 'cfb_submissions';
        
        $sql_forms = "CREATE TABLE IF NOT EXISTS $forms_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            fields longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql_submissions = "CREATE TABLE IF NOT EXISTS $submissions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            data longtext NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_forms);
        dbDelta($sql_submissions);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Form Builder',
            'Form Builder',
            'manage_options',
            'custom-form-builder',
            [$this, 'admin_page'],
            'dashicons-feedback',
            30
        );
        
        add_submenu_page(
            'custom-form-builder',
            'Submissions',
            'Submissions',
            'manage_options',
            'cfb-submissions',
            [$this, 'submissions_page']
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_custom-form-builder' && $hook !== 'form-builder_page_cfb-submissions') return;
        
        wp_enqueue_style('cfb-admin', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_script('cfb-admin', plugin_dir_url(__FILE__) . 'admin-script.js', ['jquery'], '1.0', true);
        wp_localize_script('cfb-admin', 'cfbAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfb_nonce')
        ]);
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('cfb-frontend', plugin_dir_url(__FILE__) . 'frontend-style.css');
        wp_enqueue_script('cfb-frontend', plugin_dir_url(__FILE__) . 'frontend-script.js', ['jquery'], '1.0', true);
        wp_localize_script('cfb-frontend', 'cfbAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfb_nonce')
        ]);
    }
    
    public function admin_page() {
        global $wpdb;
        $forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cfb_forms ORDER BY id DESC");
        ?>
        <div class="wrap cfb-admin">
            <h1>Form Builder</h1>
            
            <div class="cfb-create-form">
                <h2>Create New Form</h2>
                <input type="text" id="cfb-form-name" placeholder="Form Name" />
                <button id="cfb-create-btn" class="button button-primary">Create Form</button>
            </div>
            
            <div id="cfb-form-editor" style="display:none;">
                <h2>Form Editor: <span id="cfb-editing-name"></span></h2>
                <input type="hidden" id="cfb-editing-id" />
                
                <div class="cfb-add-field">
                    <select id="cfb-field-type">
                        <option value="text">Text Input</option>
                        <option value="email">Email</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Dropdown</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio Buttons</option>
                    </select>
                    <input type="text" id="cfb-field-label" placeholder="Field Label" />
                    <button id="cfb-add-field-btn" class="button">Add Field</button>
                </div>
                
                <div id="cfb-fields-list"></div>
                
                <button id="cfb-save-form-btn" class="button button-primary">Save Form</button>
                <button id="cfb-cancel-btn" class="button">Cancel</button>
            </div>
            
            <div class="cfb-forms-list">
                <h2>Your Forms</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Form Name</th>
                            <th>Shortcode</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><?php echo esc_html($form->name); ?></td>
                            <td><code>[custom_form id="<?php echo $form->id; ?>"]</code></td>
                            <td><?php echo date('M j, Y', strtotime($form->created_at)); ?></td>
                            <td>
                                <button class="button cfb-edit-form" data-id="<?php echo $form->id; ?>">Edit</button>
                                <button class="button cfb-delete-form" data-id="<?php echo $form->id; ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function submissions_page() {
        global $wpdb;
        $submissions = $wpdb->get_results("
            SELECT s.*, f.name as form_name 
            FROM {$wpdb->prefix}cfb_submissions s
            LEFT JOIN {$wpdb->prefix}cfb_forms f ON s.form_id = f.id
            ORDER BY s.id DESC
            LIMIT 100
        ");
        ?>
        <div class="wrap">
            <h1>Form Submissions</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Submitted</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td><?php echo esc_html($sub->form_name); ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($sub->submitted_at)); ?></td>
                        <td>
                            <?php 
                            $data = json_decode($sub->data, true);
                            foreach ($data as $key => $value) {
                                echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function save_form() {
        check_ajax_referer('cfb_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $form_id = intval($_POST['form_id']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $fields = json_encode($_POST['fields']);
        
        if ($form_id > 0) {
            $wpdb->update(
                $wpdb->prefix . 'cfb_forms',
                ['name' => $form_name, 'fields' => $fields],
                ['id' => $form_id]
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'cfb_forms',
                ['name' => $form_name, 'fields' => $fields]
            );
            $form_id = $wpdb->insert_id;
        }
        
        wp_send_json_success(['form_id' => $form_id]);
    }
    
    public function get_form() {
        check_ajax_referer('cfb_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $form_id = intval($_POST['form_id']);
        
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cfb_forms WHERE id = %d",
            $form_id
        ));
        
        if ($form) {
            wp_send_json_success($form);
        } else {
            wp_send_json_error('Form not found');
        }
    }
    
    public function delete_form() {
        check_ajax_referer('cfb_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $form_id = intval($_POST['form_id']);
        
        $wpdb->delete($wpdb->prefix . 'cfb_forms', ['id' => $form_id]);
        $wpdb->delete($wpdb->prefix . 'cfb_submissions', ['form_id' => $form_id]);
        
        wp_send_json_success();
    }
    
    public function handle_submission() {
        check_ajax_referer('cfb_nonce', 'nonce');
        
        global $wpdb;
        $form_id = intval($_POST['form_id']);
        $form_data = $_POST['form_data'];
        
        $sanitized_data = [];
        foreach ($form_data as $key => $value) {
            $sanitized_data[sanitize_text_field($key)] = sanitize_text_field($value);
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'cfb_submissions',
            [
                'form_id' => $form_id,
                'data' => json_encode($sanitized_data)
            ]
        );
        
        wp_send_json_success(['message' => 'Form submitted successfully!']);
    }
    
    public function render_form($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $form_id = intval($atts['id']);
        
        global $wpdb;
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cfb_forms WHERE id = %d",
            $form_id
        ));
        
        if (!$form) return 'Form not found';
        
        $fields = json_decode($form->fields, true);
        
        ob_start();
        ?>
        <div class="cfb-form-wrapper">
            <form class="cfb-form" data-form-id="<?php echo $form_id; ?>">
                <?php foreach ($fields as $field): ?>
                    <div class="cfb-field">
                        <label><?php echo esc_html($field['label']); ?></label>
                        <?php
                        switch ($field['type']) {
                            case 'textarea':
                                echo '<textarea name="' . esc_attr($field['label']) . '" required></textarea>';
                                break;
                            case 'select':
                                echo '<select name="' . esc_attr($field['label']) . '" required>';
                                echo '<option value="">Select...</option>';
                                if (!empty($field['options'])) {
                                    foreach ($field['options'] as $opt) {
                                        echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>';
                                    }
                                }
                                echo '</select>';
                                break;
                            case 'radio':
                                echo '<div class="cfb-radio-group">';
                                if (!empty($field['options'])) {
                                    foreach ($field['options'] as $i => $opt) {
                                        $id = 'radio_' . md5($field['label'] . $opt);
                                        echo '<label class="cfb-radio-label">';
                                        echo '<input type="radio" id="' . esc_attr($id) . '" name="' . esc_attr($field['label']) . '" value="' . esc_attr($opt) . '" required /> ';
                                        echo esc_html($opt);
                                        echo '</label>';
                                    }
                                }
                                echo '</div>';
                                break;
                            case 'checkbox':
                                echo '<div class="cfb-checkbox-group">';
                                if (!empty($field['options'])) {
                                    foreach ($field['options'] as $i => $opt) {
                                        $id = 'checkbox_' . md5($field['label'] . $opt);
                                        echo '<label class="cfb-checkbox-label">';
                                        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($field['label']) . '[]" value="' . esc_attr($opt) . '" /> ';
                                        echo esc_html($opt);
                                        echo '</label>';
                                    }
                                }
                                echo '</div>';
                                break;
                            default:
                                echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['label']) . '" required />';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="cfb-submit">Submit</button>
                <div class="cfb-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Custom_Form_Builder();