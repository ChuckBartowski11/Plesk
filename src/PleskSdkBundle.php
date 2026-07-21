<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk;

use ChuckBartowski\PleskSdk\Client\PleskClient;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class PleskSdkBundle extends AbstractBundle
{
    protected string $extensionAlias = 'plesk_sdk';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('api_key')->defaultValue('')->end()
                ->scalarNode('login')->defaultValue('')->end()
                ->scalarNode('password')->defaultValue('')->end()
                ->integerNode('port')->defaultValue(8443)->end()
                ->booleanNode('verify_ssl')->defaultTrue()->end()
                ->floatNode('timeout')->defaultValue(30.0)->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set(PleskClient::class)
            ->args([
                $config['host'],
                $config['api_key'],
                $config['login'],
                $config['password'],
                $config['port'],
                $config['verify_ssl'],
                $config['timeout'],
                service('http_client')->nullOnInvalid(),
            ]);

        $services->set(Plesk::class)
            ->args([service(PleskClient::class)])
            ->public();

        $services->alias('plesk_sdk.plesk', Plesk::class)->public();
    }
}
