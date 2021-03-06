<?php

/**
 * Creator: Dric
 * Date: 19/05/2017
 * Time: 10:00
 */
class DefaultSettings {
	/** Nom du serveur */
	const TITLE                 = 'Les Salsifis';
	/** Mode debug */
	const DEBUG                 = false;
	/** Fond d'écran */
	const BG_IMG                = "bg.jpg";
	/** Répertoire des données */
	const DATA_PARTITION        = '/media/data';
	/** Répertoire des données pour les invités */
	const GUEST_DATA_PARTITION        = '/media/data/dlna/videos';
	/** Lien vers l'interface web de Transmission */
	const TRANSMISSION_WEB_URL  = ":9091/bt/web";
	/** Lien vers l'API RPC de Transmission */
	const TRANSMISSION_RPC_URL  = "http://localhost:9091/bt/rpc";
	/** Répertoires de téléchargement */
	const DOWNLOAD_DIRS         = array(
		'dlna/videos/Adultes' => 'Vidéos/Adultes',
	  'dlna/videos/Enfants' => 'Vidéos/Enfants',
	  'dlna/videos/Séries'  => 'Vidéos/Séries',
		'dlna/musique'        => 'Musique',
	  'jeux'                => 'Jeux',
	  'livres'              => 'Livres',
	  'fichiers'            => 'Fichiers'
	);
	/** Utiliser un mot de passe pour se connecter */
	const USE_AUTH = false;
	/** Afficher l'adresse IP externe */
	const DISPLAY_EXTERNAL_IP = false;
	/** Afficher des noms de fichiers nettoyés (peut ralentir fortement la navigation) */
	const DISPLAY_CLEAN_FILENAMES = false;
}