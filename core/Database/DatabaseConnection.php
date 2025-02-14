<?php
namespace ImpressCMS\Core\Database;

use Aura\Sql\ExtendedPdo;
use BadMethodCallException;
use ImpressCMS\Core\Event;
use PDOStatement;

/**
 * Database connection
 *
 * @copyright   The ImpressCMS Project <http://www.impresscms.org>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package    ICMS\Database
 *
 * @method mixed|PDOStatement query(string $query, ...$fetch) Executes an SQL statement and returns a result set as an SQL statement object
 */
class DatabaseConnection extends ExtendedPdo implements DatabaseConnectionInterface, LegacyDatabaseConnectionInterface
{
	/**
	 * Database prefix
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Last row count
	 *
	 * @var int
	 */
	protected $lastRowCount = 0;

	/**
	 *  Safely escape the string, but strips the outer quotes
	 *
	 * This is a legacy method and not part of PDO and must be declared in any class that implements this interface
	 *
	 * @param string $string
	 * @return    string
	 *@see DatabaseConnectionInterface::escape()
	 */
	public function escape($string)
	{
		return substr($this->quote($string), 1, -1);
	}

	public function __call($name, $arguments)
	{
		if ($name === 'query') {
			if (func_num_args() === 3) {
				return $this->queryF($arguments[0], $arguments[1], $arguments[2]);
			}

			return call_user_func_array(['parent', 'query'], func_get_args());
		}

		throw new BadMethodCallException($name);
	}

	/**
	 * @inheritDoc
	 */
	public function queryF($sql, $limit = 0, $start = 0)
	{
		if (!empty($limit) && is_numeric($limit)) {
			$sql .= ' LIMIT ' . ((int)$start) . ', ' . ((int)$limit);
		}
		$result = $this->perform($sql);
		if ($result) {
			$this->lastRowCount = $result->rowCount();
			Event::trigger('icms_db_IConnection', 'execute', $this, array('sql' => $sql, 'errorno' => null, 'error' => null));
		} else {
			$this->lastRowCount = null;
			$errorinfo = $this->errorInfo();
			Event::trigger('icms_db_IConnection', 'execute', $this, array('sql' => $sql, 'errorno' => $errorinfo[1], 'error' => $errorinfo[2]));
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated setLogger method does nothing is deprecated.
	 */
	public function setLogger($logger)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function setPrefix($value)
	{
		$this->prefix = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function prefix($tablename = '')
	{
		return $tablename ? $this->prefix . '_' . $tablename : $this->prefix;
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated genId will be removed in near future
	 */
	public function genId($sequence)
	{
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchRow($result)
	{
		return ($result instanceof PDOStatement) ? $result->fetch(self::FETCH_NUM) : false;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchArray($result)
	{
		return ($result instanceof PDOStatement) ? $result->fetch(self::FETCH_ASSOC) : false;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchBoth($result)
	{
		return ($result instanceof PDOStatement) ? $result->fetch(self::FETCH_BOTH) : false;
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated Use lastInsertId instead of getInsertId.
	 */
	public function getInsertId()
	{
		return parent::lastInsertId();
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated getRowsNum will be removed in 2.1
	 */
	public function getRowsNum($result)
	{
		return $result ? $result->rowCount() : 0;
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated getAffectedRows will be removed in 2.1
	 */
	public function getAffectedRows()
	{
		return $this->lastRowCount;
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated close method is replaced with disconnect.
	 */
	public function close()
	{
		return parent::disconnect();
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated freeRecordSet method will be removed in 2.1
	 */
	public function freeRecordSet($result)
	{
		return ($result instanceof PDOStatement) && $result->closeCursor();
	}

	/**
	 * @inheritDoc
	 */
	public function error()
	{
		$error = parent::errorInfo();
		if (!$error) {
			return null;
		}
		return $error[1];
	}

	/**
	 * @inheritDoc
	 */
	public function errno()
	{
		return parent::errorCode();
	}

	/**
	 * @inheritDoc
	 *
	 * @deprecated Use quote instead.
	 */
	public function quoteString($str)
	{
		return parent::quote($str);
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldName($result, $offset)
	{
		return $result ? $result->getColumnMeta($offset)['name'] : false;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldType($result, $offset)
	{
		return $result ? $result->getColumnMeta($offset)['mysql:decl_type'] : false;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldsNum($result)
	{
		return $result ? $result->columnCount() : false;
	}

	/**
	 * Gets server version
	 *
	 * @return string
	 */
	public function getServerVersion() {
		return $this->fetchCol('SELECT version();')[0];
	}
}
