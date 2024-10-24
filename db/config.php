<?php
/* 
    * PDO DATABASE CLASS
    * Connects Database Using PDO
	* Creates Prepared Statements
	* Binds params to values
	* Returns rows and results
	*/
class Database
{
    private $host = 'localhost';
    private $user = 'oriowebsite_live';
    private $pass = 'KJhJYkQC2RB8';
    private $dbname ='oriowebsite_live_db';
    // private $user = 'root';
    // private $pass = '';
    // private $dbname ='oex_citymail';
    private $dbh;
    private $error;
    private $stmt;
    public function __construct()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        );
        try { // Create a new PDO instanace
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        }    // Catch any errors
        catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }
    // Prepare statement with query
    public function query($query)
    {
        $this->stmt = $this->dbh->prepare($query);
    }
    // Close Connection
    public function close()
    {
        $this->dbh = null;
    }
    // Bind values
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;

                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    // Execute the prepared statement
    public function execute()
    {
        return $this->stmt->execute();
    }
    // Get result set as array of objects
    public function resultset()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }
    // Get single record as object
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }
    // Get record row count
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }
    // Returns the last inserted ID
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }
}
