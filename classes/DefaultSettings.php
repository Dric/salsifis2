<?php

/**
 * Creator: Dric
 * Date: 19/05/2017
 * Time: 10:00
 */
class DefaultSettings {
	const TITLE                 = 'Les Salsifis';
	const DEBUG                 = false;
	const BG_IMG                = "bg.jpg";
	const DATA_PARTITION        = '/media/data';
	const TRANSMISSION_WEB_URL  = "/:9091/bt/web";
	const TRANSMISSION_RPC_URL  = "http://localhost:9091/bt/rpc";
	const DOWNLOAD_DIRS         = array(
		'Vidéos/Adultes'  => 'dnla/videos/Adultes',
	  'Vidéos/Enfants'  => 'dnla/videos/Enfants',
	  'Vidéos/Séries'   => 'dnla/videos/Series',
		'Musique'         => 'dlna/musique',
	  'Jeux'            => 'jeux',
	  'Livres'          => 'livres',
	  'Fichiers'        => 'fichiers'
	);
}