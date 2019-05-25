#!/usr/bin/php
<?php

error_reporting(E_ALL);

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
        echo "=============================\n";
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
            echo "MySQL could not connect!";
            echo $this->sqlHandle->error;
            die();
        }
    }

    public function maySetupSqlTable()
    {
        /** @noinspection SqlNoDataSourceInspection */
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

        $this->sqlHandle->query($query) || die($this->sqlHandle->error);
    }

    public function debugPrintInfo()
    {
        echo "Directory info for $this->directory\n";
        echo "=============================\n";
        echo self::callCurl(
            "imaps://$this->mailHost:$this->mailPort",
            $this->mailUser,
            $this->mailPassword,
            "EXAMINE $this->directory",
            true
        );
    }

    public function fetchMails()
    {
        echo "Fetching all mails for $this->directory\n";
        echo "=============================\n";

        # Start with an id of 1, because UIDs in imap are sequential
        $results = array();
        for ($i = 1; true; $i++) {
            $result = self::callCurl(
                "imaps://$this->mailHost:$this->mailPort/$this->directory;UID=$i",
                $this->mailUser,
                $this->mailPassword
            );
            if (!$result)
                break;
            array_push($results, $result);
        }
        return $results;
    }

    public function parseMailInformation($mailPlainText)
    {
        $toFound = preg_match('!\nTo: (.*?)\n!', $mailPlainText, $to);
        $fromFound = preg_match('!\nFrom: (.*?)\n!', $mailPlainText, $from);
        $subjectFound = preg_match('!\nSubject: (.*?)\n!', $mailPlainText, $subject);
        $dateFound = preg_match('!\nDate: (.*?)\n!', $mailPlainText, $date);

        if (!$toFound || !$fromFound || !$subjectFound || !$dateFound)
            die("Mail parsing error!");

        return array(
            'To' => $to[1],
            'From' => $from[1],
            'Subject' => $subject[1],
            'Date' => $date[1]
        );
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
        echo "Processing all mails for $this->directory\n";
        echo "(processing -> fetching from imap & persisting to database)\n";
        echo "=============================\n";

        $mails = $this->fetchMails();
        foreach ($mails as $mail) {
            $mailInfo = $this->parseMailInformation($mail);
            $this->insertMailIntoDb($mailInfo, $mail);
        }
    }

    private static function callCurl($url, $username, $password, $request = null, $debug = false)
    {
        $c = curl_init();
        $c || die("Could not initialize curl!");
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

$analyzer = new Analyzer("INBOX.Analyze");
$analyzer->process();

// Because cli does not handle a newline itself
echo "\n";
