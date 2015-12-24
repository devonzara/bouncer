<?php

namespace Silber\Bouncer;

use RuntimeException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Conductors\ChecksRole;
use Silber\Bouncer\Conductors\AssignsRole;
use Silber\Bouncer\Conductors\RemovesRole;
use Silber\Bouncer\Conductors\GivesPermission;
use Silber\Bouncer\Conductors\RemovesPermission;

class Bouncer
{
    /**
     * The bouncer clipboard instance.
     *
     * @var \Silber\Bouncer\CachedClipboard
     */
    protected $clipboard;

    /**
     * The bouncer seeder instance.
     *
     * @var \Silber\Bouncer\Seeder
     */
    protected $seeder;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\CachedClipboard  $clipboard
     * @param \Silber\Bouncer\Seeder  $seeder
     */
    public function __construct(CachedClipboard $clipboard, Seeder $seeder)
    {
        $this->clipboard = $clipboard;
        $this->seeder = $seeder;
    }

    /**
     * Register a seeder callback.
     *
     * @param  \Closure|string  $seeder
     * @return $this
     */
    public function seeder($seeder)
    {
        $this->seeder->register($seeder);

        return $this;
    }

    /**
     * Run the registered seeders.
     *
     * @return $this
     */
    public function seed()
    {
        $this->seeder->run();

        return $this;
    }

    /**
     * Start a chain, to allow the given role a permission.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\GivesPermission
     */
    public function allow($role)
    {
        return new GivesPermission($role);
    }

    /**
     * Start a chain, to disallow the given role a permission.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesPermission
     */
    public function disallow($role)
    {
        return new RemovesPermission($role);
    }

    /**
     * Start a chain, to assign the given role to a user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\AssignsRole
     */
    public function assign($role)
    {
        return new AssignsRole($role);
    }

    /**
     * Start a chain, to retract the given role from a user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return \Silber\Bouncer\Conductors\RemovesRole
     */
    public function retract($role)
    {
        return new RemovesRole($role);
    }

    /**
     * Start a chain, to check if the given user has a certain role.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Silber\Bouncer\Conductors\ChecksRole
     */
    public function is(Model $user)
    {
        return new ChecksRole($user, $this->clipboard);
    }

    /**
     * Use the given cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function cache(Store $cache = null)
    {
        $cache = $cache ?: $this->make(CacheRepository::class)->getStore();

        $this->clipboard->setCache($cache);

        return $this;
    }

    /**
     * Clear the cache.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refresh(Model $user = null)
    {
        $this->clipboard->refresh($user);

        return $this;
    }

    /**
     * Clear the cache for the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refreshFor(Model $user)
    {
        $this->clipboard->refreshFor($user);

        return $this;
    }

    /**
     * Set the access gate instance.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return $this
     */
    public function setGate(Gate $gate)
    {
        $this->gate = $gate;

        return $this;
    }

    /**
     * Get the gate instance.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate|null
     *
     * @throws \RuntimeException
     */
    public function getGate($throw = false)
    {
        if ($this->gate) {
            return $this->gate;
        }

        if ($throw) {
            throw new RuntimeException('The gate instance has not been set.');
        }

        return null;
    }

    /**
     * Determine if the given permission should be granted for the current user.
     *
     * @param  string  $permission
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($permission, $arguments = [])
    {
        return $this->getGate(true)->allows($permission, $arguments);
    }

    /**
     * Determine if the given permission should be denied for the current user.
     *
     * @param  string  $permission
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($permission, $arguments = [])
    {
        return $this->getGate(true)->denies($permission, $arguments);
    }

    /**
     * Get an instance of the role model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Role
     */
    public function role(array $attributes = [])
    {
        return Models::role($attributes);
    }

    /**
     * Get an instance of the permission model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Permission
     */
    public function permission(array $attributes = [])
    {
        return Models::permission($attributes);
    }

    /**
     * Set the model to be used for permissions.
     *
     * @param string  $model
     */
    public static function usePermissionModel($model)
    {
        Models::setPermissionsModel($model);
    }

    /**
     * Set the model to be used for roles.
     *
     * @param string  $model
     */
    public static function useRoleModel($model)
    {
        Models::setRolesModel($model);
    }

    /**
     * Set the model to be used for users.
     *
     * @param string  $model
     */
    public static function useUserModel($model)
    {
        Models::setUsersModel($model);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    protected function make($abstract, array $parameters = [])
    {
        return Container::getInstance()->make($abstract, $parameters);
    }
}
