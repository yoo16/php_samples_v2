# 21_json — 商品カタログ API（JSONモックデータ）

データベースを使わず、**JSONファイルをデータソース**にした簡単な API 教材です。
PHP で JSON を読み込み・絞り込みして返し、フロントエンドの `fetch()` で表示します。

## ファイル構成

| ファイル | 役割 |
| --- | --- |
| `index.php` | `fetch()` で API を呼び、商品カードを表示するフロント |
| `router.php` | ビルトインサーバー用ルーター。`/api/*` を `api/index.php` に集約 |
| `api/index.php` | エントリーポイント（フロントコントローラ）。リソースごとに振り分け |
| `api/Router.php` | ルーティング層。パスからリソース名・IDを判定 |
| `api/ProductRepository.php` | データアクセス層。JSONファイルの読み込みを担当 |
| `api/ProductFilter.php` | 絞り込み・並び替えロジックを担当 |
| `api/data/products.json` | 商品のモックデータ（DBの代わり） |

役割ごとにファイルを分けることで、たとえばデータソースを DB に変えたい場合は
`ProductRepository.php` だけを直せば済む、という設計を学べます。

## 学べるポイント

- `file_get_contents()` + `json_decode()` で JSON ファイルを読み込む
- `array_filter` / `usort` で絞り込み・並び替え
- `json_encode()` で連想配列を JSON として返す
- フロントの `fetch()` と `URLSearchParams` でクエリを組み立てる

## API の使い方（リソースベースのルーティング）

```
GET api/products                     すべての商品
GET api/products/3                    ID=3 の商品を1件取得
GET api/products?category=オーディオ    カテゴリで絞り込み
GET api/products?keyword=マウス         商品名で部分一致検索
GET api/products?in_stock=1           在庫ありのみ
GET api/products?sort=price_asc       価格が安い順に並び替え
GET api/categories                   カテゴリ一覧
```

## 実行方法

クリーンなURL（`api/products` など）を使うため、ルーターを指定して起動します。

```
php -S localhost:8000 router.php
```

ブラウザで <http://localhost:8000/> を開きます。
