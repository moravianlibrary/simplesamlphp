<?php

class sspmod_mzk_Auth_Process_AffilationFilterLDAP extends SimpleSAML_Auth_ProcessingFilter {

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
	 * Add attributes from an LDAP server.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		$attributes =& $request['Attributes'];
		if ($attributes["source"][0] == "ldap://ad.staff.mzk.cz:389") {
			$attributes["uid"] = $attributes["id"];
			$attributes["eduPersonAffiliation"][] = "staff";
			$attributes["eduPersonScopedAffiliation"][] = "staff@mzk.cz";
			$attributes["eduPersonAffiliation"][] = "member";
			$attributes["eduPersonScopedAffiliation"][] = "member@mzk.cz";
			$attributes["eduPersonAffiliation"][] = "employee";
			$attributes["eduPersonScopedAffiliation"][] = "employee@mzk.cz";
			$attributes["mzkPermission"][] = "wifi";
		}
		if (in_array('mzkWifiAccount', $attributes['objectClass'])) {
			$attributes["mzkPermission"][] = "wifi";
		}
		if (in_array('mzkProxyAccount', $attributes['objectClass'])) {
			$attributes["eduPersonAffiliation"][] = "member";
			$attributes["eduPersonScopedAffiliation"][] = "member@mzk.cz";
		}
	}

}
