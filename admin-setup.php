<?php
/**
 * Admin Setup Script - Run this once to create admin user
 */

require_once 'config/config.php';

echo "<h2>🔐 Admin Setup Script</h2>";
echo "<hr>";

try {
    $conn = getDBConnection();
    
    // Check if admin table exists
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    if ($result->num_rows == 0) {
        echo "<p>❌ 'admins' table not found. Please create the database schema first.</p>";
        echo "<p><strong>Run this SQL to create admin table:</strong></p>";
        echo "<textarea rows='10' cols='80' style='width:100%'>";
        echo "CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";
        echo "</textarea>";
    } else {
        echo "<p>✅ 'admins' table exists</p>";
        
        // Convert constants to variables (required for bind_param)
        $admin_username = ADMIN_USERNAME;
        
        // Check if admin already exists
        $checkStmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $checkStmt->bind_param("s", $admin_username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p>⚠️ Admin user already exists!</p>";
            $admin = $result->fetch_assoc();
            echo "<p><strong>Existing Admin Details:</strong></p>";
            echo "Username: <strong>" . $admin['username'] . "</strong><br>";
            echo "Email: " . $admin['email'] . "<br>";
            echo "Created: " . $admin['created_at'] . "<br>";
        } else {
            // Create admin user - convert constants to variables
            $username = ADMIN_USERNAME;
            $password_hash = ADMIN_PASSWORD_HASH;
            $email = "admin@ujiara.com";
            
            $insertStmt = $conn->prepare("INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)");
            $insertStmt->bind_param("sss", $username, $password_hash, $email);
            
            if ($insertStmt->execute()) {
                echo "<p>✅ <strong>Admin user created successfully!</strong></p>";
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
                echo "<h3>🎯 Your Admin Login Credentials:</h3>";
                echo "<strong>Username:</strong> " . ADMIN_USERNAME . "<br>";
                echo "<strong>Password:</strong> password<br>";
                echo "<strong>Login URL:</strong> <a href='admin-login.php'>admin-login.php</a>";
                echo "</div>";
            } else {
                echo "<p>❌ Failed to create admin user: " . $conn->error . "</p>";
            }
            
            $insertStmt->close();
        }
        
        $checkStmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📝 Next Steps:</h3>";
echo "<ol>";
echo "<li>Make sure your database 'seva_connect' exists</li>";
echo "<li>Run the MySQL schema to create all tables</li>";
echo "<li>Use the credentials shown above to login</li>";
echo "<li>Go to <a href='admin-login.php'>admin-login.php</a> to test login</li>";
echo "</ol>";

echo "<h3>🔒 Default Login Info:</h3>";
echo "<p><strong>Username:</strong> " . ADMIN_USERNAME . "</p>";
echo "<p><strong>Password:</strong> password</p>";
echo "<p><small>Note: Change the password after first login in production!</small></p>";

echo "<hr>";
echo "<h3>🔧 Debug Info:</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Admin Username Constant: " . ADMIN_USERNAME . "</p>";
echo "<p>Password Hash Length: " . strlen(ADMIN_PASSWORD_HASH) . "</p>";
?>