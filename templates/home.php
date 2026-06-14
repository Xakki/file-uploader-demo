<!doctype html>
<html lang="<?= $this->lang() ?>" data-theme="auto">
<head>
  <title>file-uploader — demo</title>
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
      <a class="nav-link" href="/widget"><?= $this->t('nav_widget') ?></a>
      <?= $this->partial('lang-toggle') ?>
      <?= $this->partial('theme-toggle') ?>
    </div>
  </header>

  <!-- Hero -->
  <section class="hero">
    <h1><?= $this->t('home_h1') ?></h1>
    <p>
      <?= $this->t('home_p', $storage) ?>
    </p>
    <div class="badges">
      <span class="badge">⬆ Upload Protocol v1</span>
      <span class="badge"><?= $this->t('badge_chunked') ?></span>
      <span class="badge"><?= $this->t('badge_storage', $storage) ?></span>
      <span class="badge"><?= $this->t('badge_auth') ?></span>
    </div>
  </section>

  <!-- Inline form card -->
  <div class="card">
    <div class="card-header">
      <div>
        <p class="card-title"><?= $this->t('home_card_title') ?></p>
        <span class="card-subtitle"><?= $this->t('home_card_sub') ?></span>
      </div>
    </div>
    <div class="card-body">
      <div id="fu-form"></div>
    </div>
  </div>

  <!-- Info strip -->
  <div class="info-strip">
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_package') ?></div>
      <div class="info-item-value"><code>xakki/file-uploader</code></div>
    </div>
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_protocol') ?></div>
      <div class="info-item-value">Upload Protocol v1</div>
    </div>
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_chunk') ?></div>
      <div class="info-item-value">1 MB (1 048 576 B)</div>
    </div>
    <div class="info-item">
      <div class="info-item-label"><?= $this->t('info_storage_backend') ?></div>
      <div class="info-item-value"><code><?= $storage ?></code></div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="page-footer">
    <span>xakki/file-uploader</span>
    <a href="https://github.com/Xakki/file-uploader" target="_blank" rel="noopener">GitHub</a>
    <a href="/widget"><?= $this->t('footer_widget') ?></a>
    <span style="margin-left:auto">Upload Protocol v1</span>
  </footer>

</div>

<!-- UMD loaded synchronously so FileUploaderWidget is available immediately below -->
<script src="/file-uploader.umd.js"></script>
<script>
  (function () {
    var inst = null;
    if (window.FileUploaderWidget && typeof window.FileUploaderWidget.createForm === 'function') {
      inst = window.FileUploaderWidget.createForm({
        container: '#fu-form',
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
