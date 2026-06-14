<?php

declare(strict_types=1);

namespace Demo;

/**
 * Server-side i18n for the demo chrome (topbar, hero, badges, info strip,
 * footer, compare table). The JS widget/form carry their own translations and
 * are simply handed the resolved locale at init, so the page and the upload UI
 * stay in the same language.
 *
 * Language is resolved per request from (in order): an explicit `?lang=` query,
 * the `fu-lang` cookie, the browser `Accept-Language` header, then English.
 * The selector reloads the page with `?lang=xx`; the front controller persists
 * the choice to the cookie so it survives navigation between `/` and `/widget`.
 *
 * The supported set is exactly the widget's eight locales (js/src/widget/
 * strings.ts) so every selectable language has a real widget translation.
 */
final class I18n
{
    public const DEFAULT = 'en';

    /** Selectable locales, code => native display name (order = selector order). */
    public const LOCALES = [
        'en' => 'English',
        'ru' => 'Русский',
        'es' => 'Español',
        'pt' => 'Português',
        'zh' => '中文',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'sr' => 'Srpski',
    ];

    public readonly string $lang;

    public function __construct(string $lang = self::DEFAULT)
    {
        $this->lang = self::supports($lang) ? $lang : self::DEFAULT;
    }

    public static function supports(?string $code): bool
    {
        return $code !== null && isset(self::LOCALES[$code]);
    }

    /**
     * Pick the active locale from request signals, falling back to English.
     */
    public static function resolve(?string $query, ?string $cookie, ?string $acceptLanguage): string
    {
        if (self::supports($query)) {
            return (string) $query;
        }

        if (self::supports($cookie)) {
            return (string) $cookie;
        }

        foreach (self::parseAcceptLanguage($acceptLanguage) as $code) {
            if (self::supports($code)) {
                return $code;
            }
        }

        return self::DEFAULT;
    }

    /**
     * Primary subtags from an Accept-Language header, in declared order
     * (q-weights are ignored — first supported match wins, which is good enough
     * for a demo). "ru-RU,en;q=0.8" => ['ru', 'en'].
     *
     * @return list<string>
     */
    private static function parseAcceptLanguage(?string $header): array
    {
        if ($header === null || $header === '') {
            return [];
        }

        $codes = [];
        foreach (explode(',', $header) as $part) {
            $tag = trim(explode(';', $part)[0]);
            $primary = strtolower(explode('-', $tag)[0]);
            if ($primary !== '') {
                $codes[] = $primary;
            }
        }

        return $codes;
    }

    /**
     * Translate a key for the active locale. Trailing args are interpolated
     * with vsprintf (the message holds %s placeholders, e.g. the storage name).
     * Messages are trusted template strings and may contain HTML; callers echo
     * them raw, exactly as the previous hard-coded markup did.
     */
    public function t(string $key, string|int ...$args): string
    {
        $row = self::MESSAGES[$key] ?? null;
        if ($row === null) {
            return $key;
        }

        $value = $row[$this->lang] ?? $row[self::DEFAULT] ?? $key;

        return $args === [] ? $value : vsprintf($value, $args);
    }

