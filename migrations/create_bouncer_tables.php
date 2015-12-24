<?php

use Silber\Bouncer\Database\Models;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBouncerTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->permissions(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        Schema::create($this->roles(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->integer('role_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['role_id', 'user_id']);

            $table->foreign('role_id')->references('id')->on($this->roles())
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('user_id')->references('id')->on($this->users())
                  ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->integer('permission_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['permission_id', 'user_id']);

            $table->foreign('permission_id')->references('id')->on($this->permissions())
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('user_id')->references('id')->on($this->users())
                  ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->unique(['permission_id', 'role_id']);

            $table->foreign('permission_id')->references('id')->on($this->permissions())
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('role_id')->references('id')->on($this->roles())
                  ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('permission_role');
        Schema::drop('permission_user');
        Schema::drop('role_user');
        Schema::drop($this->roles());
        Schema::drop($this->permissions());
    }

    /**
     * Get the table name for the permission model.
     *
     * @return string
     */
    protected function permissions()
    {
        return Models::permission()->getTable();
    }

    /**
     * Get the table name for the role model.
     *
     * @return string
     */
    protected function roles()
    {
        return Models::role()->getTable();
    }

    /**
     * Get the table name for the user model.
     *
     * @return string
     */
    protected function users()
    {
        return Models::user()->getTable();
    }
}
