<?php
function generate_unique_transaction_id($mysqli) {
    $length = 6;
    $max_attempts = 10;
    $attempt = 0;

    while ($attempt < $max_attempts) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }

        // Check if the ID already exists in the database
        $stmt = $mysqli->prepare("SELECT id FROM orders WHERE transaction_id = ?");
        $stmt->bind_param("s", $random_string);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $stmt->close();
            return $random_string;
        }

        $stmt->close();
        $attempt++;
    }

    // Fallback to a longer, more random ID if we can't find a unique one
    return uniqid('TXN-', true);
}
?>