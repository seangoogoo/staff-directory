<?php
require_once __DIR__ . '/bootstrap.php';
require_once PRIVATE_PATH . '/config/database.php';

header('Content-Type: application/json');

// Handle only AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $value = isset($_POST['value']) ? $_POST['value'] : '';
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
    // Staff member being edited, excluded so it is not reported as its own duplicate.
    // id is AUTO_INCREMENT from 1, so 0 excludes nothing and "AND id != ?" can stay
    // unconditional: absent or forged values degrade to a plain duplicate check.
    $exclude_id = (isset($_POST['exclude_id']) && is_numeric($_POST['exclude_id'])) ? (int)$_POST['exclude_id'] : 0;

    $result = ['duplicate' => false, 'message' => ''];

    if ($type === 'name' && !empty($first_name) && !empty($last_name)) {
        $sql = "SELECT id FROM " . TABLE_STAFF_MEMBERS . " WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $first_name, $last_name, $exclude_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $result['duplicate'] = true;
            $result['message'] = __('duplicate_name');
        }
        $stmt->close();
    } elseif ($type === 'email' && !empty($value)) {
        $sql = "SELECT id FROM " . TABLE_STAFF_MEMBERS . " WHERE LOWER(email) = LOWER(?) AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $value, $exclude_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $result['duplicate'] = true;
            $result['message'] = __('duplicate_email');
        }
        $stmt->close();
    }

    echo json_encode($result);
    exit;
}

// Return error if not a POST request
echo json_encode(['error' => 'Invalid request method']);