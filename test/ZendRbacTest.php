<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-authorization-rbac for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-authorization-rbac/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Authorization\Rbac;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Authorization\Exception;
use Zend\Expressive\Authorization\Rbac\ZendRbac;
use Zend\Expressive\Authorization\Rbac\ZendRbacAssertionInterface;
use Zend\Expressive\Router\RouteResult;
use Zend\Permissions\Rbac\Rbac;

class ZendRbacTest extends TestCase
{
    /** @var Rbac|ObjectProphecy */
    private $rbac;

    /** @var ZendRbacAssertionInterface|ObjectProphecy */
    private $assertion;

    protected function setUp()
    {
        $this->rbac = $this->prophesize(Rbac::class);
        $this->assertion = $this->prophesize(ZendRbacAssertionInterface::class);
    }

    public function testConstructorWithoutAssertion()
    {
        $zendRbac = new ZendRbac($this->rbac->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testConstructorWithAssertion()
    {
        $zendRbac = new ZendRbac($this->rbac->reveal(), $this->assertion->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testIsGrantedWithoutRouteResult()
    {
        $zendRbac = new ZendRbac($this->rbac->reveal(), $this->assertion->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)->willReturn(false);

        $this->expectException(Exception\RuntimeException::class);
        $zendRbac->isGranted('foo', $request->reveal());
    }

    public function testIsGrantedWithoutAssertion()
    {
        $this->rbac->isGranted('foo', 'home', null)->willReturn(true);
        $zendRbac = new ZendRbac($this->rbac->reveal());

        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('home');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)
                ->willReturn($routeResult->reveal());

        $result = $zendRbac->isGranted('foo', $request->reveal());
        $this->assertTrue($result);
    }

    public function testIsNotGrantedWithoutAssertion()
    {
        $this->rbac->isGranted('foo', 'home', null)->willReturn(false);
        $zendRbac = new ZendRbac($this->rbac->reveal());

        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('home');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)
                ->willReturn($routeResult->reveal());

        $result = $zendRbac->isGranted('foo', $request->reveal());
        $this->assertFalse($result);
    }

    public function testIsGrantedWitAssertion()
    {
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('home');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)
                ->willReturn($routeResult->reveal());

        $this->rbac->isGranted('foo', 'home', $this->assertion->reveal())->willReturn(true);

        $zendRbac = new ZendRbac($this->rbac->reveal(), $this->assertion->reveal());

        $result = $zendRbac->isGranted('foo', $request->reveal());
        $this->assertTrue($result);
        $this->assertion->setRequest($request->reveal())->shouldBeCalled();
    }

    public function testIsNotGrantedWitAssertion()
    {
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('home');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)
                ->willReturn($routeResult->reveal());

        $this->rbac->isGranted('foo', 'home', $this->assertion->reveal())->willReturn(false);

        $zendRbac = new ZendRbac($this->rbac->reveal(), $this->assertion->reveal());

        $result = $zendRbac->isGranted('foo', $request->reveal());
        $this->assertFalse($result);
        $this->assertion->setRequest($request->reveal())->shouldBeCalled();
    }
}
