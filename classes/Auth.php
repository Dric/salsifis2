<?php

use FileSystem\Fs;

/**
 * Created by PhpStorm.
 * User: Dric
 * Date: 05/06/2017
 * Time: 00:01
 */
class Auth {

	/** Fichier contenant les identifiants */
	protected static $credsFile = './settings/creds.conf';
	protected static $sessionsFile = './logs/sessions.log';

	/**
	 * Vérifie que l'utilisateur connecté est un invité
	 *
	 * @return boolean
	 */
	static function isGuest(){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		return (isset($_COOKIE[$cookieName]) and $_COOKIE[$cookieName] === Settings::GUEST_PASSWORD);
	}

	/**
	 * Suppression du cookie d'authentification et de la session PHP
	 */
	static function deleteCookie(){
		global $user;
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		setcookie($cookieName, "", time()-3600, '/', '', FALSE, TRUE); //On supprime le cookie
		unset($_COOKIE[$cookieName]);
		$sessions = self::getSessions();
		unset($sessions[$user]);
		self::saveSessions();
		$_SESSION = array();
		session_destroy();
	}

	/**
	 * Vérifie que le cookie permet de s'identifier
	 *
	 * @return bool|string Nom de l'utilisateur si OK
	 */
	static function isValidCookie(){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		$sessions = self::getSessions();
		if (isset($_COOKIE[$cookieName])) {
			$login = array_search($_COOKIE[$cookieName], $sessions);
			return $login;
		}
		return false;
	}

	/**
	 * Crée un cookie avec une durée de validité
	 *
	 * @param int 		$expiration
	 * @param string	$login		Nom d'utilisateur
	 *
	 * @return bool
	 */
	static function setCookie($expiration = 0, $login){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		$sessions = self::getSessions();
		$creds = self::getSavedCreds();
		if (isset($creds['@@@_'.$login])) {
			$login = '@@@_'.$login;
		}
		$sessions[$login] = str_replace('=', '#', base64_encode(random_bytes(32)));
		self::saveSessions($sessions);
		return setcookie($cookieName, $sessions[$login], $expiration, '/', '', FALSE, TRUE);
	}

	/**
	 * Vérifie que l'utilisateur est connecté
	 *
	 * @return string|bool
	 */
	static function isLoggedIn(){
		return self::isValidCookie();
	}

