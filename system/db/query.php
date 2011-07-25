<?php namespace System\DB;

use System\DB;
use System\Str;
use System\Config;

class Query {

	/**
	 * The database connection name.
	 *
	 * @var string
	 */
	private $connection;

	/**
	 * The database connection configuration.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * The SELECT clause.
	 *
	 * @var string
	 */
	public $select;

	/**
	 * Indicates if the query should return distinct results.
	 *
	 * @var bool
	 */
	public $distinct = false;

	/**
	 * The FROM clause.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * The table name.
	 *
	 * @var string
	 */
	public $table;

	/**
	 * The WHERE clause.
	 *
	 * @var string
	 */
	public $where = 'WHERE 1 = 1';

	/**
	 * The ORDER BY columns.
	 *
	 * @var array
	 */
	public $orderings = array();

	/**
	 * The LIMIT value.
	 *
	 * @var int
	 */
	public $limit;

	/**
	 * The OFFSET value.
	 *
	 * @var int
	 */
	public $offset;

	/**
	 * The query value bindings.
	 *
	 * @var array
	 */
	public $bindings = array();

	/**
	 * Create a new query instance.
	 *
	 * @param  string  $table
	 * @param  string  $connection
	 * @return void
	 */
	public function __construct($table, $connection = null)
	{
		$this->connection = (is_null($connection)) ? Config::get('db.default') : $connection;
		$this->from = 'FROM '.$this->wrap($this->table = $table);
	}

	/**
	 * Create a new query instance.
	 *
	 * @param  string  $table
	 * @param  string  $connection
	 * @return Query
	 */
	public static function table($table, $connection = null)
	{
		return new static($table, $connection);
	}

	/**
	 * Force the query to return distinct results.
	 *
	 * @return Query
	 */
	public function distinct()
	{
		$this->distinct = true;
		return $this;
	}

	/**
	 * Add columns to the SELECT clause.
	 *
	 * @param  array  $columns
	 * @return Query
	 */
	public function select($columns = array('*'))
	{
		$this->select = ($this->distinct) ? 'SELECT DISTINCT ' : 'SELECT ';

		$wrapped = array();

		foreach ($columns as $column)
		{
			// If the column name is being aliased, we will need to wrap the column
			// name and its alias in keyword identifiers.
			if (strpos(strtolower($column), ' as ') !== false)
			{
				$segments = explode(' ', $column);

				$wrapped[] = $this->wrap($segments[0]).' AS '.$this->wrap($segments[2]);				
			}
			else
			{
				$wrapped[] = $this->wrap($column);
			}
		}

		$this->select .= implode(', ', $wrapped);

		return $this;
	}

	/**
	 * Set the FROM clause.
	 *
	 * @param  string  $from
	 * @return Query
	 */
	public function from($from)
	{
		$this->from = $from;
		return $this;
	}

	/**
	 * Add a join to the query.
	 *
	 * @param  string  $table
	 * @param  string  $column1
	 * @param  string  $operator
	 * @param  string  $column2
	 * @param  string  $type
	 * @return Query
	 */
	public function join($table, $column1, $operator, $column2, $type = 'INNER')
	{
		$this->from .= ' '.$type.' JOIN '.$this->wrap($table).' ON '.$this->wrap($column1).' '.$operator.' '.$this->wrap($column2);
		return $this;
	}

	/**
	 * Add a left join to the query.
	 *
	 * @param  string  $table
	 * @param  string  $column1
	 * @param  string  $operator
	 * @param  string  $column2
	 * @return Query
	 */
	public function left_join($table, $column1, $operator, $column2)
	{
		return $this->join($table, $column1, $operator, $column2, 'LEFT');
	}

	/**
	 * Add a raw where condition to the query.
	 *
	 * @param  string  $where
	 * @param  array   $bindings
	 * @param  string  $connector
	 * @return Query
	 */
	public function raw_where($where, $bindings = array(), $connector = 'AND')
	{
		$this->where .= ' '.$connector.' '.$where;
		$this->bindings = array_merge($this->bindings, $bindings);

		return $this;
	}

