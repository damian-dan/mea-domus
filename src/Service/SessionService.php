<?php
declare(strict_types=1);

namespace House\Service;

use Evenement\EventEmitter;
use House\Model\Session;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

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
        $nextSessionId = $this->generateSessionId();
        $pid = $nextSessionId;
        $possiblyTheCurrentSession = $nextSessionId;
        if ($nextSessionId > 1) {
            $possiblyTheCurrentSession -= 1;
        }

        $pathToCurrentSessionDir = sprintf('%s/%s/stop', $this->sessionDir, $possiblyTheCurrentSession);

        if (!file_exists($pathToCurrentSessionDir)) { //the last session on disk is still open
            $pid = $possiblyTheCurrentSession;
        }

        return new Session($pid, $this->sessionDir);
    }

    /**
     * @param Session|null $session
     * @return Session
     */
    public function start(Session $session = null) : Session
    {
        $session = $session ?? new Session($this->generateSessionId(), $this->sessionDir);

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

    /**
     * @param int $sessionId
     */
    public function remove(int $sessionId)
    {
        rmdir(sprintf($this->sessionDir . DIRECTORY_SEPARATOR . (int) $sessionId));
    }

    /**
     * Gets the latest session ID from the directories
     * within the session directory, increments it by one
     * and returns the value
     *
     * @return int
     */
    protected function generateSessionId() : int
    {
        $finder = new Finder();
        $finder
            ->directories() //only directories
            ->in($this->sessionDir) //only in session directory
            ->depth('== 0') //don't search recursively
            ->filter(function(\SplFileInfo $dir) { //filter only directories that have only digits in their name
                return preg_match('/^\d+$/', $dir->getBasename());
            })
            ->sort(function(\SplFileInfo $a, \SplFileInfo $b) { //sort desc by name
                return $b <=> $a;
            });

        // take the first from the list, increment it by one and return it
        foreach ($finder as $lastSessionDir) {
            return 1 + (int) $lastSessionDir->getBasename();
        }

        return 1;
    }
}
