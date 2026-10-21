<?php

declare(strict_types=1);

namespace SimpleSAML\Logger;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Utils;

/**
 * A logging handler that dumps logs to files.
 *
 * @author Lasse Birnbaum Jensen, SDU.
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class FileLoggingHandler implements LoggingHandlerInterface
{
    /**
     * A string with the path to the file where we should log our messages.
     *
     * @var null|string
     */
    protected $logFile = null;

    /**
     * This array contains the mappings from syslog log levels to names. Copied more or less directly from
     * SimpleSAML\Logger\ErrorLogLoggingHandler.
     *
     * @var array
     */
    private static $levelNames = [
        Logger::EMERG   => 'EMERGENCY',
        Logger::ALERT   => 'ALERT',
        Logger::CRIT    => 'CRITICAL',
        Logger::ERR     => 'ERROR',
        Logger::WARNING => 'WARNING',
        Logger::NOTICE  => 'NOTICE',
        Logger::INFO    => 'INFO',
        Logger::DEBUG   => 'DEBUG',
    ];

    /** @var string|null */
    protected $processname = null;

    /** @var string */
    protected $format = "%b %d %H:%M:%S";


    /**
     * Build a new logging handler based on files.
     * @param \SimpleSAML\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        // get the metadata handler option from the configuration
        $this->logFile = $config->getPathValue('loggingdir', 'log/') .
            $config->getString('logging.logfile', 'simplesamlphp.log');

        // Remove any non-printable characters before storing
        $this->processname = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $config->getString('logging.processname', 'SimpleSAMLphp'));

        if (@file_exists($this->logFile)) {
            if (!@is_writeable($this->logFile)) {
                throw new \Exception("Could not write to logfile: " . $this->logFile);
            }
        } else {
            if (!@touch($this->logFile)) {
                throw new \Exception(
                    "Could not create logfile: " . $this->logFile .
                    " The logging directory is not writable for the web server user."
                );
            }
        }

        Utils\Time::initTimezone();
    }


    /**
     * Set the format desired for the logs.
     *
     * @param string $format The format used for logs.
     * @return void
     */
    public function setLogFormat($format)
    {
        $this->format = $format;
    }


    /**
     * Log a message to the log file.
     *
     * @param int    $level The log level.
     * @param string $string The formatted message to log.
     * @return void
     */
    public function log($level, $string)
    {
        if (!is_null($this->logFile)) {
            // set human-readable log level. Copied from SimpleSAML\Logger\ErrorLogLoggingHandler.
            $levelName = sprintf('UNKNOWN%d', $level);
            if (array_key_exists($level, self::$levelNames)) {
                $levelName = self::$levelNames[$level];
            }

            $formats = ['%process', '%level'];
            $replacements = [$this->processname, $levelName];

            $matches = [];
            if (preg_match('/%date(?:\{([^\}]+)\})?/', $this->format, $matches)) {
                $format = "%b %d %H:%M:%S";
                if (isset($matches[1])) {
                    $format = $matches[1];
                }

                array_push($formats, $matches[0]);
                array_push($replacements, self::formatDate($format));
            }

            $string = str_replace($formats, $replacements, $string);
            file_put_contents($this->logFile, $string . \PHP_EOL, FILE_APPEND);
        }
    }


    /**
     * Format the current time using a strftime() format string.
     *
     * strftime() is deprecated as of PHP 8.1, so the common conversion
     * specifiers are translated to their date() equivalents.
     *
     * @param string $format The strftime() format.
     * @return string
     */
    private static function formatDate(string $format): string
    {
        $map = [
            'a' => 'D', 'A' => 'l', 'd' => 'd', 'e' => 'j', 'j' => 'z', 'u' => 'N', 'w' => 'w',
            'V' => 'W', 'b' => 'M', 'h' => 'M', 'B' => 'F', 'm' => 'm', 'y' => 'y', 'Y' => 'Y',
            'G' => 'o', 'H' => 'H', 'k' => 'G', 'I' => 'h', 'l' => 'g', 'M' => 'i', 'p' => 'A',
            'P' => 'a', 'S' => 's', 'z' => 'O', 'Z' => 'T', 's' => 'U',
            'D' => 'm/d/y', 'F' => 'Y-m-d', 'T' => 'H:i:s', 'R' => 'H:i', 'r' => 'h:i:s A',
        ];
        $result = '';
        $length = strlen($format);
        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];
            if ($char === '%' && $i + 1 < $length) {
                $spec = $format[++$i];
                if (isset($map[$spec])) {
                    $result .= date($map[$spec]);
                    continue;
                }
                $char = ($spec === '%') ? '%' : '%' . $spec;
            }
            $result .= $char;
        }
        return $result;
    }
}
