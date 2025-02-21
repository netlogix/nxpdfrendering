<?php

declare(strict_types=1);

namespace Netlogix\Nxpdfrendering\Tests\Unit\Utility;

use Netlogix\Nxpdfrendering\Options\MiddlewareOptions;
use Netlogix\Nxpdfrendering\Utility\UriUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UriUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testIsPdfRenderingUriReturnTrueIfPageTypeIsSetCorrectly(): void
    {
        $middlewareOptionsMock = $this->getMockBuilder(MiddlewareOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $middlewareOptionsMock
            ->expects($this->any())
            ->method('get')
            ->with('pdfRenderingPageType')
            ->willReturn('1337');

        $pageArgumentsMock = $this->getMockBuilder(PageArguments::class)->disableOriginalConstructor()->getMock();

        $pageArgumentsMock->expects($this->any())->method('getPageType')->willReturn('1337');

        $serverRequestMock = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serverRequestMock
            ->expects($this->any())
            ->method('getAttribute')
            ->with('routing')
            ->willReturn($pageArgumentsMock);

        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uriUtility = new UriUtility($middlewareOptionsMock, $contentObjectRendererMock);
        $this->assertTrue($uriUtility->isPdfRenderingUri($serverRequestMock));
    }

    /**
     * @test
     */
    public function testIsPdfRenderingUriReturnFalseIfPageTypeIsNotSetCorrectly(): void
    {
        $middlewareOptionsMock = $this->getMockBuilder(MiddlewareOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $middlewareOptionsMock
            ->expects($this->any())
            ->method('get')
            ->with('pdfRenderingPageType')
            ->willReturn('1337');

        $pageArgumentsMock = $this->getMockBuilder(PageArguments::class)->disableOriginalConstructor()->getMock();

        $pageArgumentsMock->expects($this->any())->method('getPageType')->willReturn('1330');

        $serverRequestMock = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serverRequestMock
            ->expects($this->any())
            ->method('getAttribute')
            ->with('routing')
            ->willReturn($pageArgumentsMock);

        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uriUtility = new UriUtility($middlewareOptionsMock, $contentObjectRendererMock);
        $this->assertFalse($uriUtility->isPdfRenderingUri($serverRequestMock));
    }

    /**
     * @test
     */
    public function testGetInternalRenderingUriReturnsUriCorrectly(): void
    {
        $internalRenderingUri = 'https://www.foo.de/bar.pdf';

        $middlewareOptionsMock = $this->getMockBuilder(MiddlewareOptions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $middlewareOptionsMock
            ->expects($this->any())
            ->method('get')
            ->with('printRenderingPageType')
            ->willReturn('1337');

        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contentObjectRendererMock
            ->expects($this->any())
            ->method('typoLink_URL')
            ->with([
                'parameter' => 't3://page?uid=current&type=1337',
                'addQueryString' => true,
                'useCacheHash' => true,
                'forceAbsoluteUrl' => true,
            ])
            ->willReturn($internalRenderingUri);

        $serverRequestMock = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uriUtility = new UriUtility($middlewareOptionsMock, $contentObjectRendererMock);
        $result = $uriUtility->getInternalRenderingUri($serverRequestMock);

        $this->assertInstanceOf(UriInterface::class, $result);
        $this->assertEquals($internalRenderingUri, $result->__toString());
    }
}
