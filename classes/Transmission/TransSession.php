<?php

namespace Transmission;

/**
* Classe de session Transmission
 *
 * @see <https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt>
 * @package Transmission
 *
 * @property      float       $ratioLimit         Limite de ratio définie
 * @property      bool        $isRatioLimited     Statut de la limite de ratio (activée/désactivée)
 * @property      int         $dlSpeed            Vitesse de téléchargement en mode normal
 * @property      int         $upSpeed            Vitesse de partage en mode normal
 * @property      int         $altDlSpeed         Vitesse de téléchargement en mode tortue
 * @property      int         $altUpSpeed         Vitesse de partage en mode tortue
 * @property      bool        $altSpeedEnabled    Statut du mode tortue (activé/désactivé)
 * @property      int         $altBegin           Heure de début du mode tortue
 * @property      int         $altEnd             Heure de fin du mode tortue
 * @property      int[]|int   $altDaysSchedule    Jours d'activation du mode tortue
 * @property      bool        $altModeEnabled     Mode tortue paramétré
 * @property      string      $blockList          Liste de blocages d'adresses IP
 * @property      bool        $blockListEnabled   Activation de la liste de blocage
 * @property-read int         $blockListSize      Nombre de règles de la liste de blocage
 * @property-read string      $defaultDownloadDir Répertoire de téléchargement par défaut
 */
class TransSession extends TransmissionRPC{

	/**
	* Limite max de ratio partage/téléchargement
	* @var float
	*/
	protected $ratioLimit = 2;

	/**
	 * Limite de ratio activée
	 * @var bool
	 */
	protected $isRatioLimited = true;

	/**
	* Débit descendant (en ko/s)
	* @var int
	*/
	protected $dlSpeed = 350;

	/**
	* Débit montant (en ko/s)
	* @var int
	*
	*/
	protected $upSpeed = 90;

	/**
	* Débit descendant alternatif (en ko/s)
	* @var int
	*/
	protected $altDlSpeed = 80;

	/**
	* Débit montant alternatif (en ko/s)
	* @var int
	*
	*/
	protected $altUpSpeed = 30;

	/**
	* Mode tortue actif
	*
	* A ne pas confondre avec `$altModeEnabled` qui lui gère la planification
	*
	* @var bool
	*/
	protected $altSpeedEnabled = false;

	/**
	* Heure quotidienne du basculement sur les vitesses alternatives (exprimé en secondes depuis 0h00)
	*
	* 27000 secondes = 7h30
	* @var int
	*/
	protected $altBegin = 27000;

	/**
	* Heure quotidienne de la fin d'utilisation des vitesses alternatives (exprimé en secondes depuis 0h00)
	*
	* 84600 secondes = 23h30
	* @var int
	*/
	protected $altEnd = 84600;

	/**
	* Jours d'activation des vitesses alternatives
	*
	* Dimanche					= 1			(binary: 0000001)
  * Lundi							= 2			(binary: 0000010)
  * Mardi							= 4			(binary: 0000100)
  * Mercredi					= 8			(binary: 0001000)
  * Jeudi							= 16		(binary: 0010000)
  * Vendredi					= 32		(binary: 0100000)
  * Samedi						= 64		(binary: 1000000)
  * Jours ouvrés			= 62		(binary: 0111110)
  * Weekend						= 65		(binary: 1000001)
  * Toute la semaine	= 127		(binary: 1111111)
  * Aucun							= 0			(binary: 0000000)
  *
  * Il suffit d'additionner les jours pour en cumuler plusieurs. Ex : lundi, mardi et mercredi : 14
	* @var int
	*/
	protected $altDaysSchedule = 127;

	/**
	 * Activation de la planification de mode tortue
	 * @var bool
	 */
	protected $altModeEnabled = true;

	/**
	 * Répertoire de téléchargement par défaut
	 * @var string
	 */
	protected $defaultDownloadDir = null;

	/**
	 * Liste de blocage d'adresses IP
	 * @var string
	 */
	protected $blockList = null;

