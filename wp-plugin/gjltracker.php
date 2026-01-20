<?php
/**
 * Plugin Name: GJL Tracker Plugin
 * Plugin URI: https://github.com/GeekGirl1024/GJL-BillTracker
 * Description: A Wordpress plugin to display Bills or Lawsuits Information.
 * Version: 1.1
 * Author: Sophia Lee
 * Author URI: https://www.sophialee.dev
 * License: MIT License
 */

 // Add an admin menu for the Bill Tracker plugin

function billtracker_admin_menu() {
    add_menu_page(
        'Tracker', // Page title
        'Tracker', // Menu title
        'manage_options', // Capability
        'tracker', // Menu slug
        'tracker_admin_page', // Callback function
        'dashicons-list-view', // Icon
        20 // Position
    );

    add_submenu_page(
        'tracker', // Parent slug
        'Bill Tracker', // Page title
        'Bill Tracker', // Menu title
        'manage_options', // Capability
        'tracker_billtracker', // Menu slug
        'billtracker_admin_page' // Callback function
    );

    add_submenu_page(
        'tracker', // Parent slug
        'Lawsuit Tracker', // Page title
        'Lawsuit Tracker', // Menu title
        'manage_options', // Capability
        'tracker_lawsuittracker', // Menu slug
        'lawsuittracker_admin_page' // Callback function
    );
}
add_action('admin_menu', 'billtracker_admin_menu');

function tracker_admin_page() {
    ?>

    <div class="wrap">
        <h1>GJL Tracker</h1>
        

        
    </div>

    <?php

}

