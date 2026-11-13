## What's Photo
画像をアップロードして、Gemini API に内容を解析させる機能です。

### ファイル構成
```treeview
28_gemini/
├── whats_photo.php
├── env.php
├── uploads/
└── services
    └── GeminiService.php
```

### リクエストボディ（画像）
テキストのリクエストに加えて、`inline_data` に画像データを含めます。

|キー|型|説明|
|-|-|-|
|`text`|string|画像に対するプロンプト|
|`inline_data`|object|画像データ|
|`inline_data.mime_type`|string|画像の MIMEタイプ（例：`image/jpeg`）|
|`inline_data.data`|string|Base64エンコードした画像データ|

#### JSONの構造
```json
{
  "contents": [{
    "parts": [
      { "text": "この画像に写っている内容を日本語で説明してください。" },
      {
        "inline_data": {
          "mime_type": "image/jpeg",
          "data": "<Base64エンコードした画像データ>"
        }
      }
    ]
  }]
}
```

### image() メソッド
#### 画像の読み込みと Base64エンコード
画像ファイルを読み込み、Base64エンコードして `inline_data` に含めます。

###### services/GeminiService.php
```php
    function image(string $image_path): array
    {
        if (!file_exists($image_path)) {
            return ['error' => '画像ファイルが見つかりません'];
        }

        $url = sprintf(
            '%s%s:generateContent?key=%s',
            $this->baseURL,
            GEMINI_MODEL,
            GEMINI_API_KEY
        );

        // 画像ファイルを読み込む
        $image = file_get_contents($image_path);
        if ($image === false) {
            return ['error' => '画像ファイルの読み込みに失敗しました'];
        }

        // MIMEタイプを取得（例：image/jpeg）
        $mime_type = mime_content_type($image_path) ?: 'image/jpeg';
        // Base64エンコード
        $image_base64 = base64_encode($image);
        $prompt = 'この画像に写っている内容を日本語で説明してください。';
    }
```

#### リクエストボディの作成
`text` と `inline_data` を `parts` に配列で指定します。

###### services/GeminiService.php
```php
        // リクエストデータを作成
        $data = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mime_type,
                            'data'      => $image_base64
                        ]
                    ]
                ]
            ]]
        ];

        // リクエストヘッダーを設定
        $this->options['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);

        // ストリームコンテキストを作成
        $context = stream_context_create($this->options);
```

#### リクエスト＆レスポンス
レスポンスのテキストデータを `$results` 配列に格納して返します。

###### services/GeminiService.php
```php
        // GeminiAPIにリクエストを送信し、レスポンスを取得
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $results['error'] = 'APIリクエストに失敗';
        } else {
            $json = json_decode($response, true);
            if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                $results['text'] = nl2br(htmlspecialchars($json['candidates'][0]['content']['parts'][0]['text']));
            } else {
                $results['error'] = '画像解析失敗';
            }
        }
        return $results;
```

### メインプログラム
#### 画像アップロード関数
`uploadImage()` で画像ファイルを `uploads/` ディレクトリに保存します。

###### whats_photo.php
```php
function uploadImage()
{
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return;
    }
    $uploadDir = 'uploads/';
    // 画像保存先ディレクトリがなければ作成
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName  = $_FILES['image']['name'];
    // 拡張子を取得
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    // ユニークなファイル名を生成
    $fileName  = uniqid() . ".{$extension}";
    // アップロード画像の保存先
    $uploadedImagePath = "uploads/{$fileName}";

    $filePath = $_FILES['image']['tmp_name'];
    // アップロード画像を保存
    move_uploaded_file($filePath, $uploadedImagePath);

    return $uploadedImagePath;
}
```

#### プロンプト処理実行
アップロードした画像のパスを `image()` に渡して解析します。

###### whats_photo.php
```php
<?php
require_once 'env.php';
require_once 'services/GeminiService.php';

$uploadedImagePath = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 画像をアップロード
    $uploadedImagePath = uploadImage();

    // GeminiAPIに画像を送信
    if ($uploadedImagePath && file_exists($uploadedImagePath)) {
        $gemini = new GeminiService();
        $results = $gemini->image($uploadedImagePath);
    }
}
```

#### フォーム
ファイルを送信するフォームには `enctype="multipart/form-data"` を指定します。

###### whats_photo.php
```php
<?php
require_once 'env.php';
require_once 'services/GeminiService.php';

$uploadedImagePath = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedImagePath = uploadImage();
    if ($uploadedImagePath && file_exists($uploadedImagePath)) {
        $gemini = new GeminiService();
        $results = $gemini->image($uploadedImagePath);
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>What's Photo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-bold mb-4">What's Photo</h1>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="file" name="image" accept="image/*" required class="w-full p-2 border rounded">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">解析する</button>
        </form>

        <?php if (isset($results['error'])): ?>
            <div class="mt-6 bg-red-50 p-4 rounded border border-red-200 text-red-700">
                <?= htmlspecialchars($results['error']) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($results['text'])): ?>
            <div class="mt-6 bg-gray-50 p-4 rounded border">
                <h2 class="text-lg font-semibold">解析結果</h2>
                <?php if (!empty($uploadedImagePath)): ?>
                    <img src="<?= htmlspecialchars($uploadedImagePath) ?>" alt="アップロード画像" class="mt-3 w-full rounded">
                <?php endif; ?>
                <p class="mt-3 leading-8"><?= $results['text'] ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
```

#### 動作確認

<img src="/storage/teaching_material/gemini_php_rest_whats_photo.png" class="w-[600px]" alt="">
