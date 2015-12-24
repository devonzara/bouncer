<?php

namespace Silber\Bouncer\Database;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Constraints\Permissions as PermissionsConstraint;

class Role extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The permissions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Models::classname(Permission::class));
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
     * Constrain the given query by the provided permission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return void
     */
    public function scopeWhereCan($query, $permission, $model = null)
    {
        (new PermissionsConstraint)->constrainRoles($query, $permission, $model);
    }
}
