<?php
namespace Phalcon\Db\Result;

use Phalcon\Db\Result\Pdo as ResultPdo;

class PdoSqlsrv extends ResultPdo
{
    /**
     * Last number of rows from PDO statement
     *
     * @var boolean
     */
    protected $_rowCount = false;

    /**
     * Gets number of rows returned by a resultset. This method does not work
     * for SELECT statements withou using SCROLL cursors
     * <code>
     * $result = $connection->query("SELECT * FROM robots ORDER BY name");
     * echo 'There are ', $result->numRows(), ' rows in the resultset';
     * </code>.
     *
     * @return int
     */
    public function numRows()
    {
        $rowCount = $this->_rowCount;
        if(!$this->isRowCountValid($rowCount)) {
            $rowCount = $this->_pdoStatement->rowCount();
            if(!$this->isRowCountValid($rowCount)) {
                $sqlStatement = $this->_sqlStatement;
                $connection = $this->_connection;
                if(strpos($sqlStatement, 'SELECT COUNT(*)') === false) {
                    $matches = null;
                    if(strripos($sqlStatement, 'OFFSET') === false && stripos($sqlStatement, 'ORDER BY') > 0) {
                        $sqlStatement .= ' OFFSET 0 ROWS';
                    }
                    if(preg_match("/^SELECT\\s+(.*)/i", $sqlStatement, $matches)) {
                        $result = $connection->query(
                            'SELECT COUNT(*) [numrows] FROM (SELECT ' . $matches[1] . ') as temp_table',
                            $this->_bindParams,
                            $this->_bindTypes
                        );
                        if($result) {
                            $row = $result->fetch();
                            if(is_object($row)) {
                                $rowCount = $row->numrows;
                            } else {
                                if(isset($row['numrows']))
                                    $rowCount = $row['numrows'];
                                else
                                    $rowCount = $row[0];
                            }
                        }
                    }
                } else {
                    $rowCount = 1;
                }
            }
            $this->_rowCount = $rowCount;
        }
        return $rowCount;
    }

    private function isRowCountValid($rowCount)
    {
        if($rowCount === false || $rowCount == -1)
            return false;
        return true;
    }
}
