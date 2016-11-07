<?php
declare(strict_types=1);

namespace House\Model;

/**
 * Class Session
 * @package House\Model
 */
class Session
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $sessionDirectory;

    /**
     * @var \DateTime
     */
    protected $startTime;

    /**
     * @var \DateTime
     */
    protected $stopTime;

    /**
     * Session constructor.
     * @param string|int $id
     * @param string $sessionDirectory
     */
    public function __construct($id, string $sessionDirectory)
    {
        $this->sessionDirectory = sprintf('%s/%s', rtrim($sessionDirectory, '/'), $id);

        if (!realpath($this->sessionDirectory) && !@mkdir($this->sessionDirectory, 0777, true)) {
            throw new \RuntimeException(sprintf('Session directory could not be created at %s', $this->sessionDirectory));
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Creates a file named "start" within the session directory
     * and saves the current timestamp in it
     */
    public function open() : Session
    {
        if ($this->isOpened()) {
            throw new \RuntimeException('Session already started');
        }

        $sessionStartFile = sprintf('%s/start', $this->sessionDirectory);

        if (!@file_put_contents($sessionStartFile, date("D M j Y G:i:s"))) {
            throw new \RuntimeException(sprintf('Session could not be started at %s', $sessionStartFile));
        }

        return $this;
    }

    /**
     * Creates a file named "stop" within the session directory
     * and saves the current timestamp in it
     */
    public function close() : Session
    {
        if ($this->isClosed()) {
            throw new \RuntimeException('Session already stopped');
        }

        $sessionStartFile = sprintf('%s/start', $this->sessionDirectory);

        if (!@file_put_contents($sessionStartFile, date("D M j Y G:i:s"))) {
            throw new \RuntimeException(sprintf('Session could not be started at %s', $sessionStartFile));
        }

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function startTime()
    {
        if (!$this->isOpened()) {
            return null;
        }

        if (!$this->startTime) {
            $this->startTime = new \DateTime();
            $this->startTime->setTimestamp(strtotime(sprintf('%s/start', $this->sessionDirectory)));
        }
        return $this->startTime;
    }

    /**
     * @return \DateTime|null
     */
    public function closeTime()
    {
        if (!$this->isClosed()) {
            return null;
        }

        if (!$this->stopTime) {
            $this->stopTime = new \DateTime();
            $this->stopTime->setTimestamp(strtotime(sprintf('%s/stop', $this->sessionDirectory)));
        }
        return $this->stopTime;
    }

    /**
     * @return bool
     */
    public function isOpened() : bool
    {
        return (bool) realpath(sprintf('%s/start', $this->sessionDirectory));
    }

    /**
     * @return bool
     */
    public function isClosed() : bool
    {
        return (bool) realpath(sprintf('%s/stop', $this->sessionDirectory));
    }
}