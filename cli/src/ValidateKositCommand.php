<?php

declare(strict_types=1);

namespace XrechnungKit\Cli;

/**
 * Implements the validate-kosit CLI: take a file or directory of XRechnung
 * XML, hand each file to the KoSIT Schematron validator JAR via java, and
 * surface the verdicts on stdout.
 *
 * Process invocation goes through proc_open with a list-form argv (no shell
 * interpretation) so user-supplied paths and filenames cannot inject
 * arguments into the java command line.
 *
 * Exit codes (architecture section 6.5 + checklist A5):
 *   0 - all targets KoSIT-strict valid
 *   1 - one or more targets failed validation (HTML report written)
 *   2 - validator JAR not found (kosit-bundle not installed?)
 *   3 - java not found on PATH
 *   4 - cache directory not writable
 *   5 - usage error (no target supplied)
 */
final class ValidateKositCommand
{
    /** @param list<string> $argv */
    public static function run(array $argv): int
    {
        if (count($argv) < 2) {
            self::stderr("Usage: validate-kosit <file-or-directory>\n");
            return 5;
        }
        $target = $argv[1];

        $javaPath = self::resolveJava();
        if ($javaPath === null) {
            self::stderr("java not found on PATH; install JRE 17+ and retry\n");
            return 3;
        }

        if (!class_exists('XrechnungKit\\KositBundle\\Bundle')) {
            self::stderr("kosit-bundle not installed; require vineethkrishnan/xrechnung-kit-kosit-bundle and retry\n");
            return 2;
        }

        try {
            $jar = call_user_func(['XrechnungKit\\KositBundle\\Bundle', 'validatorJarPath']);
            $scenarios = call_user_func(['XrechnungKit\\KositBundle\\Bundle', 'scenariosPath']);
        } catch (\RuntimeException $e) {
            self::stderr($e->getMessage() . "\n");
            return 2;
        }
        if (!\is_string($jar) || !\is_string($scenarios)) {
            self::stderr("kosit-bundle returned non-string paths\n");
            return 2;
        }

        $cacheDir = self::resolveCacheDir();
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
            self::stderr("Cache directory not writable: {$cacheDir}\n");
            return 4;
        }

        $files = self::collectFiles($target);
        if ($files === []) {
            self::stderr("No XML files found at {$target}\n");
            return 5;
        }

        $failed = 0;
        foreach ($files as $file) {
            $exitCode = self::runValidator($javaPath, $jar, $scenarios, $cacheDir, $file);
            if ($exitCode === 0) {
                fwrite(STDOUT, "PASS  {$file}\n");
            } else {
                fwrite(STDOUT, "FAIL  {$file}\n");
                $failed++;
            }
        }

        return $failed === 0 ? 0 : 1;
    }

    /**
     * Invokes java -jar with the validator JAR via proc_open + array argv so
     * no shell interprets the arguments. Returns the JVM's exit code.
     */
    private static function runValidator(
        string $javaPath,
        string $jar,
        string $scenarios,
        string $cacheDir,
        string $file,
    ): int {
        $cmd = [$javaPath, '-jar', $jar, '-s', $scenarios, '-r', $cacheDir, $file];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $proc = proc_open($cmd, $descriptors, $pipes);
        if (!\is_resource($proc)) {
            return 254;
        }
        fclose($pipes[0]);
        // Drain stdout / stderr to prevent the child from blocking on full pipes.
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        return proc_close($proc);
    }

    private static function resolveJava(): ?string
    {
        $javaHome = getenv('JAVA_HOME');
        if (\is_string($javaHome) && $javaHome !== '') {
            $candidate = $javaHome . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'java';
            if (is_executable($candidate)) {
                return $candidate;
            }
        }
        $path = getenv('PATH');
        if (!\is_string($path) || $path === '') {
            return null;
        }
        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'java';
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Cache directory resolution per architecture section 7:
     *   1. XRECHNUNG_KIT_CACHE_DIR
     *   2. XDG_CACHE_HOME/xrechnung-kit
     *   3. ~/.cache/xrechnung-kit
     */
    private static function resolveCacheDir(): string
    {
        $explicit = getenv('XRECHNUNG_KIT_CACHE_DIR');
        if (\is_string($explicit) && $explicit !== '') {
            return $explicit;
        }
        $xdg = getenv('XDG_CACHE_HOME');
        if (\is_string($xdg) && $xdg !== '') {
            return $xdg . DIRECTORY_SEPARATOR . 'xrechnung-kit';
        }
        $home = getenv('HOME');
        if (\is_string($home) && $home !== '') {
            return $home . '/.cache/xrechnung-kit';
        }
        return sys_get_temp_dir() . '/xrechnung-kit';
    }

    /** @return list<string> */
    private static function collectFiles(string $target): array
    {
        if (is_file($target)) {
            return [$target];
        }
        if (!is_dir($target)) {
            return [];
        }
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo
                && $file->isFile()
                && strtolower($file->getExtension()) === 'xml'
            ) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    private static function stderr(string $message): void
    {
        fwrite(STDERR, $message);
    }
}
