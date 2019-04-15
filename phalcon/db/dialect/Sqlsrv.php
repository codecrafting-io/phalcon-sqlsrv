<?php
namespace Phalcon\Db\Dialect;

use Phalcon\Db\Column;
use Phalcon\Db\Dialect;
use Phalcon\Db\Exception;

class Sqlsrv extends Dialect
{
    const MIN_DB_VERSION = "11.0.2100";

    //SQL Server minimal version support: 11.0.2100 - 2012
    private static $dbVersion = self::MIN_DB_VERSION;

    /**
     * Gets the column name in MsSQL.
     *
     * @param mixed $column
     *
     * @return string
     */
    public function getColumnDefinition(\Phalcon\Db\ColumnInterface $column)
    {
        $columnSql = '';
        $type   = $column->getType();
        $size   = $column->getSize();
        $scale  = $column->getScale();

        if (is_string($type)) {
            $columnSql .= $type;
            $type = $column->getTypeReference();
        }

        switch ($type) {
            case Column::TYPE_INTEGER:
                if (empty($columnSql)) {
                    $columnSql .= 'INT';
                }
                break;

            case Column::TYPE_DATE:
                if (empty($columnSql)) {
                    $columnSql .= 'DATE';
                }
                break;

            case Column::TYPE_VARCHAR:
                if (empty($columnSql)) {
                    $columnSql .= 'NVARCHAR';
                }
                if($size > 0)  {
                    $columnSql .= '('.$size.')';
                }
                break;

            case Column::TYPE_DECIMAL:
                if (empty($columnSql)) {
                    $columnSql .= 'DECIMAL';
                }
                if ($size) {
                    $columnSql .= '('.$size;
                    if ($scale) {
                        $columnSql .= ','.$scale.')';
                    } else {
                        $columnSql .= ')';
                    }
                }
                break;

            case Column::TYPE_DATETIME:
                if (empty($columnSql)) {
                    $columnSql .= 'DATETIME2';
                }
                if($size > 0)  {
                    $columnSql .= '('.$size.')';
                }
                else {
                    $columnSql .= '(3)';
                }
                break;

            case Column::TYPE_TIMESTAMP:
                if (empty($columnSql)) {
                    $columnSql .= 'TIMESTAMP';
                }
                break;

            case Column::TYPE_CHAR:
                if (empty($columnSql)) {
                    $columnSql .= 'CHAR';
                }
                if($size > 0)  {
                    $columnSql .= '('.$size.')';
                }
                break;

            case Column::TYPE_TEXT:
                if (empty($columnSql)) {
                    $columnSql .= 'NVARCHAR(MAX)';
                }
                break;

            case Column::TYPE_FLOAT:
                if (empty($columnSql)) {
                    $columnSql .= 'FLOAT';
                }
                break;

            case Column::TYPE_DOUBLE:
                if (empty($columnSql)) {
                    $columnSql .= 'NUMERIC';
                }
                if ($size) {
                    $columnSql .= '('.$size;
                    if ($scale) {
                        $columnSql .= ','.$scale.')';
                    } else {
                        $columnSql .= ')';
                    }
                }
                break;

            case Column::TYPE_BIGINTEGER:
                if (empty($columnSql)) {
                    $columnSql .= 'BIGINT';
                }
                break;

            case Column::TYPE_JSON:
                if (empty($columnSql)) {
                    //JSON datatype only is present on SQL Server 2014 or above
                    if(version_compare(self::$dbVersion, "12.0.2254") >= 0) {
                        $columnSql .= 'JSON';
                    }
                    else {
                        $columnSql .= 'NVARCHAR(MAX)';
                    }
                }
                break;

            case Column::TYPE_BOOLEAN:
                if (empty($columnSql)) {
                    $columnSql .= 'BIT';
                }
                break;

            case Column::TYPE_TINYBLOB:
                if (empty($columnSql)) {
                    $columnSql .= 'VARBINARY(255)';
                }
                break;

            case Column::TYPE_BLOB:
            case Column::TYPE_MEDIUMBLOB:
            case Column::TYPE_LONGBLOB:
            case Column::TYPE_JSONB:
                if (empty($columnSql)) {
                    $columnSql .= 'VARBINARY(MAX)';
                }
                break;

            default:
                if (empty($columnSql)) {
                    throw new Exception('Unrecognized SQL Server data type at column '.$column->getName());
                }
                $typeValues = $column->getTypeValues();
                if (!empty($typeValues)) {
                    if (is_array($typeValues)) {
                        $valueSql = '';
                        foreach ($typeValues as $value) {
                            $valueSql .= "'".str_replace("\\'","''",addcslashes($defaultValue, "\'"))."', ";
                        }
                        $columnSql .= "(".substr($valueSql, 0, -2).")";
                    } else {
                        $columnSql .= "('".str_replace("\\'","''",addcslashes($defaultValue, "\'"))."')";
                    }
                }
                break;
        }

        return $columnSql;
    }

