<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Atomic request counters shared across browser sessions and PHP workers. */
class Request_guard
{
    private $directory;

    public function __construct($options = array())
    {
        $this->directory = isset($options['directory'])
            ? rtrim($options['directory'], DIRECTORY_SEPARATOR)
            : APPPATH . 'cache/request-guard';
    }

    /** Returns 0 when allowed, seconds until retry when limited, or false on I/O failure. */
    public function consume($bucket, $identity, $limit, $window)
    {
        if ($limit < 1 || $window < 1 || $window > 86400) {
            return false;
        }
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0750, true)
            && !is_dir($this->directory)) {
            return false;
        }

        // Never use client-controlled strings as paths or store raw IP addresses.
        $path = $this->directory . '/' . hash('sha256', $bucket . '|' . $identity) . '.json';
        $previous_umask = umask(0077);
        $handle = @fopen($path, 'c+b');
        umask($previous_umask);
        if (!$handle) {
            return false;
        }
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return false;
        }

        try {
            $now = $this->now();
            $state = json_decode(stream_get_contents($handle, 4096), true);
            if (!is_array($state) || !isset($state['started'], $state['count'])
                || $state['started'] > $now || $now - $state['started'] >= $window) {
                $state = array('started' => $now, 'count' => 0);
            }
            if ($state['count'] >= $limit) {
                return max(1, $state['started'] + $window - $now);
            }
            $state['count']++;
            $json = json_encode($state);
            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
                return false;
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        // Bound cleanup work on any one request and expire counters after a day.
        if (mt_rand(1, 100) === 1) {
            $this->prune();
        }
        return 0;
    }

    protected function now()
    {
        return time();
    }

    private function prune()
    {
        $seen = 0;
        $cutoff = $this->now() - 86400;
        try {
            foreach (new DirectoryIterator($this->directory) as $file) {
                if (++$seen > 1000) break;
                if (!$file->isFile() || !preg_match('/^[a-f0-9]{64}\.json$/', $file->getFilename())
                    || $file->getMTime() >= $cutoff) continue;
                $handle = @fopen($file->getPathname(), 'r+b');
                if (!$handle) continue;
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    $stat = fstat($handle);
                    if ($stat && $stat['mtime'] < $cutoff) @unlink($file->getPathname());
                    flock($handle, LOCK_UN);
                }
                fclose($handle);
            }
        } catch (UnexpectedValueException $exception) {
            // Counters already work; a cleanup race must not break the request.
        }
    }
}
