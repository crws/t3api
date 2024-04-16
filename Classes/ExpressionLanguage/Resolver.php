<?php

declare(strict_types=1);

namespace SourceBroker\T3api\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;

/*
temporary fix
this is just basically copied over from TYPO3\CMS\Core\ExpressionLanguage\Resolver,
with expressionLanguageVariables set to public...
originally this class extended the core resolver and added the public getters
*/

class Resolver
{
    private ExpressionLanguage $expressionLanguage;
    public array $expressionLanguageVariables;


    public function getExpressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguage;
    }

    public function getExpressionLanguageVariables(): array
    {
        return $this->getExpressionLanguageVariables();
    }

    public function __construct(string $context, array $variables)
    {
        $functionProviderInstances = [];
        // @todo: The entire ProviderConfigurationLoader approach should fall and
        //        substituted with a symfony service provider strategy in v13.
        //        Also, the magic "DefaultProvider" approach should fall at this time,
        //        default functions and variable providers should be provided explicitly
        //        by config.
        //        The entire construct should be reviewed at this point and most likely
        //        declared final as well.
        $providers = GeneralUtility::makeInstance(ProviderConfigurationLoader::class)->getExpressionLanguageProviders()[$context] ?? [];
        // Always add default provider
        array_unshift($providers, DefaultProvider::class);
        $providers = array_unique($providers);
        $functionProviders = [];
        $generalVariables = [];
        foreach ($providers as $provider) {
            /** @var ProviderInterface $providerInstance */
            $providerInstance = GeneralUtility::makeInstance($provider);
            $functionProviders[] = $providerInstance->getExpressionLanguageProviders();
            $generalVariables[] = $providerInstance->getExpressionLanguageVariables();
        }
        $functionProviders = array_merge(...$functionProviders);
        $generalVariables = array_replace_recursive(...$generalVariables);
        $this->expressionLanguageVariables = array_replace_recursive($generalVariables, $variables);
        foreach ($functionProviders as $functionProvider) {
            /** @var ExpressionFunctionProviderInterface[] $functionProviderInstances */
            $functionProviderInstances[] = GeneralUtility::makeInstance($functionProvider);
        }
        $this->expressionLanguage = new ExpressionLanguage(null, $functionProviderInstances);
    }

    /**
     * Evaluate an expression.
     */
    public function evaluate(string $expression, array $contextVariables = []): mixed
    {
        return $this->expressionLanguage->evaluate($expression, array_replace($this->expressionLanguageVariables, $contextVariables));
    }

    /**
     * Compiles an expression to source code.
     * Currently unused in core: We *may* add support for this later to speed up condition parsing?
     */
    public function compile(string $condition): string
    {
        return $this->expressionLanguage->compile($condition, array_keys($this->expressionLanguageVariables));
    }
}