    /**
     * Generates the SQL for LIMIT clause
     * <code>
     * $sql = $dialect->limit('SELECT * FROM robots', 10);
     * echo $sql; // SELECT * FROM robots LIMIT 10
     * $sql = $dialect->limit('SELECT * FROM robots', [10, 50]);
     * echo $sql; // SELECT * FROM robots OFFSET 10 ROWS FETCH NEXT 50 ROWS ONLY
     * </code>.
     *
     * @param string $sqlQuery
     * @param mixed  $number
     * @return string
     */
    public function limit($sqlQuery, $number)
    {
        $offset = 0;
        if (is_array($number)) {
            if (isset($number[1]) && strlen($number[1])) {
                $offset = $number[1];
            }
            $number = $number[0];
        }
        if (strpos($sqlQuery, 'ORDER BY') === false) {
            $sqlQuery .= ' ORDER BY (SELECT 0)';
        }
        return $sqlQuery." OFFSET {$offset} ROWS FETCH NEXT {$number} ROWS ONLY";
    }

    /**
     * Returns a SQL modified with a FOR UPDATE clause.
     *
     * <code>
     * $sql = $dialect->forUpdate('SELECT * FROM robots');
     * echo $sql; // SELECT * FROM robots WITH (UPDLOCK)
     * </code>
     */
    public function forUpdate($sqlQuery)
    {
        return $sqlQuery . ' WITH (UPDLOCK) ';
    }

    /**
     * Returns a SQL modified with a LOCK IN SHARE MODE clause.
     *
     * <code>
     * $sql = $dialect->sharedLock('SELECT * FROM robots');
     * echo $sql; // SELECT * FROM robots WITH (NOLOCK)
     * </code>
     */
    public function sharedLock($sqlQuery)
    {
        return $sqlQuery . ' WITH (NOLOCK) ';
    }

    /**
     * Prepares table for this RDBMS
     *
     * @param string $table
     * @param string $schema
     * @param string $alias
     * @param string $escapeChar
     *
     * @return string
     */
    protected function prepareTable($table, $schema = null, $alias = null, $escapeChar = null)
    {
        /**
         * Schema
         */
        $table = "[" . $table . "]";

        /**
         * Schema
         */
        if ($schema != "") {
            $table = "[" . $schema . "]." . $table;
        }

        /**
         * Alias
         */
        if ($alias != "") {
            $table .= " AS [" . $alias . "]";
        }

        return $table;
    }

    /**
     * Generates SQL to add a column to a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $column
     *
     * @return string
     */
    public function addColumn($tableName, $schemaName, \Phalcon\Db\ColumnInterface $column)
    {

        if (!is_object($column)) {
            throw new Exception("Column definition must be an object implement Phalcon\\Db\\ColumnInterface");
        }

        $sql = 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' ADD [' . $column->getName() . '] ' . $this->getColumnDefinition($column);

        if ($column->hasDefault()) {
            $sql .= ' DEFAULT ' . $this->_castDefault($column);
        }

        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        }

