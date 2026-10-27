<?php

namespace SimpleSAML\Module\mzk\Auth\Process;

class EduPersonTargetedIDLogger extends \SimpleSAML\Auth\ProcessingFilter {

    /**
     * Path to the separate audit log file, or null if only the main
     * SimpleSAMLphp log should be used.
     *
     * @var string|null
     */
    private $logFile = null;


    /**
     * Initialize this filter.
     *
     * Options:
     *  - logfile: file to which access entries are also appended. A relative
     *             path is resolved against the 'loggingdir' option.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        if (isset($config['logfile'])) {
            $logFile = $config['logfile'];
            if ($logFile[0] !== '/') {
                $globalConfig = \SimpleSAML\Configuration::getInstance();
                $logFile = $globalConfig->getPathValue('loggingdir', 'log/') . $logFile;
            }
            $this->logFile = $logFile;
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
        \SimpleSAML\Logger::info($message);
        if ($this->logFile !== null) {
            $trackId = \SimpleSAML\Session::getSessionFromRequest()->getTrackID();
            $line = date('c') . " [$trackId] $message" . PHP_EOL;
            if (@file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX) === false) {
                \SimpleSAML\Logger::error("EduPersonTargetedIDLogger: could not write to {$this->logFile}");
            }
        }
    }

}
