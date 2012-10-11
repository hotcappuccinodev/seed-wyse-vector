<?php

class Create_Users {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::create('users', function($table) {
			$table->increments('id');
			$table->string('firstname', 128);
			$table->string('lastname', 128);
			$table->string('email', 255)->unique();
			$table->string('password', 64);
			$table->integer('role');
			$table->boolean('active');
			$table->timestamps();
		});

		$user = new User(array(
			'firstname' => 'Admin',
			'lastname' => 'Admin',
			'email' => 'admin@vectorwyse.com',
			'password' => Hash::make('admin'),
			'active' => true
		));
		$user->save();

	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		//
		Schema::drop('users');
	}

}