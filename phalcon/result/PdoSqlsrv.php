<?php
namespace Phalcon\Db\Result;

use Pdo as ResultPdo;

class PdoSqlsrv extends ResultPdo
{
    /**
     * Gets number of rows returned by a resultset
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
        if ($rowCount === false) {
            $rowCount = $this->_pdoStatement->rowCount();
            if ($rowCount === false) {
                $rowCount = -1;
            }
            $this->_rowCount = $rowCount;
        }
        return $rowCount;
    }
}
