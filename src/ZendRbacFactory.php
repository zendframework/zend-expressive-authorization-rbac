<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-authorization-rbac for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-authorization-rbac/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Authorization\Rbac;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Authorization\AuthorizationInterface;
use Zend\Expressive\Authorization\Exception;
use Zend\Permissions\Rbac\Exception\ExceptionInterface as RbacExceptionInterface;
use Zend\Permissions\Rbac\Rbac;

class ZendRbacFactory
{
    /**
     * @throws Exception\InvalidConfigException
     */
    public function __invoke(ContainerInterface $container) : AuthorizationInterface
    {
        $config = $container->get('config')['authorization'] ?? null;
        if (null === $config) {
            throw new Exception\InvalidConfigException(sprintf(
                'Cannot create %s instance; no "authorization" config key present',
                ZendRbac::class
            ));
        }
        if (! isset($config['roles'])) {
            throw new Exception\InvalidConfigException(sprintf(
                'Cannot create %s instance; no authorization.roles configured',
                ZendRbac::class
            ));
        }
        if (! isset($config['permissions'])) {
            throw new Exception\InvalidConfigException(sprintf(
                'Cannot create %s instance; no authorization.permissions configured',
                ZendRbac::class
            ));
        }

        $rbac = new Rbac();
        $this->injectRoles($rbac, $config['roles']);
        $this->injectPermissions($rbac, $config['permissions']);

        $assertion = $container->has(ZendRbacAssertionInterface::class)
            ? $container->get(ZendRbacAssertionInterface::class)
            : null;

        return new ZendRbac($rbac, $assertion);
    }

    /**
     * @throws Exception\InvalidConfigException
     */
    private function injectRoles(Rbac $rbac, array $roles) : void
    {
        $rbac->setCreateMissingRoles(true);

        // Roles and parents
        foreach ($roles as $role => $parents) {
            try {
                $rbac->addRole($role, $parents);
            } catch (RbacExceptionInterface $e) {
                throw new Exception\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws Exception\InvalidConfigException
     */
    private function injectPermissions(Rbac $rbac, array $specification) : void
    {
        foreach ($specification as $role => $permissions) {
            foreach ($permissions as $permission) {
                try {
                    $rbac->getRole($role)->addPermission($permission);
                } catch (RbacExceptionInterface $e) {
                    throw new Exception\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
                }
            }
        }
    }
}
