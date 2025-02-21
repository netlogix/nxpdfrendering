<?php

declare(strict_types=1);

namespace Netlogix\Nxpdfrendering\Utility;

use Netlogix\Nxpdfrendering\Options\MiddlewareOptions;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class UriUtility
{
    public function __construct(
        protected MiddlewareOptions $middlewareOptions,
        protected ContentObjectRenderer $contentObjectRenderer
    ) {
    }

    public function isPdfRenderingUri(ServerRequestInterface $request): bool
    {
        return $request->getAttribute('routing')->getPageType() ===
            $this->middlewareOptions->get('pdfRenderingPageType');
    }

    public function getInternalRenderingUri(ServerRequestInterface $request): UriInterface
    {
        $routing = $request->getAttribute('routing');

        $parameters = $routing->getArguments();
        if (isset($parameters['cHash'])) {
            unset($parameters['cHash']);
        }
        $parameters['type'] = $this->middlewareOptions->get('printRenderingPageType');

        return $request->getAttribute('site')
            ->getRouter()
            ->generateUri($routing->getPageId(), $parameters);
    }
}
