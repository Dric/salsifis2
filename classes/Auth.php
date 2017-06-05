<?php

/**
 * Created by PhpStorm.
 * User: Dric
 * Date: 05/06/2017
 * Time: 00:01
 */
class Auth {
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
		return (isset($_COOKIE[$cookieName]) and $_COOKIE[$cookieName] === hash('sha256', Settings::TITLE.$_SERVER['SERVER_ADDR'])) ? true : false;
	}

	/**
	 * Crée un cookie avec une durée de validité
	 *
	 * @param int $expiration
	 *
	 * @return bool
	 */
	static function setCookie($expiration = 0){
		$cookieName = Sanitize::sanitizeFilename(Settings::TITLE);
		return setcookie($cookieName, hash('sha256', Settings::TITLE.$_SERVER['SERVER_ADDR']), $expiration, '/', '', FALSE, TRUE);
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
			self::doLogin($stayConnected, $from);
		}
		Components::Alert('danger', 'Mot de passe incorrect !');
		return false;
	}

	/**
	 * Effectue la connexion et redirige vers la page demandée le cas échéant
	 *
	 * @param bool    $stayConnected
	 * @param string  $from
	 */
	static function doLogin($stayConnected = false, $from = null){
		$cookieExpiration = ($stayConnected) ? (time()+15552000) : 0;
		if (self::setCookie($cookieExpiration)) {
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