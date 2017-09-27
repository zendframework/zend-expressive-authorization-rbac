<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-authorization-rbac for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-authorization-rbac/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Authorization\Rbac;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            ZendRbac::class => ZendRbacFactory::class,
        ];
    }
}
