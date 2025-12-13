<?php
// Test URL paths
echo "<!DOCTYPE html><html><head><title>Path Test</title></head><body>";
echo "<h1>Path Testing</h1>";
echo "<p>Current file: " . __FILE__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>PHP self: " . $_SERVER['PHP_SELF'] . "</p>";

echo "<h2>Link Tests</h2>";
echo "<ul>";
echo "<li><a href='reports/revenue.php'>reports/revenue.php (relative)</a></li>";
echo "<li><a href='/garage_system/public/reports/revenue.php'>/garage_system/public/reports/revenue.php (absolute)</a></li>";
echo "<li><a href='admin/manage_staff.php'>admin/manage_staff.php (relative)</a></li>";
echo "<li><a href='/garage_system/public/admin/manage_staff.php'>/garage_system/public/admin/manage_staff.php (absolute)</a></li>";
echo "<li><a href='../customers/list.php'>../customers/list.php (up one)</a></li>";
echo "<li><a href='/garage_system/customers/list.php'>/garage_system/customers/list.php (absolute)</a></li>";
echo "</ul>";
echo "</body></html>";
?>
