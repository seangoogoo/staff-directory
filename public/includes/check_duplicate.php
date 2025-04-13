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

    $result = ['duplicate' => false, 'message' => ''];

    if ($type === 'name' && !empty($first_name) && !empty($last_name)) {
        $sql = "SELECT id FROM staff_members WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $first_name, $last_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $result['duplicate'] = true;
            $result['message'] = "A staff member with this name already exists.";
        }
        $stmt->close();
    } elseif ($type === 'email' && !empty($value)) {
        $sql = "SELECT id FROM staff_members WHERE LOWER(email) = LOWER(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $result['duplicate'] = true;
            $result['message'] = "This email address is already in use.";
        }
        $stmt->close();
    }

    echo json_encode($result);
    exit;
}

// Return error if not a POST request
echo json_encode(['error' => 'Invalid request method']);