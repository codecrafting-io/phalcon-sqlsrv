<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\Dialect\Sqlsrv as SqlsrvDialect;

class OdbcSqlsrv extends Sqlsrv
{
    protected $_type = 'odbc';

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
        if(isset($descriptor["username"])) {
            $username = $descriptor["username"];
            unset($descriptor["username"]);
        } else {
            $username = null;
        }

        /**
         * Check for a password or use null as default
         */
        if(isset($descriptor["password"])) {
            $password = $descriptor["password"];
            unset($descriptor["password"]);
        } else {
            $password = null;
        }

        /**
         * Check if the developer has defined custom options or create one from scratch
         */
        if(isset($descriptor["options"])) {
            $options = $descriptor["options"] + $this->options;
            unset($descriptor["options"]);
        } else {
            $options = $this->options;
        }
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $this->options = $options;

        /**
         * Check if the connection must be persistent
         */
        if(isset($descriptor["persistent"])) {
            if($persistent = $descriptor["persistent"]) {
                $options[\PDO::ATTR_PERSISTENT] = true;
            }
            unset($descriptor["persistent"]);
        }

        /**
         * Remove the dialectClass from the descriptor if any
         */
        if(isset($descriptor["dialectClass"])) {
            unset($descriptor["dialectClass"]);
        }

        /**
         * Remove the charset from the descriptor if any
         * User must use SQLSRV_ATTR_ENCODING
         */
        if(isset($descriptor["charset"])) {
            unset($descriptor["charset"]);
        }

        /**
         * ODBC does not has support for cursors
         */
        if(isset($descriptor["cursorType"])) {
            unset($descriptor["cursorType"]);
        }

        /**
         * Check if the user has defined a custom dsn
         */
         if(isset($descriptor["dsn"])) {
            $dsnAttributes = $descriptor["dsn"];
         } else {
            if(!isset($descriptor["driver"])) {
                 throw new \Phalcon\Db\Exception("ODBC connection dsn must have a driver attribute");
            }
            if(!isset($descriptor["MultipleActiveResultSets"])) {
                $descriptor["MultipleActiveResultSets"] = "false";
            }
            foreach ($descriptor as $key => $value) {
                if($key == "dbname") {
                    $dsnAttributes[] = "database" . "=" . $value;
                } elseif($key == "host") {
                    $dsnAttributes[] = "server" . "=" . $value;
                } else {
                    $dsnAttributes[] = $key . "=" . $value;
                }
            }
            $dsnAttributes = implode(";", $dsnAttributes);
         }

        /**
         * Create the connection using PDO
         */
         $dsn = $this->_type . ":" . $dsnAttributes;
         $this->_pdo = new \PDO($dsn, $username, $password, $options);

        /**
         * Set sql version number for better compatibility
         */
         $sqlVersionNumber = $this->_getSqlVersionNumber();
         if(!empty($this->_pdo)) {
            SqlsrvDialect::setDbVersion($sqlVersionNumber);
         }

         return true;
    }

    /**
     * Get SQL Server Version Number
     *
     * @return  null|string
     */
    private function _getSqlVersionNumber()
    {
        $sqlVersion = $this->fetchAll(SqlsrvDialect::getDbVersion(), \Phalcon\Db::FETCH_ASSOC);
        if($sqlVersion) {
            $sqlVersionArr = explode(".", $sqlVersion[0]["version_number"]);
            array_pop($sqlVersionArr);
            return implode(".", $sqlVersionArr);
        }
        return null;
    }
}
