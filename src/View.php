<?php

declare(strict_types=1);

namespace Demo;

/**
 * Minimal template renderer: includes a PHP template from templates/ with the
 * given variables and returns the captured output. Templates pull shared chrome
 * via $this->partial('name').
 *
 * Whitespace note: the previous front controller built pages with
 * sprintf(<<<'HTML' … HTML, …) — a heredoc, which has no trailing newline, and
 * injected partials that were themselves trailing-newline-free nowdocs. To keep
 * byte-for-byte output, partials are emitted with their trailing newline trimmed
 * and the whole page output is trimmed of a single trailing newline.
 */
final class View
{
    public function __construct(
        private readonly I18n $i18n = new I18n,
        private readonly string $templateDir = __DIR__.'/../templates',
    ) {}

    /**
     * Translate a chrome string for the active locale (templates call this as
     * `$this->t('key')`; trailing args feed vsprintf placeholders). Output is
     * trusted markup and is echoed raw by the templates.
     */
    public function t(string $key, string|int ...$args): string
    {
        return $this->i18n->t($key, ...$args);
    }

    /** Active locale code (templates set `<html lang>` and the widget `locale`). */
    public function lang(): string
    {
        return $this->i18n->lang;
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    public function render(string $template, array $vars = []): string
    {
        $body = $this->capture($template, $vars);

        // Match the heredoc: no trailing newline.
        if (str_ends_with($body, "\n")) {
            $body = substr($body, 0, -1);
        }

        return $body;
    }

    /**
     * Render a shared partial (templates/partials/<name>.php), normalised to a
     * single trailing newline. Templates place these on their own line, e.g.
     * `<?= $this->partial('head') ?>` followed by the next markup line; PHP eats
     * exactly one newline after the `?>`, so the partial supplies the newline that
     * the old trailing-newline-free nowdocs got from the surrounding heredoc.
     *
     * @param  array<string, mixed>  $vars
     */
    public function partial(string $name, array $vars = []): string
    {
        $body = $this->capture('partials/'.$name, $vars);

        return rtrim($body, "\n")."\n";
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    private function capture(string $template, array $vars): string
    {
        $file = $this->templateDir.'/'.$template.'.php';

        ob_start();
        (function () use ($file, $vars): void {
            extract($vars, EXTR_SKIP);
            require $file;
        })();

        return (string) ob_get_clean();
    }
}
