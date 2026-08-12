<?php
$file = 'api/shifts.php';
$content = file_get_contents($file);

// Replace date_clause logic
$old_logic = "    // Build date filter
    \$date_clause = '';
    if (\$cycle_start) {
        \$safe = \$mysqli->real_escape_string(\$cycle_start);
        if (\$include_older_open) {
            // Tickets created in this cycle OR older tickets still open
            \$date_clause = \"AND (c.CreatedDate >= '\$safe' OR LOWER(c.Status) != 'closed')\";
        } else {
            \$date_clause = \"AND c.CreatedDate >= '\$safe'\";
        }
    } else {
        // Inactive group: only open tickets
        \$date_clause = \"AND LOWER(c.Status) != 'closed'\";
    }";

$new_logic = "    // Build date filter
    \$date_clause = '';
    // For active group, fetch recent history without restricting to cycle_start
    if (\$cycle_start) {
        // We will just fetch the latest tickets (history). We don't restrict by date.
        // We will apply a LIMIT below.
        \$date_clause = \"\";
    } else {
        // Inactive group: only open tickets
        \$date_clause = \"AND LOWER(c.Status) != 'closed'\";
    }";

$content = str_replace($old_logic, $new_logic, $content);

// Add LIMIT to query
$old_query = "              \$date_clause
              ORDER BY c.CreatedDate DESC\";";

$new_query = "              \$date_clause
              ORDER BY c.CreatedDate DESC
              LIMIT \" . (\$cycle_start ? 150 : 50) . \";\";";

$content = str_replace($old_query, $new_query, $content);

file_put_contents($file, $content);
echo "Patched api/shifts.php\n";
