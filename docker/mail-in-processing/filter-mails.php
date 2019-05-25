#!/usr/bin/php
<?php

error_reporting(E_ALL);

class Filterer
{

    // SQL environment
    private $sqlHost;
    private $sqlPort;
    private $sqlUser;
    private $sqlPassword;
    private $sqlDatabase;
    private $sqlTablePlainText;
    private $sqlTableFiltered;

    /**
     * @var mysqli
     */
    private $sqlHandle;

    public function __construct()
    {
        echo "=============================\n";
        $this->loadEnvironmentVariables();
        $this->connectSqlDb();
        $this->maySetupSqlTable();
    }

    public function loadEnvironmentVariables()
    {
        // SQL
        $this->sqlHost = getenv('SQL_HOST');
        $this->sqlPort = getenv('SQL_PORT');
        $this->sqlUser = getenv('SQL_USER');
        $this->sqlPassword = getenv('SQL_PASSWORD');
        $this->sqlDatabase = getenv('SQL_DATABASE');
		$this->sqlTablePlainText = getenv('SQL_TABLE_PLAINTEXT');
		$this->sqlTableFiltered = getenv('SQL_TABLE_FILTERED');
    }

    public function connectSqlDb()
    {
        $this->sqlHandle = new mysqli($this->sqlHost, $this->sqlUser, $this->sqlPassword,
            $this->sqlDatabase, $this->sqlPort);
        if (!$this->sqlHandle || $this->sqlHandle->error != null) {
            echo "MySQL could not connect!";
            echo $this->sqlHandle->error;
            die();
        }
    }

    public function maySetupSqlTable()
    {
		$this->sqlHandle->query("DROP TABLE `".$this->sqlTableFiltered."`");
		
        /** @noinspection SqlNoDataSourceInspection */
        $query = <<<QUERY
CREATE TABLE IF NOT EXISTS `{$this->sqlTableFiltered}` (
  `To` VARCHAR(255) NOT NULL,
  `From` VARCHAR(255) NOT NULL,
  `Subject` VARCHAR(255),
  `Date` VARCHAR(255) NOT NULL,
  `Plaintext` TEXT,
  PRIMARY KEY (`To`, `From`, `Subject`, `Date`)
);
QUERY;

        $this->sqlHandle->query($query) || die($this->sqlHandle->error);
    }

    public function insertMailIntoDb($mailInformation, $plaintext)
    {
        /** @noinspection SqlNoDataSourceInspection */
        $query = <<<QUERY
INSERT IGNORE INTO `{$this->sqlTable}`
VALUES(
  '{$mailInformation['To']}',
  '{$mailInformation['From']}',
  '{$mailInformation['Subject']}',
  '{$mailInformation['Date']}',
  '{$plaintext}'
);
QUERY;

        $this->sqlHandle->query($query) || die($this->sqlHandle->error);

        @$GLOBALS[@'insertMailIntoDbCounter']++;
        echo "Inserted mail #{$GLOBALS['insertMailIntoDbCounter']}\n";
    }

    public function process()
    {
        echo "Babedibubip\n";
        echo "=============================\n";
		
		$mails = $this->loadAllPlaintextMails();
		foreach($mails as $mail) {
			if($this->isMailFiltered($mail)) {
				$mailData = $this->extractMailDataFromPlaintextMail($mail);
				$this->insertMailIntoDb($mailData);
			}
		}
    }
}

$analyzer = new Filterer();
$analyzer->process();

// Because cli does not handle a newline itself
echo "\n";
