<?php

declare(strict_types=1);

use Netlogix\Nxpdfrendering\Middleware\PdfRenderingMiddleware;

return [
    'frontend' => [
        'netlogix/nxpdfrendering/pdfrendering' => [
            'target' => PdfRenderingMiddleware::class,
            'after' => ['typo3/cms-frontend/maintenance-mode'],
        ],
    ],
];
