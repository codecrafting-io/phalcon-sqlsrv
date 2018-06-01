<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Adapter\Pdo as PdoAdapter;
use Phalcon\Db\Dialect\Sqlsrv as SqlsrvDialect;
use Phalcon\Db\Result\PdoSqlsrv as SqlsrvResult;

class Sqlsrv extends PdoAdapter
{
    /**
     * The PDO connection type
     *
     * @var string
     */
    protected $_type = 'sqlsrv';

    /**
     * The PDOAdapter Dialect class name
     *
     * @var string
     */
    protected $_dialectType = 'Sqlsrv';

    /**
     * THe dsn connection settings
     *
     * @var string
     */
    protected $dsn = '';

    /**
     * The default statement cursors options
     *
     * @var array
     */
    protected $cursor = [];

    /**
     * The PDO connection default options.
     *
     * @var array
     */
    protected $options = [
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL
    ];

    /**
     * This method is automatically called in Phalcon\Db\Adapter\Pdo constructor.
     * Call it when you need to restore a database connection.
     *
     * @param array $descriptor
     *
     * @return bool
     */
    public function connect(array $descriptor = null)
    {
        if(empty($descriptor)) {
            $descriptor = $this->_descriptor;
        }

        /**
         * Check for a username or use null as default
         */
        if(isset($descriptor['username'])) {
            $username = $descriptor['username'];
            unset($descriptor['username']);
        } else {
            $username = null;
        }

        /**
         * Check for a password or use null as default
         */
        if(isset($descriptor['password'])) {
            $password = $descriptor['password'];
            unset($descriptor['password']);
        } else {
            $password = null;
        }

        /**
         * Check if the developer has defined custom options or create one from scratch
         * ERRMODE_EXCEPTION is requried
         */
        if(isset($descriptor['options'])) {
            $options = $descriptor['options'] + $this->options;
            unset($descriptor['options']);
        }
        $this->options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        /**
         * Check if the connection must be persistent
         */
        if(isset($descriptor['persistent'])) {
            if($persistent = $descriptor['persistent']) {
                $options[\PDO::ATTR_PERSISTENT] = true;
            }
            unset($descriptor['persistent']);
        }

        /**
         * Remove the dialectClass from the descriptor if any
         */
        if(isset($descriptor['dialectClass'])) {
            unset($descriptor['dialectClass']);
        }

        /**
         * Remove the charset from the descriptor if any
         * User must use SQLSRV_ATTR_ENCODING
         */
        if(isset($descriptor['charset'])) {
            unset($descriptor['charset']);
        }

        /**
         * Check any cursor options
         */
        if(isset($descriptor['cursor'])) {
            if($descriptor['cursor']) {
                $this->cursor = [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL];
                if(is_string($descriptor['cursor'])) {
                    $this->cursor[\PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = constant('\PDO::' . $descriptor['cursor']);
                }
            }
            unset($descriptor['cursor']);
        }

        /**
         * Create the connection using PDO
         */
         $this->_pdo = new \PDO($this->buildDsn($descriptor), $username, $password, $options);
         $this->_setSqlVersionNumber();
         return true;
    }

    /**
     * Undocumented function
     *
     * @param [type] $config
     * @return void
     */
    public function buildDsn($config)
    {
        if(isset($config['dsn'])) {
            $this->dsn = $config['dsn'];
            return $config['dsn'];
        } else {
            $arguments = ['Server' => $this->buildHostString($config, ',')];
            if (isset($config['driver'])) {
                $arguments['Driver'] = $config['driver'];
            }
            if (isset($config['dbname'])) {
                $arguments['Database'] = $config['dbname'];
            }
            if (isset($config['intent'])) {
                $arguments['ApplicationIntent'] = $config['intent'];
            }
            if (isset($config['pooling']) && $config['pooling'] === false) {
                $arguments['ConnectionPooling'] = (boolval($config['pooling'])) ? '1' : '0';
            }
            if (isset($config['appname'])) {
                $arguments['APP'] = $config['appname'];
            }
            if (isset($config['useADAuth']) && $config['useADAuth'] === true) {
                $arguments['Authentication'] = 'ActiveDirectoryPassword';
            }
            if (isset($config['encrypt'])) {
                $arguments['Encrypt'] = $config['encrypt'];
            }
            if (isset($config['connectionRetryCount'])) {
                $arguments['ConnectRetryCount'] = $config['connectionRetryCount'];
            }
            if (isset($config['connectionRetryInterval'])) {
                $arguments['ConnectRetryInterval'] = $config['connectionRetryInterval'];
            }
            if (isset($config['failover'])) {
                $arguments['Failover_Partner'] = $config['failover'];
            }
            if (isset($config['timeout'])) {
                $arguments['LoginTimeout'] = strval($config['timeout']);
            }
            if (isset($config['trustServerCertificate'])) {
                $arguments['TrustServerCertificate'] = (boolval($config['trustServerCertificate'])) ? '1' : '0';
            }
            if (isset($config['mars'])) {
                $arguments['MultipleActiveResultSets'] = (boolval($config['mars'])) ? 'true' : 'false';
            }
            if (isset($config['quoteIdentifier'])) {
                $arguments['QuotedId'] = (boolval($config['quoteIdentifier'])) ? '1' : '0';
            }
            if (isset($config['trace'])) {
                $arguments['TraceOn'] = (boolval($config['trace'])) ? '1' : '0';
            }
            $this->dsn = $this->buildConnectionString($arguments);
            return  $this->dsn;
        }
    }

    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * Set query statement option cursors
     *
     * @param array $cursor
     * @return void
     */
    public function setCursor(array $cursor)
    {
        $this->cursor = $cursor;
    }

    /**
     * Get query statement option cursors
     *
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Sends SQL statements to the database server returning the success state.
     * Use this method only when the SQL statement sent to the server is returning rows
     *<code>
     * // Querying data
     * $resultset = $connection->query(
     *     "SELECT * FROM robots WHERE type = 'mechanical'"
     * );
     *
     * $resultset = $connection->query(
     *     "SELECT * FROM robots WHERE type = ?",
     *     [
     *         "mechanical",
     *     ]
     * );
     *</code>
     *
     * @param string $sqlStatement
     * @param mixed  $bindParams
     * @param mixed  $bindTypes
     *
     * @return bool|\Phalcon\Db\ResultInterface
     */
    public function query($sqlStatement, $bindParams = null, $bindTypes = null)
    {
        $sqlStatement = str_replace('"', '', $sqlStatement);
        $eventsManager = $this->_eventsManager;

        /**
         * In order to SQL Server return numRows cursors must be used
         */
        $pdo = $this->_pdo;
        $cursorOpt = $this->cursor;
        if (strpos($sqlStatement, 'exec') !== false) {
            $cursorOpt = [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY];
            $sqlStatement = 'SET NOCOUNT ON; ' . $sqlStatement;
        }

        /**
		 * Execute the beforeQuery event if an EventsManager is available
		 */
        if(is_object($eventsManager)) {
			$this->_sqlStatement = $sqlStatement;
			$this->_sqlVariables = $bindParams;
            $this->_sqlBindTypes = $bindTypes;
            if($eventsManager->fire('db:beforeQuery', $this) === false) {
                return false;
            }
        }

        if(is_array($bindParams)) {
            $statement = $pdo->prepare($sqlStatement, $cursorOpt);
            if(is_object($statement)) {
                $statement = $this->executePrepared($statement, $bindParams, $bindTypes);
            }
        } else {
            $statement = $pdo->prepare($sqlStatement, $cursorOpt);
            $statement->execute();
        }

		/**
		 * Execute the afterQuery event if an EventsManager is available
		 */
		if(is_object($statement)) {
			if(is_object($eventsManager)) {
				$eventsManager->fire('db:afterQuery', $this);
            }
            return new SqlsrvResult($this, $statement, $sqlStatement, $bindParams, $bindTypes);
        }
		return $statement;
    }

    /**
     * Escapes a column/table/schema name
     *
     *<code>
     * $escapedTable = $connection->escapeIdentifier(
     *     "robots"
     * );
     *
     * $escapedTable = $connection->escapeIdentifier(
     *     [
     *         "store",
     *         "robots",
     *     ]
     * );
     *</code>
     *
     * @param array|string identifier
     */
    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return '[' . $identifier[0] . '].[' . $identifier[1] . ']';
        }
        return '[' . $identifier . ']';
    }

    /**
     * Returns an array of Phalcon\Db\Column objects describing a table
     * <code>
     * print_r($connection->describeColumns("posts"));
     * </code>.
     *
     * @param string $table
     * @param string $schema
     *
     * @return \Phalcon\Db\Column
     */
    public function describeColumns($table, $schema = null)
    {
        $oldColumn = null;

        /*
         * Get primary keys
         */
        $columns = [];
        $primaryKeys = [];
        foreach ($this->fetchAll($this->_dialect->getPrimaryKey($table, $schema), \Phalcon\Db::FETCH_ASSOC) as $field) {
            $primaryKeys[$field['COLUMN_NAME']] = true;
        }

        foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), \Phalcon\Db::FETCH_ASSOC) as $field) {
            /*
             * By default the bind types is two
             */
            $definition = ['bindType' => Column::BIND_PARAM_STR];

            /*
             * By checking every column type we convert it to a Phalcon\Db\Column
             */
            $autoIncrement = false;
            $columnType = $field['TYPE_NAME'];
            switch ($columnType) {

                /*
                 * Smallint/Bigint/Integers/Int are int
                 */
                case 'int identity':
                case 'tinyint identity':
                case 'smallint identity':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    $autoIncrement = true;
                    break;

                case 'bigint' :
                    $definition['type'] = Column::TYPE_BIGINTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    break;

                case 'decimal':
                case 'money':
                case 'smallmoney':
                    $definition['type'] = Column::TYPE_DECIMAL;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_DECIMAL;
                    break;

                case 'numeric':
                    $definition['type'] = Column::TYPE_DOUBLE;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_DECIMAL;
                    break;

                case 'int':
                case 'tinyint':
                case 'smallint':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    break;

                case 'float':
                    $definition['type'] = Column::TYPE_FLOAT;
                    $definition['isNumeric'] = true;
                    $definition['bindType'] = Column::BIND_PARAM_DECIMAL;
                    break;

                /*
                 * Boolean
                 */
                case 'bit':
                    $definition['type'] = Column::TYPE_BOOLEAN;
                    $definition['bindType'] = Column::BIND_PARAM_BOOL;
                    break;

                /*
                 * Date are dates
                 */
                case 'date':
                    $definition['type'] = Column::TYPE_DATE;
                    break;

                /*
                 * Special type for datetime
                 */
                case 'datetime':
                case 'datetime2':
                case 'smalldatetime':
                    $definition['type'] = Column::TYPE_DATETIME;
                    break;
                /*
                 * Timestamp are dates
                 */
                case 'timestamp':
                    $definition['type'] = Column::TYPE_TIMESTAMP;
                    break;
                /*
                 * Chars are chars
                 */
                case 'char':
                case 'nchar':
                    $definition['type'] = Column::TYPE_CHAR;
                    break;

                case 'varchar':
                case 'nvarchar':
                    $definition['type'] = Column::TYPE_VARCHAR;
                    break;

                /*
                 * Text are varchars
                 */
                case 'text':
                case 'ntext':
                    $definition['type'] = Column::TYPE_TEXT;
                    break;

                /*
                 * blob type
                 */
                case 'varbinary':
                    $definition['type'] = Column::TYPE_BLOB;
                    break;

                /*
                 * json type
                 */
                case 'json':
                    $definition['type'] = Column::TYPE_JSON;
                    break;

                /*
                 * By default is string
                 */
                default:
                    $definition['type'] = Column::TYPE_VARCHAR;
                    break;
            }

            /*
             * If the column type has a parentheses we try to get the column size from it
             */
            $definition['size'] = (int) $field['PRECISION'];
            if (($field['SCALE'] || $field['SCALE'] == '0') && $definition['type'] != Column::TYPE_DATETIME) {
                $definition['scale'] = (int) $field['SCALE'];
            }

            /*
             * Positions
             */
            if (!$oldColumn) {
                $definition['first'] = true;
            } else {
                $definition['after'] = $oldColumn;
            }

            /*
             * Check if the field is primary key
             */
            if (isset($primaryKeys[$field['COLUMN_NAME']])) {
                $definition['primary'] = true;
            }

            /*
             * Check if the column allows null values
             */
            if ($field['NULLABLE'] == 0) {
                $definition['notNull'] = true;
            }

            /*
             * Check if the column is auto increment
             */
            if ($autoIncrement) {
                $definition['autoIncrement'] = true;
            }

            /*
             * Check if the column is default values
             */
            if ($field['COLUMN_DEF'] != null) {
                $definition['default'] = $field['COLUMN_DEF'];
            }

            $columnName = $field['COLUMN_NAME'];
            $columns[] = new Column($columnName, $definition);
            $oldColumn = $columnName;
        }
        return $columns;
    }

    /**
     * Lists table indexes
     *
     * @param	string table
     * @param	string schema
     *
     * @return	\Phalcon\Db\Index[]
     */
    public function describeIndexes($table, $schema = null)
    {
        $indexes = $this->fetchAll($this->_dialect->describeIndexes($table,$schema), \Phalcon\Db::FETCH_ASSOC);
        $columns = [];
        foreach ($indexes as $key => $value) {
            $columns[$value['key_name']][] = $value['column_name'];
        }

        $indexObjects = [];
        foreach ($indexes as $key => $value) {
            if(!isset($indexObjects[$value['key_name']])) {
               $indexObjects[$value['key_name']] = new \Phalcon\Db\Index($value['key_name'], $columns[$value['key_name']], $value['type']);
            }
        }
        return $indexObjects;
    }

    /**
     * Lists table references
     *
     *<code>
     * print_r($connection->describeReferences('robots_parts'));
     *</code>
     *
     * @param	string table
     * @param	string schema
     *
     * @return	\Phalcon\Db\Reference[]
     */
    public function describeReferences($table, $schema = null)
    {
        $references = $this->fetchAll($this->_dialect->describeReferences($table,$schema), \Phalcon\Db::FETCH_ASSOC);
        $columns = [];
        $referencedColumns = [];

        foreach ($references as $key => $value) {
            $columns[$value['name']][] = $value['column'];
            $referencedColumns[$value['name']][] = $value['referenced_column'];
        }
        $referenceObjects = [];
        foreach ($references as $key => $value) {
            if(!isset($referenceObjects[$value['name']])) {
                $referenceObjects[$value['name']] = new \Phalcon\Db\Reference($value['name'],[
                    'schema'            => $value['schema_name'],
                    'referencedSchema'  => $value['referenced_schema'],
                    'referencedTable'   => $value['referenced_table'],
                    'columns'           => $columns[$value['name']],
                    'referencedColumns' => $referencedColumns[$value['name']],
                    'onDelete'          => $value['on_delete'],
                    'onUpdate'          => $value['on_update']
                ]);
            }
        }
        return $referenceObjects;
    }

    /**
     * Build a host string from the given configuration.
     *
     * @param  array  $config
     * @param  string  $separator
     * @return string
     */
    protected function buildHostString(array $config, $separator)
    {
        if (isset($config['port']) && ! empty($config['port'])) {
            return $config['host'].$separator.$config['port'];
        } else {
            return $config['host'];
        }
    }

    /**
     * Build a connection string from the given arguments.
     *
     * @param  string  $driver
     * @param  array  $arguments
     * @return string
     */
    protected function buildConnectionString(array $arguments)
    {
        if($this->_type == 'OdbcSqlsrv' && !isset($arguments['driver'])) {
            throw new \Phalcon\Db\Exception("ODBC connection dsn must have a driver attribute");
        }
        return $this->_type.':'.implode(';', array_map(function ($key) use ($arguments) {
            return sprintf('%s=%s', $key, $arguments[$key]);
        }, array_keys($arguments)));
    }

    /**
     * Set SQL Server Version Number for dialect better compatibility
     *
     * @return  null|string
     */
    protected function _setSqlVersionNumber()
    {
        if($this->_pdo) {
            SqlsrvDialect::setDbVersion($this->_pdo->getAttribute(\PDO::ATTR_SERVER_VERSION));
        }
    }
}
