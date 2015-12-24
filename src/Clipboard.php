<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class Clipboard
{
    use HandlesAuthorization;

    /**
     * Register the clipboard at the given gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function registerAt(Gate $gate)
    {
        $gate->before(function ($user, $permission, $model = null, $additional = null) {
            if ( ! is_null($additional)) {
                return;
            }

            if ($id = $this->checkGetId($user, $permission, $model)) {
                return $this->allow('Bouncer granted permission via permission #'.$id);
            }
        });
    }

    /**
     * Determine if the given user has the given permission.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function check(Model $user, $permission, $model = null)
    {
        return (bool) $this->checkGetId($user, $permission, $model);
    }

    /**
     * Determine if the given user has the given permission and return the permission ID.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return int|bool
     */
    protected function checkGetId(Model $user, $permission, $model = null)
    {
        $permissions = $this->getPermissions($user)->toBase()->lists('identifier', 'id');

        $requested = $this->compilePermissionIdentifiers($permission, $model);

        foreach ($permissions as $id => $permission) {
            if (in_array($permission, $requested)) {
                return $id;
            }
        }

        return false;
    }

    /**
     * Check if a user has the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  array|string  $roles
     * @param  string  $boolean
     * @return bool
     */
    public function checkRole(Model $user, $roles, $boolean = 'or')
    {
        $available = $this->getRoles($user)->intersect($roles);

        if ($boolean == 'or') {
            return $available->count() > 0;
        }
        elseif ($boolean === 'not') {
            return $available->count() === 0;
        }

        return $available->count() == count((array) $roles);
    }

    /**
     * Compile a list of permission identifiers that match the provided parameters.
     *
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function compilePermissionIdentifiers($permission, $model)
    {
        if (is_null($model)) {
            return [strtolower($permission)];
        }

        return $this->compileModelPermissionIdentifiers($permission, $model);
    }

    /**
     * Compile a list of permission identifiers that match the given model.
     *
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function compileModelPermissionIdentifiers($permission, $model)
    {
        $model = $model instanceof Model ? $model : new $model;

        $identifier = strtolower($permission.'-'.$model->getMorphClass());

        if ( ! $model->exists) {
            return [$identifier];
        }

        return [$identifier, $identifier.'-'.$model->getKey()];
    }

    /**
     * Get the given user's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $user)
    {
        return $user->roles()->lists('name');
    }

    /**
     * Get a list of the user's permissions.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions(Model $user)
    {
        $query = Models::permission()->whereHas('roles', $this->getRoleUsersConstraint($user));

        return $query->orWhereHas('users', $this->getUserConstraint($user))->get();
    }

    /**
     * Constrain a roles query by the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Closure
     */
    protected function getRoleUsersConstraint(Model $user)
    {
        return function ($query) use ($user) {
            $query->whereHas('users', $this->getUserConstraint($user));
        };
    }

    /**
     * Constrain a related query to the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Closure
     */
    protected function getUserConstraint(Model $user)
    {
        return function ($query) use ($user) {
            $column = "{$user->getTable()}.{$user->getKeyName()}";

            $query->where($column, $user->getKey());
        };
    }
}
