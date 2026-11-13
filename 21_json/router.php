<?php
// ============================================================
// router.php - PHPビルトインサーバー用ルーター
//
// 起動: php -S localhost:8000 router.php
//
// /api/... へのリクエストをすべて api/index.php に集約し、
// それ以外（index.php など）は通常のファイルとして配信する。
// これにより api/products のようなクリーンなURLが使える。
// ============================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// /api または /api/xxx へのアクセスは API のフロントコントローラへ
if (preg_match('#^/api(/.*)?$#', $uri, $m)) {
    $_SERVER['ROUTE'] = $m[1] ?? '/';   // 例: "/products/3"
    require __DIR__ . '/api/index.php';
    return true;
}

// それ以外はリクエストされたファイルをそのまま配信
return false;