	/**
	 * Add a raw or where condition to the query.
	 *
	 * @param  string  $where
	 * @param  array   $bindings
	 * @return Query
	 */
	public function raw_or_where($where, $bindings = array())
	{
		return $this->raw_where($where, $bindings, 'OR');
	}

	/**
	 * Add a where condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $connector
	 * @return Query
	 */
	public function where($column, $operator, $value, $connector = 'AND')
	{
		$this->where .= ' '.$connector.' '.$this->wrap($column).' '.$operator.' ?';
		$this->bindings[] = $value;

		return $this;
	}

	/**
	 * Add an or where condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return Query
	 */
	public function or_where($column, $operator, $value)
	{
		return $this->where($column, $operator, $value, 'OR');
	}

	/**
	 * Add a where in condition to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  string  $connector
	 * @return Query
	 */
	public function where_in($column, $values, $connector = 'AND')
	{
		$this->where .= ' '.$connector.' '.$this->wrap($column).' IN ('.$this->parameterize($values).')';
		$this->bindings = array_merge($this->bindings, $values);

		return $this;
	}

	/**
	 * Add an or where in condition to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @return Query
	 */
	public function or_where_in($column, $values)
	{
		return $this->where_in($column, $values, 'OR');
	}

	/**
	 * Add a where not in condition to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  string  $connector
	 * @return Query
	 */
	public function where_not_in($column, $values, $connector = 'AND')
	{
		$this->where .= ' '.$connector.' '.$this->wrap($column).' NOT IN ('.$this->parameterize($values).')';
		$this->bindings = array_merge($this->bindings, $values);

		return $this;
	}

	/**
	 * Add an or where not in condition to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @return Query
	 */
	public function or_where_not_in($column, $values)
	{
		return $this->where_not_in($column, $values, 'OR');
	}

	/**
	 * Add a where null condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $connector
	 * @return Query
	 */
	public function where_null($column, $connector = 'AND')
	{
		$this->where .= ' '.$connector.' '.$this->wrap($column).' IS NULL';
		return $this;
	}

	/**
	 * Add an or where null condition to the query.
	 *
	 * @param  string  $column
	 * @return Query
	 */
	public function or_where_null($column)
	{
		return $this->where_null($column, 'OR');
	}

	/**
	 * Add a where not null condition to the query.
	 *
	 * @param  string  $column
	 * @param  string  $connector
	 * @return Query
	 */
	public function where_not_null($column, $connector = 'AND')
	{
		$this->where .= ' '.$connector.' '.$this->wrap($column).' IS NOT NULL';
		return $this;
	}

	/**
	 * Add an or where not null condition to the query.
	 *
	 * @param  string  $column
	 * @return Query
	 */
	public function or_where_not_null($column)
	{
		return $this->where_not_null($column, 'OR');
	}

	/**
	 * Add an ordering to the query.
	 *
	 * @param  string  $column
	 * @param  string  $direction
	 * @return Query
	 */
	public function order_by($column, $direction)
	{
		$this->orderings[] = $this->wrap($column).' '.strtoupper($direction);
		return $this;
	}

	/**
	 * Set the query offset.
	 *
	 * @param  int  $value
	 * @return Query
	 */
	public function skip($value)
	{
		$this->offset = $value;
		return $this;
	}

	/**
	 * Set the query limit.
	 *
	 * @param  int  $value
	 * @return Query
	 */
	public function take($value)
	{
		$this->limit = $value;
		return $this;
	}

	/**
	 * Find a record by the primary key.
	 *
	 * @param  int     $id
	 * @param  array   $columns
	 * @return object
	 */
	public function find($id, $columns = array('*'))
	{
		return $this->where('id', '=', $id)->first($columns);
	}

	/**
	 * Execute the query as a SELECT statement and return the first result.
	 *
	 * @param  array   $columns
	 * @return object
	 */
	public function first($columns = array('*'))
	{

		return (count($results = $this->take(1)->get($columns)) > 0) ? $results[0] : null;
	}

	/**
	 * Execute the query as a SELECT statement.
	 *
	 * @param  array  $columns
	 * @return array
	 */
	public function get($columns = array('*'))
	{
		if (is_null($this->select))
		{
			$this->select($columns);
		}

		$results = DB::query(Query\Compiler::select($this), $this->bindings, $this->connection);

		// Reset the SELECT clause so more queries can be performed using the same instance.
		// This is helpful for performing counts and then getting actual results, such as
		// when paginating results.
		$this->select = null;

		return $results;
	}

