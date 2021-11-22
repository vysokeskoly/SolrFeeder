<?php declare(strict_types=1);

namespace VysokeSkoly\SolrFeeder\Utils;

/*
 * Copy of https://raw.githubusercontent.com/symfony/symfony/v3.3.18/src/Symfony/Component/Filesystem/LockHandler.php
 *
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * LockHandler class provides a simple abstraction to lock anything by means of
 * a file lock.
 *
 * A locked file is created based on the lock name when calling lock(). Other
 * lock handlers will not be able to lock the same name until it is released
 * (explicitly by calling release() or implicitly when the instance holding the
 * lock is destroyed).
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Romain Neutron <imprec@gmail.com>
 * @author Nicolas Grekas <p@tchwork.com>
 */
class LockHandler
{
    private string $file;
    /** @var resource|null|false */
    private $handle;

    /**
     * @param string      $name     The lock name
     * @param string|null $lockPath The directory to store the lock. Default values will use temporary directory
     *
     * @throws IOException If the lock directory could not be created or is not writable
     */
    public function __construct(string $name, ?string $lockPath = null)
    {
        $lockPath = $lockPath ?: sys_get_temp_dir();

        if (!is_dir($lockPath)) {
            $fs = new Filesystem();
            $fs->mkdir($lockPath);
        }

        if (!is_writable($lockPath)) {
            throw new IOException(sprintf('The directory "%s" is not writable.', $lockPath), 0, null, $lockPath);
        }

        $this->file = sprintf('%s/sf.%s.%s.lock', $lockPath, preg_replace('/[^a-z0-9\._-]+/i', '-', $name), hash('sha256', $name));
    }

    /**
     * Lock the resource.
     *
     * @param bool $blocking Wait until the lock is released
     *
     * @throws IOException If the lock file could not be created or opened
     * @return bool Returns true if the lock was acquired, false otherwise
     */
    public function lock(bool $blocking = false): bool
    {
        if ($this->handle) {
            return true;
        }

        $error = null;

        // Silence error reporting
        set_error_handler(function ($errno, $msg) use (&$error): ?bool {
            $error = $msg;

            return null;
        });

        if (!$this->handle = fopen($this->file, 'rb')) {
            if ($this->handle = fopen($this->file, 'xb')) {
                chmod($this->file, 0444);
            } elseif (!$this->handle = fopen($this->file, 'rb')) {
                usleep(100); // Give some time for chmod() to complete
                $this->handle = fopen($this->file, 'rb');
            }
        }
        restore_error_handler();

        if (!$this->handle) {
            throw new IOException((string) $error, 0, null, $this->file);
        }

        // On Windows, even if PHP doc says the contrary, LOCK_NB works, see
        // https://bugs.php.net/54129
        if (!flock($this->handle, LOCK_EX | ($blocking ? 0 : LOCK_NB))) {
            fclose($this->handle);
            $this->handle = null;

            return false;
        }

        return true;
    }

    /**
     * Release the resource.
     */
    public function release(): void
    {
        if ($this->handle) {
            flock($this->handle, LOCK_UN | LOCK_NB);
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
