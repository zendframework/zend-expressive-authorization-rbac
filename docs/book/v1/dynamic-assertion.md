# Dynamic assertion

In some cases, you need to authorize a `role` based on a specific HTTP request.
For instance, imagine that you have an `editor` rule that can add/update/delete
a page in a Content Management System (CMS). We want prevent an `editor` to be
able to modify a page that has not been created by him/her.

These types of authorization are called [dynamic assertions](https://docs.zendframework.com/zend-permissions-rbac/examples/#dynamic-assertions)
and are implemented by `Zend\Permissions\Rbac\AssertionInterface` of
[zend-permissions-rbac](https://github.com/zendframework/zend-permissions-rbac).

In order to use it, we need to implement a `ZendRbacAssertionInterface`
interface, that extends the `Zend\Permissions\Rbac\AssertionInterface`:

```php
namespace Zend\Expressive\Authorization\Rbac;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Permissions\Rbac\AssertionInterface;

interface ZendRbacAssertionInterface extends AssertionInterface
{
    public function setRequest(ServerRequestInterface $request) : void;
}
```

The `Zend\Permissions\Rbac\AssertionInterface` is as follows:

```php
namespace Zend\Permissions\Rbac;

interface AssertionInterface
{
    public function assert(Rbac $rbac, RoleInterface $role, string $permission) : bool;
}
```

Going back to the previous use case, we can build a class to manage the `editor`
authorization needs, as follows:

```php
use Zend\Expressive\Authorization\Rbac\ZendRbacAssertionInterface;
use App\Service\Article;

class EditorAuth implements ZendRbacAssertionInterface
{
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function assert(Rbac $rbac, RoleInterface $role, string $permission)
    {
        $user = $this->request->getAttribute(UserInterface::class, false);
        return $this->article->isUserOwner($user->getIdentity(), $this->request);
    }
}
```

Where `Article` is a service/model class to check if the logged user is the
owner of the article identified by the HTTP request.

For instance, if you manage the articles using a SQL database the implementation
of `isUserOwner` can be something like the follows:

```php
public function isUserOwner(string $identity, ServerRequestInterface $request): bool
{
    // get the article {url} attribute specified in the route
    $url = $request->getAttribute('url', false);
    if (! $url) {
        return false;
    }
    $sth = $this->pdo->prepare(
        'SELECT * FROM article WHERE url = :url AND owner = :identity'
    );
    $sth->bindParam(':url', $url);
    $sth->bindParam(':identity', $identity);
    if (! $sth->execute()) {
        return false;
    }
    $row = $sth->fetch();
    return ! empty($row);
}
```

To pass the `Article` dependency you can use a Factory class that generates the
`EditorAuth` class instance, as follows:

```php
use App\Service\Article;

class EditorAuthFactory
{
    public function __invoke(ContainerInterface $container) : EditorAuth
    {
        return new EditorAuth(
            $container->get(Article::class);
        );
    }
}
```

And configure the service container to use `EditorAuthFactory` to point to
`EditorAuth`. If you use [zend-servicemanager](https://github.com/zendframework/zend-servicemanager)
this is done by the following configuration:

```php
return [    
    'dependencies' => [
        'factories' => [
            // ...
            EditorAuth::class => EditorAuthFactory::class
        ]
    ]
];
```
