<?php

namespace Silber\Bouncer\Database;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Create a new permission for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $name
     * @return static
     */
    public static function createForModel(Model $model, $name)
    {
        return static::forceCreate([
            'name'        => $name,
            'entity_type' => $model->getMorphClass(),
            'entity_id'   => $model->exists ? $model->getKey() : null,
        ]);
    }

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
     * The users relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(Models::classname(User::class));
    }

    /**
     * Get the identifier for this permission.
     *
     * @return string
     */
    final public function getIdentifierAttribute()
    {
        $slug = $this->attributes['name'];

        if ($this->attributes['entity_type']) {
            $slug .= '-'.$this->attributes['entity_type'];
        }

        if ($this->attributes['entity_id']) {
            $slug .= '-'.$this->attributes['entity_id'];
        }

        return strtolower($slug);
    }

    /**
     * Get the permission's "slug" attribute.
     *
     * @return string
     */
    public function getSlugAttribute()
    {
        return $this->getIdentifierAttribute();
    }

    /**
     * Constrain a query to simple permissions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function scopeSimplePermission($query)
    {
        $query->where(function ($query) {
            $query->whereNull('entity_id')->whereNull('entity_type');
        });
    }

    /**
     * Constrain a query to an permission for a specific model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  bool  $strict
     * @return void
     */
    public function scopeForModel($query, $model, $strict = false)
    {
        $model = is_string($model) ? new $model : $model;

        $query->where(function ($query) use ($model, $strict) {
            $query->where('entity_type', $model->getMorphClass());

            $query->where(function ($query) use ($model, $strict) {
                // If the model does not exist, we want to search for blanket permissions
                // that cover all instances of this model. If it does exist, we only
                // want to find blanket permissions if we're not using strict mode.
                if ( ! $model->exists || ! $strict) {
                    $query->whereNull('entity_id');
                }

                if ($model->exists) {
                    $query->orWhere('entity_id', $model->getKey());
                }
            });
        });
    }
}
