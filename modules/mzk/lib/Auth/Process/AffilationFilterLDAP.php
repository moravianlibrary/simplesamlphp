<?php

namespace SimpleSAML\Module\mzk\Auth\Process;

class AffilationFilterLDAP extends \SimpleSAML\Auth\ProcessingFilter {

    private $mapping = [];

    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        $this->mapping = $config['mapping'] ?? [];
    }

    /**
     * Add attributes from an LDAP server.
     *
     * @param array &$request  The current request
     */
    public function process(&$request) {
        $attributes = &$request['Attributes'];
        $employee = isset($attributes["ldap.source"])
            && $attributes["ldap.source"][0] == "employees";
        if ($employee) {
            $attributes["uid"] = $attributes["id"];
            $attributes["eduPersonAffiliation"][] = "staff";
            $attributes["eduPersonScopedAffiliation"][] = "staff@mzk.cz";
            $attributes["eduPersonAffiliation"][] = "member";
            $attributes["eduPersonScopedAffiliation"][] = "member@mzk.cz";
            $attributes["eduPersonAffiliation"][] = "employee";
            $attributes["eduPersonScopedAffiliation"][] = "employee@mzk.cz";
            $attributes["mzkPermission"][] = "wifi";
            $entityId = $request['SPMetadata']['entityid'];
            $this->processEmployeeGroups($entityId, $attributes);
        }
        if (in_array('mzkWifiAccount', $attributes['objectClass'])) {
            $attributes["mzkPermission"][] = "wifi";
        }
        if (in_array('mzkProxyAccount', $attributes['objectClass'])) {
            $attributes["eduPersonAffiliation"][] = "member";
            $attributes["eduPersonScopedAffiliation"][] = "member@mzk.cz";
            $attributes["eduPersonEntitlement"][] = "urn:mace:dir:entitlement:common-lib-terms";
        }
    }

    protected function processEmployeeGroups($entityId, &$attributes) {
        $attributes["eduPersonEntitlement"][] = "urn:mace:dir:entitlement:common-lib-terms";
        if (!isset($attributes['memberOf']) || !is_array($attributes['memberOf'])) {
            return;
        }
        $mapping = $this->mapping[$entityId] ?? null;
        if ($mapping == null) {
            $mapping = $this->mapping['default'] ?? [];
        }
        foreach ($mapping as $memberOf => $entitlements) {
            if (in_array($memberOf, $attributes['memberOf'])) {
                foreach ($entitlements as $entitlement) {
                    $attributes["eduPersonEntitlement"][] = $entitlement;
                }
            }
        }
    }

}
