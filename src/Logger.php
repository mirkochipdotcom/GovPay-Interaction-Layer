<?php
namespace App;

class Logger
{
    private static ?Logger $instance = null;
    private string $filePath;

    private function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            $path = __DIR__ . '/../storage/logs/app.log';
            self::$instance = new Logger($path);
        }
        return self::$instance;
    }

    private function write(string $level, string $message, array $context = []): void
    {
        $ts = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:sP');
        $entry = sprintf("%s [%s] %s", $ts, strtoupper($level), $message);
        if ($context) {
            $entry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $entry .= PHP_EOL;

        // Write atomically
        $fp = @fopen($this->filePath, 'a');
        if ($fp) {
            @flock($fp, LOCK_EX);
            @fwrite($fp, $entry);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }
}
