<?php

namespace Silber\Bouncer\Database;

use Illuminate\Container\Container;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Conductors\ChecksRole;
use Silber\Bouncer\Conductors\AssignsRole;
use Silber\Bouncer\Conductors\RemovesRole;
use Silber\Bouncer\Conductors\GivesPermission;
use Silber\Bouncer\Conductors\RemovesPermission;
use Silber\Bouncer\Database\Constraints\Roles as RolesConstraint;
use Silber\Bouncer\Database\Constraints\Permissions as PermissionsConstraint;

trait HasRolesAndPermissions
{
    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Models::classname(Role::class));
    }

    /**
     * The Permissions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Models::classname(Permission::class));
    }

    /**
     * Get all of the user's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions()
    {
        return $this->getClipboardInstance()->getPermissions($this);
    }

    /**
     * Give permissions to the user.
     *
     * @param  mixed  $permission
     * @param  mixed|null  $model
     * @return $this
     */
    public function allow($permission, $model = null)
    {
        (new GivesPermission($this))->to($permission, $model);

        return $this;
    }

    /**
     * Remove permissions from the user.
     *
     * @param  mixed  $permission
     * @param  mixed|null  $model
     * @return $this
     */
    public function disallow($permission, $model = null)
    {
        (new RemovesPermission($this))->to($permission, $model);

        return $this;
    }

    /**
     * Assign the given role to the user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return $this
     */
    public function assign($role)
    {
        (new AssignsRole($role))->to($this);

        return $this;
    }

    /**
     * Retract the given role from the user.
     *
     * @param  \Silber\Bouncer\Database\Role|string  $role
     * @return $this
     */
    public function retract($role)
    {
        (new RemovesRole($role))->from($this);

        return $this;
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function is($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'or');
    }

    /**
     * Check if the user has none of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function isNot($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'not');
    }

    /**
     * Check if the user has all of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function isAll($role)
    {
        $roles = func_get_args();

        $clipboard = $this->getClipboardInstance();

        return $clipboard->checkRole($this, $roles, 'and');
    }

    /**
     * Constrain the given query by the provided permission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return void
     */
    public function scopeWhereCan($query, $permission, $model = null)
    {
        (new PermissionsConstraint)->constrainUsers($query, $permission, $model);
    }

    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIs($query, $role)
    {
        $constrainer = new RolesConstraint;

        $params = array_slice(func_get_args(), 1);

        array_unshift($params, $query);

        call_user_func_array([$constrainer, 'constrainWhereIs'], $params);
    }

    /**
     * Constrain the given query by all provided roles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIsAll($query, $role)
    {
        $constrainer = new RolesConstraint;

        $params = array_slice(func_get_args(), 1);

        array_unshift($params, $query);

        call_user_func_array([$constrainer, 'constrainWhereIsAll'], $params);
    }

    /**
     * Get an instance of the bouncer's clipboard.
     *
     * @return \Silber\Bouncer\Clipboard
     */
    protected function getClipboardInstance()
    {
        $container = Container::getInstance() ?: new Container;

        return $container->make(Clipboard::class);
    }
}
