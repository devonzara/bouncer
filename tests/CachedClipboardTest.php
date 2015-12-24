<?php

use Silber\Bouncer\CachedClipboard;

use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;

class CachedClipboardTest extends BaseTestCase
{
    public function test_it_caches_permissions()
    {
        $cache = new ArrayStore;

        $bouncer = $this->bouncer($user = User::create())->cache($cache);

        $bouncer->allow($user)->to('ban-users');

        $this->assertEquals(['ban-users'], $this->getAbliities($cache, $user));

        $bouncer->allow($user)->to('create-users');

        $this->assertEquals(['ban-users'], $this->getAbliities($cache, $user));
    }

    public function test_it_caches_roles()
    {
        $cache = new ArrayStore;

        $bouncer = $this->bouncer($user = User::create())->cache($cache);

        $bouncer->assign('editor')->to($user);

        $this->assertEquals(['editor'], $this->getRoles($cache, $user));

        $bouncer->assign('moderator')->to($user);

        $this->assertEquals(['editor'], $this->getRoles($cache, $user));
    }

    public function test_it_can_refresh_the_cache()
    {
        $cache = new ArrayStore;

        $bouncer = $this->bouncer($user = User::create())->cache($cache);

        $bouncer->allow($user)->to('create-posts');
        $bouncer->assign('editor')->to($user);
        $bouncer->allow('editor')->to('delete-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbliities($cache, $user));

        $bouncer->disallow('editor')->to('delete-posts');
        $bouncer->allow('editor')->to('edit-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbliities($cache, $user));

        $bouncer->refresh();

        $this->assertEquals(['create-posts', 'edit-posts'], $this->getAbliities($cache, $user));
    }

    public function test_it_can_refresh_the_cache_only_for_one_user()
    {
        $user1 = User::create();
        $user2 = User::create();

        $cache = new ArrayStore;

        $bouncer = $this->bouncer($user = User::create())->cache($cache);

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(['ban-users'], $this->getAbliities($cache, $user1));
        $this->assertEquals(['ban-users'], $this->getAbliities($cache, $user2));

        $bouncer->disallow('admin')->to('ban-users');
        $bouncer->refreshFor($user1);

        $this->assertEquals([], $this->getAbliities($cache, $user1));
        $this->assertEquals(['ban-users'], $this->getAbliities($cache, $user2));
    }

    /**
     * Get the user's permissions from the given cache instance through the clipboard.
     *
     * @param  \Illuminate\Cache\ArrayStore  $cache
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return array
     */
    protected function getAbliities(ArrayStore $cache, Model $user)
    {
        $clipboard = new CachedClipboard($cache);

        $permissions = $clipboard->getPermissions($user)->lists('name');

        return $permissions->sort()->values()->all();
    }

    /**
     * Get the user's roles from the given cache instance through the clipboard.
     *
     * @param  \Illuminate\Cache\ArrayStore  $cache
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return array
     */
    protected function getRoles(ArrayStore $cache, Model $user)
    {
        $clipboard = new CachedClipboard($cache);

        return $clipboard->getRoles($user)->all();
    }
}
