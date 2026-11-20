<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    jsonError('Method not allowed.', 405);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        'SELECT id, slug, name, tagline, price_monthly, price_yearly, storage_label, storage_limit_mb, features_json, is_featured
         FROM plans
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC'
    );
    $rows = $stmt->fetchAll();

    $plans = array_map(static function (array $row): array {
        $decodedFeatures = json_decode((string) ($row['features_json'] ?? '[]'), true);
        return [
            'id'               => (int) $row['id'],
            'slug'             => (string) $row['slug'],
            'name'             => (string) $row['name'],
            'tagline'          => (string) ($row['tagline'] ?? ''),
            'price_monthly'    => (float) $row['price_monthly'],
            'price_yearly'     => (float) $row['price_yearly'],
            'storage_label'    => (string) $row['storage_label'],
            'storage_limit_mb' => (int) $row['storage_limit_mb'],
            'is_featured'      => ((int) $row['is_featured']) === 1,
            'features'         => is_array($decodedFeatures) ? $decodedFeatures : [],
        ];
    }, $rows);

    jsonSuccess('Plans fetched successfully.', ['plans' => $plans]);
} catch (Throwable $e) {
    error_log('[ScanPro API Plans Error] ' . $e->getMessage());
    jsonError('Unable to load subscription plans from the database.', 500);
}