function lawsuittracker_admin_page() {
    lawsuittracker_admin_do_updates();

    // Fetch all records from the custom table
    $lawsuits = (get_option('lawsuittracker_data', []));
    usort($lawsuits, function ($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    // Display the admin page

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lawsuittracker_edit'])) {
        $edit_index = intval($_POST['edit_index']);
        $lawsuit_to_edit = $lawsuits[$edit_index] ?? null;
    }
    ?>
    
    <div class="wrap">
        <h1>Lawsuit Tracker</h1>
        <h2><?php echo isset($lawsuit_to_edit) ? 'Edit Lawsuit' : 'Add New Lawsuit'; ?></h2>
        <form method="POST">
            <input type="hidden" name="edit_index" value="<?php echo isset($lawsuit_to_edit) ? esc_attr($edit_index) : ''; ?>">
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo isset($lawsuit_to_edit) ? esc_attr(stripslashes($lawsuit_to_edit['name'])) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="category">Category</label></th>
                    <td>
                        <select name="category" id="category" onchange="toggleNewCategoryInput(this)">
                            <option value="">Select a Category</option>
                            <?php
                            // Get existing categories from the lawsuits
                            $existing_categories = array_unique(array_column($lawsuits, 'category'));
                            foreach ($existing_categories as $existing_category): ?>
                                <option value="<?php echo esc_attr(stripslashes($existing_category)); ?>" <?php echo isset($lawsuit_to_edit) && $lawsuit_to_edit['category'] === $existing_category ? 'selected' : ''; ?>>
                                    <?php echo esc_html(stripslashes($existing_category)); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">Add New Category</option>
                        </select>
                        <input type="text" name="new_category" id="new_category" placeholder="Enter new category" style="display:none;" />
                    </td>
                </tr>
                <tr>
                    <th><label for="url">URL</label></th>
                    <td><input type="url" name="url" id="url" value="<?php echo isset($lawsuit_to_edit) ? esc_attr($lawsuit_to_edit['url']) : ''; ?>" placeholder="Enter a URL"></td>
                </tr>
                <tr>
                    <th><label for="stage">Stage</label></th>
                    <td><input type="number" name="stage" id="stage" value="<?php echo isset($lawsuit_to_edit) ? esc_attr($lawsuit_to_edit['stage']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="passage">Sort</label></th>
                    <td><input type="number" name="sort" id="sort" value="<?php echo isset($lawsuit_to_edit) ? esc_attr($lawsuit_to_edit['sort']) : ''; ?>" required></td>
                </tr>
            </table>
            <p>
                <input type="submit" name="<?php echo isset($lawsuit_to_edit) ? 'lawsuittracker_update' : 'lawsuittracker_add'; ?>" class="button button-primary" value="<?php echo isset($lawsuit_to_edit) ? 'Update Lawsuit' : 'Add Lawsuit'; ?>">
            </p>
        </form>

        <h2>Existing Lawsuits</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>URL</th>
                    <th>Stage</th>
                    <th>Sort</th>
                    <th>Actions</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php $index = 0;
                    foreach ($lawsuits as $row): ?>
                    <tr>
                        <td><?php echo esc_html(stripslashes($row['name'])); ?></td>
                        <td><?php echo esc_html(stripslashes($row['category'])); ?></td>
                        <td><a href="<?php echo esc_url($row['url']); ?>" target="_blank"><?php echo esc_html($row['url']); ?></a></td>
                        <td><?php echo esc_html($row['stage']); ?></td>
                        <td><?php echo esc_html($row['sort']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="edit_index" value="<?php echo esc_attr($index); ?>">
                                <input type="submit" name="lawsuittracker_edit" class="button" value="Edit">
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_index" value="<?php echo esc_attr($index); ?>">
                                <input type="submit" name="tracker_delete" class="button button-danger" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php
                    $index++;
                    endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <?php

    admin_javascript();

}

function lawsuittracker_admin_do_updates()  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    // Handle form submissions for adding new records
    if (isset($_POST['lawsuittracker_add'])) {
        $lawsuits = get_option('lawsuittracker_data', []); // Retrieve existing data
        usort($lawsuits, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
        $lawsuits = is_array($lawsuits) ? $lawsuits : []; // Ensure it's an array

        $category = sanitize_text_field($_POST['category']);
        if ($category === 'new') {
            $category = sanitize_text_field($_POST['new_category']);
        }

        // Add the new bill to the array
        $lawsuits[] = [
            'name' => sanitize_text_field($_POST['name']),
            'category' => $category,
            'url' => esc_url_raw($_POST['url']),
            'stage' => intval($_POST['stage']),
            'sort' => intval($_POST['sort']),
        ];

        // Save the updated data
        update_option('lawsuittracker_data', $lawsuits);

        echo '<div class="updated"><p>Record added successfully!</p></div>';
    } else if (isset($_POST['lawsuittracker_update'])) {
        $edit_index = intval($_POST['edit_index']);
        $lawsuits = (get_option('lawsuittracker_data', []));
        usort($lawsuits, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
        $category = sanitize_text_field($_POST['category']);
        if ($category === 'new') {
            $category = sanitize_text_field($_POST['new_category']);
        }
        $lawsuits[$edit_index] = [
            'name' => sanitize_text_field($_POST['name']),
            'category' => $category,
            'url' => esc_url_raw($_POST['url']),
            'stage' => intval($_POST['stage']),
            'sort' => intval($_POST['sort']),
        ];
        update_option('lawsuittracker_data', $lawsuits);
    
        echo '<div class="updated"><p>Record updated successfully!</p></div>';
    } else if (isset($_POST['tracker_delete'])) {
        $delete_index = intval($_POST['delete_index']);
        $lawsuits = (get_option('lawsuittracker_data', []));
        usort($lawsuits, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
    
        // Remove the selected bill from the array
        if (isset($lawsuits[$delete_index])) {
            unset($lawsuits[$delete_index]);
            $lawsuits = array_values($lawsuits); // Reindex the array
            update_option('billtracker_data', $lawsuits);
    
            echo '<div class="updated"><p>Record deleted successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Failed to delete the record. Record not found.</p></div>';
        }
    }
}

// Render the admin page
function billtracker_admin_page() {
    billtracker_admin_do_updates();

    // Fetch all records from the custom table
    $bills = (get_option('billtracker_data', []));
    usort($bills, function ($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    // Display the admin page

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['billtracker_edit'])) {
        $edit_index = intval($_POST['edit_index']);
        $bill_to_edit = $bills[$edit_index] ?? null;
    } 
    ?>
    
    <div class="wrap">
        <h1>Bill Tracker</h1>
        <h2><?php echo isset($bill_to_edit) ? 'Edit Bill' : 'Add New Bill'; ?></h2>
        <form method="POST">
            <input type="hidden" name="edit_index" value="<?php echo isset($bill_to_edit) ? esc_attr($edit_index) : ''; ?>">
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo isset($bill_to_edit) ? esc_attr(stripslashes($bill_to_edit['name'])) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="category">Category</label></th>
                    <td>
                        <select name="category" id="category" onchange="toggleNewCategoryInput(this)">
                            <option value="">Select a Category</option>
                            <?php
                            // Get existing categories from the bills
                            $existing_categories = array_unique(array_column($bills, 'category'));
                            foreach ($existing_categories as $existing_category): ?>
                                <option value="<?php echo esc_attr(stripslashes($existing_category)); ?>" <?php echo isset($bill_to_edit) && $bill_to_edit['category'] === $existing_category ? 'selected' : ''; ?>>
                                    <?php echo esc_html(stripslashes($existing_category)); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">Add New Category</option>
                        </select>
                        <input type="text" name="new_category" id="new_category" placeholder="Enter new category" style="display:none;" />
                    </td>
                </tr>
                <tr>
                    <th><label for="stance">Stance</label></th>
                    <td>
                        <select name="stance" id="stance" required>
                            <option value="">Select a Stance</option>
                            <option value="pro" <?php echo isset($bill_to_edit) && key_exists('stance', $bill_to_edit) && $bill_to_edit['stance'] === "pro" ? 'selected' : ''; ?> >Pro</option>
                            <option value="con" <?php echo isset($bill_to_edit) && key_exists('stance', $bill_to_edit) &&$bill_to_edit['stance'] === "con" ? 'selected' : ''; ?>>Con</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="url">URL</label></th>
                    <td><input type="url" name="url" id="url" value="<?php echo isset($bill_to_edit) ? esc_attr($bill_to_edit['url']) : ''; ?>" placeholder="Enter a URL" required></td>
                </tr>
                <tr>
                    <th><label for="house">House</label></th>
                    <td><input type="number" name="house" id="house" value="<?php echo isset($bill_to_edit) ? esc_attr($bill_to_edit['house']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="senate">Senate</label></th>
                    <td><input type="number" name="senate" id="senate" value="<?php echo isset($bill_to_edit) ? esc_attr($bill_to_edit['senate']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="passage">Passage</label></th>
                    <td><input type="number" name="passage" id="passage" value="<?php echo isset($bill_to_edit) ? esc_attr($bill_to_edit['passage']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="passage">Sort</label></th>
                    <td><input type="number" name="sort" id="sort" value="<?php echo isset($bill_to_edit) ? esc_attr($bill_to_edit['sort']) : ''; ?>" required></td>
                </tr>
            </table>
            <p>
                <input type="submit" name="<?php echo isset($bill_to_edit) ? 'billtracker_update' : 'billtracker_add'; ?>" class="button button-primary" value="<?php echo isset($bill_to_edit) ? 'Update Bill' : 'Add Bill'; ?>">
            </p>
        </form>

        <h2>Existing Bills</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stance</th>
                    <th>Url</th>
                    <th>House</th>
                    <th>Senate</th>
                    <th>Passage</th>
                    <th>Sort</th>
                    <th>Actions</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php $index = 0;
                    foreach ($bills as $row): ?>
                    <tr>
                        <td><?php echo esc_html(stripslashes($row['name'])); ?></td>
                        <td><?php echo esc_html(stripslashes($row['category'])); ?></td>
                        <td><?php echo esc_html(stripslashes(key_exists('stance', $row) ? $row['stance'] : "")); ?></td>
                        <td><a href="<?php echo esc_url($row['url']); ?>" target="_blank"><?php echo esc_html($row['url']); ?></a></td>
                        <td><?php echo esc_html($row['house']); ?></td>
                        <td><?php echo esc_html($row['senate']); ?></td>
                        <td><?php echo esc_html($row['passage']); ?></td>
                        <td><?php echo esc_html($row['sort']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="edit_index" value="<?php echo esc_attr($index); ?>">
                                <input type="submit" name="billtracker_edit" class="button" value="Edit">
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_index" value="<?php echo esc_attr($index); ?>">
                                <input type="submit" name="tracker_delete" class="button button-danger" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php
                    $index++;
                    endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <?php

    admin_javascript();
}

function admin_javascript() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add a confirmation dialog to all delete buttons
            const deleteButtons = document.querySelectorAll('input[name="tracker_delete"]');
            deleteButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    const confirmed = confirm('Are you sure you want to delete this bill?');
                    if (!confirmed) {
                        event.preventDefault(); // Cancel the form submission
                    }
                });
            });
        
            const form = document.querySelector('form');
            form.addEventListener('submit', function (event) {
                const categorySelect = document.getElementById('category');
                const newCategoryInput = document.getElementById('new_category');

                // Check if a category is selected
                if (categorySelect.value === '') {
                    alert('Please select a category.');
                    event.preventDefault(); // Prevent form submission
                    return;
                }

                // Check if "Add New Category" is selected but no new category is entered
                if (categorySelect.value === 'new' && newCategoryInput.value.trim() === '') {
                    alert('Please enter a new category.');
                    event.preventDefault(); // Prevent form submission
                    return;
                }
            });
        });
    
        function toggleNewCategoryInput(selectElement) {
            const newCategoryInput = document.getElementById('new_category');
            if (selectElement.value === 'new') {
                newCategoryInput.style.display = 'inline-block';
                newCategoryInput.required = true;
            } else {
                newCategoryInput.style.display = 'none';
                newCategoryInput.required = false;
            }
        }
    </script>
    <?php
}

