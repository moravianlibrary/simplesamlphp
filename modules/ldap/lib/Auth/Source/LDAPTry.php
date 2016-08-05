<?php

/**
 * LDAP authentication source.
 *
 * See the ldap-entry in config-templates/authsources.php for information about
 * configuration of this authentication source.
 *
 * This class is based on www/auth/login.php.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_ldap_Auth_Source_LDAPTry extends sspmod_core_Auth_UserPassBase {

	/**
	 * A LDAP configuration object.
	 */
	private $ldapConfig;
	private $servers;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		$this->servers = array();
		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgHelper = SimpleSAML_Configuration::loadFromArray($config,
                        'Authentication source ' . var_export($this->authId, TRUE));

                $this->ldaps = array();
                foreach ($config as $name => $value) {
                        $cfg = $cfgHelper->getArray($name);
                        $orgId = $name;
                        $orgCfg = new sspmod_ldap_ConfigHelper($cfg,
                                'Authentication source ' . var_export($this->authId, TRUE) .
                                ', organization ' . var_export($orgId, TRUE));
			$orgCfg->id = "uid";
                        $this->ldaps[] = $orgCfg;
			$this->servers[] = array("id" => $cfg['id'], "server" => $orgCfg, "source"=> array($cfg["hostname"]));
                }
	}


	/**
	 * Attempt to log in using the given username and password.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * param array $sasl_arg  Associative array of SASL options
	 * @return array  Associative array with the users attributes.
	 */
	public function login($username, $password, array $sasl_args = NULL) {
		assert('is_string($username)');
		assert('is_string($password)');
		foreach($this->servers as $server) {
			try {
				$result = $server["server"]->login($username, $password, $sasl_args);
				$result['id'] = $result[$server["id"]];
				$result['source'] = $server["source"];
				return $result;
			} catch (Exception $ex) {}
		}
		throw new SimpleSAML_Error_Error('WRONGUSERPASS');
	}

	public function lookupUserByAttributeName($attribute, $username) {
		assert('is_string($username)');
		assert('is_string($attribute)');
		foreach($this->servers as $server) {
			try {
				$dn = $server["server"]->searchfordn($attribute, $username, true);
				if ($dn != NULL) {
					$result =  $server["server"]->getAttributes($dn);
					$result['id'] = $result[$server["id"]];
					$result['source'] = $server["source"];
					return $result;
				}
				
			} catch (Exception $ex) {
			}
		}
		return null;
	}

}


?>
