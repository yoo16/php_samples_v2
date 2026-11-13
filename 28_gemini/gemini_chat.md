## GeminiAPIの概要
### URL
Gemini API の URL はバージョン、モデル、メソッドによって異なります。
|セクション|内容|備考|
|-|-|-|
| `https://generativelanguage.googleapis.com` | Google Generative Language API のベースURL|GoogleのAPIサーバー|
| `/v1beta`|APIのバージョン|`v1beta` = ベータ版|
| `/models/gemini-2.0-flash`|使用するモデルの指定|この例では `gemini-2.0-flash`|
| `:generateContent`|呼び出すメソッド|コンテンツ生成
| `?key=YOUR_API_KEY`|認証用のAPIキー|クエリパラメータ|


### HTTPヘッダー
HTTPヘッダーは以下のオプションを指定します。

|オプション名|型|説明|使用例|
|-|-|-|-|
|`method`|string|HTTP リクエストのメソッドを指定。デフォルトは `GET`| `'method' : 'POST'`|
|`header`|string|リクエスト時に送信する追加のヘッダーを指定|`'header' : "Content-Type: application/json`|
| `ignore_errors` |boolean |HTTPステータエラーで、レスポンスを取得するか| `'ignore_errors' : true`| 


### リクエストボディ
リクエストボディは、`contents`, `parts`, `text` などのデータ構造で指定します。

|階層|キー|型|説明|
|-|-|-|-|
|root|`contents`|array| 会話内容のリスト（複数発言にも対応）|
|contents\[]|`parts`|array| 発言の構成要素（テキストや画像など）|
|parts\[]|`text`|string| ユーザーが入力するテキスト内容|

#### JSONの構造
Gemini API のリクエストボディサンプル（JSON）は以下の通りです。

```json
{
  "contents": [
    {
      "parts": [
        {
          "text": "Explain how AI works in a few words"
        }
      ]
    }
  ]
}
```

### REST
`Python`や`JavaScript`以外のプログラムで Gemini API を利用するときは、`REST`通信で処理します。ライブラリを利用しないため処理が若干複雑になります。

#### cURL
*cURL（カール）* は、サーバー同士やアプリとAPIがHTTP通信をするためのツール・機能です。[公式ドキュメント](https://ai.google.dev/gemini-api/docs?hl=ja#rest) では以下の `cURL`サンプルが記載されています。

```curl
curl　"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=YOUR_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [
      {
        "parts": [
          {
            "text": "Explain how AI works in a few words"
          }
        ]
      }
    ]
  }'
```

## チャットボット
### ファイル構成
```treeview
28_gemini/
├── chat.php
├── env.php
└── services
    └── GeminiService.php
```

### GeminiServiceクラス
#### APIキー設定
`env.php` に APIキーとモデル名を設定します。

###### env.php
```php
<?php
const GEMINI_API_KEY = '自分のAPIキー';
const GEMINI_MODEL   = 'gemini-2.5-flash';
```

- このキーは公開しないように管理してください

#### HTTPヘッダー
`GeminiService`クラスを作成します。ベースURLやHTTPヘッダーオプションも指定しておきます。

###### services/GeminiService.php
```php
<?php

class GeminiService
{
    private string $baseURL = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private array $options = [
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'ignore_errors' => true,
            'content'       => ''
        ]
    ];
}
```

### チャット処理
#### プロンプトのコンテキスト
`chat()` を定義し、プロンプトのコンテキストを作成します。

###### services/GeminiService.php
```php
    function chat(string $prompt): ?string
    {
        if (empty($prompt)) return "プロンプトが空です";

        $url = sprintf(
            '%s%s:generateContent?key=%s',
            $this->baseURL,
            GEMINI_MODEL,
            GEMINI_API_KEY
        );

        // リクエストボディを作成
        $requestData = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        // リクエストヘッダーを設定
        $this->options['http']['content'] = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        // ストリームコンテキストを作成
        $context = stream_context_create($this->options);
    }
```

#### リクエスト＆レスポンス
`file_get_contents()` で Gemini API にリクエスト＆レスポンスします。
レスポンスデータを `JSON`デコードし、テキストデータを返します。

###### services/GeminiService.php
```php
    function chat(string $prompt): ?string
    {
        ...

        // GeminiAPIにリクエストを送信し、レスポンスを取得
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        // レスポンスをデコード
        $json = json_decode($response, true);
        // テキストデータを返す
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
```

### メインプログラム
#### プロンプト処理実行
`chat.php` で、`GeminiService`クラスを読み込んでプロンプトを実行します。

###### chat.php
```php
<?php
require_once 'env.php';
require_once 'services/GeminiService.php';

$prompt = $_POST['prompt'] ?? '';
$result = '';

// POSTリクエストが送信された場合
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // GeminiServiceクラスのインスタンスを作成
    $gemini = new GeminiService();
    // GeminiAPIにリクエストを送信
    $result = $gemini->chat($prompt);
}
?>
```

#### フォーム
ブラウザからプロンプト送信するHTMLフォームを作成します。
###### chat.php
```php
<?php
require_once 'env.php';
require_once 'services/GeminiService.php';

$prompt = $_POST['prompt'] ?? '';
$result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gemini = new GeminiService();
    $result = $gemini->chat($prompt);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Gemini Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-bold mb-4">Gemini API Chat</h1>
        <form method="POST" class="space-y-4">
            <textarea name="prompt" rows="4" class="w-full p-2 border rounded" placeholder="質問を入力してください..."><?= htmlspecialchars($prompt) ?></textarea>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">送信</button>
        </form>

        <?php if ($result): ?>
            <div class="mt-6 bg-gray-50 p-4 rounded border">
                <h2 class="text-lg font-semibold">Geminiの回答</h2>
                <p class="mt-2 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($result)) ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
```

#### 動作確認

<img src="/storage/teaching_material/gemini_php_rest_chat.png" class="w-[600px]" alt="">
