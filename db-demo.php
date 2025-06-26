<?php
/**
 * Plugin Name: DB Demo
 * Description: A simple plugin to demonstrate database operations in WordPress.
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: db-demo
 */

class DB_Demo {
    function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('init', [$this, 'activate']);
        // add_action('init', [$this, 'activate']);
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_post_db_demo_add', [$this, 'handle_add']);
        add_action('admin_post_db_demo_edit', [$this, 'handle_edit']);
        add_action('admin_post_db_demo_delete', [$this, 'handle_delete']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    function enqueue_admin_scripts() {
        wp_enqueue_media();
        wp_enqueue_script('db-demo-media-js', plugin_dir_url(__FILE__) . "admin/js/media.js", [], time(), true);
    }

    function register_admin_page() {
        add_menu_page(
            'DB Demo',
            'DB Demo',
            'manage_options',
            'db-demo',
            [$this, 'admin_page'],
            'dashicons-admin-generic',
            26
        );
    }

    function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'db_demo';
        $form_submission_url = admin_url('admin-post.php');

        $editing = false;
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_row = null;
        if ($edit_id) {
            $editing = true;
            //SELECT * FROM $table_name WHERE id = 
            $prepared_statement = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id);
            $edit_row = $wpdb->get_row($prepared_statement);
        }
        $action_name = $editing === false ? 'db_demo_add' : 'db_demo_edit';
        $button_label = $editing === false ? 'Add Person' : 'Edit Person';
        $media_url = ($editing && !empty($edit_row->media)) ? $edit_row->media : '';
        ?>
        <div class="wrap">
            <h1>DB Demo</h1>
            <h2>Add New Person</h2>
            <form method="post" action="<?php echo esc_url($form_submission_url); ?>">
                <?php wp_nonce_field('db_demo_action', 'db_demo_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr($action_name); ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($edit_row->id); ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="name">Person Name</label></th>
                        <td><input name="name" value="<?php echo esc_attr($editing ? $edit_row->name : ''); ?>" type="text" id="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="email">Person Email</label></th>
                        <td><input name="email" value="<?php echo esc_attr($editing ? $edit_row->email : ''); ?>" type="email" id="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="media_url">Media</label></th>
                        <td>
                            <input type="hidden" name="media_url" id="media_url" value="<?php echo $media_url; ?>" >
                            <button type="button" class="button" id="db-demo-media-btn">Select/Upload Image</button>
                            <div id="db-demo-media-preview" style="margin-top:10px;">
                                <?php if ($media_url) { ?>
									<img src="<?php echo esc_url($media_url); ?>" style="max-width:200px;max-height:100px;">
								<?php } ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" class="button button-primary" value="<?= esc_attr($button_label); ?>">
                        </td>
                    </tr>

                </table>
            </form>
            <?php
            $persons = $wpdb->get_results("SELECT * FROM $table_name");
            ?>
            <h2><?= _e('All Persons', 'db-demo'); ?></h2>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Media</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($persons) {
                        foreach ($persons as $person) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($person->id); ?></td>
                                <td><?php echo esc_html($person->name); ?></td>
                                <td><?php echo esc_html($person->email); ?></td>
                                <td>
                                    <?php
                                    if (!empty($person->media)) {
                                        echo "<img src='" . esc_url($person->media) . "' style='max-width:60px;max-height:60px;'/>";
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($person->created_at); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=db-demo&edit=' . $person->id); ?>" class="button">Edit</a>
                                    <form onsubmit="return confirm('are you sure?')" method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                        <?php wp_nonce_field('db_demo_action', 'db_demo_nonce'); ?>
                                        <input type="hidden" name="action" value="db_demo_delete">
                                        <input type="hidden" name="id" value="<?php echo esc_attr($person->id); ?>">
                                        <input type="submit" class="button button-secondary" value="Delete">
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    function handle_add() {
        if (!isset($_POST['db_demo_nonce']) || !wp_verify_nonce($_POST['db_demo_nonce'], 'db_demo_action')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'db_demo';

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $media_url = isset($_POST['media_url']) ? esc_url_raw($_POST['media_url']) : '';

        $wpdb->insert($table_name, [
            'name' => $name,
            'email' => $email,
            'media' => $media_url
        ]);

        wp_redirect(admin_url('admin.php?page=db-demo'));
    }

    function handle_edit() {
        if (!isset($_POST['db_demo_nonce']) || !wp_verify_nonce($_POST['db_demo_nonce'], 'db_demo_action')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'db_demo';

        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $media_url = isset($_POST['media_url']) ? esc_url_raw($_POST['media_url']) : '';

        if ($id <= 0 || empty($name) || empty($email)) {
            wp_die('Invalid data provided.');
        }

        $wpdb->update($table_name, [
            'name' => $name,
            'email' => $email,
            'media'=>$media_url
        ], ['id' => $id]);

        wp_redirect(admin_url('admin.php?page=db-demo'));
    }

    function handle_delete() {
        if (!isset($_POST['db_demo_nonce']) || !wp_verify_nonce($_POST['db_demo_nonce'], 'db_demo_action')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'db_demo';

        $id = intval($_POST['id']);

        if ($id <= 0) {
            wp_die('Invalid data provided.');
        }

        $wpdb->delete($table_name, ['id' => $id]);

        wp_redirect(admin_url('admin.php?page=db-demo'));
    }

    function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . "db_demo";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			email VARCHAR(100) NOT NULL,
            media VARCHAR(255) DEFAULT '' NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

new DB_Demo();