function billtracker_admin_do_updates() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    // Handle form submissions for adding new records
    if (isset($_POST['billtracker_add'])) {
        $bills = get_option('billtracker_data', []); // Retrieve existing data
        usort($bills, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
        $bills = is_array($bills) ? $bills : []; // Ensure it's an array

        $category = sanitize_text_field($_POST['category']);
        if ($category === 'new') {
            $category = sanitize_text_field($_POST['new_category']);
        }

        $stance = sanitize_text_field($_POST['stance']);
        if(!($stance == "pro" || $stance == "con")) {
            $stance = "";
        }

        // Add the new bill to the array
        $bills[] = [
            'name' => sanitize_text_field($_POST['name']),
            'category' => $category,
            'stance' => $stance,
            'url' => esc_url_raw($_POST['url']),
            'house' => intval($_POST['house']),
            'senate' => intval($_POST['senate']),
            'passage' => intval($_POST['passage']),
            'sort' => intval($_POST['sort']),
        ];

        // Save the updated data
        update_option('billtracker_data', $bills);

        echo '<div class="updated"><p>Record added successfully!</p></div>';
    } else if (isset($_POST['billtracker_update'])) {
        $edit_index = intval($_POST['edit_index']);
        $bills = (get_option('billtracker_data', []));
        usort($bills, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
        $category = sanitize_text_field($_POST['category']);
        if ($category === 'new') {
            $category = sanitize_text_field($_POST['new_category']);
        }

        $stance = sanitize_text_field($_POST['stance']);
        if(!($stance == "pro" || $stance == "con")) {
            $stance = "";
        }

        $bills[$edit_index] = [
            'name' => sanitize_text_field($_POST['name']),
            'category' => $category,
            'stance' => $stance,
            'url' => esc_url_raw($_POST['url']),
            'house' => intval($_POST['house']),
            'senate' => intval($_POST['senate']),
            'passage' => intval($_POST['passage']),
            'sort' => intval($_POST['sort']),
        ];
        update_option('billtracker_data', $bills);
    
        echo '<div class="updated"><p>Record updated successfully!</p></div>';
    } else if (isset($_POST['tracker_delete'])) {
        $delete_index = intval($_POST['delete_index']);
        $bills = (get_option('billtracker_data', []));
        usort($bills, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
    
        // Remove the selected bill from the array
        if (isset($bills[$delete_index])) {
            unset($bills[$delete_index]);
            $bills = array_values($bills); // Reindex the array
            update_option('billtracker_data', $bills);
    
            echo '<div class="updated"><p>Record deleted successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Failed to delete the record. Record not found.</p></div>';
        }
    }
}


// Create a shortcode to display JSON data
function display_bills() {

    $bills = get_option('billtracker_data', []);

    // Sort the bills by the 'sort' field
    usort($bills, function ($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    $output = '<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>

    <table class="widefat fixed bills" cellspacing="0" style="">';
    $output .= '<tbody>';

    // Loop through the bills and add rows to the table
    $category = "blank";
    foreach ($bills as $bill) {
        if ($category != $bill['category'] ) {
            $category = $bill['category'];
            $output .= '<tr class="category" style="">';
            $output .= '<td colspan="5" style="">' . esc_html(stripslashes($category)) . '</td>';
            $output .= '</tr>';
        }

        $name_link = !empty($bill['url']) ? '<a href="' . esc_url($bill['url']) . '" target="_blank">' . esc_html(stripslashes($bill['name'])) . '</a>' : esc_html(stripslashes($bill['name']));

        if(!array_key_exists('stance', $bill)) {
            $bill["stance"] = "";
        }
        $stance = "<span class='stance ".esc_html(stripslashes($bill['stance']))."'>".esc_html(stripslashes($bill['stance']))."</span>";

        $output .=
        '<tr class="name" style="">
            <td colspan="5" style="">' . $name_link . $stance . '</td>
        </tr>';
        $output .= get_steps_info("House", "house", ['Introduced', 'In Committee', 'On Floor', 'Passed'], $bill['house']);
        $output .= get_steps_info("Senate", "senate", ['Introduced', 'In Committee', 'On Floor', 'Passed'], $bill['senate']);
        $output .= get_steps_info("Passage", "passage", ['Passed Legislature', 'Governor\'s Desk', 'Governor Acted', 'Session Law'], $bill['passage']);
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '<div class="bills"><button class="download">Download Image</button></div>';
    $output .= '
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const downloadButton = document.querySelector(".bills button.download");

                downloadButton.addEventListener("click", function (event) {
                    htmlToImage.toJpeg(document.querySelector("table.bills tbody"), { quality: 0.95 })
                        .then(function (dataUrl) {
                            var link = document.createElement("a");
                            link.download = "gjl-bills.jpeg";
                            link.href = dataUrl;
                            link.click();
                        });
                });
            });
        </script>';

    $output .= get_bills_css();

    return $output;
}


function display_lawsuits() {
    $lawsuits = get_option('lawsuittracker_data', []);

    // Sort the lawsuits by the 'sort' field
    usort($lawsuits, function ($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    $output = '<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>

    <table class="widefat fixed lawsuits" cellspacing="0" style="">';
    $output .= '<tbody>';

    // Loop through the lawsuits and add rows to the table
    $category = "blank";
    foreach ($lawsuits as $lawsuit) {
        if ($category != $lawsuit['category']) {
            $category = $lawsuit['category'];
            $output .= '<tr class="category" style="">';
            $output .= '<td colspan="5" style="">' . esc_html(stripslashes($category)) . '</td>';
            $output .= '</tr>';
        }

        $name_link = !empty($lawsuit['url']) ? '<a href="' . esc_url($lawsuit['url']) . '" target="_blank">' . esc_html(stripslashes($lawsuit['name'])) . '</a>' : esc_html(stripslashes($lawsuit['name']));
        $output .=
        '<tr class="name" style="">
            <td colspan="5" style="">' . $name_link . '</td>
        </tr>';
        $output .= get_steps_info("", "stage", ['Filed', 'In Court', 'Ruling Issued', 'Appealed', 'Resolved'], $lawsuit['stage']);
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '<div class="lawsuits"><button class="download">Download Image</button></div>';
    $output .= '
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const downloadButton = document.querySelector(".lawsuits button.download");

                downloadButton.addEventListener("click", function (event) {
                    htmlToImage.toJpeg(document.querySelector("table.lawsuits tbody"), { quality: 0.95 })
                        .then(function (dataUrl) {
                            var link = document.createElement("a");
                            link.download = "gjl-lawsuits.jpeg";
                            link.href = dataUrl;
                            link.click();
                        });
                });
            });
        </script>';

    $output .= get_lawsuits_css();

    return $output;
}

function get_bills_css() {
    $output =
    '<style>

        table.bills {
            border-collapse: collapse;
            min-width: 450px;
            width: 100%;
        }

        table.bills tr.category {
            background-color:#444;
            color: white;
        }

        table.bills tr.name {
            background-color:#A21C1F;
            color:white;
        }

        table.bills tr.name a {
            color:white;
            text-decoration: underline;
        }

        table.bills tr.name .stance {
            float: right;
            border-style: solid;
            border-radius: 10px;
            padding-left: 10px;
            padding-right: 10px;
            text-transform: uppercase;
        }

        table.bills tr.name .pro {
            background: lightgreen;
            color: green;
            border-color: green;
        }

        table.bills tr.name .con {
            background: pink;
            color: red;
            border-color: red;
        }

        table.bills tr.house {
            background-color:#ffe3e3
        }

        table.bills tr.senate {
            background-color:#fefce0
        }

        table.bills tr.passage {
            background-color: #FFeebb
        }

        table.bills td {
            font-weight: bold;
            padding: 8px;
        }

        table.bills td .stepper-wrapper {
            display: flex;
            font-size:small;
            font-weight: normal;
        }

        table.bills .stepper-item {
            align-items: center;
            display: flex;
            flex: 1;
            flex-direction: column;
            position: relative;
        }

        table.bills .stepper-item::before {
            border-bottom: 2px solid #ccc;
            content: "";    
            position: absolute;
            left: -50%;
            top: 15px;
            width: 100%;
            z-index: 2;
        }

        table.bills .stepper-item::after {
            border-bottom: 2px solid #ccc;
            content: "";
            position: absolute;
            left: 50%;
            top: 15px;
            width: 100%;
            z-index: 2;
        }

        table.bills .stepper-item .step-counter {
            align-items: center;
            background: #ccc;
            border-radius: 50%;
            color: #666;
            display: flex;    
            height: 30px;
            justify-content: center;
            margin-bottom: 6px;
            position: relative;
            width: 30px;
            z-index: 5;
        }

        table.bills .stepper-item.active,
        table.bills .stepper-item.completed {
            font-weight: bold;
        }

        table.bills .stepper-item.active .step-counter {
            background-color: #FFBB43
        }

        table.bills .stepper-item.completed .step-counter {
            background-color: #A21C1F;
            color: #FFF8D7;
        }

        table.bills .stepper-item.completed::after {
            position: absolute;
            content: "";
            border-bottom: 2px solid #A21C1F;
            width: 100%;
            top: 15px;
            left: 50%;
            z-index: 3;
        }

        table.bills .stepper-item:first-child::before,
        table.bills .stepper-item:last-child::after {
            content: none;
        }
    </style>';
    return $output;
}

function get_lawsuits_css() {
    $output =
    '<style>

        table.lawsuits {
            border-collapse: collapse;
            min-width: 450px;
            width: 100%;
        }

        table.lawsuits tr.category {
            background-color:#444;
            color: white;
        }

        table.lawsuits tr.name {
            background-color:#A21C1F;
            color:white;
        }

        table.lawsuits tr.name a {
            color:white;
            text-decoration: underline;
        }

        table.lawsuits tr.stage {
            background-color: #FFeebb
        }

        table.lawsuits td {
            font-weight: bold;
            padding: 8px;
        }

        table.lawsuits td .stepper-wrapper {
            display: flex;
            font-size:small;
            font-weight: normal;
        }

        table.lawsuits .stepper-item {
            align-items: center;
            display: flex;
            flex: 1;
            flex-direction: column;
            position: relative;
        }

        table.lawsuits .stepper-item::before {
            border-bottom: 2px solid #ccc;
            content: "";    
            position: absolute;
            left: -50%;
            top: 15px;
            width: 100%;
            z-index: 2;
        }

        table.lawsuits .stepper-item::after {
            border-bottom: 2px solid #ccc;
            content: "";
            position: absolute;
            left: 50%;
            top: 15px;
            width: 100%;
            z-index: 2;
        }

        table.lawsuits .stepper-item .step-counter {
            align-items: center;
            background: #ccc;
            border-radius: 50%;
            color: #666;
            display: flex;    
            height: 30px;
            justify-content: center;
            margin-bottom: 6px;
            position: relative;
            width: 30px;
            z-index: 5;
        }

        table.lawsuits .stepper-item.active,
        table.lawsuits .stepper-item.completed {
            font-weight: bold;
        }

        table.lawsuits .stepper-item.active .step-counter {
            background-color: #FFBB43
        }

        table.lawsuits .stepper-item.completed .step-counter {
            background-color: #A21C1F;
            color: #FFF8D7;
        }

        table.lawsuits .stepper-item.completed::after {
            position: absolute;
            content: "";
            border-bottom: 2px solid #A21C1F;
            width: 100%;
            top: 15px;
            left: 50%;
            z-index: 3;
        }

        table.lawsuits .stepper-item:first-child::before,
        table.lawsuits .stepper-item:last-child::after {
            content: none;
        }
    </style>';
    return $output;
}

function get_steps_info($name, $class, $steps, $status) {
    $output = '<tr class="'. $class .'" style="">
            <td style="">'.$name.'</td>
            <td colspan="4" style=";">
                <div class="stepper-wrapper">';
    for($i = 0; $i < sizeof($steps); $i++) {
        $output .= '<div class="stepper-item '.($status == $i + 1 ? 'active' : ($status > $i + 1 ? 'completed' : '')).'">
                        <div class="step-counter">'.($i+1).'</div>
                        <div class="step-name">'.$steps[$i].'</div>
                    </div>';    
    }

    $output .='</div>
            </td>
        </tr>';
    return $output;
}

add_shortcode('display_bills', 'display_bills');

add_shortcode('display_lawsuits', 'display_lawsuits');


