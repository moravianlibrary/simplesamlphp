<?php

class sspmod_walkin_Auth_Process_RevalidateLibraryWalkIn extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * Authsources configuration .. 
	 */
	private $authsourcesConfig;


	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		$this->authsourcesConfig = SimpleSAML_Configuration::getConfig('authsources.php')->toArray();

	}


	/*
	 * Check user really is walkin.
	 *
	 * This filter is here because user could have tried to use his cookie to reauthenticate outside of the library
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {

		if (isset($request['IdPMetadata']) && isset($request['IdPMetadata']['auth']))
			/* 
			 * TODO: We should check if this AuthId is multiauth .. and if it is, our goal is to find authId
			 * of used walkin IdP instead of hardcoded 'walkin' - it doesn't have to be named like that
			 */
			$authId = $request['IdPMetadata']['auth'];

		$authId = 'walkin';

		$canBeWalkIn = sspmod_walkin_Auth_Source_LibraryWalkIn::canBeWalkIn($this->authsourcesConfig[$authId], $request);

		if ($canBeWalkIn === FALSE) {
			$id  = SimpleSAML_Auth_State::saveState($request, 'mzk:outside_library');
			$url = SimpleSAML\Module::getModuleURL('mzk/outside_library.php');
			\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
		}
	}

}
