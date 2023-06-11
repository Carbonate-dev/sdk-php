<?php

namespace Carbonate\PhpUnit;

use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    private $logs = [];
    private $outputLogs;

    public function __construct($outputLogs = null)
    {
        if ($outputLogs === null) {
            $isDebug = in_array('--debug', $_SERVER['argv'], true);
            $isVerbose = in_array('--verbose', $_SERVER['argv'], true);

            $outputLogs = $isDebug || $isVerbose;
        }

        $this->outputLogs = $outputLogs;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->outputLogs) {
            $log = $this->formatLog($level, $message, $context);
            fwrite(STDERR, $log);
        }
        else {
            $this->logs[] = compact('level', 'message', 'context');
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    public function formatLog($level, $message, array $context)
    {
        return sprintf("%s: %s %s\n", $level, $message, json_encode($context));
    }

    public function clearLogs()
    {
        $this->logs = [];
    }

    public function flushLogs()
    {
        $logs = $this->getLogs();

        $this->logs = [];
        fwrite(STDERR, $logs);
    }

    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @return string
     */
    public function getLogs(): string
    {
        $logs = '';
        foreach ($this->logs as $log) {
            $logs .= $this->formatLog($log['level'], $log['message'], $log['context']);
        }
        return $logs;
    }
}
