<?php

/**
 * Fetches and displays a user's portfolio with real-time valuations safely.
 *
 * @param mysqli $db
 *   The active database connection interface.
 * @param int|string $user_id
 *   The targeted user record identifier.
 */
function get_portfolio(mysqli $db, $user_id): void {
    // 1. Sanitize the primary input argument context safely.
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        echo "<p>Invalid user identifier specified.</p>";
        return;
    }

    // 2. Performance Fix: Consolidate the data execution layer into 1 single query.
    // We use a window function subquery to grab the latest historical price 
    // without trapped N+1 looping structures.
    $sql = "
        SELECT 
            p.symbol, 
            p.qty, 
            hp.close AS last_price
        FROM portfolios p
        LEFT JOIN (
            SELECT 
                symbol, 
                close,
                ROW_NUMBER() OVER (PARTITION BY symbol ORDER BY trade_date DESC) as rn
            FROM historical_prices
        ) hp ON p.symbol = hp.symbol AND hp.rn = 1
        WHERE p.user_id = ?
    ";

    // 3. Security Fix: Defend against SQL Injection using modern prepared statements.
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        echo "<p>An internal system tracking anomaly occurred.</p>";
        return;
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    // 4. Security Fix: Leverage htmlspecialchars to shield outputs against XSS hooks.
    $safe_user_id = htmlspecialchars((string) $user_id, ENT_QUOTES, 'UTF-8');
    echo "<h2>Portfolio for user " . $safe_user_id . "</h2>";

    if (empty($rows)) {
        echo "<p>No active portfolio records discovered.</p>";
        return;
    }

    foreach ($rows as $r) {
        $safe_symbol = htmlspecialchars($r['symbol'], ENT_QUOTES, 'UTF-8');
        
        // Handle instances where price records don't exist yet for the asset ticker
        $last_price = $r['last_price'] !== null ? (float) $r['last_price'] : 0.0;
        $total_value = (int) $r['qty'] * $last_price;

        echo "<div>" . $safe_symbol . ": " . number_format($total_value, 2) . "</div>";
    }
}