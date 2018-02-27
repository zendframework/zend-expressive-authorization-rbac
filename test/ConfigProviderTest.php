<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-authorization-rbac for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-authorization-rbac/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Authorization\Rbac;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Authorization\Rbac\ConfigProvider;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $provider;

    protected function setUp()
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray()
    {
        $config = ($this->provider)();
        $this->assertInternalType('array', $config);
        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config)
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertInternalType('array', $config['dependencies']);
        $this->assertArrayHasKey('factories', $config['dependencies']);
        $this->assertInternalType('array', $config['dependencies']['factories']);
    }


    public function testServicesDefinedInConfigProvider()
    {
        $config = ($this->provider)();

        $json = json_decode(
            file_get_contents(__DIR__ . '/../composer.lock'),
            true
        );
        foreach ($json['packages'] as $package) {
            if (isset($package['extra']['zf']['config-provider'])) {
                $configProvider = new $package['extra']['zf']['config-provider']();
                $config = array_merge_recursive($config, $configProvider());
            }
        }

        $config['dependencies']['services']['config'] = [
            'authorization' => ['roles' => [], 'permissions' => []],
        ];
        $container = $this->getContainer($config['dependencies']);

        $dependencies = $this->provider->getDependencies();
        foreach ($dependencies['factories'] as $name => $factory) {
            $this->assertTrue($container->has($name), sprintf('Container does not contain service %s', $name));
            $this->assertInternalType(
                'object',
                $container->get($name),
                sprintf('Cannot get service %s from container using factory %s', $name, $factory)
            );
        }
    }

    private function getContainer(array $dependencies) : ServiceManager
    {
        $container = new ServiceManager();
        (new Config($dependencies))->configureServiceManager($container);

        return $container;
    }
}
