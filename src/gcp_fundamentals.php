<?php
header('Content-Type: application/json');

$host = '34.58.189.84'; // Your Cloud SQL Public IP
$port = '5432';          // PostgreSQL default port
$dbname = 'postgres'; // Replace with your actual database name
$user = 'postgres';    // Replace with your database username
$password = 'D!rtydangles1869'; // Replace with your database password

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("WITH PivotedEdgar AS (
    SELECT 
        ticker, 
        entityname,
        end_date,
        MAX(val) FILTER (WHERE tag = 'NetCashProvidedByUsedInOperatingActivities') AS cash_flow,
        MAX(val) FILTER (WHERE tag = 'WeightedAverageNumberOfDilutedSharesOutstanding') AS shares,
        MAX(val) FILTER (WHERE tag = 'EarningsPerShareDiluted') AS eps
    FROM edgar_data
    WHERE tag IN ('NetCashProvidedByUsedInOperatingActivities', 'WeightedAverageNumberOfDilutedSharesOutstanding', 'EarningsPerShareDiluted')
    GROUP BY 1, 2, 3
),
cte AS (
    SELECT 
        e.ticker, e.entityname, e.end_date,
        (e.end_date + INTERVAL '3 months')::date AS target_price_date,
        e.cash_flow, e.eps,
        (e.cash_flow / NULLIF(e.shares, 0)) AS ocf_per_share,
        p.close AS price_3m_later
    FROM PivotedEdgar AS e
    LEFT JOIN LATERAL (
        SELECT close
        FROM prices_stocks
        WHERE ticker = e.ticker
          AND date >= (e.end_date + INTERVAL '3 months')
          AND date < (e.end_date + INTERVAL '4 months')
        ORDER BY date DESC
        LIMIT 1
    ) p ON TRUE
    -- Move the heavy yield filter here to prune rows BEFORE calculating returns
    WHERE p.close IS NOT NULL 
      AND ((e.cash_flow / NULLIF(e.shares, 0)) / NULLIF(p.close, 0)) * 100 BETWEEN -40 AND 50
)
SELECT 
    c.*,
    (c.ocf_per_share / NULLIF(c.price_3m_later, 0)) * 100 AS ocf_yield_pct,
    r.forward_12m_return_pct,
    t.sector
FROM cte c
LEFT JOIN LATERAL (
    SELECT (EXP(SUM(LN(NULLIF(1 + pct_change, 0)))) - 1) * 100 AS forward_12m_return_pct
    FROM us_monthly_returns
    WHERE ticker = c.ticker
      AND date > c.target_price_date
      AND date <= (c.target_price_date + INTERVAL '12 months')
      AND (1 + pct_change) > 0
) r ON TRUE
LEFT JOIN tickers t ON c.ticker = t.ticker
ORDER BY c.end_date ASC, c.ticker ASC;
");

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