	/**
	 * Valide la connexion
	 * @return bool
	 */
	static function tryLogin(){
		$from = (isset($_REQUEST['from'])) ? $_REQUEST['from'] : '';
		if (!isset($_REQUEST['loginName']) or empty($_REQUEST['loginName'])) {
			Components::Alert('danger', 'Erreur : Le nom est requis !');
			return false;
		}
		if (!isset($_REQUEST['loginPwd']) or empty($_REQUEST['loginPwd'])) {
			Components::Alert('danger', 'Erreur : Le mot de passe est requis !');
			return false;
		}
		$loginName = htmlspecialchars($_REQUEST['loginName']);
		// On va vérifier le mot de passe directement sans rien faire d'autre, pas besoin de triturer la chaîne envoyée.
		$loginPwd = $_REQUEST['loginPwd'];
		$stayConnected = (isset($_REQUEST['stayConnected'])) ? true : false;
		$creds = Auth::getSavedCreds();
		if (isset($creds[$loginName]) or isset($creds['@@@_'.$loginName])) {
			if (password_verify($loginPwd, $creds[$loginName]) or password_verify($loginPwd, $creds['@@@_'.$loginName])){
				self::doLogin($stayConnected, $from, $loginName);
			}
		}
		Components::Alert('danger', 'Mot de passe incorrect ou nom inconnu !');
		if (!isset($_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']])) {
			$_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']] = 0;
		}
		$_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']]++;
		// Au bout de 5 essais, le temps pour afficher la mire de connexion va doubler (30 secondes pour afficher la mire après 5 essais ratés).
		if (isset($_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']]) and $_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']] > 5) {
			sleep(pow(2, $_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']]-1));
		}
		return false;
	}

	/**
	 * Effectue la connexion et redirige vers la page demandée le cas échéant
	 *
	 * @param bool		$stayConnected	Le cookie n'expire pas à la fin de la session
	 * @param string	$from			Page d'origine
	 * @param string	$login			Nom d'utilisateur
	 */
	static function doLogin($stayConnected = false, $from = null, $login){
		$cookieExpiration = ($stayConnected) ? (time()+15552000) : 0;
		unset($_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']]);
		if (self::setCookie($cookieExpiration, $login)) {
			if (!empty($from)){
				$args = Get::urlParamsToArray($from);
				unset($args['from']);
				unset($args['action']);
				$urlArgs = http_build_query($args);
				header('location: index.php'. ((!empty($urlArgs)) ? '?'.$urlArgs : ''));
			}else{
				header('location: index.php');
			}
		} else {
			Components::Alert('danger', 'Erreur : Impossible de créer le cookie d\'authentification !');
		}
	}

	/**
	 * Récupère la liste des identifiants avec leurs mots de passe associés
	 *
	 * @return array|bool retourne un tableau associatif contenant les identifiants en clé et les mots de passe en valeur, et `false` en cas d'absence de fichier
	 */
	static function getSavedCreds() {
		if (!file_exists(self::$credsFile)) {
			$ret = self::createCredsFile();
			if (!$ret) {
				Components::Alert('danger', 'Erreur : Impossible de créer le fichier des identifiants !');
				return false;
			}
		}
		$creds = array();
		$credsRaw = file(self::$credsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!empty($credsRaw)) {
			foreach ($credsRaw as $line) {
				$line = trim($line);
				if (!empty($line)) {
					list($login, $pwd) = explode('=', $line);
					$creds[$login] = $pwd;
				}
			}
		}
		return $creds;
	}

	/**
	 * Crée le fichier des identifiants
	 *
	 * @return bool
	 */
	static function createCredsFile() {
		// Un `fclose(fopen())` fonctionne si php a des droits d'écriture mais n'est pas propriétaire du fichier, ce qui n'est pas le cas avec `touch`
		return fclose(fopen(self::$credsFile, 'a'));
	}

	/**
	 * Sauvegarde les identifiants dans un fichier
	 *
	 * @param array $creds tableau associatif des identifiants
	 * @param bool	$noPwdEncryption ne chiffre pas les mots de passe si défini à `true`
	 * @return bool
	 */
	static function saveCreds(array $creds, $noPwdEncryption = false) {
		$savedCreds = self::getSavedCreds();
		foreach ($creds as $login => $pwd) {
			if (empty($pwd)) {
				unset($creds[$login]);
			} elseif (!$noPwdEncryption) {
				$creds[$login] = password_hash($pwd, PASSWORD_DEFAULT);
			}
		}
		// Pour conserver en priorité les valeurs à sauvegarder, on met le tableau `$creds` en dernier
		$creds = array_merge($savedCreds, $creds);
		ksort($creds);
		$toSave = '';
		foreach ($creds as $login => $pwd) {
			if (!empty($login) and !empty($pwd)) {
				$toSave .= $login . '=' .  $pwd . PHP_EOL ;
			}
		}
		$toSave = rtrim($toSave);
		return file_put_contents(self::$credsFile, $toSave);
	}

	/**
	 * supprime un utilisateur
	 * 
	 * A l'instar de la commande `unset`, la fonction ne renvoie rien
	 *
	 * @param string $user
	 * @return void
	 */
	static function removeLogin($user) {
		$savedCreds = self::getSavedCreds();
		unset($savedCreds[$user]);
		self::saveCreds($savedCreds, true);
	}

	/**
	 * Récupère la liste des identifiants avec leurs hashs de session
	 *
	 * @return array|bool retourne un tableau associatif contenant les identifiants en clé et les sessions en valeur, et `false` en cas d'absence de fichier
	 */
	static function getSessions() {
		if (!file_exists(self::$sessionsFile)) {
			$ret = self::createSessionFile();
			if (!$ret) {
				Components::Alert('danger', 'Erreur : Impossible de créer le fichier des sessions !');
				return false;
			}
		}
		$sessions = array();
		$sessionsRaw = file(self::$sessionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!empty($sessionsRaw)) {
			foreach ($sessionsRaw as $line) {
				list($login, $session) = explode('=', $line);
				$sessions[$login] = $session;
			}
		}
		return $sessions;
	}

	/**
	 * Crée le fichier des sessions
	 *
	 * @return bool
	 */
	static function createSessionFile() {
		// Un `fclose(fopen())` fonctionne si php a des droits d'écriture mais n'est pas propriétaire du fichier, ce qui n'est pas le cas avec `touch`
		return fclose(fopen(self::$sessionsFile, 'a'));
	}

	/**
	 * Sauvegarde les identifiants dans un fichier
	 *
	 * @param array $creds tableau associatif des identifiants
	 * @return bool
	 */
	static function saveSessions($sessions) {
		$savedSessions = self::getSessions();
		foreach ($sessions as $login => $session) {
			if (empty($session)) {
				unset($sessions[$login]);
			}
		}
		// Pour conserver en priorité les valeurs à sauvegarder, on met le tableau `$sessions` en dernier
		$sessions = array_merge($savedSessions, $sessions);
		ksort($sessions);
		$toSave = '';
		foreach ($sessions as $login => $session) {
			if (!empty($login)) {
				$toSave .= $login . '=' . $session . PHP_EOL;
			}
		}
		$toSave = rtrim($toSave);
		return file_put_contents(self::$sessionsFile, $toSave);
	}

}