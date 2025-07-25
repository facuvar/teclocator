<?php
/**
 * Database setup script
 * 
 * This script creates the database and loads the schema
 */

// Connect to MySQL without selecting a database
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to MySQL server successfully.<br>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS elevator_repair_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'elevator_repair_system' created or already exists.<br>";
    
    // Select the database
    $pdo->exec("USE elevator_repair_system");
    echo "Database selected.<br>";
    
    // Read schema file
    $schemaFile = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split schema into individual queries
    $queries = explode(';', $schemaFile);
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            // Skip the CREATE DATABASE and USE statements as we've already done those
            if (strpos($query, 'CREATE DATABASE') === false && strpos($query, 'USE elevator_repair_system') === false) {
                $pdo->exec($query);
                echo "Executed query: " . substr($query, 0, 50) . "...<br>";
            }
        }
    }
    
    echo "<br>Database setup completed successfully!<br>";
    
    // Create a sample admin user
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $stmt->execute(['Administrator', 'admin@example.com', $hashedPassword, 'admin']);
    echo "Sample admin user created (email: admin@example.com, password: admin123)<br>";
    
    // Create a sample technician user
    $hashedPassword = password_hash('tech123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, zone) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $stmt->execute(['Technician', 'tech@example.com', $hashedPassword, 'technician', 'Norte']);
    echo "Sample technician user created (email: tech@example.com, password: tech123)<br>";
    
    // Create a sample client
    $stmt = $pdo->prepare("INSERT INTO clients (name, business_name, latitude, longitude, address) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $stmt->execute(['Juan Pérez', 'Edificio Central', 40.416775, -3.703790, 'Calle Gran Vía 1, Madrid']);
    $clientId = $pdo->lastInsertId();
    echo "Sample client created<br>";
    
    // Create a sample ticket
    $technicianId = $pdo->query("SELECT id FROM users WHERE role = 'technician' LIMIT 1")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO tickets (client_id, technician_id, description, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$clientId, $technicianId, 'El ascensor hace ruido extraño al subir', 'pending']);
    echo "Sample ticket created<br>";
    
    echo "<br>Sample data created successfully!<br>";
    echo "<br><a href='../index.php'>Go to application</a>";
    
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
