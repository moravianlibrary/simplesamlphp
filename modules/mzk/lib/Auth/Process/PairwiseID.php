<?php

namespace SimpleSAML\Module\mzk\Auth\Process;

use SimpleSAML\Utils;

class PairwiseID extends \SimpleSAML\Auth\ProcessingFilter {

    protected const ATTR_PAIRWISE_ID = 'urn:oasis:names:tc:SAML:attribute:pairwise-id';


    /**
     * The configuration.
     *
     * Associative array of strings.
     */
    private $config = array();

    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        $this->config = $config;
    }

    /**
     * Add commonNameASCII generated from CN
     *
     * @param array &$request  The current request
     */
    public function process(&$request) {
        $attributes = &$request['Attributes'];
        $metadata = &$request['SPMetadata'];
        $uid = $attributes['uid'][0];
        $scope = $attributes['schacHomeOrganization'][0];
        if ($uid == null || $scope == null) {
            return;
        }
        $entityId = $metadata['entityid'];
        $secretSalt = Utils\Config::getSecretSalt();
        $uid = $attributes['uid'][0];
        $hash = hash_hmac('sha256', $uid . '|' . $entityId, $secretSalt, false);
        $value = $hash . '@' . strtolower($scope);
        $attributes[self::ATTR_PAIRWISE_ID][] = $value;
    }

}
