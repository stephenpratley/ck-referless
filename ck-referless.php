<?
/**
* Plugin Name: CK Referless
* Plugin URI: https://github.com/stephenpratley/ck-referless/
* Description: A sign-up form for Convertkit without the referral popup
* Version: 1.0 
* Author: Plugin Stephen Pratley
* Author URI: http://stephenpratley.com
* License: GPL12
*/

// Register the setting for the API key
function convertkit_register_settings() {
    add_option('convertkit_api_key', '');
    register_setting('convertkit_options_group', 'convertkit_api_key', 'convertkit_callback');
}
add_action('admin_init', 'convertkit_register_settings');

// Add a settings page for the ConvertKit API key
function convertkit_register_options_page() {
    add_options_page('ConvertKit Settings', 'ConvertKit Settings', 'manage_options', 'convertkit', 'convertkit_options_page');
}
add_action('admin_menu', 'convertkit_register_options_page');

// Display the settings page
function convertkit_options_page() {
    ?>
    <div>
        <h2>ConvertKit Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('convertkit_options_group'); ?>
            <table>
                <tr valign="top">
                    <th scope="row"><label for="convertkit_api_key">ConvertKit API Key</label></th>
                    <td><input type="text" id="convertkit_api_key" name="convertkit_api_key" value="<?php echo get_option('convertkit_api_key'); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


// Handle the form submission
function convertkit_handle_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convertkit_subscribe'])) {
        $api_key = get_option('convertkit_api_key');
        $email = sanitize_email($_POST['email']);
        $form_id = sanitize_text_field($_POST['form_id']);
        $success_url = esc_url_raw($_POST['success_url']);

        // Prepare the payload
        $payload = json_encode([
            'api_key' => $api_key,
            'email' => $email
        ]);

        // Initialize cURL
        $api_url = "https://api.convertkit.com/v3/forms/{$form_id}/subscribe";
        $ch = curl_init($api_url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // Execute cURL request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            // Redirect to the success URL with a unique parameter to avoid caching issues
            wp_redirect("{$success_url}?subscribed=1&email=" . urlencode($email));
            exit;
        } else {
            wp_die('Failed to add subscriber. Please try again.');
        }
    }
}
add_action('template_redirect', 'convertkit_handle_form_submission');



// Shortcode function to display the subscription form
function convertkit_subscribe_form($atts) {
    $atts = shortcode_atts(['form_id' => '', 'success_url' => ''], $atts, 'convertkit_subscribe_form');
    $form_id = esc_attr($atts['form_id']);
    $success_url = esc_url($atts['success_url']);

    // Get the current URL
    $current_url = esc_url(add_query_arg(NULL, NULL));

    ob_start();
    ?>
    <form class="row" role="form" method="post" action="<?php echo $current_url; ?>">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
        <input type="hidden" name="success_url" value="<?php echo $success_url; ?>">
        <input type="hidden" name="convertkit_subscribe" value="1">
        <div class="col-md mb-3">
            <input name="email" type="email" placeholder="Enter your email address" class="form-control rounded" required="">
        </div>
        <div class="col-md-4 mb-3">
            <button type="submit" data-element="submit" class="btn rounded btn-sm w-100">Subscribe</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('convertkit_subscribe_form', 'convertkit_subscribe_form');
