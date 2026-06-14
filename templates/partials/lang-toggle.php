<?php /** Language selector: reloads the current page with ?lang=xx; the front controller persists the choice to the fu-lang cookie. */ ?>
<label class="lang-select" title="<?= $this->t('lang_group') ?>">
  <span class="lang-select-icon" aria-hidden="true">🌐</span>
  <select aria-label="<?= $this->t('lang_group') ?>"
          onchange="location.assign('?lang=' + encodeURIComponent(this.value))">
<?php foreach (\Demo\I18n::LOCALES as $code => $name): ?>
    <option value="<?= $code ?>"<?= $code === $this->lang() ? ' selected' : '' ?>><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach ?>
  </select>
</label>
