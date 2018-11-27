<?php

/**
 * Created by PhpStorm.
 * User: Dric
 * Date: 05/06/2017
 * Time: 00:01
 */
class Auth {

	/**
	 * Vérifie que l'utilisateur connecté est un invité
	 *
	 * @return boolean
	 */
	static function isGuest(){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		return ($_COOKIE[$cookieName] === Settings::GUEST_PASSWORD);
	}

	/**
	 * Suppression du cookie d'authentification et de la session PHP
	 */
	static function deleteCookie(){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		setcookie($cookieName, "", time()-3600, '/', '', FALSE, TRUE); //On supprime le cookie
		unset($_COOKIE[$cookieName]);
		$_SESSION = array();
		session_destroy();
	}

	/**
	 * Vérifie que le cookie permet de s'identifier
	 *
	 * @return bool
	 */
	static function isValidCookie(){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		return (isset($_COOKIE[$cookieName]) and ($_COOKIE[$cookieName] === Settings::PASSWORD or $_COOKIE[$cookieName] === Settings::GUEST_PASSWORD)) ? true : false;
	}

	/**
	 * Crée un cookie avec une durée de validité
	 *
	 * @param int 		$expiration
	 * @param string	$pwd		Mot de passe utilisé
	 *
	 * @return bool
	 */
	static function setCookie($expiration = 0, $pwd){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		// Mettre le mot de passe dans le cookie est probablement horrible d'un point de vue sécurité, mais on ne cherche pas non plus à sécuriser Fort Knox.
		return setcookie($cookieName, $pwd, $expiration, '/', '', FALSE, TRUE);
	}

	/**
	 * Vérifie que l'utilisateur est connecté
	 *
	 * @return bool
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
		if (!isset($_REQUEST['loginPwd']) or empty($_REQUEST['loginPwd'])) {
			Components::Alert('danger', 'Erreur : Le mot de passe est requis !');
			return false;
		}
		$loginPwd = htmlspecialchars($_REQUEST['loginPwd']);
		$stayConnected = (isset($_REQUEST['stayConnected'])) ? true : false;
		if (password_verify($loginPwd, Settings::PASSWORD)){
			self::doLogin($stayConnected, $from, Settings::PASSWORD);
		}
		if (password_verify($loginPwd, Settings::GUEST_PASSWORD)){
			self::doLogin($stayConnected, $from, Settings::GUEST_PASSWORD);
		}
		Components::Alert('danger', 'Mot de passe incorrect !');
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
	 * @param string	$pwd			Mot de passe chiffré
	 */
	static function doLogin($stayConnected = false, $from = null, $pwd){
		$cookieExpiration = ($stayConnected) ? (time()+15552000) : 0;
		unset($_SESSION['loginAttempts'][$_SERVER['REMOTE_ADDR']]);
		if (self::setCookie($cookieExpiration, $pwd)) {
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

}