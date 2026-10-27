<?php

namespace SimpleSAML\Module\mzk\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Logger\FileLoggingHandler;
use SimpleSAML\Session;

class EduPersonTargetedIDLogger extends \SimpleSAML\Auth\ProcessingFilter {

    /**
     * File to which access entries are also appended, or null if only the
     * main SimpleSAMLphp log should be used.
     *
     * @var string|null
     */
    private $logFile = null;


    /**
     * Initialize this filter.
     *
     * Options:
     *  - logfile: file to which access entries are also appended, using the
     *             'logging.format' option. A relative path is resolved against
     *             the 'loggingdir' option.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        if (isset($config['logfile'])) {
            $this->logFile = $config['logfile'];
        }
    }


    /**
     * Log the user accessing the service provider.
     *
     * @param array &$request  The current request
     */
    public function process(&$request) {
        assert(is_array($request));
        assert(array_key_exists('Attributes', $request));
        $attributes = &$request['Attributes'];
        $metadata = &$request['SPMetadata'];
        $pseudonym = null;
        if (isset($attributes['pseudonym']) && is_array($attributes['pseudonym'])) {
            $pseudonym = $attributes['pseudonym'][0];
        }
        $eduPersonPrincipalName = $attributes['eduPersonPrincipalName'][0];
        $eduPersonTargetedID = $attributes['eduPersonTargetedID'][0]->getValue();
        $consumerService = $metadata['entityid'];
        if ($pseudonym != null) {
            $message = "User $eduPersonPrincipalName ($eduPersonTargetedID) with"
                 . " username $pseudonym accesses $consumerService";
        } else {
            $message = "User $eduPersonPrincipalName ($eduPersonTargetedID) accesses $consumerService";
        }
        Logger::info($message);
        if ($this->logFile !== null) {
            $this->logToFile($message);
        }
    }


    /**
     * Append the message to the separate log file, formatted the same way
     * as the main log.
     *
     * The %date, %process and %level placeholders are replaced by
     * FileLoggingHandler, the rest is done by Logger::log() in the main log,
     * which is private, so it is replicated here.
     *
     * @param string $message  The message to log.
     */
    private function logToFile($message) {
        $globalConfig = Configuration::getInstance();
        $options = $globalConfig->toArray();
        if ($this->logFile[0] === '/') {
            $options['loggingdir'] = dirname($this->logFile);
            $options['logging.logfile'] = basename($this->logFile);
        } else {
            $options['logging.logfile'] = $this->logFile;
        }
        try {
            $handler = new FileLoggingHandler(Configuration::loadFromArray($options));
        } catch (\Exception $e) {
            Logger::error('EduPersonTargetedIDLogger: ' . $e->getMessage());
            return;
        }
        $format = $globalConfig->getString('logging.format', '%date{%b %d %H:%M:%S} %process %level %stat[%trackid] %msg');
        $handler->setLogFormat($format);
        $line = str_replace(
            ['%trackid', '%msg', '%srcip', '%stat'],
            [Session::getSessionFromRequest()->getTrackID(), $message, $_SERVER['REMOTE_ADDR'] ?? 'N/A', ''],
            $format
        );
        $handler->log(Logger::INFO, $line);
    }

}
