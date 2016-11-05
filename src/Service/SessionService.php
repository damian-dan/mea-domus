<?php
declare(strict_types=1);

namespace House\Service;

use Evenement\EventEmitter;
use House\Model\Session;

/**
 * Class SessionService
 * @package House\Service
 */
class SessionService
{
    /**
     * @var EventEmitter
     */
    protected $emitter;

    /**
     * @var string
     */
    protected $sessionDir;

    /**
     * SessionService constructor.
     * @param EventEmitter $emitter
     * @param string $sessionDir
     */
    public function __construct(EventEmitter $emitter, string $sessionDir)
    {
        if (!realpath($sessionDir) && !@mkdir($sessionDir, 0777, true)) {
            throw new \RuntimeException(sprintf('Session directory creation failed at path %s', $sessionDir));
        }
        $this->emitter = $emitter;
        $this->sessionDir = $sessionDir;
    }

    /**
     * @return Session
     */
    public function current() : Session
    {
        $pid = getmypid();
        return new Session($pid, $this->sessionDir);
    }

    /**
     * @param Session|null $session
     * @return Session
     */
    public function start(Session $session = null) : Session
    {
        $session = $session ?? new Session(getmypid(), $this->sessionDir);

        $session->open();

        $this->emitter->emit('session.started', [$session]);

        return $session;
    }

    /**
     * @param Session $session
     * @return Session
     */
    public function close(Session $session) : Session
    {
        $session->close();

        $this->emitter->emit('session.ended', [$session]);

        return $session;
    }
}
