## AI Translate
言語を選択してテキストを入力し、Gemini API で翻訳する機能です。
フォーム送信ではなく、JavaScript の `fetch` で非同期通信します。

### ファイル構成
```txt
28_gemini/
├── translate.php
├── env.php
├── api/
│   └── ai/
│       └── translate.php
├── js/
│   ├── env.js
│   └── translate.js
├── lib/
│   └── Lang.php
└── services/
    └── GeminiService.php
```

### 処理の流れ
```
translate.php（画面）
  ↓ fetch POST（JSON）
api/ai/translate.php（APIエンドポイント）
  ↓ GeminiService::translate()
services/GeminiService.php
  ↓ Gemini API
  ↑ 翻訳結果（JSON レスポンス）
js/translate.js（結果を画面に表示）
```

### 言語リスト
#### Langクラス
翻訳に使用する言語コードと言語名を管理します。

###### lib/Lang.php
```php
<?php
class Lang
{
    public static $languages = [
        ['key' => 'ja', 'code' => 'ja-JP', 'name' => 'Japanese'],
        ['key' => 'en', 'code' => 'en-US', 'name' => 'English'],
        ['key' => 'fr', 'code' => 'fr-FR', 'name' => 'French'],
        ['key' => 'de', 'code' => 'de-DE', 'name' => 'German'],
        ['key' => 'es', 'code' => 'es-ES', 'name' => 'Spanish'],
        ['key' => 'zh', 'code' => 'zh-CN', 'name' => 'Chinese'],
        ['key' => 'ko', 'code' => 'ko-KR', 'name' => 'Korean'],
        ...
    ];

    // 言語コードから言語名を返す
    public static function getByCode(string $code): string
    {
        foreach (self::$languages as $language) {
            if ($language['code'] === $code) {
                return $language['name'];
            }
        }
        return "";
    }
}
```

### translate() メソッド
#### プロンプトの組み立て
言語コード（例：`ja-JP`）を `Lang::getByCode()` で言語名（例：`Japanese`）に変換し、翻訳プロンプトを組み立てます。

###### services/GeminiService.php
```php
    function translate(string $origin, string $fromLang, string $toLang): ?string
    {
        if (empty($origin)) return "翻訳元のテキストが空です";
        if (empty($fromLang) || empty($toLang)) return "翻訳元または翻訳先の言語コードが空です";

        $url = sprintf(
            '%s%s:generateContent?key=%s',
            $this->baseURL,
            GEMINI_MODEL,
            GEMINI_API_KEY
        );

        // 言語コードを言語名に変換（例: ja-JP → Japanese）
        $fromLang = Lang::getByCode($fromLang);
        $toLang   = Lang::getByCode($toLang);

        $prompt = "Please translate from {$fromLang} to {$toLang} 
        without bracket character.
        If it cannot be translated, 
        please return it as it cannot be translated in {$toLang}.
        \n {$origin}";
    }
```

#### リクエスト＆レスポンス

###### services/GeminiService.php
```php
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $this->options['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        $context  = stream_context_create($this->options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $json = json_decode($response, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
```

### APIエンドポイント
#### JSONリクエストの受け取り
`fetch` から送られた JSON ボディを `php://input` で受け取ります。

###### api/ai/translate.php
```php
<?php
require_once '../../env.php';
require_once '../../lib/Lang.php';
require_once '../../services/GeminiService.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// リクエストボディ（JSON）を取得
$input = file_get_contents('php://input');
$posts = json_decode($input, true);

if (!is_array($posts)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($posts['origin']) || !isset($posts['fromLang']) || !isset($posts['toLang'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data'], JSON_UNESCAPED_UNICODE);
    exit;
}
```

#### JSONレスポンス
翻訳結果を JSON 形式で返します。

###### api/ai/translate.php
```php
// 翻訳実行
$gemini = new GeminiService();
$posts['translate'] = $gemini->translate($posts['origin'], $posts['fromLang'], $posts['toLang']);
$posts['status']    = $posts['translate'] === null ? 'error' : 'success';

echo json_encode($posts, JSON_UNESCAPED_UNICODE);
```

#### レスポンス例
```json
{
  "origin": "こんにちは",
  "fromLang": "ja-JP",
  "toLang": "en-US",
  "translate": "Hello",
  "status": "success"
}
```

### フロントエンド
#### ホスト・パスの設定
`env.js` でAPIのベースURLを動的に生成します。

###### js/env.js
```js
const HOST      = `${window.location.protocol}//${window.location.host}`;
const BASE_PATH = window.location.pathname.split('/').slice(0, -1).join('/');
```

#### 翻訳リクエスト
翻訳ボタンのクリックで `fetch` を使って API に POST 送信します。

###### js/translate.js
```js
const translate = async (origin, fromLang, toLang) => {
    const data = { origin, fromLang, toLang };
    const TRANSLATION_URI = `${HOST}${BASE_PATH}/api/ai/translate.php`;

    const response = await fetch(TRANSLATION_URI, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await response.json();
    renderTranslation(result);
};

startButton.addEventListener('click', () => {
    const text = resultElement.value;
    if (!text) return;
    translate(text, fromLangSelect.value, toLangSelect.value);
});
```

#### 結果の表示
翻訳前後のテキストをチャット形式で表示し、翻訳結果を音声で読み上げます。

###### js/translate.js
```js
// 翻訳結果を表示
const renderTranslation = (translationData) => {
    addTranslation(translationData);
    speakTranslation(translationData.translate);
};

// 翻訳結果を音声で読み上げ（Web Speech API）
const speakTranslation = (text) => {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = toLangSelect.value;
    window.speechSynthesis.speak(utterance);
};
```

#### 音声入力
Web Speech API の `SpeechRecognition` でマイクからテキスト入力し、そのまま翻訳します。

###### js/translate.js
```js
SpeechRecognition = webkitSpeechRecognition || SpeechRecognition;
const recognition = new SpeechRecognition();
recognition.interimResults = false;

// 音声認識結果をテキストエリアにセットして翻訳
recognition.onresult = (event) => {
    const text = event.results[0][0].transcript;
    resultElement.value = text;
    translate(text, fromLangSelect.value, toLangSelect.value);
};

const startSpeech = () => {
    recognition.lang = fromLangSelect.value;
    recognition.start();
};
```

### メインプログラム
#### 言語セレクトボックス
`Lang::$languages` をループしてセレクトボックスを生成します。

###### translate.php
```php
<?php
require_once 'lib/Lang.php';
$langs = Lang::$languages;

$defaultFromLang = 'ja-JP';
$defaultToLang   = 'en-US';

function selected(mixed $value, mixed $selected): string
{
    return ($value == $selected) ? 'selected' : '';
}
?>
```

```html
<select id="fromLang">
    <?php foreach ($langs as $lang): ?>
        <option value="<?= $lang['code'] ?>" <?= selected($defaultFromLang, $lang['code']) ?>><?= $lang['name'] ?></option>
    <?php endforeach ?>
</select>
```

#### スクリプトの読み込み
`env.js` を先に読み込んで `HOST` と `BASE_PATH` を定義してから `translate.js` を読み込みます。

###### translate.php
```html
<script src="js/env.js" defer></script>
<script src="js/translate.js" defer></script>
```

#### 動作確認

<img src="/storage/teaching_material/gemini_php_rest_translate.png" class="w-[600px]" alt="">
