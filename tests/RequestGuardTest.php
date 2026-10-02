<?php

use PHPUnit\Framework\TestCase;

defined('BASEPATH') || define('BASEPATH', __DIR__);
require_once __DIR__ . '/../application/libraries/Request_guard.php';

class ClockedRequestGuard extends Request_guard
{
    public $timestamp = 100000;
    protected function now() { return $this->timestamp; }
}

final class RequestGuardTest extends TestCase
{
    private $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/aplead-guard-test-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') as $file) unlink($file);
        rmdir($this->directory);
    }

    private function guard()
    {
        return new ClockedRequestGuard(array('directory' => $this->directory));
    }

    public function testLimitPersistsAcrossInstancesAndExpiresAtTheBoundary()
    {
        $guard = $this->guard();
        $this->assertSame(0, $guard->consume('signup', '192.0.2.1', 2, 60));
        $this->assertSame(0, $this->guard()->consume('signup', '192.0.2.1', 2, 60));
        $this->assertSame(60, $guard->consume('signup', '192.0.2.1', 2, 60));
        $guard->timestamp += 59;
        $this->assertSame(1, $guard->consume('signup', '192.0.2.1', 2, 60));
        // Rejected attempts must not extend the lockout.
        $guard->timestamp++;
        $this->assertSame(0, $guard->consume('signup', '192.0.2.1', 2, 60));
    }

    public function testClientsAndActionsHaveSeparateLimits()
    {
        $guard = $this->guard();
        $this->assertSame(0, $guard->consume('signup', '192.0.2.1', 1, 60));
        $this->assertSame(60, $guard->consume('signup', '192.0.2.1', 1, 60));
        $this->assertSame(0, $guard->consume('signup', '192.0.2.2', 1, 60));
        $this->assertSame(0, $guard->consume('email_lookup', '192.0.2.1', 1, 60));
    }

    public function testStorageFailureDoesNotSilentlyAllowRequests()
    {
        $file = $this->directory . '/not-a-directory';
        file_put_contents($file, '');
        $guard = new Request_guard(array('directory' => $file));
        $this->assertFalse($guard->consume('signup', '192.0.2.1', 5, 60));
    }

    public function testCounterDoesNotStoreRawIdentityOrUseItAsAPath()
    {
        $guard = $this->guard();
        $this->assertSame(0, $guard->consume('../signup', '../../192.0.2.1', 1, 60));
        $files = glob($this->directory . '/*');
        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}\.json$/', basename($files[0]));
        $this->assertStringNotContainsString('192.0.2.1', file_get_contents($files[0]));
    }

    public function testConcurrentWorkersCannotExceedTheLimit()
    {
        $source = realpath(__DIR__ . '/../application/libraries/Request_guard.php');
        $code = 'define("BASEPATH", true); require ' . var_export($source, true)
            . '; $guard = new Request_guard(array("directory" => ' . var_export($this->directory, true)
            . ')); echo $guard->consume("signup", "192.0.2.1", 5, 60) === 0 ? "allowed" : "limited";';
        $workers = array();
        for ($i = 0; $i < 12; $i++) {
            $process = proc_open(array(PHP_BINARY, '-r', $code), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
            $this->assertIsResource($process);
            $workers[] = array($process, $pipes);
        }
        $allowed = 0;
        foreach ($workers as list($process, $pipes)) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $errors);
            $this->assertContains($output, array('allowed', 'limited'));
            if ($output === 'allowed') $allowed++;
        }
        $this->assertSame(5, $allowed);
    }
}
