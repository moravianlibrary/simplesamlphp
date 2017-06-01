<?php

/**
 * Authentication module which serves to automatically authenticate walk-in users.
 *
 * This solution is following Pilot 2 of AARC at 2017
 *
 * @author Jiří Kozlovský, <mail@jkozlovsky.cz>
 * @package SimpleSAMLphp
 */
class sspmod_walkin_Auth_Source_LibraryWalkIn extends SimpleSAML_Auth_Source {

    /**
     * Affiliation to be used when authenticated as walk-in
     */
    const LIBRARY_WALK_IN_AFFILIATION = 'library-walk-in';

	/**
	 * Array of CIDRs defining library's network for walk-in users
	 */
	private $libraryCIDRs;

	/**
	 * Array of allowed entity ids, to which can be user authenticated using a guest account
	 */
	private $allowedEntityIds = null;

	/**
	 * Walkin attributes to send to SP
	 */
	private $attributes;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 *
	 * @throws SimpleSAML_Error_Exception
	 */
	public function __construct($info, $config) {

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgParse = SimpleSAML_Configuration::loadFromArray($config,
			'Authentication source ' . var_export($this->authId, TRUE));

		$this->libraryCIDRs = $cfgParse->getArray('libraryCIDRs', NULL);

		if ($this->libraryCIDRs === NULL)
			throw new SimpleSAML_Error_Exception('Missing libraryCIDRs array for library walkin');

		$this->allowedEntityIds = $cfgParse->getArray('allowedEntityIds', array());

		if (empty($this->allowedEntityIds))
			SimpleSAML\Logger::warning('walkin->allowedEntityIds is empty, no resource can be accessed as walkin');

		$this->attributes = $cfgParse->getArray('attributes', array());
	}


	/**
	 * Authenticate the user, only if he really is walkin user.
	 *
	 * @param array &$state  Information about the current authentication.
	 *
	 * @throws SimpleSAML_Error_Error
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		# It throws appropriate exceptions when walkin unathorized
		sspmod_walkin_Auth_Source_LibraryWalkIn::canBeWalkIn(null, $state, $this);

		$state['Attributes'] = $this->attributes;

		SimpleSAML_Auth_Source::completeAuth($state);
	}

    /**
     * Determines whether current user can login as a walkin
     *
     * @param array $config
     * @param array $state
     * @param sspmod_walkin_Auth_Source_LibraryWalkIn $thisInstance
     *     (optional)
     * @return bool $canBeWalkIn
     * @throws Exception
     * @throws SimpleSAML_Error_Error
     */
	public static function canBeWalkIn($config, $state, $thisInstance = null) {

		if ($state === null)
			throw new \Exception('You must provide $state!');

		$isProbing = sspmod_walkin_Auth_Source_LibraryWalkIn::isProbing($state);
		if ($isProbing) {
			return TRUE;
		}

		if ($thisInstance !== null) {
			# We have already parsed the configuration while initializing this class ..
			$libraryCIDRs = $thisInstance->libraryCIDRs;
			$allowedEntityIds = $thisInstance->allowedEntityIds;
		} else if (isset($config)) {
			# Check that we have gained mandatory configuration vars
			if (! isset($config['libraryCIDRs']) || ! isset($config['allowedEntityIds']) || ! is_array($config['allowedEntityIds'])) {
				# Cannot be walkin without proper configuration
				return FALSE;
			}
			$libraryCIDRs = $config['libraryCIDRs'];
			$allowedEntityIds = $config['allowedEntityIds'];
		} else {
			SimpleSAML_Logger::info('Neither $config or $thisInstance provided, disabling LibraryWalkIn.');
			return false;
		}

		# Check that user's IP matches defined library CIDR
		$userIpOkay = sspmod_walkin_Auth_Source_LibraryWalkIn::user_cidr_match($libraryCIDRs);
		
		# Obtain requested entityId
		$entityId = isset($state['SPMetadata']) && isset($state['SPMetadata']['entityid']) ? $state['SPMetadata']['entityid'] : null;
		
		# Check that requested entityId is within set of allowed entity ids
		$entityIdSupported = in_array($entityId, $allowedEntityIds);
		SimpleSAML\Logger::info('Checking if entityId "' . $entityId . '" accepts walkin users -> ' . ( $entityIdSupported ? 'TRUE' : 'FALSE'));

		# Throw errors only if we are calling this from this class
		if ($thisInstance !== NULL) {
			if (! $userIpOkay)
				throw new SimpleSAML_Error_Error('___ --- ATTENTION! --- ___ Guest account can be used only from the library network!');
			if (! $entityIdSupported)
				throw new SimpleSAML_Error_Error('___ ---  ATTENTION! --- ___ This resource is not available via guest account! Please log in using your real account.');

		}
		
		$canBeWalkIn = $userIpOkay && $entityIdSupported;

		return $canBeWalkIn;
	
	}


	/**
	 * Returns TRUE only if there is at least one cidr mathing user's IP
	 * 
	 * See https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5/
	 *
	 * @param array $cidrs
	 * @return bool $cidrMatches
	 */
	public static function user_cidr_match($cidrs) {

		$cidrMatches = FALSE;

		$userIp = $_SERVER['REMOTE_ADDR'];
		$ip = ip2long($userIp);

		foreach ($cidrs as $cidr) {

			list ($subnet, $bits) = explode('/', $cidr);

			$subnet = ip2long($subnet);
			$mask = -1 << (32 - $bits);
			$subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned

			if ( ($ip & $mask) == $subnet ) {
				$cidrMatches = TRUE;
				break;
			}
		}

		SimpleSAML\Logger::info('IP address ' . $userIp . ($cidrMatches ? ' belongs ' : " doesn't belong ") . 'to any following CIDR: ' . join(', ', $cidrs));

		return $cidrMatches;
	}

	/**
	 * Checks whether there is only a probe for login using configured authsources without
	 * trying to authentize to external service.
	 *
	 * @param array $state
	 * @return bool $isProbing
	 */
	private static function isProbing($state) {

		$returnTo = isset($state['ReturnTo']) ? $state['ReturnTo'] : '';

		if (strpos($returnTo, 'module.php/core/authenticate.php?as') !== FALSE)
			return TRUE;

		$stateParams = isset($state['stateparams']) ? $state['stateparams'] : [];
		$authState = isset($stateParams['AuthState']) ? $stateParams['AuthState'] : FALSE;

		if ($authState === FALSE)
			return FALSE;

		$linksNull = ! isset($state['links']);
		$metadataNull = ! isset($state['SPMetadata']);

		if (! $linksNull || ! $metadataNull)
			return FALSE;

		list($hash, $entityId) = explode(':', $authState, 2);

		$isProbing = (strpos($entityId, 'module.php/core/as_login.php') !== FALSE);

		return $isProbing;
	}

}
