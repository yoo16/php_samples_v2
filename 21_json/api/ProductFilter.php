<?php
// ============================================================
// ProductFilter.php - 絞り込み・並び替え層
//
// 商品配列に対して「カテゴリ・キーワード・在庫」で絞り込み、
// 指定された順序で並び替える役割だけを持つ。
// データの取得方法（JSON/DB）とは無関係なので独立している。
// ============================================================

class ProductFilter
{
    /**
     * 条件で絞り込む
     *
     * @param array $products 商品配列
     * @param array $criteria ['category'=>?, 'keyword'=>?, 'in_stock'=>bool]
     * @return array
     */
    public function filter(array $products, array $criteria): array
    {
        $category = $criteria['category'] ?? '';
        $keyword  = $criteria['keyword']  ?? '';
        $inStock  = $criteria['in_stock'] ?? false;

        $filtered = array_filter($products, function ($p) use ($category, $keyword, $inStock) {
            if ($category !== '' && $p['category'] !== $category) {
                return false;
            }
            if ($keyword !== '' && !str_contains($p['name'], $keyword)) {
                return false;
            }
            if ($inStock && $p['stock'] <= 0) {
                return false;
            }
            return true;
        });

        return array_values($filtered);   // キーを 0 から振り直す
    }

    /**
     * 並び替える
     *
     * @param array  $products 商品配列
     * @param string $sort     price_asc | price_desc | rating_desc
     * @return array
     */
    public function sort(array $products, string $sort): array
    {
        switch ($sort) {
            case 'price_asc':
                usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
                break;
            case 'price_desc':
                usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
                break;
            case 'rating_desc':
                usort($products, fn($a, $b) => $b['rating'] <=> $a['rating']);
                break;
        }
        return $products;
    }
}