    /**
     * @var array<string, array<string, string>>
     */
    private const MESSAGES = [
        // ── Top bar / nav ────────────────────────────────────────────────
        'nav_widget' => [
            'en' => 'Widget demo →', 'ru' => 'Демо виджета →', 'es' => 'Demo del widget →',
            'pt' => 'Demo do widget →', 'zh' => '小部件演示 →', 'fr' => 'Démo du widget →',
            'de' => 'Widget-Demo →', 'sr' => 'Demo vidžeta →',
        ],
        'nav_form' => [
            'en' => '← Form demo', 'ru' => '← Демо формы', 'es' => '← Demo del formulario',
            'pt' => '← Demo do formulário', 'zh' => '← 表单演示', 'fr' => '← Démo du formulaire',
            'de' => '← Formular-Demo', 'sr' => '← Demo forme',
        ],

        // ── Home hero ────────────────────────────────────────────────────
        'home_h1' => [
            'en' => 'Chunked uploads,<br><span>framework-agnostic.</span>',
            'ru' => 'Загрузка по частям,<br><span>без привязки к фреймворку.</span>',
            'es' => 'Subidas por fragmentos,<br><span>sin depender del framework.</span>',
            'pt' => 'Envios em blocos,<br><span>independente de framework.</span>',
            'zh' => '分块上传，<br><span>不依赖任何框架。</span>',
            'fr' => 'Envois par fragments,<br><span>indépendant du framework.</span>',
            'de' => 'Upload in Chunks,<br><span>framework-unabhängig.</span>',
            'sr' => 'Otpremanje u delovima,<br><span>nezavisno od frejmvorka.</span>',
        ],
        'home_p' => [
            'en' => 'This demo runs the <strong>xakki/file-uploader</strong> PHP core against <strong>%s</strong> storage, speaking <strong>Upload Protocol&nbsp;v1</strong>. Files are uploaded in 1&nbsp;MB chunks; the same API works with Laravel, Symfony, or any plain-PHP backend.',
            'ru' => 'Это демо запускает PHP-ядро <strong>xakki/file-uploader</strong> поверх хранилища <strong>%s</strong> по протоколу <strong>Upload Protocol&nbsp;v1</strong>. Файлы загружаются частями по 1&nbsp;МБ; тот же API работает с Laravel, Symfony или любым backend на чистом PHP.',
            'es' => 'Esta demo ejecuta el núcleo PHP <strong>xakki/file-uploader</strong> sobre almacenamiento <strong>%s</strong>, usando <strong>Upload Protocol&nbsp;v1</strong>. Los archivos se suben en fragmentos de 1&nbsp;MB; la misma API funciona con Laravel, Symfony o cualquier backend en PHP plano.',
            'pt' => 'Esta demo executa o núcleo PHP <strong>xakki/file-uploader</strong> sobre o armazenamento <strong>%s</strong>, falando <strong>Upload Protocol&nbsp;v1</strong>. Os arquivos são enviados em blocos de 1&nbsp;MB; a mesma API funciona com Laravel, Symfony ou qualquer backend em PHP puro.',
            'zh' => '此演示在 <strong>%s</strong> 存储之上运行 <strong>xakki/file-uploader</strong> PHP 核心，使用 <strong>Upload Protocol&nbsp;v1</strong>。文件以 1&nbsp;MB 的分块上传；同一套 API 适用于 Laravel、Symfony 或任何纯 PHP 后端。',
            'fr' => 'Cette démo exécute le cœur PHP <strong>xakki/file-uploader</strong> sur un stockage <strong>%s</strong>, en parlant <strong>Upload Protocol&nbsp;v1</strong>. Les fichiers sont envoyés par fragments de 1&nbsp;Mo&nbsp;; la même API fonctionne avec Laravel, Symfony ou tout backend en PHP pur.',
            'de' => 'Diese Demo betreibt den PHP-Kern <strong>xakki/file-uploader</strong> auf <strong>%s</strong>-Speicher und spricht <strong>Upload Protocol&nbsp;v1</strong>. Dateien werden in 1&nbsp;MB-Chunks hochgeladen; dieselbe API funktioniert mit Laravel, Symfony oder jedem reinen PHP-Backend.',
            'sr' => 'Ova demonstracija pokreće PHP jezgro <strong>xakki/file-uploader</strong> nad <strong>%s</strong> skladištem, koristeći <strong>Upload Protocol&nbsp;v1</strong>. Fajlovi se otpremaju u delovima od 1&nbsp;MB; isti API radi sa Laravel, Symfony ili bilo kojim čistim PHP backendom.',
        ],
        'badge_chunked' => [
            'en' => '⚡ 1 MB chunked', 'ru' => '⚡ Части по 1 МБ', 'es' => '⚡ Fragmentos de 1 MB',
            'pt' => '⚡ Blocos de 1 MB', 'zh' => '⚡ 1 MB 分块', 'fr' => '⚡ Fragments de 1 Mo',
            'de' => '⚡ 1-MB-Chunks', 'sr' => '⚡ Delovi od 1 MB',
        ],
        'badge_storage' => [
            'en' => '🗄 Storage: %s', 'ru' => '🗄 Хранилище: %s', 'es' => '🗄 Almacenamiento: %s',
            'pt' => '🗄 Armazenamento: %s', 'zh' => '🗄 存储：%s', 'fr' => '🗄 Stockage : %s',
            'de' => '🗄 Speicher: %s', 'sr' => '🗄 Skladište: %s',
        ],
        'badge_auth' => [
            'en' => '🔒 Auth: none (demo)', 'ru' => '🔒 Авторизация: нет (демо)',
            'es' => '🔒 Autenticación: ninguna (demo)', 'pt' => '🔒 Autenticação: nenhuma (demo)',
            'zh' => '🔒 认证：无（演示）', 'fr' => '🔒 Authentification : aucune (démo)',
            'de' => '🔒 Auth: keine (Demo)', 'sr' => '🔒 Autentifikacija: nema (demo)',
        ],
        'badge_overlay' => [
            'en' => '🪟 Floating overlay', 'ru' => '🪟 Плавающий слой', 'es' => '🪟 Capa flotante',
            'pt' => '🪟 Camada flutuante', 'zh' => '🪟 浮动层', 'fr' => '🪟 Surcouche flottante',
            'de' => '🪟 Schwebendes Overlay', 'sr' => '🪟 Plutajući sloj',
        ],

        // ── Home card ────────────────────────────────────────────────────
        'home_card_title' => [
            'en' => 'Upload files', 'ru' => 'Загрузка файлов', 'es' => 'Subir archivos',
            'pt' => 'Enviar arquivos', 'zh' => '上传文件', 'fr' => 'Envoyer des fichiers',
            'de' => 'Dateien hochladen', 'sr' => 'Otpremanje fajlova',
        ],
        'home_card_sub' => [
            'en' => 'Drag & drop or click to browse — list persists across page refreshes',
            'ru' => 'Перетащите файлы или нажмите для выбора — список сохраняется между обновлениями страницы',
            'es' => 'Arrastra y suelta o haz clic para explorar — la lista se conserva al recargar la página',
            'pt' => 'Arraste e solte ou clique para procurar — a lista persiste entre recarregamentos da página',
            'zh' => '拖放或点击浏览 — 列表在页面刷新后仍保留',
            'fr' => 'Glissez-déposez ou cliquez pour parcourir — la liste persiste après le rechargement de la page',
            'de' => 'Ziehen und ablegen oder zum Durchsuchen klicken — die Liste bleibt über Seitenaktualisierungen erhalten',
            'sr' => 'Prevucite i otpustite ili kliknite za pretragu — lista ostaje sačuvana nakon osvežavanja stranice',
        ],

        // ── Info strip (home) ────────────────────────────────────────────
        'info_package' => [
            'en' => 'Package', 'ru' => 'Пакет', 'es' => 'Paquete', 'pt' => 'Pacote',
            'zh' => '软件包', 'fr' => 'Paquet', 'de' => 'Paket', 'sr' => 'Paket',
        ],
        'info_protocol' => [
            'en' => 'Protocol', 'ru' => 'Протокол', 'es' => 'Protocolo', 'pt' => 'Protocolo',
            'zh' => '协议', 'fr' => 'Protocole', 'de' => 'Protokoll', 'sr' => 'Protokol',
        ],
        'info_chunk' => [
            'en' => 'Chunk size', 'ru' => 'Размер части', 'es' => 'Tamaño de fragmento',
            'pt' => 'Tamanho do bloco', 'zh' => '分块大小', 'fr' => 'Taille de fragment',
            'de' => 'Chunk-Größe', 'sr' => 'Veličina dela',
        ],
        'info_storage_backend' => [
            'en' => 'Storage backend', 'ru' => 'Бэкенд хранилища', 'es' => 'Backend de almacenamiento',
            'pt' => 'Backend de armazenamento', 'zh' => '存储后端', 'fr' => 'Backend de stockage',
            'de' => 'Speicher-Backend', 'sr' => 'Backend skladišta',
        ],

        // ── Footer ───────────────────────────────────────────────────────
        'footer_widget' => [
            'en' => 'Widget demo', 'ru' => 'Демо виджета', 'es' => 'Demo del widget',
            'pt' => 'Demo do widget', 'zh' => '小部件演示', 'fr' => 'Démo du widget',
            'de' => 'Widget-Demo', 'sr' => 'Demo vidžeta',
        ],
        'footer_form' => [
            'en' => 'Form demo', 'ru' => 'Демо формы', 'es' => 'Demo del formulario',
            'pt' => 'Demo do formulário', 'zh' => '表单演示', 'fr' => 'Démo du formulaire',
            'de' => 'Formular-Demo', 'sr' => 'Demo forme',
        ],

        // ── Widget page hero ─────────────────────────────────────────────
        'widget_h1' => [
            'en' => 'Floating <span>widget</span> template',
            'ru' => 'Шаблон плавающего <span>виджета</span>',
            'es' => 'Plantilla de <span>widget</span> flotante',
            'pt' => 'Modelo de <span>widget</span> flutuante',
            'zh' => '浮动<span>小部件</span>模板',
            'fr' => 'Modèle de <span>widget</span> flottant',
            'de' => 'Vorlage für schwebendes <span>Widget</span>',
            'sr' => 'Šablon plutajućeg <span>vidžeta</span>',
        ],
        'widget_p' => [
            'en' => 'The <strong>widget</strong> template renders a floating button (bottom-right corner) that opens a modal panel for uploading and managing files. Same <strong>Upload Protocol v1</strong> engine as the inline form — choose whichever template fits your UI.',
            'ru' => 'Шаблон <strong>widget</strong> рисует плавающую кнопку (в правом нижнем углу), которая открывает модальную панель для загрузки и управления файлами. Тот же движок <strong>Upload Protocol v1</strong>, что и у встроенной формы — выбирайте шаблон под свой интерфейс.',
            'es' => 'La plantilla <strong>widget</strong> muestra un botón flotante (esquina inferior derecha) que abre un panel modal para subir y gestionar archivos. El mismo motor <strong>Upload Protocol v1</strong> que el formulario en línea — elige la plantilla que mejor encaje en tu interfaz.',
            'pt' => 'O modelo <strong>widget</strong> renderiza um botão flutuante (canto inferior direito) que abre um painel modal para enviar e gerenciar arquivos. O mesmo motor <strong>Upload Protocol v1</strong> do formulário em linha — escolha o modelo que melhor se encaixa na sua interface.',
            'zh' => '<strong>widget</strong> 模板渲染一个浮动按钮（右下角），点击后打开用于上传和管理文件的模态面板。与内联表单使用相同的 <strong>Upload Protocol v1</strong> 引擎 — 选择最适合你界面的模板。',
            'fr' => 'Le modèle <strong>widget</strong> affiche un bouton flottant (coin inférieur droit) qui ouvre un panneau modal pour envoyer et gérer les fichiers. Le même moteur <strong>Upload Protocol v1</strong> que le formulaire en ligne — choisissez le modèle adapté à votre interface.',
            'de' => 'Die <strong>widget</strong>-Vorlage zeigt eine schwebende Schaltfläche (untere rechte Ecke), die ein modales Panel zum Hochladen und Verwalten von Dateien öffnet. Dieselbe <strong>Upload Protocol v1</strong>-Engine wie das eingebettete Formular — wählen Sie die Vorlage, die zu Ihrer Oberfläche passt.',
            'sr' => 'Šablon <strong>widget</strong> prikazuje plutajuće dugme (donji desni ugao) koje otvara modalni panel za otpremanje i upravljanje fajlovima. Isti <strong>Upload Protocol v1</strong> mehanizam kao ugrađena forma — izaberite šablon koji odgovara vašem interfejsu.',
        ],

        // ── Widget page card ─────────────────────────────────────────────
        'widget_card_title' => [
            'en' => 'Floating widget', 'ru' => 'Плавающий виджет', 'es' => 'Widget flotante',
            'pt' => 'Widget flutuante', 'zh' => '浮动小部件', 'fr' => 'Widget flottant',
            'de' => 'Schwebendes Widget', 'sr' => 'Plutajući vidžet',
        ],
        'widget_card_sub' => [
            'en' => 'Click the ⇪ button in the bottom-right corner to open the upload panel',
            'ru' => 'Нажмите кнопку ⇪ в правом нижнем углу, чтобы открыть панель загрузки',
            'es' => 'Haz clic en el botón ⇪ de la esquina inferior derecha para abrir el panel de subida',
            'pt' => 'Clique no botão ⇪ no canto inferior direito para abrir o painel de envio',
            'zh' => '点击右下角的 ⇪ 按钮以打开上传面板',
            'fr' => "Cliquez sur le bouton ⇪ dans le coin inférieur droit pour ouvrir le panneau d'envoi",
            'de' => 'Klicken Sie auf die Schaltfläche ⇪ in der unteren rechten Ecke, um das Upload-Panel zu öffnen',
            'sr' => 'Kliknite na dugme ⇪ u donjem desnom uglu da otvorite panel za otpremanje',
        ],
        'widget_card_body' => [
            'en' => 'The widget mounts to the page and renders a floating toggle button. Open it to upload files, view the list, delete, or clean up the trash. Uploaded files persist across page refreshes.',
            'ru' => 'Виджет монтируется на страницу и рисует плавающую кнопку-переключатель. Откройте её, чтобы загружать файлы, смотреть список, удалять или очищать корзину. Загруженные файлы сохраняются между обновлениями страницы.',
            'es' => 'El widget se monta en la página y muestra un botón flotante. Ábrelo para subir archivos, ver la lista, eliminar o vaciar la papelera. Los archivos subidos se conservan al recargar la página.',
            'pt' => 'O widget é montado na página e renderiza um botão flutuante. Abra-o para enviar arquivos, ver a lista, excluir ou limpar a lixeira. Os arquivos enviados persistem entre recarregamentos da página.',
            'zh' => '小部件挂载到页面并渲染一个浮动切换按钮。打开它即可上传文件、查看列表、删除或清空回收站。已上传的文件在页面刷新后仍保留。',
            'fr' => 'Le widget se monte sur la page et affiche un bouton flottant. Ouvrez-le pour envoyer des fichiers, consulter la liste, supprimer ou vider la corbeille. Les fichiers envoyés persistent après le rechargement de la page.',
            'de' => 'Das Widget wird in die Seite eingebunden und zeigt eine schwebende Umschalt-Schaltfläche. Öffnen Sie es, um Dateien hochzuladen, die Liste anzusehen, zu löschen oder den Papierkorb zu leeren. Hochgeladene Dateien bleiben über Seitenaktualisierungen erhalten.',
            'sr' => 'Vidžet se montira na stranicu i prikazuje plutajuće dugme. Otvorite ga da otpremate fajlove, pregledate listu, brišete ili ispraznite korpu. Otpremljeni fajlovi ostaju sačuvani nakon osvežavanja stranice.',
        ],

        // ── Info strip (widget) ──────────────────────────────────────────
        'info_template' => [
            'en' => 'Template', 'ru' => 'Шаблон', 'es' => 'Plantilla', 'pt' => 'Modelo',
            'zh' => '模板', 'fr' => 'Modèle', 'de' => 'Vorlage', 'sr' => 'Šablon',
        ],
        'info_trigger' => [
            'en' => 'Trigger', 'ru' => 'Триггер', 'es' => 'Disparador', 'pt' => 'Gatilho',
            'zh' => '触发', 'fr' => 'Déclencheur', 'de' => 'Auslöser', 'sr' => 'Okidač',
        ],
        'info_api' => [
            'en' => 'API endpoint', 'ru' => 'API-эндпоинт', 'es' => 'Endpoint de API',
            'pt' => 'Endpoint da API', 'zh' => 'API 端点', 'fr' => 'Point de terminaison API',
            'de' => 'API-Endpunkt', 'sr' => 'API endpoint',
        ],
        'widget_template_value' => [
            'en' => '(floating)', 'ru' => '(плавающий)', 'es' => '(flotante)', 'pt' => '(flutuante)',
            'zh' => '（浮动）', 'fr' => '(flottant)', 'de' => '(schwebend)', 'sr' => '(plutajući)',
        ],
        'widget_trigger_value' => [
            'en' => '⇪ bottom-right button', 'ru' => '⇪ кнопка справа внизу',
            'es' => '⇪ botón inferior derecho', 'pt' => '⇪ botão no canto inferior direito',
            'zh' => '⇪ 右下角按钮', 'fr' => '⇪ bouton en bas à droite',
            'de' => '⇪ Schaltfläche unten rechts', 'sr' => '⇪ dugme dole desno',
        ],

        // ── Compare table (widget) ───────────────────────────────────────
        'compare_title' => [
            'en' => 'Comparing templates', 'ru' => 'Сравнение шаблонов', 'es' => 'Comparación de plantillas',
            'pt' => 'Comparação de modelos', 'zh' => '模板对比', 'fr' => 'Comparaison des modèles',
            'de' => 'Vorlagen im Vergleich', 'sr' => 'Poređenje šablona',
        ],
        'compare_placement' => [
            'en' => 'Placement', 'ru' => 'Размещение', 'es' => 'Ubicación', 'pt' => 'Posicionamento',
            'zh' => '位置', 'fr' => 'Emplacement', 'de' => 'Platzierung', 'sr' => 'Postavljanje',
        ],
        'compare_listvisible' => [
            'en' => 'List visible', 'ru' => 'Список виден', 'es' => 'Lista visible',
            'pt' => 'Lista visível', 'zh' => '列表可见', 'fr' => 'Liste visible',
            'de' => 'Liste sichtbar', 'sr' => 'Lista vidljiva',
        ],
        'compare_bestfor' => [
            'en' => 'Best for', 'ru' => 'Лучше для', 'es' => 'Ideal para', 'pt' => 'Ideal para',
            'zh' => '最适合', 'fr' => 'Idéal pour', 'de' => 'Am besten für', 'sr' => 'Najbolje za',
        ],
        'compare_inline' => [
            'en' => 'Inline in page', 'ru' => 'Встроен в страницу', 'es' => 'En línea en la página',
            'pt' => 'Em linha na página', 'zh' => '嵌入页面中', 'fr' => 'Intégré dans la page',
            'de' => 'Eingebettet in die Seite', 'sr' => 'Ugrađeno u stranicu',
        ],
        'compare_overlay' => [
            'en' => 'Floating overlay', 'ru' => 'Плавающий слой', 'es' => 'Capa flotante',
            'pt' => 'Camada flutuante', 'zh' => '浮动层', 'fr' => 'Surcouche flottante',
            'de' => 'Schwebendes Overlay', 'sr' => 'Plutajući sloj',
        ],
        'compare_bydefault' => [
            'en' => 'By default', 'ru' => 'По умолчанию', 'es' => 'Por defecto', 'pt' => 'Por padrão',
            'zh' => '默认', 'fr' => 'Par défaut', 'de' => 'Standardmäßig', 'sr' => 'Podrazumevano',
        ],
        'compare_onopen' => [
            'en' => 'On open', 'ru' => 'При открытии', 'es' => 'Al abrir', 'pt' => 'Ao abrir',
            'zh' => '打开时', 'fr' => "À l'ouverture", 'de' => 'Beim Öffnen', 'sr' => 'Pri otvaranju',
        ],
        'compare_dedicated' => [
            'en' => 'Dedicated upload pages', 'ru' => 'Отдельные страницы загрузки',
            'es' => 'Páginas de subida dedicadas', 'pt' => 'Páginas de envio dedicadas',
            'zh' => '专用上传页面', 'fr' => "Pages d'envoi dédiées",
            'de' => 'Dedizierte Upload-Seiten', 'sr' => 'Namenske stranice za otpremanje',
        ],
        'compare_addon' => [
            'en' => 'Add-on to existing UI', 'ru' => 'Дополнение к существующему интерфейсу',
            'es' => 'Complemento para una interfaz existente', 'pt' => 'Complemento para uma interface existente',
            'zh' => '现有界面的附加组件', 'fr' => 'Complément à une interface existante',
            'de' => 'Ergänzung zu bestehender Oberfläche', 'sr' => 'Dodatak postojećem interfejsu',
        ],

        // ── Theme toggle ─────────────────────────────────────────────────
        'theme_group' => [
            'en' => 'Color theme', 'ru' => 'Цветовая тема', 'es' => 'Tema de color',
            'pt' => 'Tema de cor', 'zh' => '配色主题', 'fr' => 'Thème de couleur',
            'de' => 'Farbschema', 'sr' => 'Tema boja',
        ],
        'theme_light' => [
            'en' => 'Light', 'ru' => 'Светлая', 'es' => 'Claro', 'pt' => 'Claro',
            'zh' => '浅色', 'fr' => 'Clair', 'de' => 'Hell', 'sr' => 'Svetla',
        ],
        'theme_auto' => [
            'en' => 'Auto', 'ru' => 'Авто', 'es' => 'Auto', 'pt' => 'Auto',
            'zh' => '自动', 'fr' => 'Auto', 'de' => 'Auto', 'sr' => 'Auto',
        ],
        'theme_dark' => [
            'en' => 'Dark', 'ru' => 'Тёмная', 'es' => 'Oscuro', 'pt' => 'Escuro',
            'zh' => '深色', 'fr' => 'Sombre', 'de' => 'Dunkel', 'sr' => 'Tamna',
        ],

        // ── Language selector ────────────────────────────────────────────
        'lang_group' => [
            'en' => 'Language', 'ru' => 'Язык', 'es' => 'Idioma', 'pt' => 'Idioma',
            'zh' => '语言', 'fr' => 'Langue', 'de' => 'Sprache', 'sr' => 'Jezik',
        ],
    ];
}
