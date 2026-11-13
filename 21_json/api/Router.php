<?php
// ============================================================
// Router.php - ルーティング層
//
// リクエストされたパス（例: /products/3）を分解し、
// 「どのリソースか」「IDが付いているか」を判定する役割だけを持つ。
// 実際の処理内容（データ取得など）はここには書かない。
// ============================================================

class Router
{
    /** @var string リソース名（products / categories など） */
    public string $resource;

    /** @var int|null リソースID（/products/3 の 3）。無ければ null */
    public ?int $id;

    public function __construct(string $path)
    {
        // "/products/3" → ["products", "3"]
        $segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));

        $this->resource = $segments[0] ?? 'products';   // 省略時は products
        $this->id       = isset($segments[1]) ? (int)$segments[1] : null;
    }
}
