<?php namespace Laravel\Session;

use Laravel\Database\Connection;

class Database extends Driver implements Sweeper {

	/**
	 * The database connection.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * The database table to which the sessions should be written.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Create a new database session driver.
	 *
	 * @param  Connection  $connection
	 * @param  string      $table
	 * @return void
	 */
	public function __construct(Connection $connection, $table)
	{
		$this->table = $table;
		$this->connection = $connection;
	}

	/**
	 * Load a session by ID.
	 *
	 * The session will be retrieved from persistant storage and returned as an array.
	 * The array contains the session ID, last activity UNIX timestamp, and session data.
	 *
	 * @param  string  $id
	 * @return array
	 */
	protected function load($id)
	{
		$session = $this->table()->find($id);

		if ( ! is_null($session))
		{
			return array(
				'id'            => $session->id,
				'last_activity' => $session->last_activity,
				'data'          => unserialize($session->data)
			);
		}
	}

	/**
	 * Save the session to persistant storage.
	 *
	 * @return void
	 */
	protected function save()
	{
		$this->delete($this->session['id']);

		$this->table()->insert(array(
			'id'            => $this->session['id'], 
			'last_activity' => $this->session['last_activity'], 
			'data'          => serialize($this->session['data'])
		));
	}

	/**
	 * Delete the session from persistant storage.
	 *
	 * @return void
	 */
	protected function delete()
	{
		$this->table()->delete($this->session['id']);
	}

	/**
	 * Delete all expired sessions from persistant storage.
	 *
	 * @param  int   $expiration
	 * @return void
	 */
	public function sweep($expiration)
	{
		$this->table()->where('last_activity', '<', $expiration)->delete();
	}

	/**
	 * Get a session database query.
	 *
	 * @return Query
	 */
	private function table()
	{
		return $this->connection->table($this->table);		
	}
	
}