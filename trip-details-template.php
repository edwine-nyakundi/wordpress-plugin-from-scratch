<?php
/**
 * Template Name: Trip Details
 */

get_header();

echo '<h2>Expenses</h2>';

if (isset($_GET['trip_id'])) {
    global $wpdb;
    $trip_id = intval($_GET['trip_id']);
    $table_name = $wpdb->prefix . 'travel_cost_calculator';
    $trip = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $trip_id));

    if ($trip) {
        echo '<h1>' . esc_html($trip->trip_title) . '</h1>';

        $expenses_table = $wpdb->prefix . 'travel_cost_expenses';

        // Check if form submitted for adding expense
        if (isset($_POST['add_expense_submit'])) {
            $expense_title = sanitize_text_field($_POST['expenseTitle']);
            $expense_category = sanitize_text_field($_POST['expenseCategory']);
            $expense_description = sanitize_text_field($_POST['expenseDescription']);
            $expense_no_of_people = intval($_POST['expenseNoOfPeople']);
            $expense_travel_from = sanitize_text_field($_POST['expenseTravelFrom']);
            $expense_travel_to = sanitize_text_field($_POST['expenseTravelTo']);
            $expense_price = floatval($_POST['expensePrice']);

            // Insert expense into database
            $wpdb->insert(
                $expenses_table,
                array(
                    'trip_id' => $trip_id,
                    'title' => $expense_title,
                    'category' => $expense_category,
                    'description' => $expense_description,
                    'no_of_people' => $expense_no_of_people,
                    'travel_from' => $expense_travel_from,
                    'travel_to' => $expense_travel_to,
                    'price' => $expense_price,
                ),
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%f'
                )
            );

            // Update total in trips table
            $new_total = $trip->total + $expense_price;
            $wpdb->update(
                $table_name,
                array('total' => $new_total),
                array('id' => $trip_id),
                array('%f'),
                array('%d')
            );
            $trip->total = $new_total;
        }

        // Check if form submitted for deleting expense
        if (isset($_POST['delete_expense'])) {
            $expense_id = intval($_POST['expense_id']);
            $expense = $wpdb->get_row($wpdb->prepare("SELECT * FROM $expenses_table WHERE id = %d", $expense_id));
            if ($expense) {
                // Delete expense from database
                $wpdb->delete(
                    $expenses_table,
                    array('id' => $expense_id),
                    array('%d')
                );

                // Update total in trips table
                $new_total = $trip->total - $expense->price;
                $wpdb->update(
                    $table_name,
                    array('total' => $new_total),
                    array('id' => $trip_id),
                    array('%f'),
                    array('%d')
                );
                $trip->total = $new_total;
            }
        }

        // Check if form submitted for editing expense
        if (isset($_POST['edit_expense_submit'])) {
            $expense_id = intval($_POST['expense_id']);
            $expense_title = sanitize_text_field($_POST['expenseTitle']);
            $expense_category = sanitize_text_field($_POST['expenseCategory']);
            $expense_description = sanitize_text_field($_POST['expenseDescription']);
            $expense_no_of_people = intval($_POST['expenseNoOfPeople']);
            $expense_travel_from = sanitize_text_field($_POST['expenseTravelFrom']);
            $expense_travel_to = sanitize_text_field($_POST['expenseTravelTo']);
            $expense_price = floatval($_POST['expensePrice']);

            $expense = $wpdb->get_row($wpdb->prepare("SELECT * FROM $expenses_table WHERE id = %d", $expense_id));

            if ($expense) {
                // Update expense in database
                $wpdb->update(
                    $expenses_table,
                    array(
                        'title' => $expense_title,
                        'category' => $expense_category,
                        'description' => $expense_description,
                        'no_of_people' => $expense_no_of_people,
                        'travel_from' => $expense_travel_from,
                        'travel_to' => $expense_travel_to,
                        'price' => $expense_price,
                    ),
                    array('id' => $expense_id),
                    array(
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                        '%f'
                    ),
                    array('%d')
                );

                // Update total in trips table
                $price_difference = $expense_price - $expense->price;
                $new_total = $trip->total + $price_difference;
                $wpdb->update(
                    $table_name,
                    array('total' => $new_total),
                    array('id' => $trip_id),
                    array('%f'),
                    array('%d')
                );
                $trip->total = $new_total;
            }
        }

        $expenses = $wpdb->get_results($wpdb->prepare("SELECT * FROM $expenses_table WHERE trip_id = %d", $trip_id));

        if ($expenses) {
            echo '<table class="trip-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>No of People</th>
                            <th>Travel From</th>
                            <th>Travel To</th>
                            <th>Price</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($expenses as $expense) {
                echo '<tr data-expense-id="' . esc_attr($expense->id) . '">
                        <td class="view-mode">' . esc_html($expense->title) . '</td>
                        <td class="view-mode">' . esc_html($expense->category) . '</td>
                        <td class="view-mode">' . esc_html($expense->description) . '</td>
                        <td class="view-mode">' . esc_html($expense->no_of_people) . '</td>
                        <td class="view-mode">' . esc_html($expense->travel_from) . '</td>
                        <td class="view-mode">' . esc_html($expense->travel_to) . '</td>
                        <td class="view-mode">' . esc_html($expense->price) . '</td>';
                if ($expense->image_url) {
                    echo '<td class="view-mode"><img src="' . esc_url($expense->image_url) . '" alt="' . esc_attr($expense->title) . '" style="width: 50px; height: 50px;"></td>';
                } else {
                    echo '<td class="view-mode">No image</td>';
                }
                echo '<td class="view-mode">
                        <button class="edit-expense-btn" data-expense-id="' . esc_attr($expense->id) . '">Edit</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="expense_id" value="' . esc_attr($expense->id) . '">
                            <input type="submit" name="delete_expense" value="Delete">
                        </form>
                      </td>
                    </tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No expenses found for this trip.</p>';
        }

        echo '<p>Total COST: <span id="total-expenses">' . esc_html($trip->total) . '</span></p>';

        ?>
        <div class="add-expense-btn-container">
            <button class="add-expense-btn" data-category="Transportation">TRANSPORTATION +</button>
            <button class="add-expense-btn" data-category="Accommodation">ACCOMMODATION +</button>
            <button class="add-expense-btn" data-category="Other Expenses">OTHER EXPENSES +</button>
        </div>

        <!-- Expense form -->
        <div id="expense-form-container" style="display: none; background: white; padding: 20px; border: 1px solid #ccc;">
            <h2>Add Expense</h2>
            <form id="expense-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="tripId" value="<?php echo esc_attr($trip_id); ?>">
                <input type="hidden" id="categoryField" name="expenseCategory" value="">
                <label for="expenseTitle">Title:</label><br>
                <input type="text" id="expenseTitle" name="expenseTitle" required><br><br>
                <label for="expenseDescription">Description:</label><br>
                <textarea id="expenseDescription" name="expenseDescription" required></textarea><br><br>
                <label for="expenseNoOfPeople">Number of People:</label><br>
                <input type="number" id="expenseNoOfPeople" name="expenseNoOfPeople" required><br><br>
                <label for="expenseTravelFrom">Travel From:</label><br>
                <input type="text" id="expenseTravelFrom" name="expenseTravelFrom" required><br><br>
                <label for="expenseTravelTo">Travel To:</label><br>
                <input type="text" id="expenseTravelTo" name="expenseTravelTo" required><br><br>
                <label for="expensePrice">Price:</label><br>
                <input type="number" id="expensePrice" name="expensePrice" step="0.01" required><br><br>
                <label for="expenseImage">Image:</label><br>
                <input type="file" id="expenseImage" name="expenseImage"><br><br>
                <input type="submit" name="add_expense_submit" value="Add Expense">
            </form>
        </div>

        <!-- Edit expense modal -->
        <div id="edit-expense-modal" style="display: none; background: white; padding: 20px; border: 1px solid #ccc;">
            <h2>Edit Expense</h2>
            <form id="edit-expense-form" method="post">
                <input type="hidden" id="edit-expense-id" name="expense_id">
                <label for="edit-expense-title">Title:</label><br>
                <input type="text" id="edit-expense-title" name="expenseTitle" required><br><br>
                <label for="edit-expense-category">Category:</label><br>
                <input type="text" id="edit-expense-category" name="expenseCategory" required><br><br>
                <label for="edit-expense-description">Description:</label><br>
                <textarea id="edit-expense-description" name="expenseDescription" required></textarea><br><br>
                <label for="edit-expense-no-of-people">Number of People:</label><br>
                <input type="number" id="edit-expense-no-of-people" name="expenseNoOfPeople" required><br><br>
                <label for="edit-expense-travel-from">Travel From:</label><br>
                <input type="text" id="edit-expense-travel-from" name="expenseTravelFrom" required><br><br>
                <label for="edit-expense-travel-to">Travel To:</label><br>
                <input type="text" id="edit-expense-travel-to" name="expenseTravelTo" required><br><br>
                <label for="edit-expense-price">Price:</label><br>
                <input type="number" id="edit-expense-price" name="expensePrice" step="0.01" required><br><br>
                <input type="submit" name="edit_expense_submit" value="Update Expense">
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const addExpenseBtns = document.querySelectorAll('.add-expense-btn');
                const expenseFormContainer = document.getElementById('expense-form-container');
                const expenseForm = document.getElementById('expense-form');
                const categoryField = document.getElementById('categoryField');

                addExpenseBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        categoryField.value = this.dataset.category;
                        expenseFormContainer.style.display = 'block';
                    });
                });

                const editExpenseBtns = document.querySelectorAll('.edit-expense-btn');
                const editExpenseModal = document.getElementById('edit-expense-modal');
                const editExpenseForm = document.getElementById('edit-expense-form');
                const editExpenseId = document.getElementById('edit-expense-id');

                editExpenseBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const expenseId = this.dataset.expenseId;
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_expense_details&expense_id=' + expenseId)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    editExpenseId.value = data.data.id;
                                    editExpenseForm.querySelector('#edit-expense-title').value = data.data.title;
                                    editExpenseForm.querySelector('#edit-expense-category').value = data.data.category;
                                    editExpenseForm.querySelector('#edit-expense-description').value = data.data.description;
                                    editExpenseForm.querySelector('#edit-expense-no-of-people').value = data.data.no_of_people;
                                    editExpenseForm.querySelector('#edit-expense-travel-from').value = data.data.travel_from;
                                    editExpenseForm.querySelector('#edit-expense-travel-to').value = data.data.travel_to;
                                    editExpenseForm.querySelector('#edit-expense-price').value = data.data.price;
                                    editExpenseModal.style.display = 'block';
                                }
                            });
                    });
                });
            });
        </script>
        <?php
    } else {
        echo '<p>Trip not found.</p>';
    }
} else {
    echo '<p>No trip ID provided.</p>';
}

get_footer();
?>
