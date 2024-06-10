<?php
/**
 * Plugin Name: Travel Cost Calculator
 * Description: A plugin to calculate and track travel costs.
 * Version: 1.0
 * Author: senior developer edwine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Activation hook
function tcc_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'travel_cost_calculator';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        trip_title text NOT NULL,
        travel_from text NOT NULL,
        travel_to text NOT NULL,
        total float NOT NULL,
        date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $table_name_expenses = $wpdb->prefix . 'travel_cost_expenses';

    $sql_expenses = "CREATE TABLE $table_name_expenses (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        trip_id mediumint(9) NOT NULL,
        title text NOT NULL,
        category text NOT NULL,
        description text NOT NULL,
        no_of_people int NOT NULL,
        travel_from text NOT NULL,
        travel_to text NOT NULL,
        price float NOT NULL,
        image_url text DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_expenses);
}
register_activation_hook(__FILE__, 'tcc_activate');

// Deactivation hook
function tcc_deactivate() {
    // No specific actions needed on deactivation.
}
register_deactivation_hook(__FILE__, 'tcc_deactivate');

// Handle form submission
function tcc_handle_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'travel_cost_calculator';

        if (isset($_POST['tcc_submit'])) {
            // Add a new trip
            $user_id = get_current_user_id();
            $trip_title = sanitize_text_field($_POST['tripTitle']);
            $travel_from = sanitize_text_field($_POST['travelFrom']);
            $travel_to = sanitize_text_field($_POST['travelTo']);
            $total = floatval($_POST['total']);
            $date_created = sanitize_text_field($_POST['dateCreated']);

            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'trip_title' => $trip_title,
                    'travel_from' => $travel_from,
                    'travel_to' => $travel_to,
                    'total' => $total,
                    'date_created' => $date_created
                ]
            );
        } elseif (isset($_POST['delete_trip'])) {
            // Delete a trip
            $trip_id = intval($_POST['trip_id']);
            $wpdb->delete($table_name, ['id' => $trip_id]);
        } elseif (isset($_POST['edit_trip_submit'])) {
            // Edit a trip
            $trip_id = intval($_POST['trip_id']);
            $trip_title = sanitize_text_field($_POST['tripTitle']);
            $travel_from = sanitize_text_field($_POST['travelFrom']);
            $travel_to = sanitize_text_field($_POST['travelTo']);
            $total = floatval($_POST['total']);
            $date_created = sanitize_text_field($_POST['dateCreated']);

            $wpdb->update(
                $table_name,
                [
                    'trip_title' => $trip_title,
                    'travel_from' => $travel_from,
                    'travel_to' => $travel_to,
                    'total' => $total,
                    'date_created' => $date_created
                ],
                ['id' => $trip_id]
            );
        }
    }
}
add_action('init', 'tcc_handle_form_submission');

// Shortcode to display the travel cost calculator
function tcc_display_calculator() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">log in</a> to use the travel cost calculator.</p>';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'travel_cost_calculator';
    $user_id = get_current_user_id();
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    ob_start();
    ?>
    <div class="tcc-container">
        <h1 class="tcc-heading">Travel Cost Calculator</h1>
        <button class="add-trip-btn" id="addTripBtn">+ Add Trip</button>
        <!-- The Modal -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <form method="post">
                    <label for="tripTitle">Trip Title:</label><br>
                    <input type="text" id="tripTitle" name="tripTitle" required><br><br>
                    <label for="travelFrom">Travelling from:</label><br>
                    <input type="text" id="travelFrom" name="travelFrom" required><br><br>
                    <label for="travelTo">Travelling to:</label><br>
                    <input type="text" id="travelTo" name="travelTo" required><br><br>
                    <label for="total">Total:</label><br>
                    <input type="text" id="total" name="total" required><br><br>
                    <label for="dateCreated">Date created:</label><br>
                    <input type="date" id="dateCreated" name="dateCreated" required><br><br>
                    <input type="submit" name="tcc_submit" value="Submit">
                </form>
            </div>
        </div>

        <!-- Display the trip data in a table -->
        <table class="trip-table">
            <thead>
                <tr>
                    <th>Trip Title</th>
                    <th>Travelling from</th>
                    <th>Travelling to</th>
                    <th>Total</th>
                    <th>Date created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results): ?>
                    <?php foreach ($results as $row): ?>
                        <tr class="trip-row" data-trip-id="<?php echo esc_attr($row->id); ?>">
                            <td><a href="<?php echo get_permalink(get_page_by_path('trip-details')) . '?trip_id=' . esc_attr($row->id); ?>"><?php echo esc_html($row->trip_title); ?></a></td>
                            <td><?php echo esc_html($row->travel_from); ?></td>
                            <td><?php echo esc_html($row->travel_to); ?></td>
                            <td><?php echo esc_html($row->total); ?></td>
                            <td><?php echo esc_html($row->date_created); ?></td>
                            <td>
                                <button class="edit-trip-btn" data-trip-id="<?php echo esc_attr($row->id); ?>" data-trip-title="<?php echo esc_attr($row->trip_title); ?>" data-travel-from="<?php echo esc_attr($row->travel_from); ?>" data-travel-to="<?php echo esc_attr($row->travel_to); ?>" data-total="<?php echo esc_attr($row->total); ?>" data-date-created="<?php echo esc_attr($row->date_created); ?>">Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="trip_id" value="<?php echo esc_attr($row->id); ?>">
                                    <input type="submit" name="delete_trip" value="Delete">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No trips found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('myModal');
            var btn = document.getElementById('addTripBtn');
            var span = document.getElementsByClassName('close')[0];

            btn.onclick = function() {
                modal.style.display = 'block';
            }

            span.onclick = function() {
                modal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            var editButtons = document.querySelectorAll('.edit-trip-btn');
            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var tripId = button.getAttribute('data-trip-id');
                    var tripTitle = button.getAttribute('data-trip-title');
                    var travelFrom = button.getAttribute('data-travel-from');
                    var travelTo = button.getAttribute('data-travel-to');
                    var total = button.getAttribute('data-total');
                    var dateCreated = button.getAttribute('data-date-created');

                    var editFormHtml = `
                        <form method="post" class="edit-form">
                            <input type="hidden" name="trip_id" value="${tripId}">
                            <label for="editTripTitle">Trip Title:</label><br>
                            <input type="text" id="editTripTitle" name="tripTitle" value="${tripTitle}" required><br><br>
                            <label for="editTravelFrom">Travelling from:</label><br>
                            <input type="text" id="editTravelFrom" name="travelFrom" value="${travelFrom}" required><br><br>
                            <label for="editTravelTo">Travelling to:</label><br>
                            <input type="text" id="editTravelTo" name="travelTo" value="${travelTo}" required><br><br>
                            <label for="editTotal">Total:</label><br>
                            <input type="text" id="editTotal" name="total" value="${total}" required><br><br>
                            <label for="editDateCreated">Date created:</label><br>
                            <input type="date" id="editDateCreated" name="dateCreated" value="${dateCreated}" required><br><br>
                            <input type="submit" name="edit_trip_submit" value="Submit">
                        </form>
                    `;

                    var row = button.closest('tr');
                    var existingForm = row.querySelector('.edit-form');
                    if (existingForm) {
                        existingForm.remove();
                    } else {
                        row.insertAdjacentHTML('afterend', `<tr class="edit-form-row"><td colspan="6">${editFormHtml}</td></tr>`);
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('travel_cost_calculator', 'tcc_display_calculator');

// Add page template for trip details
function tcc_trip_details_template($template) {
    if (is_page('trip-details')) {
        $template = plugin_dir_path(__FILE__) . 'trip-details-template.php';
    }
    return $template;
}
add_filter('template_include', 'tcc_trip_details_template');

// Register and enqueue necessary scripts and styles
function tcc_enqueue_scripts() {
    wp_enqueue_style('tcc-styles', plugin_dir_url(__FILE__) . 'css/styles.css');
}
add_action('wp_enqueue_scripts', 'tcc_enqueue_scripts');
