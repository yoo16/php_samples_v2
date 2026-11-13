<?php
// ============================================================
// ProductRepository.php - データアクセス層
//
// data/products.json を読み込み、PHPの配列として返す役割だけを持つ。
// 「どこからデータを取ってくるか」をこのクラスに閉じ込めることで、
// 将来 DB に差し替える場合もここだけ直せば済むようにする。
// ============================================================

class ProductRepository
{
    private string $jsonPath;

    public function __construct(string $jsonPath)
    {
        $this->jsonPath = $jsonPath;
    }

    /**
     * 全商品を取得する
     * @return array<int, array>
     */
    public function all(): array
    {
        $json = file_get_contents($this->jsonPath);
        $data = json_decode($json, true);      // JSON文字列 → PHP連想配列
        return $data['products'] ?? [];
    }

    /**
     * IDで1件取得する。見つからなければ null。
     */
    public function find(int $id): ?array
    {
        foreach ($this->all() as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }
        return null;
    }

    /**
     * データに含まれるカテゴリ一覧（重複なし）を取得する
     * @return array<int, string>
     */
    public function categories(): array
    {
        return array_values(array_unique(array_column($this->all(), 'category')));
    }
}
