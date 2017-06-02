<?php

/**
 * Creator: Dric
 * Date: 29/05/2017
 * Time: 14:01
 */

/**
 * Class Page
 *
 * Page principale (index)
 */
class Page {

	protected $title = null;

	var $url = '';

	/**
	 * Entête
	 */
	public function subTitle(){
		return $this->title;
	}

	/**
	 * Contenu principal
	 */
	public function main(){
		?>
		<div class="uk-child-width-1-3@m uk-grid-small uk-grid-match uk-animation-fade" uk-grid>
			<a href="?page=downloads" title="Pour savoir où en sont vos téléchargements, pour les classer ou les supprimer, c'est par ici !" class="salsifis-main-menu-link" uk-tooltip="pos: bottom">
				<div class="uk-card uk-background-blend-multiply uk-card-body uk-border-rounded uk-box-shadow-medium uk-overlay-default salsifis-main-menu salsifis-main-downloads">
					<h3 class="uk-card-title">Téléchargements</h3>
					<p>Vos téléchargements en cours</p>
				</div>
			</a>
			<a href="?page=files" title="Cliquez ici pour voir les fichiers stockés sur le serveur et obtenir des détails." class="salsifis-main-menu-link" uk-tooltip="pos: bottom">
				<div class="uk-card uk-background-blend-multiply uk-card-body uk-border-rounded uk-box-shadow-medium uk-overlay-default salsifis-main-menu salsifis-main-library">
					<h3 class="uk-card-title">Fichiers</h3>
					<p>La liste des fichiers sur le serveur</p>
				</div>
			</a>
			<a href="?page=reboot" title="Parfois, les choses ne vont pas comme on le voudrait. <br><br>En informatique, la manoeuvre la plus élémentaire consiste non pas à jeter le matériel par la fenêtre, mais à le redémarrer." class="salsifis-main-menu-link" uk-tooltip="pos: bottom">
				<div class="uk-card uk-background-blend-multiply uk-card-body uk-border-rounded uk-box-shadow-medium uk-overlay-default salsifis-main-menu salsifis-main-exit">
					<h3 class="uk-card-title">Redémarrer</h3>
					<p>Un problème ? redémarrez.</p>
				</div>
			</a>
		</div>

		<div class="uk-section" id="diskUsage">
		</div>
		<div class="uk-section" id="externalIP">
		</div>
		<div class="uk-section">
			<div class="uk-text-center">
				Démarré depuis
			</div>
			<div class="uk-grid-small uk-child-width-auto uk-flex-center" uk-grid uk-timer="date: <?php echo Components::getUptimeJSDate() //2017-05-21T08:24:04+00:00 ?>">

				<div>
					<div class="uk-countdown-number uk-countdown-days"></div>
					<div class="uk-countdown-label uk-margin-small uk-text-center uk-visible@s">Jours</div>
				</div>
				<div class="uk-countdown-separator">:</div>
				<div>
					<div class="uk-countdown-number uk-countdown-hours"></div>
					<div class="uk-countdown-label uk-margin-small uk-text-center uk-visible@s">Heures</div>
				</div>
				<div class="uk-countdown-separator">:</div>
				<div>
					<div class="uk-countdown-number uk-countdown-minutes"></div>
					<div class="uk-countdown-label uk-margin-small uk-text-center uk-visible@s">Minutes</div>
				</div>
				<div class="uk-countdown-separator">:</div>
				<div>
					<div class="uk-countdown-number uk-countdown-seconds"></div>
					<div class="uk-countdown-label uk-margin-small uk-text-center uk-visible@s">Secondes</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Menu latéral
	 */
	public function menu(){

	}

	/**
	 * Permet de construire une URL pour appeler le module avec des arguments
	 *
	 * Exemple pour un module `Users` :
	 *  <code>$this->buildModuleQuery(array(id => 2));</code>
	 *  Donne l'URL :
	 *  <code>http://poulpe2/module/Users?id=2</code>
	 *
	 * @param array $args Arguments à passer dans l'URL de la forme array(`argument` => `valeur`)
	 *
	 * @return string
	 */
	protected function buildArgsURL(Array $args){
		$url = $this->url;
		if (stripos($url, '?') !== false) {
			// Pas de pretty Url du type `poulpe2/module/<moduleName>`
			foreach ($args as $key => $value){
				$url .= '&'.$key.'='.$value;
			}
		}else{
			// Pretty Url
			$isFirst = true;
			foreach ($args as $key => $value){
				if ($isFirst){
					$url .= '?'.$key.'='.$value;
					$isFirst = false;
				}else{
					$url .= '&'.$key.'='.$value;
				}
			}
		}
		return $url;
	}
}