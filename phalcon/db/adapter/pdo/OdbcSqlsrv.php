<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\Dialect\Sqlsrv as SqlsrvDialect;

class OdbcSqlsrv extends Sqlsrv
{
    protected $_type = 'odbc';

    /**
     * Get SQL Server Version Number
     *
     * @return  null|string
     */
    private function _setSqlVersionNumber()
    {
        $sqlVersion = $this->fetchAll(SqlsrvDialect::getDbVersion(), \Phalcon\Db::FETCH_ASSOC);
        if($sqlVersion) {
            $sqlVersionArr = explode(".", $sqlVersion[0]["version_number"]);
            array_pop($sqlVersionArr);
            SqlsrvDialect::setDbVersion(implode(".", $sqlVersionArr));
        }
    }
}
