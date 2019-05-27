#!/usr/bin/php
<?php

error_reporting(E_ALL);
require_once("/vendor/autoload.php");

use Ddeboer\Imap\Server;
use Ddeboer\Imap\MessageInterface;

class Analyzer
{
    // Mail environment
    private $mailHost;
    private $mailPort;
    private $mailUser;
    private $mailPassword;

    // SQL environment
    private $sqlHost;
    private $sqlPort;
    private $sqlUser;
    private $sqlPassword;
    private $sqlDatabase;
    private $sqlTable;

    /**
     * @var string
     */
    private $directory;
    /**
     * @var mysqli
     */
    private $sqlHandle;

    public function __construct($directory)
    {
        myLog("=============================");
        $this->directory = $directory;
        $this->loadEnvironmentVariables();
        $this->connectSqlDb();
        $this->maySetupSqlTable();
    }

    public function loadEnvironmentVariables()
    {
        // Mail
        $this->mailHost = getenv('MAIL_HOST');
        $this->mailPort = getenv('MAIL_PORT');
        $this->mailUser = getenv('MAIL_USER');
        $this->mailPassword = getenv('MAIL_PASSWORD');
        // SQL
        $this->sqlHost = getenv('SQL_HOST');
        $this->sqlPort = getenv('SQL_PORT');
        $this->sqlUser = getenv('SQL_USER');
        $this->sqlPassword = getenv('SQL_PASSWORD');
        $this->sqlDatabase = getenv('SQL_DATABASE');
        $this->sqlTable = getenv('SQL_TABLE');
    }

    public function connectSqlDb()
    {
        $this->sqlHandle = new mysqli($this->sqlHost, $this->sqlUser, $this->sqlPassword,
            $this->sqlDatabase, $this->sqlPort);
        if (!$this->sqlHandle || $this->sqlHandle->error != null) {
            myLog("MySQL could not connect!", true);
            myLog($this->sqlHandle->error, true);
            die();
        }
    }

    public function maySetupSqlTable()
    {
        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $query = <<<QUERY
CREATE TABLE IF NOT EXISTS `{$this->sqlTable}` (
  `To` VARCHAR(255) NOT NULL,
  `From` VARCHAR(255) NOT NULL,
  `Subject` VARCHAR(255),
  `Date` DATE NOT NULL,
  `RawHeader` TEXT,
  `RawMessage` TEXT,
  PRIMARY KEY (`To`, `From`, `Subject`, `Date`)
);
QUERY;

        $result = $this->sqlHandle->query($query);
        if (!$result) {
            myLog($this->sqlHandle->error);
            die();
        }
    }

    public function fetchMails()
    {
        myLog("Fetching all mails for $this->directory");

        $server = new Server($this->mailHost, $this->mailPort, '/imap/ssl/novalidate-cert');
        $connection = $server->authenticate($this->mailUser, $this->mailPassword);
        $mailbox = $connection->getMailbox($this->directory);
        $messages = $mailbox->getMessages();

        myLog(sizeof($messages) . " mail entries fetched");
        myLog("=============================");

        return $messages;
    }

    public function insertMailIntoDb(MessageInterface $mail)
    {
        $to = $this->sqlHandle->escape_string($mail->getTo()[0]->getFullAddress());
        $from = $this->sqlHandle->escape_string($mail->getFrom()->getFullAddress());
        $subject = $this->sqlHandle->escape_string($mail->getSubject());
        $date = $this->sqlHandle->escape_string($mail->getDate()->format(\DateTime::ATOM));
        $rawHeader = $this->sqlHandle->escape_string($mail->getRawHeaders());
        $rawMessage = $this->sqlHandle->escape_string($mail->getRawMessage());

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $query = <<<QUERY
INSERT IGNORE INTO `{$this->sqlTable}`
VALUES(
  '{$to}',
  '{$from}',
  '{$subject}',
  '{$date}',
  '{$rawHeader}',
  '{$rawMessage}'
);
QUERY;

        $result = $this->sqlHandle->query($query);
        if (!$result) {
            myLog($this->sqlHandle->error);
            die();
        }

        //@$GLOBALS[@'insertMailIntoDbCounter']++;
        //myLog("Inserted mail #{$GLOBALS['insertMailIntoDbCounter']}");
    }

    public function process()
    {
        myLog("Processing all mails for $this->directory");
        myLog("(processing -> fetching from imap & persisting to database)");
        myLog("=============================");

        $mails = $this->fetchMails();
        foreach ($mails as $mail) {
            $this->insertMailIntoDb($mail);
        }

        myLog("Processing done!");
        myLog(sizeof($mails) . " processed");
        myLog("=============================");
    }
}

function myLog($message, $isError = false)
{
    $date = new DateTime();
    $dateStr = $date->format("y:m:d h:i:s");
    echo "[$dateStr] " . ($isError ? "[ERROR]" : "") . " $message\n";
}

$analyzer = new Analyzer("INBOX");
$analyzer->process();

// Because cli does not handle a newline itself
echo "\n";
