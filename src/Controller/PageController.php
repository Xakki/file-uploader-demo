<?php

declare(strict_types=1);

namespace Demo\Controller;

use Demo\Config;
use Demo\Http\Response;
use Demo\View;

/**
 * Static landing + widget pages. No core/manager construction here — these
 * routes must stay side-effect-free (no logger, no storage dirs).
 */
final class PageController
{
    public function __construct(
        private readonly Config $config,
        private readonly View $view,
        private readonly Response $response,
    ) {}

    public function home(): never
    {
        $this->response->html($this->view->render('home', [
            'storage' => $this->config->storageLabel(),
            'chunkSize' => $this->config->chunkSize,
        ]));
    }

    public function widget(): never
    {
        $this->response->html($this->view->render('widget', [
            'storage' => $this->config->storageLabel(),
            'chunkSize' => $this->config->chunkSize,
        ]));
    }
}
