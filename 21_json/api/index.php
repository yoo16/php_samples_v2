<?php
// ============================================================
// api/index.php - エントリーポイント（フロントコントローラ）
//
// すべての api/* リクエストはここに集約され、
// Router でリソースを判定して各処理へ振り分ける。
//
// ルーティング:
//   GET api/products          商品一覧（絞り込みクエリ対応）
//   GET api/products/{id}     商品を1件取得
//   GET api/categories        カテゴリ一覧
// ============================================================

// ---------- ① レスポンスヘッダー設定 ----------
header('Content-Type: application/json; charset=utf-8');

// ---------- ② 各役割のクラスを読み込み ----------
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/ProductRepository.php';
require_once __DIR__ . '/ProductFilter.php';

// ---------- ③ ルーティング ----------
// router.php が渡すパス（例: /products/3）を解析する
$router = new Router($_SERVER['ROUTE'] ?? '/');

$repository = new ProductRepository(__DIR__ . '/data/products.json');
$filter     = new ProductFilter();

// ---------- ④ リソースごとに処理を振り分け ----------
switch ($router->resource) {

    // --- カテゴリ一覧 ---
    case 'categories':
        respond(['categories' => $repository->categories()]);
        break;

    // --- 商品 ---
    case 'products':
        // /products/{id} … 1件取得
        if ($router->id !== null) {
            $product = $repository->find($router->id);
            if ($product === null) {
                respond(['error' => '商品が見つかりません'], 404);
            }
            respond(['product' => $product]);
        }

        // /products … 一覧（絞り込み・並び替え）
        $criteria = [
            'category' => trim($_GET['category'] ?? ''),
            'keyword'  => trim($_GET['keyword']  ?? ''),
            'in_stock' => ($_GET['in_stock'] ?? '') === '1',
        ];
        $products = $filter->filter($repository->all(), $criteria);
        $products = $filter->sort($products, $_GET['sort'] ?? '');

        respond([
            'count'    => count($products),
            'products' => $products,
        ]);
        break;

    // --- 未定義のリソース ---
    default:
        respond(['error' => 'エンドポイントが存在しません'], 404);
}

// ---------- ⑤ JSON 返却用ヘルパー ----------
function respond(array $body, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
