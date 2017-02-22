<?php

class sspmod_walkin_Auth_Process_RevalidateLibraryWalkIn extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * Array of CIDRs defining library's network for walk-in users
	 */
	private $libraryCIDRs;


	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);


		$config = SimpleSAML_Configuration::getConfig('authsources.php')->toArray();

		if (isset($config['walkin']) && isset($config['walkin']['libraryCIDRs']) ) {
			$this->libraryCIDRs = $config['walkin']['libraryCIDRs'];
		}

		if ($this->libraryCIDRs === NULL)
			throw new SimpleSAML_Error_Exception('Missing libraryCIDRs array for library walkin');
	}


	/**
	 * Check user really is walkin.
	 *
	 * This filter is here because user could have tried to use his cookie to reauthenticate outside of the library
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {

		$isWalkIn = sspmod_walkin_Auth_Source_LibraryWalkIn::user_cidr_match($this->libraryCIDRs);

		if ($isWalkIn === FALSE) {
			$id  = SimpleSAML_Auth_State::saveState($request, 'mzk:outside_library');
			$url = SimpleSAML\Module::getModuleURL('mzk/outside_library.php');
			\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
		}
	}

}
