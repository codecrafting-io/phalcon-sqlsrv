# MS SQL Server Phalcon Implementation

This is a unofficial support for MS SQL Server for the framework [Phalcon](https://phalconphp.com). This small library was based on previous work from @ToNict with his SQL Server implementation.

## Installation

This library can be installed via ***composer*** with the command `composer require codecrafting-io/phalcon-sqlsrv`, but feel free to download manually from this repository. This SQL Server "driver" for Phalcon have the following requirements:

- PHP >= 5.6.0
- Phalcon >= 3
- SQL Server >= 2012
- ODBC or SQL SERVER PHP extensions

## Usage

To use this driver, if you opted for composer installation, you need to add the vendor autoload and just create a new instance of the SQL Server classes. This library offers two classe: `Phalcon\Db\Adapter\Pdo\Sqlsrv` and `Phalcon\Db\Adapter\Pdo\OdbcSqlsrv`. The first one is meant for the PDO SQL SERVER extensions and the latter for ODBC. To use this connections you can add the following example to your services/providers:

```php
$di->set('db', function() use ($config) {
    return new \Phalcon\Db\Adapter\Pdo\Sqlsrv(array(
        "host"         => $config->database->host,
        "username"     => $config->database->username,
        "password"     => $config->database->password,
        "dbname"       => $config->database->name
    ));
});
```

## Options

This SQL Server driver have the following options that can be passed:

Name | Type | Default Value | Description | Required
------------ | ------------ | ------------- | ------------- | -------------
host | string | | Set the server attribute on the DSN string connection for the desired host | yes
username | string | | The username attribute on the DSN string connection for the desired DB username | yes
password | string | | The password attribute on the DSN string connection for the desired DB password | yes
dbname | string | | The database attribute on the DSN string connection for the desired DB name | yes
driver | string | | The driver attribute on the DSN string connection for the desired driver | only for OdbcSqlsrv class
options | array | `[\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL, \PDO::ATTR_STRINGIFY_FETCHES => false, \PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true ];` | The optional PDO connection attributes | no
cursor | mixed (bool &#124; string) | false | Set or not a MS SQL Server cursor. If the value is true for every new statement the option `[\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]` will be passed. If the value is a string not only the cursor is setted to SCROLL but, the attribute `\PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE` is also setted with option value. For `EXEC` statements the cursor option is ignored | no
mars | boolean | false | Set or not the MultipleActiveResultSets attribute on the DSN string connection | no
intent | string | ReadWrite | Declares the application workload type when connecting to a server. Possible values are ReadOnly and ReadWrite | no
pooling | boolean | true | Specifies whether the connection is assigned from a connection pool | no
useADAuth | boolean | false | Specifies to whether to use a "ActiveDirectoryPassword" | no
encrypt | boolean | false | Specifies whether the communication with SQL Server is encrypted | no
connectionRetryCount | integer | 1 | The maximum number of attempts to reestablish a broken connection before giving up. By default, a single attempt is made to reestablish a connection when broken. A value of 0 means that no reconnection will be attempted | no
connectionRetryInterval | integer | 1 | The time, in seconds, between attempts to reestablish a connection. The application will attempt to reconnect immediately upon detecting a broken connection, and will then wait ConnectRetryInterval seconds before trying again. Ignored if value is 0 | no
failover | string | | Specifies Failover Partner for the server and instance of the database's mirror (if enabled and configured) to use when the primary server is unavailable. | no
timeout | integer | | Specifies Login Timeout for the number of seconds to wait before failing the connection attempt. | no
trustServerCertificate | boolean | false | Specifies whether the client should trust | no
quotedIdentifier | boolean | true | Specifies whether to use SQL-92 rules for quoted identifiers or to use legacy Transact-SQL rules. | no
trace | boolean | false | Specifies whether ODBC tracing is enabled or disabled for the connection being established. | no

**Note:** All these settings are based on official MS SQL Server connection options, for more see [Microsoft docummentation page](https://docs.microsoft.com/en-us/sql/connect/php/connection-options?view=sql-server-2017).

### Note on cursors

 This library have options for the MS SQL Server cursors (for more see [Microsoft documentation](https://docs.microsoft.com/en-us/sql/connect/php/cursor-types-pdo-sqlsrv-driver?view=sql-server-2017)), but be aware of the shortcomings of his utilization. First, using SCROLL cursors have significant performance impact, which can be eliminated by using the buffered cursor, as discussed on this [issue](https://github.com/Microsoft/msphpsql/issues/189). The main advantage to use cursors is the avaiability off `rowCount`, since the return of number of lines only works for INSERT, UPDATE, DELETE and EXEC statements without the usage of cursors. The Phalcon framework expect that rowCount returns the number of rows for SELECT statements, despite the implementation of a custom ResultSet class (if the result is not a standard model class, a Simple or Complex ResultSet object will be used instead). To support the number of rows, for every new SELECT statement a new query like `SELECT COUNT(*) FROM (YOUR QUERY)` is made which can introduce more latency, but have much better performance in comparison to SCROLLABLE cursors (except the buffered). The usage of buffered cursor is recommended for small or medium result sets, since consumes more memory, but is faster, so if you want it use just pass the config like `[cursor => 'SQLSRV_CURSOR_BUFFERED']`.

## Why the minimum SQL Server 2012

The SQL Server 2012 for now it's the minimal requirement for the usage of this library. The reason for this is mainly related to the LIMIT clause for the SELECT statements. The 2012 versions and beyond, offers a support for OFFSET on the ORDER BY clause, which turns mutch easier to implement the tradional LIMIT clause. I have plans to use a similar work arrond like the Laravel framework did, which make the desired statement a sub query and add `ROW_NUMBER() OVER`, but it's not ready yet.