        if ($column->isAutoIncrement()) {
            $sql .= ' IDENTITY(1,1)';
        }

        /* SQL Server does not support this
        if ($column->isFirst()) {
            $sql .= ' FIRST';
        } else {
            $afterPosition = $column->getAfterPosition();
            if ($afterPosition) {
                $sql .= ' AFTER '.$afterPosition;
            }
        }*/

        return $sql;
    }


    /**
     * Generates SQL to modify a column in a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $column
     * @param mixed  $currentColumn
     *
     * @return string
     */
	public function modifyColumn($tableName, $schemaName, \Phalcon\Db\ColumnInterface $column, \Phalcon\Db\ColumnInterface $currentColumn = NULL)
    {
        if (!is_object($column)) {
            throw new Exception("Column definition must be an object implement Phalcon\\Db\\ColumnInterface");
        }

        $sql = 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' ALTER COLUMN [' . $column->getName() . '] ' . $this->getColumnDefinition($column);
        if ($column->hasDefault()) {
            $sql .= ' DEFAULT '.$this->_castDefault($column);
        }
        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        }
        if ($column->isAutoIncrement()) {
            $sql .= ' IDENTITY(1,1)';
        }

        return $sql;
    }

    /**
     * Generates SQL to delete a column from a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $columnName
     *
     * @return string
     */
    public function dropColumn($tableName, $schemaName, $columnName)
    {
        return 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' DROP COLUMN [' . $columnName . ']';
    }

    /**
     * Generates SQL to add an index to a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $index
     *
     * @return string
     */
	public function addIndex($tableName, $schemaName, \Phalcon\Db\IndexInterface $index)
    {
        if (!is_object($index)) {
            throw new Phalcon\Db\Exception("Index parameter must be an object implement Phalcon\\Db\\IndexInterface");
        }

        $indexType = $index->getType();
        if (!empty($indexType)) {
            $sql = ' CREATE '.$indexType.' INDEX ';
        } else {
            $sql = ' CREATE INDEX ';
        }

        $sql .= ' ['.$index->getName() . '] ON ' . $this->prepareTable($tableName, $schemaName) . ' (' . $this->getColumnList($index->getColumns()) . ')';

        return $sql;
    }

    /**
     * Generates SQL to delete an index from a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $indexName
     *
     * @return string
     */
    public function dropIndex($tableName, $schemaName, $indexName)
    {
        return 'DROP INDEX [' . $indexName . '] ON ' . $this->prepareTable($tableName, $schemaName);
    }

    /**
     * Generates SQL to add the primary key to a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $index
     *
     * @return string
     */
    public function addPrimaryKey($tableName, $schemaName, \Phalcon\Db\IndexInterface $index)
    {
        if (!is_object($index)) {
            throw new Phalcon\Db\Exception("Index parameter must be an object implements with Phalcon\\Db\\IndexInterface");
        }
        return 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' ADD PRIMARY KEY (' . $this->getColumnList($index->getColumns()) . ')';
    }

    /**
     * Generates SQL to delete primary key from a table.
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function dropPrimaryKey($tableName, $schemaName)
    {
        return 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' DROP PRIMARY KEY';
    }

    /**
     * Generates SQL to add an index to a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param mixed  $reference
     *
     * @return string
     */
    public function addForeignKey($tableName, $schemaName, \Phalcon\Db\ReferenceInterface $reference)
    {
        $sql = 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' ADD CONSTRAINT ['.$reference->getName().'] FOREIGN KEY ('.$this->getColumnList($reference->getColumns()).')
        REFERENCES ' . $this->prepareTable($reference->getReferencedTable(), $reference->getReferencedSchema()) . '(' . $this->getColumnList($reference->getReferencedColumns()) . ')';

        $onDelete = $reference->getOnDelete();
        if (!empty($onDelete)) {
            $sql .= ' ON DELETE '.$onDelete;
        }
        $onUpdate = $reference->getOnUpdate();
        if (!empty($onUpdate)) {
            $sql .= ' ON UPDATE '.$onUpdate;
        }
        return $sql;
    }
    /**
     * Generates SQL to delete a foreign key from a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param string $referenceName
     *
     * @return string
     */
    public function dropForeignKey($tableName, $schemaName, $referenceName)
    {
        return 'ALTER TABLE '.$this->prepareTable($tableName, $schemaName).' DROP FOREIGN KEY ['.$referenceName.']';
    }

    /**
     * Generates SQL to create a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param array  $definition
     *
     * @return string
     */
    public function createTable($tableName, $schemaName, array $definition)
    {
        if (isset($definition['columns']) === false) {
            throw new Exception("The index 'columns' is required in the definition array");
        }
        $table = $this->prepareTable($tableName, $schemaName);
        $temporary = false;
        if (isset($definition['options']) === true) {
            $temporary = (bool) $definition['options']['temporary'];
        }
        /*
         * Create a temporary o normal table
         */
        if ($temporary) {
            $sql = 'CREATE TEMPORARY TABLE '.$table." (\n\t";
        } else {
            $sql = 'CREATE TABLE '.$table." (\n\t";
        }
        $createLines = [];
        foreach ($definition['columns'] as $column) {
            $columnLine = '['.$column->getName().'] '.$this->getColumnDefinition($column);
            /*
             * Add a Default clause
             */
            if ($column->hasDefault()) {
                $sql .= ' DEFAULT '.$this->_castDefault($column);
            }

            /*
             * Add a NOT NULL clause
             */
            if ($column->isNotNull()) {
                $columnLine .= ' NOT NULL';
            }
            /*
             * Add an AUTO_INCREMENT clause
             */
            if ($column->isAutoIncrement()) {
                $columnLine .= ' IDENTITY(1,1)';
            }
            /*
             * Mark the column as primary key
             */
            if ($column->isPrimary()) {
                $columnLine .= ' PRIMARY KEY';
            }
            $createLines[] = $columnLine;
        }
        /*
         * Create related indexes
         */
        if (isset($definition['indexes']) === true) {
            foreach ($definition['indexes'] as $index) {
                $indexName = $index->getName();
                $indexType = $index->getType();
                /*
                 * If the index name is primary we add a primary key
                 */
                if ($indexName == 'PRIMARY') {
                    $indexSql = 'PRIMARY KEY ('.$this->getColumnList($index->getColumns()).')';
                } else {
                    if (!empty($indexType)) {
                        $indexSql = $indexType.' KEY ['.$indexName.'] ('.$this->getColumnList($index->getColumns()).')';
                    } else {
                        $indexSql = 'KEY ['.$indexName.'] ('.$this->getColumnList($index->getColumns()).')';
                    }
                }
                $createLines[] = $indexSql;
            }
        }

        /*
         * Create related references
         */
        if (isset($definition['references']) === true) {
            foreach ($definition['references'] as $reference) {
                $referenceSql = 'CONSTRAINT ['.$reference->getName().'] FOREIGN KEY ('.$this->getColumnList($reference->getColumns()).')'
                    .' REFERENCES ['.$reference->getReferencedTable().'] ('.$this->getColumnList($reference->getReferencedColumns()).')';
                $onDelete = $reference->getOnDelete();
                if (!empty($onDelete)) {
                    $referenceSql .= ' ON DELETE '.$onDelete;
                }
                $onUpdate = $reference->getOnUpdate();
                if (!empty($onUpdate)) {
                    $referenceSql .= ' ON UPDATE '.$onUpdate;
                }
                $createLines[] = $referenceSql;
            }
        }
        $sql .= implode(",\n\t", $createLines)."\n)";
        if (isset($definition['options'])) {
            $sql .= ' '.$this->_getTableOptions($definition);
        }
        return $sql;
    }

	/**
	 * Generates SQL to truncate a table
	 */
	public function truncateTable($tableName, $schemaName)
	{
        if($schemaName) {
            $table = $schemaName . "." . $tableName;
        } else {
            $table = $tableName;
        }
		return "TRUNCATE TABLE " . $table;
	}

    /**
     * Generates SQL to drop a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param bool   $ifExists
     *
     * @return string
     */
    public function dropTable($tableName, $schemaName = null, $ifExists = true)
    {
        $table = $this->prepareTable($tableName, $schemaName);
        if ($ifExists) {
            $sql = 'DROP TABLE IF EXISTS '.$table;
        } else {
            $sql = 'DROP TABLE '.$table;
        }
        return $sql;
    }

    /**
     * Generates SQL to create a view.
     *
     * @param string $viewName
     * @param array  $definition
     * @param string $schemaName
     *
     * @return string
     */
    public function createView($viewName, array $definition, $schemaName = null)
    {
        if (!isset($definition['sql'])) {
            throw new Exception("The index 'sql' is required in the definition array");
        }
        return 'CREATE VIEW '.$this->prepareTable($viewName, $schemaName).' AS '.$definition['sql'];
    }

    /**
     * Generates SQL to drop a view.
     *
     * @param string $viewName
     * @param string $schemaName
     * @param bool   $ifExists
     *
     * @return string
     */
    public function dropView($viewName, $schemaName = null, $ifExists = true)
    {
        $view = $this->prepareTable($viewName, $schemaName);
        if ($ifExists) {
            $sql = 'DROP VIEW IF EXISTS '.$view;
        } else {
            $sql = 'DROP VIEW '.$view;
        }
        return $sql;
    }

    /**
     * Generates SQL checking for the existence of a schema.table
     * <code>
     * echo $dialect->tableExists("posts", "blog");
     * echo $dialect->tableExists("posts");
     * </code>.
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function tableExists($tableName, $schemaName = null)
    {
        $sql = "SELECT COUNT(1) FROM [information_schema].[tables] WHERE [table_name] = '{$tableName}'";
        if ($schemaName) {
            $sql .= " AND [table_schema] = '{$schemaName}'";
        }
        return $sql;
    }

    /**
     * Generates SQL checking for the existence of a schema.view.
     *
     * @param string $viewName
     * @param string $schemaName
     *
     * @return string
     */
    public function viewExists($viewName, $schemaName = null)
    {
        $sql = "SELECT COUNT(1) FROM [information_schema].[views] WHERE [table_name] = '{$viewName}'";
        if ($schemaName) {
            $sql .= " AND [table_schema] = '{$schemaName}'";
        }
        return $sql;
    }

    /**
     * Generates SQL describing a table
     * <code>
     * print_r($dialect->describeColumns("posts"));
     * </code>.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function describeColumns($table, $schema = null)
    {
        $sql = "exec sp_columns @table_name = '{$table}'";
        if ($schema) {
            $sql .= ", @table_owner = '{$schema}'";
        }
        return $sql;
    }

    /**
     * List all tables in database
     * <code>
     * print_r($dialect->listTables("blog"))
     * </code>.
     *
     * @param string $schemaName
     *
     * @return string
     */
    public function listTables($schemaName = null)
    {
        $sql = 'SELECT [table_name] FROM [information_schema].[tables]';
        if ($schemaName) {
            $sql .= " WHERE [table_schema] = '{$schemaName}'";
        }
        return $sql.' ORDER BY [table_name]';
    }

    /**
     * Generates the SQL to list all views of a schema or user.
     *
     * @param string $schemaName
     *
     * @return string
     */
    public function listViews($schemaName = null)
    {
        $sql = 'SELECT [table_name] AS [view_name] FROM [information_schema].[views]';
        if ($schemaName) {
            $sql .= " WHERE [table_schema] = '{$schemaName}'";
        }
        return $sql.' ORDER BY [view_name]';
    }


    /**
     * Generates SQL to query indexes on a table
     *
     * @param   string table
     * @param   string schema
     * @return  string
     * TODO schema not finish yet
     */
    public function describeIndexes($table, $schema = null)
    {
        $sql = "SELECT [t].[name] [table],
                [ind].[name] [key_name],
                CASE WHEN [ind].[is_primary_key] = 1 THEN 'CLUSTERED' ELSE CASE WHEN [ind].[is_unique] = 1 THEN 'UNIQUE' ELSE '' END END [type],
                [ind].[type_desc] [type_desc],
                [ind].[is_unique] [is_unique],
                [col].[name] [column_name]
                FROM [sys].[indexes] [ind]
                INNER JOIN [sys].[index_columns] AS [ic] ON  [ind].[object_id] = [ic].[object_id] AND [ind].[index_id] = [ic].[index_id]
                INNER JOIN [sys].[columns] AS [col] ON [ic].[object_id] = [col].[object_id] AND [ic].[column_id] = [col].[column_id]
                INNER JOIN [sys].[tables] AS [t] ON [ind].[object_id] = [t].[object_id]
                INNER JOIN [sys].[schemas] AS [sch] ON [t].[schema_id] = [sch].[schema_id]
                WHERE [t].[name] = '{$table}'";

        if($schema) {
            $sql .= " AND [sch].[name] = '{$schema}'";
        }
        return $sql." ORDER BY [type_desc], [key_name]";
    }

    /**
     * Generates SQL to query foreign keys on a table
     *
     * @param   string table
     * @param   string schema
     * @return  string
     */
    public function describeReferences($table, $schema = null)
    {
        $sql = "SELECT [fk].[name] AS [name],
                sch1.[name] AS [schema_name],
                tab1.[name] AS [table],
                sch2.[name] AS [referenced_schema],
                tab2.[name] AS [referenced_table],
                col1.[name] AS [column],
                col2.[name] AS [referenced_column],
                [fk].[delete_referential_action_desc] [on_delete],
                [fk].[update_referential_action_desc] [on_update]
                FROM [sys].[foreign_keys] [fk]
                INNER JOIN [sys].[foreign_key_columns] AS [fkc] ON [fk].[object_id] = [fkc].[constraint_object_id]
                INNER JOIN [sys].[tables] AS [tab1] ON [tab1].[object_id] = [fkc].[parent_object_id]
                INNER JOIN [sys].[schemas] AS [sch1] ON [tab1].[schema_id] = [sch1].[schema_id]
                INNER JOIN [sys].[columns] AS [col1] ON [col1].[column_id] = [fkc].[parent_column_id] AND [col1].[object_id] = [tab1].[object_id]
                INNER JOIN [sys].[tables] AS [tab2] ON [tab2].[object_id] = [fkc].[referenced_object_id]
                INNER JOIN [sys].[schemas] AS [sch2] ON [tab2].[schema_id] = [sch2].[schema_id]
                INNER JOIN [sys].[columns] [col2] ON [col2].[column_id] = [fkc].[referenced_column_id] AND [col2].[object_id] = [tab2].[object_id]
                WHERE";
        if ($schema) {
            $sql .= " [sch1].[name] = '{$schema}' AND [tab1].[name] = '{$table}' ORDER BY [schema_name], [table], [name]";
        } else {
            $sql .= " [tab1].[name] = '{$table}' ORDER BY [schema_name], [table], [name]";
        }
        return $sql;
    }

    /**
     * Generate SQL to create a new savepoint
     *
     * @param string $name
     * @return string
     */
	public function createSavepoint($name)
	{
		return 'SAVE TRANSACTION '.$name;
    }

    /**
     * Checks whether the platform supports releasing savepoints. SQL Server automatically releases
     */
    public function supportsReleaseSavepoints()
    {
        return false;
    }

    /**
     *  Generate SQL to rollback a savepoint
     *
     * @param string $name
     * @return string
     */
	public function rollbackSavepoint($name)
	{
		return 'ROLLBACK TRANSACTION '.$name;
	}

    /**
     * Generates the SQL to describe the table creation options.
     *
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    public function tableOptions($table, $schema = null)
    {
        return "";
    }

    /**
     * Generates SQL primary key a table.
     *
     * @param string $table
     * @param string $schema
     * @return string
     */

    public function getPrimaryKey($table, $schema = null)
    {
        $sql = "exec sp_pkeys @table_name = '{$table}'";
        if ($schema) {
            $sql .= ", @table_owner = '{$schema}'";
        }
        return $sql;
    }

    /**
     * Generates SQL to add the table creation options.
     *
     * @param array $definition
     *
     * @return string
     */
    protected function _getTableOptions($definition)
    {
        if (isset($definition['options']) === true) {
            $tableOptions = array();
            $options = $definition['options'];
            /*
             * Check if there is an ENGINE option
             */
            if (isset($options['ENGINE']) === true &&
                $options['ENGINE'] == true) {
                $tableOptions[] = 'ENGINE='.$options['ENGINE'];
            }
            /*
             * Check if there is an AUTO_INCREMENT option
             */
            if (isset($options['AUTO_INCREMENT']) === true &&
                $options['AUTO_INCREMENT'] == true) {
                $tableOptions[] = 'AUTO_INCREMENT='.$options['AUTO_INCREMENT'];
            }
            /*
             * Check if there is a TABLE_COLLATION option
             */
            if (isset($options['TABLE_COLLATION']) === true &&
                $options['TABLE_COLLATION'] == true) {
                $collationParts = explode('_', $options['TABLE_COLLATION']);
                $tableOptions[] = 'DEFAULT CHARSET='.$collationParts[0];
                $tableOptions[] = 'COLLATE='.$options['TABLE_COLLATION'];
            }
            if (count($tableOptions) > 0) {
                return implode(' ', $tableOptions);
            }
        }
        return '';
    }

    /**
     * Cast default values for this RDBMS
     *
     * @param \Phalcon\Db\ColumnInterface $column
     * @return string
     */
    protected function _castDefault(\Phalcon\Db\ColumnInterface $column)
    {
        $defaultValue = $column->getDefault();
        $columnDefinition = $this->getColumnDefinition($column);
        $columnType = $column->getType();

        if (strpos(strtoupper($columnDefinition), "BOOLEAN")) {
            $preparedValue = $defaultValue ? "1" : "0";
        }
        elseif (strpos(strtoupper($defaultValue), "CURRENT_TIMESTAMP") !== false) {
            $preparedValue = "SYSDATETIME()";
        }
        elseif ($columnType === Column::TYPE_INTEGER ||
            $columnType === Column::TYPE_BIGINTEGER ||
            $columnType === Column::TYPE_DECIMAL ||
            $columnType === Column::TYPE_FLOAT ||
            $columnType === Column::TYPE_DOUBLE) {
                $preparedValue = (string) $defaultValue;
        } else {
            $preparedValue = "'" . str_replace("\\'", "''", addcslashes($defaultValue, "\'")) . "'";
        }

        return "((".$preparedValue."))";
    }

    /**
     * Get sql year version for this RDBMS
     * This method is static to force the use of dialect to get SQL Version
     * and dispense the use of public setSqlVersion method on the class
     *
     * @return string
     */
    public static function getDbVersion()
    {
        return "SELECT SUBSTRING(@@VERSION,22,4) AS [version], SERVERPROPERTY('productversion') AS [version_number]";
    }

    /**
     * Set the MSSQL DB version number
     *
     * @param [String] $dbVersion
     * @return void
     */
    public static function setDbVersion(String $dbVersion)
    {
        if($dbVersion) {
            if(version_compare($dbVersion, self::MIN_DB_VERSION) < 0) {
                throw new Exception("This Phalcon SQL Server driver requires a minimal version ".self::MIN_DB_VERSION." of this RDBMS");
            } else {
                self::$dbVersion = $dbVersion;
            }
        }
    }
}