	/**
	 * Activation de la liste de blocage
	 * @var bool
	 */
	protected $blockListEnabled = true;

	/**
	 * Nombre de règles de la liste de blocage
	 * @var int
	 */
	protected $blockListSize = 0;


	/**
	 * @param string $transmissionURL URL de transmissionRPC
	 */
	public function __construct($transmissionURL){
		parent::__construct($transmissionURL);
		$settings = $this->request("session-get", array())->arguments;
		//echo '<pre><code>';var_dump($settings);echo '</code></pre>';
		$this->ratioLimit			    = (isset($settings->seedRatioLimit)) ? round($settings->seedRatioLimit, 1) : 0;
		$this->isRatioLimited     = (isset($settings->seedRatioLimited)) ? (bool)$settings->seedRatioLimited : false;
		$this->dlSpeed					  = (isset($settings->speed_limit_down)) ? (int)$settings->speed_limit_down : 0;
		$this->upSpeed					  = (isset($settings->speed_limit_up)) ? (int)$settings->speed_limit_up : 0;
		$this->altDlSpeed			    = (isset($settings->alt_speed_down)) ? (int)$settings->alt_speed_down : 0;
		$this->altUpSpeed			    = (isset($settings->alt_speed_up)) ? (int)$settings->alt_speed_up : 0;
		$this->altSpeedEnabled	  = (isset($settings->alt_speed_enabled)) ? (bool)$settings->alt_speed_enabled : false;
		$this->altBegin				    = (isset($settings->alt_speed_time_begin)) ? (int)($settings->alt_speed_time_begin*60) : 0;
		$this->altEnd					    = (isset($settings->alt_speed_time_end)) ? (int)($settings->alt_speed_time_end*60) : 0;
		$this->altDaysSchedule	  = (isset($settings->alt_speed_time_day)) ? (int)$settings->alt_speed_time_day : 0;
		$this->altModeEnabled	    = (isset($settings->alt_speed_time_enabled)) ? (bool)$settings->alt_speed_time_enabled : false;
		$this->defaultDownloadDir = (isset($settings->download_dir)) ? $settings->download_dir : '';
		$this->blockList          = (isset($settings->blocklist_url)) ? $settings->blocklist_url : '';
		$this->blockListEnabled   = (isset($settings->blocklist_enabled)) ? (bool)$settings->blocklist_enabled : false;
		$this->blockListSize      = (isset($settings->blocklist_size)) ? (int)$settings->blocklist_size : 0;
		//echo \Get::varDump($this);
	}

	/**
	 * Sauvegarde les paramètres du serveur transmission
	 */
	public function saveSession(){
		$arguments = array(
			'seedRatioLimit'        => ($this->ratioLimit > 0) ?$this->ratioLimit : 0,
			'seedRatioLimited'      => (int)$this->isRatioLimited,
		  'speed-limit-down'      => $this->dlSpeed,
		  'speed-limit-up'        => $this->upSpeed,
		  'alt-speed-down'        => $this->altDlSpeed,
		  'alt-speed-up'          => $this->altUpSpeed,
		  'alt-speed-enabled'     => (int)$this->altSpeedEnabled,
		  'alt-speed-time-begin'  => floor($this->altBegin/60),
		  'alt-speed-time-end'    => floor($this->altEnd/60),
		  'alt-speed-time-day'    => $this->altDaysSchedule,
		  'alt-speed-time-enabled'=> (int)$this->altModeEnabled
		);
		$ret = $this->sset($arguments);
		if ($ret->result == 'success'){
			\Components::Alert('success', 'Les paramètres du serveur ont été sauvegardés !');
			return true;
		}else{
			\Components::Alert('danger', 'Les paramètres du serveur n\'ont pas pu être sauvegardés !');
			return false;
		}
	}

	/**
	 * Retourne le bit à la position $n dans un nombre
	 *
	 * @param int $number Nombre
	 * @param int $n Position (commence à 1)
	 *
	 * @return int
	 */
	protected function nbit($number, $n) {
		return ($number >> $n-1) & 1;
	}

