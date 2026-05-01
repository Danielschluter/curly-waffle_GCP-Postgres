<?php
header('Content-Type: application/json');

require 'db.php';
$pdo = get_db();

try {
    $rawTags = $_GET['tags'] ?? '';
    $tags = [];
    $tagFilterSql = '';
    $pivotColumns = [];
    $defaultTags = [
        'NetCashProvidedByUsedInOperatingActivities',
        'WeightedAverageNumberOfDilutedSharesOutstanding',
        'EarningsPerShareDiluted',
    ];

    if (trim($rawTags) !== '') {
        $tagCandidates = preg_split('/\s*,\s*/', $rawTags, -1, PREG_SPLIT_NO_EMPTY);
        $tagCandidates = array_map('trim', $tagCandidates);
        $tagCandidates = array_filter(array_unique($tagCandidates));
        $tags = array_filter($tagCandidates, function ($tag) {
            return preg_match('/^[A-Za-z0-9._-]+$/', $tag);
        });

        if (count($tags) === 0) {
            echo json_encode([]);
            exit;
        }
    } else {
        $tags = $defaultTags;
    }

    $quotedTags = array_map([$pdo, 'quote'], $tags);
    $tagFilterSql = "WHERE tag IN (" . implode(',', $quotedTags) . ")";

    $aliases = [];
    foreach ($tags as $index => $tag) {
        $alias = preg_replace('/[^A-Za-z0-9_]/', '_', $tag);
        $alias = preg_replace('/_{2,}/', '_', $alias);
        $alias = trim($alias, '_');
        if ($alias === '' || preg_match('/^[0-9]/', $alias)) {
            $alias = 'tag_' . ($index + 1);
        }

        $baseAlias = $alias;
        $suffix = 1;
        while (in_array($alias, $aliases, true)) {
            $alias = $baseAlias . '_' . $suffix;
            $suffix++;
        }
        $aliases[] = $alias;

        $pivotColumns[] = "MAX(val) FILTER (WHERE tag = " . $pdo->quote($tag) . ") AS \"$alias\"";
    }

    $pivotSelect = implode(",\n                ", $pivotColumns);

    $sql = "SELECT 
                ticker, 
                entityname,
                end_date,
                $pivotSelect
            FROM edgar_data
            $tagFilterSql
            GROUP BY 1, 2, 3
            ORDER BY end_date DESC";

    $stmt = $pdo->query($sql);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // return the query if results are empty to help with debugging
    if (empty($results)) {
        echo json_encode(['query' => $sql]);
        exit;
    }

    echo json_encode($results, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
