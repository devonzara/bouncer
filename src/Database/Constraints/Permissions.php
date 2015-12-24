<?php

namespace Silber\Bouncer\Database\Constraints;

use Silber\Bouncer\Database\Models;

class Permissions
{
    /**
     * Constrain the given users query by the provided permission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainUsers($query, $permission, $model = null)
    {
        return $query->where(function ($query) use ($permission, $model) {
            $query->whereHas('permissions', $this->getPermissionConstraint($permission, $model));

            $query->orWhereHas('roles', $this->getRoleConstraint($permission, $model));
        });
    }

    /**
     * Constrain the given roles query by the provided permission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function constrainRoles($query, $permission, $model = null)
    {
        $constraint = $this->getPermissionConstraint($permission, $model);

        return $query->whereHas('permissions', $constraint);
    }

    /**
     * Get the callback to constrain an permissions query to the given permission.
     *
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Closure
     */
    protected function getPermissionConstraint($permission, $model)
    {
        return function ($query) use ($permission, $model) {
            $table = Models::permission()->getTable();

            $query->where("{$table}.name", $permission);

            if ( ! is_null($model)) {
                $query->forModel($model);
            }
        };
    }

    /**
     * Get the callback to constrain a roles query to the given permission.
     *
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Closure
     */
    protected function getRoleConstraint($permission, $model)
    {
        return function ($query) use ($permission, $model) {
            $query->whereHas('permissions', $this->getPermissionConstraint($permission, $model));
        };
    }
}
