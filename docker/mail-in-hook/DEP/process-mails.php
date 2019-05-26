#!/usr/bin/php
<?php

error_reporting(E_ALL);
require_once ("composer/vendor/autoload.php");

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
  `Date` VARCHAR(255) NOT NULL,
  `Plaintext` TEXT,
  PRIMARY KEY (`To`, `From`, `Subject`, `Date`)
);
QUERY;

        $result = $this->sqlHandle->query($query);
        if (!$result) {
            myLog($this->sqlHandle->error);
            die();
        }
    }

    public function debugPrintInfo()
    {
        myLog("Directory info for $this->directory");
        myLog("=============================");
        myLog(self::callCurl(
            "imaps://$this->mailHost:$this->mailPort",
            $this->mailUser,
            $this->mailPassword,
            "EXAMINE $this->directory",
            true
        ));
    }

    public function fetchMails()
    {
        myLog("Fetching all mails for $this->directory");

        // Would work, but is shit to parse
        //$result = self::callCurl(
        //    "imaps://$this->mailHost:$this->mailPort/INBOX",
        //    $this->mailUser,
        //    $this->mailPassword,
        //    "FETCH 1:460 BODY[]",
        //    true
        //);

        $ids = self::callCurl(
            "imaps://$this->mailHost:$this->mailPort/INBOX",
            $this->mailUser,
            $this->mailPassword,
            "FETCH 1:460 BODY[]",
            true
        );

        # Start with an id of 1, because UIDs in imap are sequential
        $results = array();
        for ($i = 1; true; $i++) {


            $result = self::callCurl(
                "imaps://$this->mailHost:$this->mailPort/INBOX",
                $this->mailUser,
                $this->mailPassword,
                "FETCH 1:460 BODY.PEEK[]",
                true
            );
            echo "\n\n\n\n\n\n\n";
            // Working for subdirectories:
//            $result = self::callCurl(
//                "imaps://$this->mailHost:$this->mailPort/INBOX.A;UID=$i",
//                $this->mailUser,
//                $this->mailPassword,
//                null,
//                true
//            );
            var_dump($result);
            if($i == 2) die();
            if (!$result)
                break;
            array_push($results, $result);
        }

        myLog(sizeof($results) . " mail entries fetched");
        myLog("=============================");

        return $results;
    }

    public function parseMailInformation($mailPlainText)
    {
        $toFound = preg_match('!\nTo: (.*?)\n!', $mailPlainText, $to);
        $fromFound = preg_match('!\nFrom: (.*?)\n!', $mailPlainText, $from);
        $subjectFound = preg_match('!\nSubject: (.*?)\n!', $mailPlainText, $subject);
        $dateFound = preg_match('!\nDate: (.*?)\n!', $mailPlainText, $date);

        if (!$toFound || !$fromFound || !$subjectFound || !$dateFound) {
            myLog("Mail parsing error!", true);
            die();
        }

        return array(
            'To' => $to[1],
            'From' => $from[1],
            'Subject' => $subject[1],
            'Date' => $date[1]
        );
    }

    public function insertMailIntoDb($mailInformation, $plaintext)
    {
        $to = $this->sqlHandle->escape_string($mailInformation['To']);
        $from = $this->sqlHandle->escape_string($mailInformation['From']);
        $subject = $this->sqlHandle->escape_string($mailInformation['Subject']);
        $date = $this->sqlHandle->escape_string($mailInformation['Date']);
        $plaintext = $this->sqlHandle->escape_string($plaintext);

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $query = <<<QUERY
INSERT IGNORE INTO `{$this->sqlTable}`
VALUES(
  '{$to}',
  '{$from}',
  '{$subject}',
  '{$date}',
  '{$plaintext}'
);
QUERY;

        $result = $this->sqlHandle->query($query);
        if (!$result) {
            myLog($this->sqlHandle->error);
            die();
        }

        @$GLOBALS[@'insertMailIntoDbCounter']++;
        myLog("Inserted mail #{$GLOBALS['insertMailIntoDbCounter']}");
    }

    public function process()
    {
        myLog("Processing all mails for $this->directory");
        myLog("(processing -> fetching from imap & persisting to database)");
        myLog("=============================");

        $mails = $this->fetchMails();
        foreach ($mails as $mail) {
            $mailInfo = $this->parseMailInformation($mail);
            $this->insertMailIntoDb($mailInfo, $mail);
        }
    }

    private static function callCurl($url, $username, $password, $request = null, $debug = false)
    {
        $c = curl_init();
        if (!$c) {
            myLog("Could not initialize curl!");
            die();
        }
        // Specific options
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_USERNAME, $username);
        curl_setopt($c, CURLOPT_PASSWORD, $password);
        if ($request != null) {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $request);
        }
        // Security must be disabled
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYSTATUS, false);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        // Debug
        if ($debug) {
            curl_setopt($c, CURLOPT_VERBOSE, true);
            curl_setopt($c, CURLOPT_HEADER, true);
            print_r(curl_getinfo($c));
        }
        // Call
        $result = curl_exec($c);
        curl_close($c);
        return $result;
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