	/**
	 * Permet d'accéder aux propriétés de la classe
	 * @param string $prop Propriété
	 *
	 * @return mixed
	 */
	public function __get($prop){
		return $this->getProp($prop);
	}

	public function __isset($prop){
		return isset($this->$prop);
	}

	/**
	 * Met en forme et retourne les propriétés de la classe
	 *
	 * Les propriétés de la classe étant privées, pour y accéder il suffit de demander la variable sans le préfixe '_'.
	 * Ex : Pour obtenir la taille totale du torrent, qui est la propriété $totalSize, il suffit de demander $torrent->totalsize ou encore $this->get('totalSize') à l'intérieur de la classe
	 *
	 * @param string $prop Propriété à retourner.
	 *
	 * @param bool   $realValue
	 *
	 * @return mixed
	 */
	public function getProp($prop, Bool $realValue = false){
		switch ($prop){
			case 'ratioLimit':
			case 'isRatioLimited':
			case 'altSpeedEnabled':
			case 'altModeEnabled':
			case 'defaultDownloadDir':
			case 'blockList':
			case 'blockListEnabled':
			case 'blockListSize':
				return $this->$prop;
			case 'dlSpeed':
			case 'upSpeed':
			case 'altDlSpeed':
			case 'altUpSpeed':
				return ($realValue) ? $this->$prop : \Sanitize::readableFileSize(($this->$prop) * 1024, 0);
			case 'altBegin':
				return ($realValue) ? $this->altBegin : \Sanitize::time($this->altBegin, 'time');
			case 'altEnd':
				return ($realValue) ? $this->altEnd : \Sanitize::time($this->altEnd, 'time');
			case 'altDaysSchedule':
				if ($realValue) {
					return $this->altDaysSchedule;
				}else{
					$days = $this->altDaysSchedule;
					if (in_array($days, array(127, 65, 62, 0))) return array($days);
					$daysArr = array();
					for ($i=1;$i<8;$i++){
						if ($this->nbit($days, $i) === 1){
							// Pour obtenir la valeur numérique, on fait 2^($i-1). ex : pour une valeur de 1 sur le 4è bit, on a une valeur numérique 2^3 = 8
							$ro = (String)($i - 1);
							$num = bcpow('2', $ro, 0);
							$daysArr[$num] = $num;
						}
					}
					// Si samedi et dimanche sont cochés, alors on définit plutôt le week end
					if (isset($daysArr[64]) and isset($daysArr[1])){
						$daysArr[65] = 65;
						unset($daysArr[64]);
						unset($daysArr[1]);
					}
					// Si tous les jours de la semaine sont cochés, alors on définit les jours ouverts
					if (isset($daysArr[2]) and isset($daysArr[4]) and isset($daysArr[8]) and isset($daysArr[16]) and isset($daysArr[32])){
						$daysArr[62] = 62;
						unset($daysArr[2]);
						unset($daysArr[4]);
						unset($daysArr[8]);
						unset($daysArr[16]);
						unset($daysArr[32]);
					}
					return array_values($daysArr);
				}
			default:
				// Certaines propriétés étant des booléens, impossible de retourner false en cas de propriété inexistante.
				return 'Property not set !';
		}
	}

