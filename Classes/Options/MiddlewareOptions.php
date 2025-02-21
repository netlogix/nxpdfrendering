<?php

declare(strict_types=1);

namespace Netlogix\Nxpdfrendering\Options;

use Netlogix\Nxpdfrendering\Exception\OptionNotFoundException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class MiddlewareOptions implements SingletonInterface
{
    protected ConfigurationManagerInterface $configurationManager;

    protected array $options = [];

    public function __construct(ExtensionConfiguration $configuration)
    {
        $this->options = $configuration->get('nxpdfrendering');
    }

    public function has(string $optionName): bool
    {
        return array_key_exists($optionName, $this->options);
    }

    public function get(string $optionName)
    {
        $value = ObjectAccess::getPropertyPath($this->options, $optionName);
        if ($value === null) {
            throw new OptionNotFoundException(
                sprintf('Middleware option with name "%s" not found!', $optionName),
                1638105890,
            );
        }

        if ($optionName === 'persistentHeaders') {
            return explode(',', (string) $value);
        }

        return $value;
    }
}
