<?php

namespace app\services\imap;

use Exception;
use Ddeboer\Imap\Server;
use app\services\imap\ConnectionErrorException;

class Imap
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var string
     */
    protected $encryption;

    /**
     * @var boolean
     */
    protected $validateCertificate;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var \Ddeboer\Imap\Connection
     */
    protected $connection;

    /**
     * Create new IMAP instance
     */
    public function __construct($username, $password, $host, $encryption, $port = '', $validateCertificate = false)
    {
        $this->host                = $host;
        $this->port                = $port;
        $this->encryption          = strtolower($encryption);
        $this->username            = $username;
        $this->password            = $password;
        $this->validateCertificate = $validateCertificate;
    }

    /**
     * Get the selectable folder names
     *
     * @return array
     */
    public function getSelectableFolders()
    {
        $connection = $this->testConnection();
        $folders    = $connection->getMailboxes();

        foreach ($folders as $key => $folder) {
            if ($folder->getAttributes() & \LATT_NOSELECT) {
                unset($folders[$key]);
            }
        }

        return array_keys(array_map(function ($folder) {
            return $folder->getName();
        }, $folders));
    }

    /**
     * Test the IMAP connection
     *
     * @return \Ddeboer\Imap\Connection
     */
    public function testConnection()
    {
        try {
            return $this->createConnection();
        } catch (Exception $e) {
            throw new ConnectionErrorException($e->getMessage());
        }
    }

    /**
     * Create IMAP connection
     *
     * @return \Ddeboer\Imap\Connection
     */
    public function createConnection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        $server = new Server(
            $this->host,
            $this->port,
            $this->getConnectionFlags()
        );

        return $this->connection = $server->authenticate($this->username, $this->password);
    }

    /**
     * Get full address of mailbox.
     *
     * @return string
     */
    protected function getConnectionFlags()
    {
        $flags = '';

        if ($this->encryption) {
            $flags .= '/imap';
            if (in_array($this->encryption, ['tls', 'notls', 'ssl'])) {
                $flags .= '/' . $this->encryption;
            } elseif ($this->encryption === 'starttls') {
                $flags .= '/tls';
            }

            if (!$this->validateCertificate) {
                $flags .= '/novalidate-cert';
            } else {
                $flags .= '/validate-cert';
            }
        }


        return $flags;
    }
}