	/**
	 * Get an aggregate value.
	 *
	 * @param  string  $aggregate
	 * @param  string  $column
	 * @return mixed
	 */
	private function aggregate($aggregator, $column)
	{
		$this->select = 'SELECT '.$aggregator.'('.$this->wrap($column).') AS '.$this->wrap('aggregate');

		return $this->first()->aggregate;
	}

	/**
	 * Get paginated query results.
	 *
	 * @param  int        $per_page
	 * @return Paginator
	 */
	public function paginate($per_page)
	{
		$total = $this->count();

		$current_page = \System\Paginator::page($total, $per_page);

		return \System\Paginator::make($this->for_page($current_page, $per_page)->get(), $total, $per_page);
	}

	/**
	 * Set the LIMIT and OFFSET values for a given page.
	 *
	 * @param  int    $page
	 * @param  int    $per_page
	 * @return Query
	 */
	public function for_page($page, $per_page)
	{
		return $this->skip(($page - 1) * $per_page)->take($per_page);
	}

	/**
	 * Execute an INSERT statement.
	 *
	 * @param  array  $values
	 * @return bool
	 */
	public function insert($values)
	{
		return DB::query(Query\Compiler::insert($this, $values), array_values($values), $this->connection);
	}

	/**
	 * Execute an INSERT statement and get the insert ID.
	 *
	 * @param  array  $values
	 * @return int
	 */
	public function insert_get_id($values)
	{
		$sql = Query\Compiler::insert($this, $values);

		// Use the RETURNING clause on Postgres instead of the PDO lastInsertID method.
		// The PDO method is a little cumbersome using Postgres.
		if (DB::driver($this->connection) == 'pgsql')
		{
			$query = DB::connection($this->connection)->prepare($sql.' RETURNING '.$this->wrap('id'));

			$query->execute(array_values($values));

			return $query->fetch(\PDO::FETCH_CLASS, 'stdClass')->id;
		}

		DB::query($sql, array_values($values), $this->connection);

		return DB::connection($this->connection)->lastInsertId();
	}

	/**
	 * Execute the query as an UPDATE statement.
	 *
	 * @param  array  $values
	 * @return bool
	 */
	public function update($values)
	{
		return DB::query(Query\Compiler::update($this, $values), array_merge(array_values($values), $this->bindings), $this->connection);
	}

	/**
	 * Execute the query as a DELETE statement.
	 *
	 * @param  int   $id
	 * @return bool
	 */
	public function delete($id = null)
	{
		if ( ! is_null($id))
		{
			$this->where('id', '=', $id);
		}

		return DB::query(Query\Compiler::delete($this), $this->bindings, $this->connection);		
	}

	/**
	 * Wrap a value in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		if (is_null($this->config))
		{
			$connections = Config::get('db.connections');

			$this->config = $connections[$this->connection];		
		}

		if (array_key_exists('wrap', $this->config) and $this->config['wrap'] === false)
		{
			return $value;
		}

		$wrap = (DB::driver($this->connection) == 'mysql') ? '`' : '"';

		return implode('.', array_map(function($segment) use ($wrap) {return ($segment != '*') ? $wrap.$segment.$wrap : $segment;}, explode('.', $value)));
	}

	/**
	 * Create query parameters from an array of values.
	 *
	 * @param  array  $values
	 * @return string
	 */
	public function parameterize($values)
	{
		return implode(', ', array_fill(0, count($values), '?'));
	}

	/**
	 * Magic Method for handling dynamic functions.
	 */
	public function __call($method, $parameters)
	{
		if (strpos($method, 'where_') === 0)
		{
			return Query\Dynamic::build($method, $parameters, $this);
		}

		if (in_array($method, array('count', 'min', 'max', 'avg', 'sum')))
		{
			return ($method == 'count') ? $this->aggregate(strtoupper($method), '*') : $this->aggregate(strtoupper($method), $parameters[0]);
		}

		throw new \Exception("Method [$method] is not defined on the Query class.");
	}

}