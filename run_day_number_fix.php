<?php
require_once 'config/database.php';

$pdo = Database::getConnection();

echo "<h2>Fixing Day Numbers for Susu Collections (Full 31-Day Cycle)</h2>";

try {
    // Read and execute the SQL file
    $sql = file_get_contents('fix_all_day_numbers.sql');
    $statements = explode(';', $sql);
    
    $statementCount = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            $statementCount++;
            try {
                if (str_starts_with(strtoupper($statement), 'SELECT')) {
                    // For SELECT statements, fetch and display results
                    $stmt = $pdo->prepare($statement);
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                    
                    echo "<h3>Query Results (Statement $statementCount):</h3>";
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    if (!empty($results)) {
                        // Display headers
                        echo "<tr style='background-color: #f0f0f0;'>";
                        foreach (array_keys($results[0]) as $header) {
                            echo "<th style='padding: 8px; border: 1px solid #ccc;'>" . htmlspecialchars($header) . "</th>";
                        }
                        echo "</tr>";
                        
                        // Display data
                        foreach ($results as $row) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td style='padding: 8px; border: 1px solid #ccc;'>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' style='padding: 8px; border: 1px solid #ccc; text-align: center;'>No results found</td></tr>";
                    }
                    echo "</table>";
                    echo "<br>";
                } else {
                    // For UPDATE statements, just execute
                    $affectedRows = $pdo->exec($statement);
                    echo "<p>✅ Executed Statement $statementCount: " . substr($statement, 0, 80) . "...</p>";
                    echo "<p style='margin-left: 20px; color: #666;'>Rows affected: $affectedRows</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error in Statement $statementCount: " . $e->getMessage() . "</p>";
                echo "<p style='margin-left: 20px; color: #666;'>Statement: " . substr($statement, 0, 100) . "...</p>";
            }
        }
    }
    
    echo "<h3>🎉 Day Number Fix Completed!</h3>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>All Susu collections now have sequential day numbers (1, 2, 3, ...) based on collection date</li>";
    echo "<li>Each client's cycle starts from day 1</li>";
    echo "<li>The Susu Collection Tracker will now show the correct days as collected</li>";
    echo "<li>Works for any number of collections (1-31 days)</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Refresh the Susu Collection Tracker page</li>";
    echo "<li>Verify that days 1-10 (or whatever the actual collection count is) are marked as collected</li>";
    echo "<li>The visual grid should now match the actual transaction dates</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error reading SQL file: " . $e->getMessage() . "</p>";
}
?>
