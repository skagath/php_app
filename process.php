<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "database-1.czkq8giion5k.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "Zaq123456";
$dbname = "mydb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the 'users' table if it doesn't exist
$table_check_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE
)";

if ($conn->query($table_check_sql) === TRUE) {
    // Table is created or already exists
    // Now handle the POST request if any
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);

        $sql = "INSERT INTO users (name, email) VALUES ('$name', '$email')";

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
} else {
    echo "Error creating table: " . $conn->error;
}

// Close connection
$conn->close();
?>
