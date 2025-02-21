<?php

declare(strict_types=1);

namespace Netlogix\Nxpdfrendering\Middleware;

use DOMDocument;
use DOMXPath;
use Netlogix\HeadlessChromiumFactory\RemoteBrowserFactory;
use Netlogix\Nxpdfrendering\Exception\GhostscriptException;
use Netlogix\Nxpdfrendering\Options\MiddlewareOptions;
use Netlogix\Nxpdfrendering\Utility\UriUtility;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PdfRenderingMiddleware implements MiddlewareInterface
{
    protected RemoteBrowserFactory $browserFactory;

    public function __construct(
        protected ResponseFactoryInterface $responseFactory,
        protected MiddlewareOptions $middlewareOptions,
        protected UriUtility $uriUtility,
    ) {
        $this->browserFactory = new RemoteBrowserFactory(
            $this->middlewareOptions->get('remoteDebuggingHost'),
            $this->middlewareOptions->get('remoteDebuggingPort'),
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $originalResponse = $handler->handle($request);

        if (!$this->uriUtility->isPdfRenderingUri($request)) {
            return $originalResponse;
        }

        $desiredFilename = sprintf('%s.pdf', basename(trim($request->getUri()->getPath(), '/'), '.pdf'));
        $renderedPdfPathAndFilename = $this->renderPdf($request);

        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $desiredFilename))
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('X-Robots-Tag', 'noindex');

        foreach ($this->middlewareOptions->get('persistentHeaders') as $header) {
            if ($originalResponse->hasHeader($header)) {
                $response = $response->withHeader($header, $originalResponse->getHeader($header));
            }
        }

        $response->getBody()->write(file_get_contents($renderedPdfPathAndFilename));

        unlink($renderedPdfPathAndFilename);

        return $response->withStatus(200);
    }

    protected function renderPdf(ServerRequestInterface $request): string
    {
        $browser = $this->browserFactory->createBrowser();

        try {
            $page = $browser->createPage();

            $page
                ->navigate($this->uriUtility->getInternalRenderingUri($request)->__toString())
                ->waitForNavigation();

            $temporaryRenderedPdfPathAndFilename = tempnam(sys_get_temp_dir(), 'PdfRendering');

            $page
                ->pdf($this->middlewareOptions->get('pdfOptions'))
                ->saveToFile($temporaryRenderedPdfPathAndFilename);

            if ($this->middlewareOptions->get('mergePdfAttachments')) {
                $temporaryRenderedPdfWithAttachmentsPathAndFilename = self::mergePdfFiles(
                    $temporaryRenderedPdfPathAndFilename,
                    ...self::getAdditionalPdfPathAndFilenamesFromHtml($page->getHtml()),
                );

                unlink($temporaryRenderedPdfPathAndFilename);

                return $temporaryRenderedPdfWithAttachmentsPathAndFilename;
            }

            return $temporaryRenderedPdfPathAndFilename;
        } finally {
            $page->close();
        }
    }

    protected static function mergePdfFiles(...$pdfPathAndFilenames): string
    {
        $mergedPdfPathAndFilename = tempnam(sys_get_temp_dir(), 'PdfRendering');

        $ghostscriptMergePdfCommand = sprintf(
            'gs -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=%s %s',
            escapeshellarg($mergedPdfPathAndFilename),
            implode(' ', array_map(static fn($file): string => escapeshellarg((string) $file), $pdfPathAndFilenames)),
        );

        exec($ghostscriptMergePdfCommand, $output, $resultCode);

        if (
            $resultCode > 0 ||
            !file_exists($mergedPdfPathAndFilename) ||
            filesize($mergedPdfPathAndFilename) === 0
        ) {
            throw new GhostscriptException('An error occurred while processing the PDF file.');
        }

        return $mergedPdfPathAndFilename;
    }

    protected static function getAdditionalPdfPathAndFilenamesFromHtml(string $html): array
    {
        $document = new DOMDocument();

        $previousUseErrorsValue = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_use_internal_errors($previousUseErrorsValue);

        $xpath = new DOMXPath($document);
        $links = $xpath->query('//main//a[@href]');

        $additionalPdfPathAndFilenames = [];

        foreach ($links as $link) {
            $relativePath = parse_url($link->getAttribute('href'), PHP_URL_PATH);
            if (is_null($relativePath)) {
                continue;
            }

            if ($relativePath === $_SERVER['REQUEST_URI']) {
                continue;
            }

            $absolutePath = realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $relativePath);
            if ($absolutePath === false) {
                // File path may contain timestamp for cache busting, try again without timestamp
                $absolutePath = realpath(
                    $_SERVER['DOCUMENT_ROOT'] .
                        DIRECTORY_SEPARATOR .
                        preg_replace('/\.\d{10}\./', '.', $relativePath),
                );
            }

            if ($absolutePath === false) {
                continue;
            }

            if (pathinfo($relativePath, PATHINFO_EXTENSION) !== 'pdf') {
                continue;
            }

            if (@mime_content_type($absolutePath) !== 'application/pdf') {
                continue;
            }

            $additionalPdfPathAndFilenames[] = $absolutePath;
        }

        return array_unique($additionalPdfPathAndFilenames);
    }
}
