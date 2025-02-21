<?php

declare(strict_types=1);

namespace Netlogix\Nxpdfrendering\Tests\Unit\Options;

use Netlogix\Nxpdfrendering\Exception\OptionNotFoundException;
use Netlogix\Nxpdfrendering\Options\MiddlewareOptions;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MiddlewareOptionsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testHasReturnsTrueIfOptionExists(): void
    {
        $extensionConfigurationMock = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock
            ->expects($this->any())
            ->method('get')
            ->with('nxpdfrendering')
            ->willReturn([
                'foo' => true,
            ]);

        $middlewareOptions = new MiddlewareOptions($extensionConfigurationMock);
        $this->assertTrue($middlewareOptions->has('foo'));
    }

    /**
     * @test
     */
    public function testHasReturnsFalseIfOptionNotExists(): void
    {
        $extensionConfigurationMock = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock->expects($this->any())->method('get')->with('nxpdfrendering')->willReturn([]);

        $middlewareOptions = new MiddlewareOptions($extensionConfigurationMock);
        $this->assertFalse($middlewareOptions->has('foo'));
    }

    /**
     * @test
     */
    public function testGetReturnsValueIfOptionExists(): void
    {
        $extensionConfigurationMock = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock
            ->expects($this->any())
            ->method('get')
            ->with('nxpdfrendering')
            ->willReturn([
                'foo' => 'bar',
            ]);

        $middlewareOptions = new MiddlewareOptions($extensionConfigurationMock);
        $this->assertEquals('bar', $middlewareOptions->get('foo'));
    }

    /**
     * @test
     */
    public function testGetPersistentHeadersReturnValues(): void
    {
        $extensionConfigurationMock = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock
            ->expects($this->any())
            ->method('get')
            ->with('nxpdfrendering')
            ->willReturn([
                'persistentHeaders' => 'foo,bar',
            ]);

        $middlewareOptions = new MiddlewareOptions($extensionConfigurationMock);
        $this->assertEquals(['foo', 'bar'], $middlewareOptions->get('persistentHeaders'));
    }

    /**
     * @test
     */
    public function testGetThrowsOptionNotFoundExceptionIfOptionNotExists(): void
    {
        $this->expectException(OptionNotFoundException::class);

        $extensionConfigurationMock = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfigurationMock
            ->expects($this->any())
            ->method('get')
            ->with('nxpdfrendering')
            ->willReturn([
                'bar' => 'bar',
            ]);

        $middlewareOptions = new MiddlewareOptions($extensionConfigurationMock);
        $middlewareOptions->get('foo');
    }
}
