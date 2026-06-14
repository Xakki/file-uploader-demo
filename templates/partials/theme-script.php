  <script>
    (function () {
      var inst = null; // will be set after widget init

      function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('fu-theme', t);
        // Update toggle button states
        document.querySelectorAll('.theme-btn').forEach(function (btn) {
          btn.classList.toggle('active', btn.dataset.theme === t);
        });
        // Derive concrete value for the widget instance
        var concrete = t;
        if (t === 'auto') {
          concrete = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        if (inst && typeof inst.setTheme === 'function') {
          inst.setTheme(t);
        }
      }

      window.__fuApplyTheme = applyTheme;

      // Wire toggle buttons
      document.querySelectorAll('.theme-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          applyTheme(btn.dataset.theme);
        });
      });

      // Apply saved/default theme immediately (also updates button states)
      var saved = localStorage.getItem('fu-theme') || 'auto';
      applyTheme(saved);

      // Expose setter so widget-init code can register the instance
      window.__fuSetInst = function (i) {
        inst = i;
        // Re-apply theme so the widget picks it up
        applyTheme(localStorage.getItem('fu-theme') || 'auto');
      };
    }());
  </script>
