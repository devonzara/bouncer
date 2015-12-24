<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Permission;

use Illuminate\Database\Eloquent\Model;

class RemovesPermission
{
    /**
     * The model from which to remove a permission.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $model;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string  $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Remove the given permission from the model.
     *
     * @param  mixed  $permissions
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $entity
     * @return bool
     */
    public function to($permissions, $entity = null)
    {
        if ( ! $model = $this->getModel()) {
            return false;
        }

        if ($ids = $this->getPermissionIds($permissions, $entity)) {
            $model->permissions()->detach($ids);
        }

        return true;
    }

    /**
     * Get the model from which to remove the permissions.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getModel()
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return Models::role()->where('name', $this->model)->first();
    }

    /**
     * Get the IDs of the provided permissions.
     *
     * @param  mixed  $permissions
     * @param  \ELoquent\Database\Eloquent\Model|string|null  $model
     * @return array|int
     */
    protected function getPermissionIds($permissions, $model)
    {
        if ( ! is_null($model)) {
            return $this->getModelPermissionId($permissions, $model);
        }

        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return array_merge(
            $this->filterNumericPermissions($permissions),
            $this->getPermissionIdsFromModels($permissions),
            $this->getPermissionIdsFromStrings($permissions)
        );
    }

    /**
     * Get the permission ID for the given model.
     *
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return int|null
     */
    protected function getModelPermissionId($permission, $model)
    {
        $model = $model instanceof Model ? $model : new $model;

        return Models::permission()->where('name', $permission)->forModel($model, true)->value('id');
    }

    /**
     * Filter the provided permissions to the ones that are numeric.
     *
     * @param  array  $permissions
     * @return array
     */
    protected function filterNumericPermissions(array $permissions)
    {
        return array_filter($permissions, 'is_int');
    }

    /**
     * Get the Permission IDs from the models present in the given array.
     *
     * @param  array  $permissions
     * @return array
     */
    protected function getPermissionIdsFromModels(array $permissions)
    {
        $ids = [];

        foreach ($permissions as $permission) {
            if ($permission instanceof Permission) {
                $ids[] = $permission->getKey();
            }
        }

        return $ids;
    }

    /**
     * Get the permission IDs from the names present in the given array.
     *
     * @param  array  $permissions
     * @return array
     */
    protected function getPermissionIdsFromStrings(array $permissions)
    {
        $names = array_filter($permissions, 'is_string');

        if ( ! count($names)) {
            return [];
        }

        return Models::permission()->whereIn('name', $names)->lists('id')->all();
    }
}
