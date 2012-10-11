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

		DB::table('users')->insert(array(
			'email' => 'admin@vectorwyse.com',
			'password' => Hash::make('admin')
		));


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