<?php

require_once dirname(__FILE__) . '/ViewedProductTestCase.php';

class DirectoryGuardTest extends ViewedProductTestCase
{
    private static $indexFiles = array(
        'index.php',
        'tests/index.php',
        'tests/php/phpstan/index.php',
        'translations/index.php',
        'upgrade/index.php',
        'views/index.php',
        'views/templates/hook/index.php',
        'views/templates/index.php',
    );

    private function runChild($target, $mode = 'index')
    {
        $php = getenv('PHP_BINARY');
        if (!$php) {
            $php = 'php';
        }
        $wrapper = dirname(__FILE__) . '/bin/include-and-sentinel.php';
        $command = escapeshellarg($php)
            . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 '
            . escapeshellarg($wrapper) . ' '
            . escapeshellarg($target) . ' '
            . escapeshellarg($mode);

        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($command, $descriptorSpec, $pipes, dirname(dirname(__DIR__)));
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return array($stdout, $stderr, $exitCode);
    }

    private function assertCleanChildOutput($stdout, $stderr, $exitCode)
    {
        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stdout);
        $patterns = array(
            'Notice:',
            'Warning:',
            'Deprecated:',
            'Strict Standards:',
            'Parse error',
            'Fatal error',
        );
        foreach ($patterns as $pattern) {
            $this->assertNotContains($pattern, $stderr);
            $this->assertNotContains($pattern, $stdout);
        }
    }

    /**
     * @dataProvider indexFileProvider
     */
    public function testDirectoryIndexExitsBeforeOutput($relativePath)
    {
        $target = dirname(dirname(__DIR__)) . '/' . $relativePath;
        list($stdout, $stderr, $exitCode) = $this->runChild($target, 'index');
        $this->assertCleanChildOutput($stdout, $stderr, $exitCode);
        $this->assertNotContains('SENTINEL', $stdout);
    }

    public function indexFileProvider()
    {
        $rows = array();
        foreach (self::$indexFiles as $file) {
            $rows[] = array($file);
        }

        return $rows;
    }

    public function testModuleFileExitsWhenPsVersionIsUndefined()
    {
        $target = dirname(dirname(__DIR__)) . '/ps_viewedproduct.php';
        list($stdout, $stderr, $exitCode) = $this->runChild($target, 'module');
        $this->assertCleanChildOutput($stdout, $stderr, $exitCode);
        $this->assertNotContains('SENTINEL', $stdout);
    }
}