	/**
	 * Permet de définir une propriété de la classe
	 *
	 * @param string $prop
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function __set($prop, $value){
		switch ($prop){
			case 'ratioLimit':
				if (is_float($value)){
					$this->$prop = (float)$value;
					return true;
				}
				return false;
			case 'setDlSpeed':
			case 'setUpSpeed':
			case 'altDlSpeed':
			case 'altUpSpeed':
			case 'altBegin':
			case 'altEnd':
				if (is_int($value)) {
					$this->$prop = (int)$value;
					return true;
				}
				return false;
			case 'altDaysSchedule':
				if (is_int($value) and $value < 128) {
					$tmpVal = $value;
					for ($i=1;$i<8;$i++){
						if ($this->nbit($value, $i) === 1){
							$ro = (String)($i - 1);
							// On soustraie 2 puissance $i-1 pour enlever le jour défini, voir la déclaration de $altDaysSchedule
							try {
								$tmpVal -= bcpow('2', $ro, 0);
							} catch (\Exception $e){
								\Components::Alert('danger', 'Erreur : l\'extension php-bcmath n\'est pas installée ! Veuillez l\'installer en faisant <code>sudo apt install php-bcmath</code>.<br>'.$e->getMessage());
								return false;
							}
						}
					}
					// Si le résultat est supérieur à 0, c'est que le nombre ne suit pas les numéros de jours.
					if ($tmpVal == 0){
						$this->$prop = (int)$value;
						return true;
					}
				}
				return false;
			case 'altSpeedEnabled':
			case 'isRatioLimited':
			case 'altModeEnabled':
				if (is_bool($value)){
					$this->$prop = (bool)$value;
					return true;
				}
				return false;
			default:
				return false;
		}
	}


	/**
	 * Retourne les jours où le mode tortue est activé
	 *
	 * @param bool $realValue retourne la valeur réelle au lieu de la valeur mise en forme
	 * @param bool $textFormat Retourner sous format texte (numérique entier sinon)
	 *
	 * @return int|string
	 */
	public function getAltDaysSchedule($realValue = false, $textFormat = true) {
		if ($realValue) {
			return $this->altDaysSchedule;
		}else{
			$days = $this->altDaysSchedule;
			if ($days == 127) return ($textFormat) ? 'Tous les jours' : $days;
			if ($days == 65)	return ($textFormat) ? 'Le weekend' : $days;
			if ($days == 62)	return ($textFormat) ? 'Du lundi au vendredi' : $days;
			if ($days === 0)	return ($textFormat) ? 'Jamais' : $days;
			$daysArr = array();
			if ($this->nbit($days, 1) === 1) $daysArr[1] = ($textFormat) ? 'Dimanche'  : 1;
			if ($this->nbit($days, 2) === 1) $daysArr[2] = ($textFormat) ? 'Lundi'     : 2;
			if ($this->nbit($days, 3) === 1) $daysArr[4] = ($textFormat) ? 'Mardi'     : 4;
			if ($this->nbit($days, 4) === 1) $daysArr[8] = ($textFormat) ? 'Mercredi'  : 8;
			if ($this->nbit($days, 5) === 1) $daysArr[16] = ($textFormat) ? 'Jeudi'     : 16;
			if ($this->nbit($days, 6) === 1) $daysArr[32] = ($textFormat) ? 'Vendredi'  : 32;
			if ($this->nbit($days, 7) === 1) $daysArr[64] = ($textFormat) ? 'Samedi'    : 64;
			if (isset($daysArr[64]) and isset($daysArr[1])){
				$daysArr[65] = ($textFormat) ? 'Le weekend' : 65;
				unset($daysArr[64]);
				unset($daysArr[1]);
			}
			if (isset($daysArr[2]) and isset($daysArr[4]) and isset($daysArr[8]) and isset($daysArr[16]) and isset($daysArr[32])){
				$daysArr[62] = ($textFormat) ? 'Du lundi au vendredi' : 62;
				unset($daysArr[2]);
				unset($daysArr[4]);
				unset($daysArr[8]);
				unset($daysArr[16]);
				unset($daysArr[32]);
			}
			return implode(', ', $daysArr);
		}
	}

	/**
	 * Modifie des torrents
	 *
	 * @param int|int[]|Torrent[] $torrents
	 * @param array $arguments
	 *
	 * @return bool
	 */
	public function editTorrents($torrents, $arguments){
		$torrentsId = (is_a($torrents[0], 'Torrent')) ? array_keys($torrents) : $torrents;
		$ret = $this->set($torrentsId, $arguments);
		return (!empty($ret)) ? true : false;
	}
}
?>