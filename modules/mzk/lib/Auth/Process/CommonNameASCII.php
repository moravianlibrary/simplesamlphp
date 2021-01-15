<?php

namespace SimpleSAML\Module\mzk\Auth\Process;

class CommonNameASCII extends \SimpleSAML\Auth\ProcessingFilter {

    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
    }

    /**
     * Add commonNameASCII generated from CN
     *
     * @param array &$request  The current request
     */
    public function process(&$request) {
        $attributes = &$request['Attributes'];
        if (isset($attributes['cn']) && !empty($attributes['cn'][0])) {
            $attributes["commonNameASCII"][] = iconv("UTF-8", "ASCII//TRANSLIT", $attributes['cn'][0]);
        }
    }

}
