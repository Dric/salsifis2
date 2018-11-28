<?php

/**
 * Creator: Dric
 * Date: 24/05/2017
 * Time: 14:33
 */
class Components {

	/**
	 * Retourne la date au format nécessaire pour le composant timer de UIKit (countdown inversé)
	 * @return false|string
	 */
	static public function getUptimeJSDate(){
		return date('Y-m-d\TH:i:sP', (int)(time() - (shell_exec("cut -d. -f1 /proc/uptime"))));
	}

	/**
	 * Retourne l'adresse IP publique du routeur derrière lequel se trouve le serveur
	 * @return bool|string
	 */
	static public function getExternalIPAddress(){
		// Récupération de l'adresse IP publique
		$publicIP = @file_get_contents("http://ipecho.net/plain");
		if ($publicIP === false){
			// Si ipecho.net ne répond pas, on passe sur ip4.me
			preg_match("/.*\\+3>(.+?)</mi", file_get_contents('http://ip4.me/'), $match);
			if (isset($match[1])) $publicIP = $match[1];
		}
		return ($publicIP === false) ? 'Pas d\'IP externe' : $publicIP;
	}

	static public function getDiskStatus($part){
		$partitions = array();

		// On récupère la liste des partitions (avec un timeout, ce qui évite de bloquer toute la page si jamais il n'est pas possible de récupérer les données
		exec('timeout -k 2 2 df -h | grep ^/dev', $out);
		if (!empty($out) and !preg_match('/Complété/i', $out[0])){
			foreach ($out as $line){
				$line = preg_replace('/\s+/', ' ',$line);
				$tab = explode(' ', $line);
				$partition = end($tab);
				$total = disk_total_space($partition);
				$free = disk_free_space($partition);
				$partitions[$partition] = array(
					'total' =>  $total,
				  'free'  =>  $free,
				  'load'  =>  $total - $free
				);
			}
			if (!empty($part) and !isset($partitions[$part])){
				return false;
			}
			return (!empty($part)) ? $partitions[$part] : $partitions;
		}else{
			return false;
		}

	}

	static public function Alert($type='danger', $content){
		?>
		<div class="uk-alert-<?php echo $type; ?> uk-padding-small uk-box-shadow-medium" uk-alert>
	    <a class="uk-alert-close" uk-close></a>
	    <?php echo $content; ?>
		</div>
		<?php
	}

	static public function setAlert($type, $content){
		$_SESSION['alerts'][] = array(
				'type' => $type,
				'message' => $content
		);
	}

	/**
	 * Affichage d'une icône d'aide
	 *
	 * Le contenu de l'aide est affiché dans une infobulle
	 *
	 * @param string $text
	 * @param string $tooltipPosition
	 *  Ce paramètre peut prendre les valeurs suivantes
	 *  - bottom
	 *  - top
	 *  - left
	 *  - right
	 */
	public static function iconHelp($text, $tooltipPosition = 'bottom'){
		?>
		<span class="fas fa-question-circle help-icon" title="<?php echo $text; ?>" uk-tooltip="pos: <?php echo $tooltipPosition; ?>"></span>
		<?php
	}

	/**
	 * Affichage d'une icône d'alerte
	 *
	 * Le contenu de l'alerte est affiché dans une infobulle
	 *
	 * @param string $text
	 * @param string $tooltipPosition
	 *  Ce paramètre peut prendre les valeurs suivantes
	 *  - bottom
	 *  - top
	 *  - left
	 *  - right
	 */
	public static function iconWarning($text, $tooltipPosition = 'bottom'){
		?>
		<span class="fas fa-warning warning-icon" title="<?php echo $text; ?>" uk-tooltip="pos: <?php echo $tooltipPosition; ?>"></span>
		<?php
	}
}