# Bouncer

This package adds a bouncer at Laravel's access gate.

- [Introduction](#introduction)
- [Installation](#installation)
  - [Enabling cache](#enabling-cache)
- [Usage](#usage)
  - [Creating roles and permissions](#creating-roles-and-permissions)
  - [Assigning roles to a user](#assigning-roles-to-a-user)
  - [Giving a user an permission directly](#giving-a-user-an-permission-directly)
  - [Restricting an permission to a model](#restricting-an-permission-to-a-model)
  - [Retracting a role from a user](#retracting-a-role-from-a-user)
  - [Removing an permission](#removing-an-permission)
  - [Checking a user's roles](#checking-a-users-roles)
  - [Getting all permissions for a user](#getting-all-permissions-for-a-user)
  - [Authorizing users](#authorizing-users)
  - [Refreshing the cache](#refreshing-the-cache)
  - [Seeding roles and permissions](#seeding-roles-and-permissions)
- [Cheat sheet](#cheat-sheet)
- [License](#license)

## Introduction

Bouncer provides a mechanism to handle roles and permissions in [Laravel's ACL](http://laravel.com/docs/5.1/authorization). With an expressive and fluent syntax, it stays out of your way as much as possible: use it when you want, ignore it when you don't.

For a quick, glanceable list of Bouncer's features, check out [the cheat sheet](#cheat-sheet).

Bouncer works well with other permissions you have hard-coded in your own app. Your code always takes precedence: if your code allows an action, the bouncer will not interfere.


Once installed, you can simply tell the bouncer what you want to allow at the gate:

```php
// Give a user the permission to create posts
Bouncer::allow($user)->to('create', Post::class);

// Alternatively, do it through a role
Bouncer::allow('admin')->to('create', Post::class);
Bouncer::assign('admin')->to($user);

// You can also grant an permission only to a specific model
Bouncer::allow($user)->to('edit', $post);
```

When you check permissions at the gate, the bouncer will be consulted first. If he sees an permission that has been granted to the current user (whether directly, or through a role) he'll authorize the check.

## Installation

Simply install the bouncer package with composer:

```
"repositories": [
	{
		"url": "https://github.com/devonzara/bouncer",
		"type": "vcs"
	}
],
"require": {
	"silber/bouncer": "*"
}
```

```
$ composer update silber/bouncer
```

Once the composer installation completes, you can add the service provider and alias the facade. Open `config/app.php`, and make the following changes:

1) Add a new item to the `providers` array:

```php
Silber\Bouncer\BouncerServiceProvider::class
```

2) Add a new item to the `aliases` array:

```php
'Bouncer' => Silber\Bouncer\BouncerFacade::class
```

This part is optional. If you don't want to use the facade, you can skip step 2.

3) Add the bouncer's trait to your user model:

```php
use Silber\Bouncer\Database\HasRolesAndPermissions;

class User extends Model
{
    use HasRolesAndPermissions;
}
```

4) Now, to run the bouncer's migrations, first publish the package's migrations into your app's `migrations` directory, by running the following command:

```
$ php artisan vendor:publish --provider="Silber\Bouncer\BouncerServiceProvider" --tag="migrations"
```

5) Finally, run the migrations:

```
$ php artisan migrate
```

### Enabling cache

All queries executed by the bouncer are cached for the current request. For better performance, you may want to use cross-request caching. To enable cross-request caching, add this to your `AppServiceProvider`'s `boot` method:

```php
Bouncer::cache();
```

> **Warning:** if you enable cross-request caching, you are responsible to refresh the cache whenever you make changes to user's permissions/roles. For how to refresh the cache, read [refreshing the cache](#refreshing-the-cache).

## Usage

Adding roles and permissions to users is made extremely easy. You do not have to create a role or an permission in advance. Simply pass the name of the role/permission, and Bouncer will create it if it doesn't exist.

> **Note:** the examples below all use the `Bouncer` facade. If you don't like facades, you can instead inject an instance of `Silber\Bouncer\Bouncer` into your class.

### Creating roles and permissions

Let's create a role called `admin` and give it the permission to `ban-users` from our site:

```php
Bouncer::allow('admin')->to('ban-users');
```

That's it. Behind the scenes, Bouncer will create both a `Role` model and an `Permission` model for you.

### Assigning roles to a user

To now give the `admin` role to a user, simply tell the bouncer that the given user should be assigned the admin role:

```php
Bouncer::assign('admin')->to($user);
```

Alternatively, you can call the `assign` method directly on the user:

```php
$user->assign('admin');
```

### Giving a user an permission directly

Sometimes you might want to give a user an permission directly, without using a role:

```php
Bouncer::allow($user)->to('ban-users');
```

Here too you can accomplish the same directly off of the user:

```php
$user->allow('ban-users');
```

### Restricting an permission to a model

Sometimes you might want to restrict an permission to a specific model type. Simply pass the model name as a second argument:

```php
Bouncer::allow($user)->to('edit', Post::class);
```

If you want to restrict the permission to a specific model instance, pass in the actual model instead:

```php
Bouncer::allow($user)->to('edit', $post);
```

### Retracting a role from a user

The bouncer can also retract a previously-assigned role from a user:

```php
Bouncer::retract('admin')->from($user);
```

Or do it directly on the user:

```php
$user->retract('admin');
```

### Removing an permission

The bouncer can also remove an permission previously granted to a user:

```php
Bouncer::disallow($user)->to('ban-users');
```

Or directly on the user:

```php
$user->disallow('ban-users');
```

> **Note:** if the user has a role that allows them to `ban-users` they will still have that permission. To disallow it, either remove the permission from the role or retract the role from the user.

If the permission has been granted through a role, tell the bouncer to remove the permission from the role instead:

```php
Bouncer::disallow('admin')->to('ban-users');
```

To remove an permission for a specific model type, pass in its name as a second argument:

```php
Bouncer::disallow($user)->to('delete', Post::class);
```

> **Warning:** if the user has an permission to `delete` a specific `$post` instance, the code above will *not* remove that permission. You will have to remove the permission separately - by passing in the actual `$post` as a second argument - as shown below.

To remove an permission for a specific model instance, pass in the actual model instead:

```php
Bouncer::disallow($user)->to('delete', $post);
```

### Checking a user's roles

> **Note**: Generally speaking, you should not have a need to check roles directly. It is better to allow a role certain permissions, then check for those permissions instead. If what you need is very general, you can create very broad permissions. For example, an `access-dashboard` permission is always better than checking for `admin` or `editor` roles directly. For the rare occasion that you do want to check a role, that functionality is available here.

The bouncer can check if a user has a specific role:

```php
Bouncer::is($user)->a('moderator');
```

If the role you're checking starts with a vowel, you might want to use the `an` alias method:

```php
Bouncer::is($user)->an('admin');
```

For the inverse, you can also check if a user *doesn't* have a specific role:

```php
Bouncer::is($user)->notA('moderator');

Bouncer::is($user)->notAn('admin');
```

You can check if a user has one of many roles:

```php
Bouncer::is($user)->a('moderator', 'editor');
```

You can also check if the user has all of the given roles:

```php
Bouncer::is($user)->all('editor', 'moderator');
```

You can also check if a user has none of the given roles:

```php
Bouncer::is($user)->notAn('editor', 'moderator');
```

These checks can also be done directly on the user:

```php
$user->is('admin');

$user->isNot('admin');

$user->isAll('editor', 'moderator');
```

### Getting all permissions for a user

You can get all permissions for a user directly from the user model:

```php
$permissions = $user->getPermissions();
```

This will return a collection of the user's permissions, including any permissions granted to the user through their roles.

### Authorizing users

Authorizing users is handled directly at [Laravel's `Gate`](http://laravel.com/docs/5.1/authorization#checking-permissions), or on the user model (`$user->can($permission)`).

For convenience, the bouncer class provides two passthrough methods:

```php
Bouncer::allows($permission);
Bouncer::denies($permission);
```

These call directly into the `Gate` class.

### Refreshing the cache

All queries executed by the bouncer are cached for the current request. If you enable [cross-request caching](#enabling-cache), the cache will persist across different requests.

Whenever you need, you can fully refresh the bouncer's cache:

```php
Bouncer::refresh();
```

> **Note:** fully refreshing the cache for all users uses [cache tags](http://laravel.com/docs/5.1/cache#cache-tags) if they're available. Not all cache drivers support this. Refer to [Laravel's documentation](http://laravel.com/docs/5.1/cache#cache-tags) to see if your driver supports cache tags. If your driver does not support cache tags, calling `refresh` might be a little slow, depending on the amount of users in your system.

Alternatively, you can refresh the cache only for a specific user:

```php
Bouncer::refreshFor($user);
```

### Seeding roles and permissions

Depending on your project, you might have a set of roles and permissions that you want to pre-seed when you deploy your application. Bouncer ships with seeding functionality to make this as easy as possible.

First, register your seeding callback in your `AppServiceProvider`'s `boot` method:

```php
Bouncer::seeder(function () {
    Bouncer::allow('admin')->to(['ban-users', 'delete-posts']);
    Bouncer::allow('editor')->to('delete-posts');
});
```

You can also register a seeder class to be used for seeding:

```php
Bouncer::seeder(MySeeder::class);
```

By default, the `seed` method will be used. If you need to, you can specify a different method:

```php
Bouncer::seeder('MySeeder@run');
```

Once you've registered your seeder, you can run the seeds via the included artisan command:

```
$ php artisan bouncer:seed
```

Should you find a need to run the seeds from within your codebase, you can do that too:

```php
Bouncer::seed();
```

Note that it's ok to run the seeds multiple times. If you make a change to your seeder, simply run the seeds again. However, do note that any information that has previously been seeded will *not* be automatically reverted.

## Cheat Sheet

```php
Bouncer::allow($user)->to('ban-users');
Bouncer::allow($user)->to('edit', Post::class);
Bouncer::allow($user)->to('delete', $post);

Bouncer::disallow($user)->to('ban-users');
Bouncer::disallow($user)->to('edit', Post::class);
Bouncer::disallow($user)->to('delete', $post);

Bouncer::allow('admin')->to('ban-users');
Bouncer::disallow('admin')->to('ban-users');

Bouncer::assign('admin')->to($user);
Bouncer::retract('admin')->from($user);

$check = Bouncer::is($user)->a('subscriber');
$check = Bouncer::is($user)->an('admin');
$check = Bouncer::is($user)->notA('subscriber');
$check = Bouncer::is($user)->notAn('admin');
$check = Bouncer::is($user)->a('moderator', 'editor');
$check = Bouncer::is($user)->all('moderator', 'editor');

$check = Bouncer::allows('ban-users');
$check = Bouncer::allows('edit', Post::class);
$check = Bouncer::allows('delete', $post);

$check = Bouncer::denies('ban-users');
$check = Bouncer::denies('edit', Post::class);
$check = Bouncer::denies('delete', $post);

Bouncer::cache();
Bouncer::refresh();
Bouncer::refreshFor($user);

Bouncer::seeder($callback);
Bouncer::seed();
```

Some of this functionality is also available directly on the user model:

```php
$user->allow('ban-users');
$user->allow('edit', Post::class);
$user->allow('delete', $post);

$user->disallow('ban-users');
$user->disallow('edit', Post::class);
$user->disallow('delete', $post);

$user->assign('admin');
$user->retract('admin');

$check = $user->is('subscriber');
$check = $user->is('moderator', 'editor');
$check = $user->isAll('moderator', 'editor');
$check = $user->isNot('subscriber', 'moderator');

$permissions = $user->getPermissions();
```

## License

Bouncer is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
