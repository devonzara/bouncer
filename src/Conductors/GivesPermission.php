<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Permission;

use Exception;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class GivesPermission
{
    /**
     * The model to be given permissions.
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
     * Give the permissions to the model.
     *
     * @param  mixed  $permissions
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return bool
     */
    public function to($permissions, $model = null)
    {
        $ids = $this->getPermissionIds($permissions, $model);

        $this->givePermissions($ids, $this->getModel());

        return true;
    }

    /**
     * Give permissions to the given model.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function givePermissions(array $ids, Model $model)
    {
        $existing = $model->permissions()->whereIn('id', $ids)->lists('id')->all();

        $ids = array_diff($ids, $existing);

        $model->permissions()->attach($ids);
    }

    /**
     * Get the model or create a role.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModel()
    {
        if ($this->model instanceof Model) {
            return $this->model;
        }

        return Models::role()->firstOrCreate(['name' => $this->model]);
    }

    /**
     * Get the IDs of the provided permissions.
     *
     * @param  \Silber\Bouncer\Database\Permission|array|int  $permissions
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return array
     */
    protected function getPermissionIds($permissions, $model)
    {
        if ($permissions instanceof Permission) {
            return [$permissions->getKey()];
        }

        if ( ! is_null($model)) {
            return [$this->getModelPermission($permissions, $model)->getKey()];
        }

        return $this->permissionsByName($permissions)->pluck('id')->all();
    }

    /**
     * Get an permission for the given entity.
     *
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string  $entity
     * @return \Silber\Bouncer\Database\Permission
     */
    protected function getModelPermission($permission, $entity)
    {
        $entity = $this->getEntityInstance($entity);

        $model = Models::permission()->where('name', $permission)->forModel($entity, true)->first();

        return $model ?: Models::permission()->createForModel($entity, $permission);
    }

    /**
     * Get an instance of the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getEntityInstance($model)
    {
        if ( ! $model instanceof Model) {
            return new $model;
        }

        // Creating an permission for a model that doesn't exist gives the user the
        // permission on all instances of that model. If the developer passed in
        // a model instance that does not exist, it is probably a mistake.
        if ( ! $model->exists) {
            throw new InvalidArgumentException(
                'The model does not exist. To allow access to all models, use the class name instead'
            );
        }

        return $model;
    }

    /**
     * Get or create permissions by their name.
     *
     * @param  array|string  $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function permissionsByName($permission)
    {
        $permissions = array_unique(is_array($permission) ? $permission : [$permission]);

        $models = Models::permission()->simplePermission()->whereIn('name', $permissions)->get();

        $created = $this->createMissingPermissions($models, $permissions);

        return $models->merge($created);
    }

    /**
     * Create permissions whose name is not in the given list.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @param  array  $permissions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function createMissingPermissions(Collection $models, array $permissions)
    {
        $missing = array_diff($permissions, $models->pluck('name')->all());

        $created = [];

        foreach ($missing as $permission) {
            $created[] = Models::permission()->create(['name' => $permission]);
        }

        return $created;
    }
}
