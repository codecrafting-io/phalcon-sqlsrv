# MS SQL Server Phalcon Implementation

This is a unofficial support for MS SQL Server for the framework [Phalcon](https://phalconphp.com). This small library was based on on previous work from @ToNict with his SQL Server implementation.

## Installation

The library can be installed via ***composer*** with the command `composer require codecrafting-net/phalcon-sqlsrv`, but feel free to download manually from this repository. This SQL Server "driver" for Phalcon have the following requirements:

- PHP >= 7
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

This SQL Server driver have the following controlled options that can be passed:

Name | Type | Default Value | Description | Required
------------ | ------------ | ------------- | ------------- | -------------
host | String | | Set the server attribute on the DSN string connection for the desired host | yes
username | String | | The username attribute on the DSN string connection for the desired DB username | yes
password | String | | The password attribute on the DSN string connection for the desired DB password | yes
dbname | String | | The database attribute on the DSN string connection for the desired DB name | yes
driver | String | | The driver attribute on the DSN string connection for the desired driver | only for OdbcSqlsrv class
options | Array | ` [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL, \PDO::ATTR_STRINGIFY_FETCHES => false, \PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true ];` | The optional PDO connection attributes | no
persistent | String | false | Pass or not the attribute `\PDO::ATTR_PERSISTENT` to the PDO connection | no
cursor | mixed (bool &#124; string) | false | Set or not a MS SQL Server cursor. If the value is true for every new statement the option `[\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]` will be passed. If the value is a string not only the cursor is setted to SCROLL but, the attribute `\PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE` is also setted with option value. For `EXEC` statements the cursor option is ignored | no
MultipleActiveResultSets | String | false | Set or not the MultipleActiveResultSets attribute on the DSN string connection | no

The above configurations are specific controlled by the library, but any another configuration can be passed, but only will be used for the PDO DSN string connection.

### Note on cursors:

 The library driver have options for the MS SQL Server cursors (for more see [Microsoft documentation](https://docs.microsoft.com/en-us/sql/connect/php/cursor-types-pdo-sqlsrv-driver?view=sql-server-2017)), but be aware of the shortcomings of his utilization. First, using SCROLL cursors have significant performance impact, which can be minimized using the buffered cursor, as discussed on this [issue](https://github.com/Microsoft/msphpsql/issues/189). The main advantage to use cursors is the avaiability off `rowCount`, since the return of number of lines only works for INSERT, UPDATE, DELETE and EXEC statements without the usage of cursors. Personally, I don't recommend the usage of cursors, since the diference of performance is day and night, and the lack of `numRows` it's not that important for SELECT statements. If you must us it, I recommend the usage of buffered cursor using the option `[cursor => 'SQLSRV_CURSOR_BUFFERED']`, but consumes more memory and if the result don't fit on buffer the SQL Server driver will throw a exception.

## Why the minimum SQL Server 2012?

The SQL Server 2012 for now it's the minimal requirement for the usage of this library. The reason for this is mainly related to the LIMIT clause for the SELECT statements. The 2012 versions and beyond, offers a support for OFFSET on the ORDER BY clause, which turns mutch easier to implement the tradional LIMIT clause. I have plans to use a similar work arrond like the Laravel framework did, which make the desired statement a sub query and add `ROW_NUMBER() OVER`, but it's not ready yet.
