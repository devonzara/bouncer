<?php

namespace Silber\Bouncer\Database;

use App\User;

class Models
{
    /**
     * Map for the bouncer's models.
     *
     * @var array
     */
    protected static $models = [];

    /**
     * Set the model to be used for permissions.
     *
     * @param string  $model
     */
    public static function setPermissionsModel($model)
    {
        static::$models[Permission::class] = $model;
    }

    /**
     * Set the model to be used for roles.
     *
     * @param string  $model
     */
    public static function setRolesModel($model)
    {
        static::$models[Role::class] = $model;
    }

    /**
     * Set the model to be used for users.
     *
     * @param string  $model
     */
    public static function setUsersModel($model)
    {
        static::$models[User::class] = $model;
    }

    /**
     * Get the classname mapping for the given model.
     *
     * @param  string  $model
     * @return string
     */
    public static function classname($model)
    {
        if (isset(static::$models[$model])) {
            return static::$models[$model];
        }

        return $model;
    }

    /**
     * Get an instance of the permission model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Permission
     */
    public static function permission(array $attributes = [])
    {
        return static::make(Permission::class, $attributes);
    }

    /**
     * Get an instance of the role model.
     *
     * @param  array  $attributes
     * @return \Silber\Bouncer\Database\Role
     */
    public static function role(array $attributes = [])
    {
        return static::make(Role::class, $attributes);
    }

    /**
     * Get an instance of the user model.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function user(array $attributes = [])
    {
        return static::make(User::class, $attributes);
    }

    /**
     * Get an instance of the given model.
     *
     * @param  string  $model
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected static function make($model, array $attributes = [])
    {
        $model = static::classname($model);

        return new $model($attributes);
    }
}
