<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>商品カタログ API（JSONモックデータ）</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50">

  <!-- ===== ヘッダー ===== -->
  <header class="bg-white border-b border-slate-200 px-6 py-4 shadow-sm">
    <div class="mx-auto max-w-5xl flex items-center gap-3">
      <span class="text-2xl">🛍️</span>
      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">JSON Mock API</p>
        <h1 class="text-xl font-bold text-slate-900">商品カタログ API</h1>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-5xl px-6 py-10 space-y-6">

    <!-- ===== データフロー説明 ===== -->
    <div class="rounded-xl bg-indigo-50 border border-indigo-200 px-6 py-4">
      <p class="text-xs font-semibold text-indigo-500 uppercase tracking-widest mb-2">データフロー</p>
      <div class="flex items-center gap-2 text-sm font-mono text-indigo-800 flex-wrap">
        <span class="bg-indigo-100 rounded px-2 py-1">products.json</span>
        <span class="text-indigo-400">→</span>
        <span class="bg-indigo-100 rounded px-2 py-1">ProductRepository</span>
        <span class="text-indigo-400">→</span>
        <span class="bg-indigo-100 rounded px-2 py-1">ProductFilter</span>
        <span class="text-indigo-400">→</span>
        <span class="bg-indigo-100 rounded px-2 py-1">json_encode()</span>
        <span class="text-indigo-400">→</span>
        <span class="bg-indigo-100 rounded px-2 py-1">fetch()</span>
      </div>
    </div>

    <!-- ===== 検索・絞り込み ===== -->
    <section class="rounded-2xl bg-white p-8 shadow-sm border border-slate-200">
      <h2 class="text-lg font-bold text-slate-800 mb-6">検索・絞り込み</h2>

      <div class="grid gap-5 sm:grid-cols-2">
        <!-- キーワード -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1" for="keyword">キーワード（商品名）</label>
          <input id="keyword" type="text" placeholder="例: マウス"
            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm
                   focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
        </div>

        <!-- カテゴリ -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1" for="category">カテゴリ</label>
          <select id="category"
            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm
                   focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <option value="">すべて</option>
          </select>
        </div>

        <!-- 並び替え -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1" for="sort">並び替え</label>
          <select id="sort"
            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm
                   focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <option value="">標準</option>
            <option value="price_asc">価格が安い順</option>
            <option value="price_desc">価格が高い順</option>
            <option value="rating_desc">評価が高い順</option>
          </select>
        </div>

        <!-- 在庫 -->
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
            <input id="in_stock" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600">
            在庫ありのみ表示
          </label>
        </div>
      </div>

      <!-- リクエストURLプレビュー -->
      <div class="mt-6">
        <p class="text-sm font-semibold text-slate-700 mb-1">リクエスト URL</p>
        <div class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-3 font-mono text-sm">
          <span class="text-emerald-400 shrink-0">GET</span>
          <span id="url-preview" class="text-slate-100 break-all">api/products</span>
        </div>
      </div>
    </section>

    <!-- ===== ローディング ===== -->
    <div id="loading" class="hidden text-center py-8">
      <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
      <p class="mt-3 text-sm text-slate-500">データを取得中…</p>
    </div>

    <!-- ===== 件数 ===== -->
    <p id="result-count" class="text-sm text-slate-500"></p>

    <!-- ===== 商品カード一覧 ===== -->
    <div id="product-grid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>

    <!-- ===== JSON表示 ===== -->
    <section class="rounded-2xl bg-white shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="font-bold text-slate-800">Raw JSON レスポンス</h2>
        <p class="text-xs text-slate-400 mt-0.5">json_encode() の出力そのまま</p>
      </div>
      <pre id="json-output"
        class="overflow-x-auto p-6 text-xs leading-relaxed font-mono bg-slate-900 text-slate-100 max-h-96"></pre>
    </section>

  </main>

  <!-- ===== JavaScript ===== -->
  <script>
    // ============================================================
    // 要素
    // ============================================================
    const keyword     = document.getElementById('keyword');
    const category    = document.getElementById('category');
    const sort        = document.getElementById('sort');
    const inStock     = document.getElementById('in_stock');
    const urlPreview  = document.getElementById('url-preview');
    const loading     = document.getElementById('loading');
    const resultCount = document.getElementById('result-count');
    const grid        = document.getElementById('product-grid');
    const jsonOutput  = document.getElementById('json-output');

    // ============================================================
    // クエリ文字列を組み立てて URL を作る
    // ============================================================
    function buildUrl() {
      const params = new URLSearchParams();
      if (keyword.value.trim()) params.set('keyword', keyword.value.trim());
      if (category.value)       params.set('category', category.value);
      if (sort.value)           params.set('sort', sort.value);
      if (inStock.checked)      params.set('in_stock', '1');

      const qs = params.toString();
      return qs ? `api/products?${qs}` : 'api/products';
    }

    // ============================================================
    // API を呼んで画面を更新
    // ============================================================
    async function load() {
      const url = buildUrl();
      urlPreview.textContent = url;
      loading.classList.remove('hidden');

      try {
        const res  = await fetch(url);
        const json = await res.json();

        renderProducts(json.products);
        resultCount.textContent = `${json.count} 件の商品`;
        jsonOutput.textContent  = JSON.stringify(json, null, 2);
      } catch (e) {
        resultCount.textContent = '通信エラーが発生しました。';
      } finally {
        loading.classList.add('hidden');
      }
    }

    // ============================================================
    // カテゴリ一覧を専用エンドポイントから取得して選択肢を作る
    // ============================================================
    async function loadCategories() {
      try {
        const res  = await fetch('api/categories');
        const json = await res.json();
        json.categories.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c;
          opt.textContent = c;
          category.appendChild(opt);
        });
      } catch (_) {}
    }

    // ============================================================
    // 商品カードを描画
    // ============================================================
    function renderProducts(products) {
      grid.innerHTML = '';

      if (!products.length) {
        grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-8 text-sm">該当する商品がありません</p>';
        return;
      }

      products.forEach(p => {
        const soldOut = p.stock <= 0;
        const card = document.createElement('div');
        card.className = 'rounded-2xl bg-white p-5 shadow-sm border border-slate-200 flex flex-col';
        card.innerHTML = `
          <div class="flex items-start justify-between mb-2">
            <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 rounded px-2 py-0.5">${p.category}</span>
            <span class="text-xs ${soldOut ? 'text-red-500' : 'text-slate-400'}">
              ${soldOut ? '在庫なし' : '在庫 ' + p.stock}
            </span>
          </div>
          <h3 class="font-bold text-slate-800">${p.name}</h3>
          <p class="text-xs text-slate-500 mt-1 flex-1">${p.description}</p>
          <div class="flex items-center justify-between mt-4">
            <span class="text-lg font-bold text-slate-900">¥${Number(p.price).toLocaleString('ja-JP')}</span>
            <span class="text-sm text-amber-500">★ ${p.rating}</span>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    // ============================================================
    // イベント登録（入力のたびに再取得）
    // ============================================================
    [keyword, category, sort, inStock].forEach(el => {
      el.addEventListener('input', load);
      el.addEventListener('change', load);
    });

    // 初回ロード
    loadCategories();
    load();
  </script>

</body>

</html>
