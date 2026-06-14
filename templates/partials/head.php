  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/svg+xml" href="/assets/logo-icon.svg">
  <style>
    /* ── Design tokens ─────────────────────────────────────────────── */
    :root {
      --bg:          #f8fafc;
      --bg-card:     #ffffff;
      --fg:          #0f172a;
      --fg-muted:    #64748b;
      --border:      #e2e8f0;
      --accent:      #6366f1;
      --accent-hover:#4f46e5;
      --badge-bg:    #eef2ff;
      --badge-fg:    #4338ca;
      --code-bg:     #f1f5f9;
      --code-fg:     #0f172a;
      --btn-bg:      #6366f1;
      --btn-fg:      #ffffff;
      --btn-hover:   #4f46e5;
      --shadow:      0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.05);
      --radius:      12px;
    }
    /* Dark tokens via media query (auto) */
    @media (prefers-color-scheme: dark) {
      html:not([data-theme="light"]) {
        --bg:          #0f172a;
        --bg-card:     #1e293b;
        --fg:          #f1f5f9;
        --fg-muted:    #94a3b8;
        --border:      #334155;
        --accent:      #818cf8;
        --accent-hover:#a5b4fc;
        --badge-bg:    #1e1b4b;
        --badge-fg:    #a5b4fc;
        --code-bg:     #1e293b;
        --code-fg:     #e2e8f0;
        --btn-bg:      #6366f1;
        --btn-fg:      #ffffff;
        --btn-hover:   #818cf8;
        --shadow:      0 1px 3px rgba(0,0,0,.3), 0 4px 20px rgba(0,0,0,.25);
      }
    }
    /* Explicit dark (set by toggle) */
    html[data-theme="dark"] {
      --bg:          #0f172a;
      --bg-card:     #1e293b;
      --fg:          #f1f5f9;
      --fg-muted:    #94a3b8;
      --border:      #334155;
      --accent:      #818cf8;
      --accent-hover:#a5b4fc;
      --badge-bg:    #1e1b4b;
      --badge-fg:    #a5b4fc;
      --code-bg:     #1e293b;
      --code-fg:     #e2e8f0;
      --btn-bg:      #6366f1;
      --btn-fg:      #ffffff;
      --btn-hover:   #818cf8;
      --shadow:      0 1px 3px rgba(0,0,0,.3), 0 4px 20px rgba(0,0,0,.25);
    }

    /* ── Reset & base ───────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }
    html { color-scheme: light dark; }
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
      background: var(--bg);
      color: var(--fg);
      line-height: 1.6;
      min-height: 100vh;
      transition: background .2s, color .2s;
    }

    /* ── Layout ─────────────────────────────────────────────────────── */
    .page-wrap {
      max-width: 780px;
      margin: 0 auto;
      padding: 0 20px 60px;
    }

    /* ── Top bar ────────────────────────────────────────────────────── */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 0;
      border-bottom: 1px solid var(--border);
      margin-bottom: 48px;
    }
    .topbar-logo {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      font-size: .9rem;
      letter-spacing: -.01em;
      color: var(--fg);
      text-decoration: none;
    }
    /* Package logo, capped at 28px — at/under the topbar's tallest child (.nav-link),
       so adding it does not change the header height. */
    .topbar-logo img { height: 28px; width: auto; display: block; }
    .topbar-logo span { color: var(--accent); }
    .topbar-right { display: flex; align-items: center; gap: 12px; }

    /* ── Theme toggle ───────────────────────────────────────────────── */
    .theme-toggle {
      display: inline-flex;
      align-items: center;
      gap: 0;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
      font-size: .78rem;
      font-weight: 500;
    }
    .theme-btn {
      padding: 5px 11px;
      cursor: pointer;
      background: none;
      border: none;
      color: var(--fg-muted);
      transition: background .15s, color .15s;
      line-height: 1;
    }
    .theme-btn:hover { color: var(--fg); background: var(--border); }
    .theme-btn.active {
      background: var(--accent);
      color: #fff;
    }

    /* ── Language selector ──────────────────────────────────────────── */
    .lang-select {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0 8px;
      font-size: .78rem;
      font-weight: 500;
      color: var(--fg-muted);
    }
    .lang-select-icon { line-height: 1; font-size: .85rem; }
    .lang-select select {
      appearance: none;
      -webkit-appearance: none;
      background: none;
      border: none;
      color: var(--fg);
      font: inherit;
      padding: 5px 4px;
      cursor: pointer;
      outline: none;
    }
    .lang-select select option { color: initial; }

    /* ── Nav link ───────────────────────────────────────────────────── */
    .nav-link {
      font-size: .82rem;
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
      padding: 5px 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
      transition: background .15s;
    }
    .nav-link:hover { background: var(--badge-bg); }

    /* ── Hero ───────────────────────────────────────────────────────── */
    .hero { margin-bottom: 40px; }
    .hero h1 {
      font-size: clamp(1.6rem, 4vw, 2.25rem);
      font-weight: 800;
      letter-spacing: -.03em;
      line-height: 1.2;
      margin: 0 0 16px;
      color: var(--fg);
    }
    .hero h1 span { color: var(--accent); }
    .hero p {
      font-size: 1rem;
      color: var(--fg-muted);
      margin: 0 0 20px;
      max-width: 600px;
    }
    .badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 28px; }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: .78rem;
      font-weight: 600;
      background: var(--badge-bg);
      color: var(--badge-fg);
      border: 1px solid transparent;
    }

    /* ── Form card ──────────────────────────────────────────────────── */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    .card-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .card-title {
      font-size: .9rem;
      font-weight: 600;
      color: var(--fg);
      margin: 0;
    }
    .card-subtitle {
      font-size: .78rem;
      color: var(--fg-muted);
    }
    .card-body { padding: 24px; }

    /* ── Info strip ─────────────────────────────────────────────────── */
    .info-strip {
      margin-top: 36px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }
    .info-item {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 16px 18px;
    }
    .info-item-label {
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--fg-muted);
      margin-bottom: 6px;
    }
    .info-item-value {
      font-size: .88rem;
      font-weight: 600;
      color: var(--fg);
    }
    code {
      background: var(--code-bg);
      color: var(--code-fg);
      padding: 1px 6px;
      border-radius: 4px;
      font-size: .85em;
      font-family: 'SF Mono', 'Fira Code', ui-monospace, monospace;
    }

    /* ── Footer ─────────────────────────────────────────────────────── */
    .page-footer {
      margin-top: 52px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
      font-size: .78rem;
      color: var(--fg-muted);
      display: flex;
      flex-wrap: wrap;
      gap: 8px 20px;
      align-items: center;
    }
    .page-footer a { color: var(--accent); text-decoration: none; }
    .page-footer a:hover { text-decoration: underline; }

    /* ── Responsive tweaks ──────────────────────────────────────────── */
    @media (max-width: 520px) {
      .topbar { margin-bottom: 28px; }
      .card-body { padding: 16px; }
      .info-strip { grid-template-columns: 1fr; }
    }
  </style>
  <!-- Prevent FOUC: set data-theme synchronously from localStorage before first paint -->
  <script>
    (function(){
      var t = localStorage.getItem('fu-theme') || 'auto';
      document.documentElement.setAttribute('data-theme', t);
    }());
  </script>
