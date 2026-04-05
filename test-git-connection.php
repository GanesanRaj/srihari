<?php
// Test if Git is available on cPanel server
echo "<h2>Git Availability Test</h2>";

// Test if git command exists
$output = shell_exec('which git 2>&1');
echo "<h3>Git Location:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Test git version
$output = shell_exec('git --version 2>&1');
echo "<h3>Git Version:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Test current directory
echo "<h3>Current Directory:</h3>";
echo "<pre>" . htmlspecialchars(getcwd()) . "</pre>";

// List files to see if .git exists
echo "<h3>Directory Contents:</h3>";
$files = scandir('.');
foreach ($files as $file) {
    if (strpos($file, 'git') !== false) {
        echo "<strong>" . htmlspecialchars($file) . "</strong><br>";
    }
}
?>
