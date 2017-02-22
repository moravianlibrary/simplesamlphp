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
	 * Array of CIDRs defining library's network for walk-in users
	 */
	private $libraryCIDRs;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgParse = SimpleSAML_Configuration::loadFromArray($config,
			'Authentication source ' . var_export($this->authId, TRUE));

		$this->libraryCIDRs = $cfgParse->getArray('libraryCIDRs', NULL);

		if ($this->libraryCIDRs === NULL)
			throw new SimpleSAML_Error_Exception('Missing libraryCIDRs array for library walkin');
	}


	/**
	 * Authenticate the user, only if he really is walkin user.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');


		if ( self::user_cidr_match($this->libraryCIDRs) == FALSE ) {
			throw new SimpleSAML_Error_Error('NOACCESS');
		}

		$attributes = array();

		$attributes['eduPersonScopedAffiliation'] = array('*');
		$attributes['eduPersonAffiliation'] = array('library-walk-in');

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);
	}


	/**
	 * Returns TRUE only if there is at least one cidr mathing user's IP
	 * 
	 * See https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5/
	 *
	 * @param string $ip
	 * @param array $cidrs
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

		SimpleSAML_Logger::info('IP address ' . $userIp . ($cidrMatches ? ' belongs ' : " doesn't belong ") . 'to any following CIDR: ' . join(', ', $cidrs));
		
		return $cidrMatches;
	}

}
