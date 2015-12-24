<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;

use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class CachedClipboard extends Clipboard
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'silber-bouncer';

    /**
     * The cache store.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param \Illuminate\Contracts\Cache\Store  $cache
     */
    public function __construct(Store $cache)
    {
        $this->setCache($cache);
    }

    /**
     * Set the cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function setCache(Store $cache)
    {
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags($this->tag);
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get the given user's permissions.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions(Model $user)
    {
        $key = $this->tag.'-permissions-'.$user->getKey();

        if ($permissions = $this->cache->get($key)) {
            return $this->deserializePermissions($permissions);
        }

        $permissions = parent::getPermissions($user);

        $this->cache->forever($key, $this->serializePermissions($permissions));

        return $permissions;
    }

    /**
     * Get the given user's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $user)
    {
        $key = $this->tag.'-roles-'.$user->getKey();

        return $this->cache->sear($key, function () use ($user) {
            return parent::getRoles($user);
        });
    }

    /**
     * Clear the cache.
     *
     * @param  null|int|\Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refresh($user = null)
    {
        if ( ! is_null($user)) {
            return $this->refreshFor($user);
        }

        if ($this->cache instanceof TaggedCache) {
            $this->cache->flush();

            return $this;
        }

        foreach (Models::user()->lists('id') as $id) {
            $this->refreshFor($id);
        }

        return $this;
    }

    /**
     * Clear the cache for the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int  $user
     * @return $this
     */
    public function refreshFor($user)
    {
        $id = $user instanceof Model ? $user->getKey() : $user;

        $this->cache->forget($this->tag.'-permissions-'.$id);

        $this->cache->forget($this->tag.'-roles-'.$id);

        return $this;
    }

    /**
     * Deserialize an array of permissions into a collection of models.
     *
     * @param  array  $permissions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function deserializePermissions(array $permissions)
    {
        return Models::permission()->hydrate($permissions);
    }

    /**
     * Serialize a collection of permission models into a plain array.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $permissions
     * @return array
     */
    protected function serializePermissions(Collection $permissions)
    {
        return $permissions->map(function ($permission) {
            return $permission->getAttributes();
        })->all();
    }
}
