<!doctype html>
<html lang="<?= $this->lang() ?>" data-theme="auto">
<head>
  <title>file-uploader — widget demo</title>
<?= $this->partial('head') ?>
</head>
<body>
<div class="page-wrap">

  <!-- Top bar -->
  <header class="topbar">
    <a class="topbar-logo" href="/">
      <img src="/assets/logo.svg" alt="" width="28" height="28">
      xakki/<span>file-uploader</span>
    </a>
    <div class="topbar-right">
      <a class="nav-link" href="/"><?= $this->t('nav_form') ?></a>
      <?= $this->partial('lang-toggle') ?>
      <?= $this->partial('theme-toggle') ?>
    </div>
  </header>

  <!-- Hero -->
  <section class="hero">
    <h1><?= $this->t('widget_h1') ?></h1>
    <p>
      <?= $this->t('widget_p') ?>
    </p>
    <div class="badges">
      <span class="badge"><?= $this->t('badge_overlay') ?></span>
      <span class="badge"><?= $this->t('badge_chunked') ?></span>
      <span class="badge"><?= $this->t('badge_storage', $storage) ?></span>
    </div>
  </section>

  <!-- Widget mount card -->
  <div class="card">
    <div class="card-header">
      <div>
        <p class="card-title"><?= $this->t('widget_card_title') ?></p>
        <span class="card-subtitle"><?= $this->t('widget_card_sub') ?></span>
      </div>
    </div>
    <div class="card-body">
      <p style="margin:0;color:var(--fg-muted);font-size:.9rem">
        <?= $this->t('widget_card_body') ?>
      </p>
    </div>
  </div>

  <!-- How it works -->
  <div class="info-strip">
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_template') ?></div>
      <div class="info-item-value"><code>widget</code> <?= $this->t('widget_template_value') ?></div>
    </div>
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_trigger') ?></div>
      <div class="info-item-value"><?= $this->t('widget_trigger_value') ?></div>
    </div>
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_api') ?></div>
      <div class="info-item-value"><code>/file-upload</code></div>
    </div>
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_storage_backend') ?></div>
      <div class="info-item-value"><code><?= $storage ?></code></div>
    </div>
  </div>

  <!-- Compare templates section -->
  <div class="card" style="margin-top:28px">
    <div class="card-header">
      <p class="card-title"><?= $this->t('compare_title') ?></p>
    </div>
    <div class="card-body" style="padding:20px 24px">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem">
        <thead>
          <tr style="border-bottom:1px solid var(--border)">
            <th style="text-align:left;padding:6px 8px;color:var(--fg-muted);font-weight:600"></th>
            <th style="text-align:left;padding:6px 8px;color:var(--fg-muted);font-weight:600">form</th>
            <th style="text-align:left;padding:6px 8px;color:var(--fg-muted);font-weight:600">widget</th>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:8px;color:var(--fg-muted)"><?= $this->t('compare_placement') ?></td>
            <td style="padding:8px"><?= $this->t('compare_inline') ?></td>
            <td style="padding:8px"><?= $this->t('compare_overlay') ?></td>
          </tr>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:8px;color:var(--fg-muted)"><?= $this->t('compare_listvisible') ?></td>
            <td style="padding:8px"><?= $this->t('compare_bydefault') ?></td>
            <td style="padding:8px"><?= $this->t('compare_onopen') ?></td>
          </tr>
          <tr>
            <td style="padding:8px;color:var(--fg-muted)"><?= $this->t('compare_bestfor') ?></td>
            <td style="padding:8px"><?= $this->t('compare_dedicated') ?></td>
            <td style="padding:8px"><?= $this->t('compare_addon') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Footer -->
  <footer class="page-footer">
    <span>xakki/file-uploader</span>
    <a href="https://github.com/Xakki/file-uploader" target="_blank" rel="noopener">GitHub</a>
    <a href="/"><?= $this->t('footer_form') ?></a>
    <span style="margin-left:auto">Upload Protocol v1</span>
  </footer>

</div>

<!-- UMD loaded synchronously so FileUploaderWidget is available immediately below -->
<script src="/file-uploader.umd.js"></script>
<script>
  (function () {
    var inst = null;
    if (window.FileUploaderWidget && typeof window.FileUploaderWidget.createWidget === 'function') {
      inst = window.FileUploaderWidget.createWidget({
        endpointBase: '/file-upload',
        chunkSize: <?= (int) ($chunkSize ?? \Demo\Config::CHUNK_SIZE) ?>,
        allowList: true,
        allowDelete: true,
        allowDeleteAllFiles: true,
        allowCleanup: true,
        auth: 'none',
        locale: '<?= $this->lang() ?>',
        theme: 'auto'
      });
    }
    if (window.__fuSetInst) window.__fuSetInst(inst);
  }());
</script>

<?= $this->partial('theme-script') ?>

</body>
</html>
