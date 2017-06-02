<?php

/**
 * Creator: Dric
 * Date: 29/05/2017
 * Time: 14:03
 */
use Transmission\Torrent;
use Transmission\TransmissionRPCException;
use Transmission\TransSession;

/**
 * Class Downloads
 *
 * Page de gestion des téléchargements
 */
class Downloads extends Page{

	protected $title = 'Téléchargements';

	/**
	 * Filtres généraux des torrents
	 * @var array
	 */
	protected $selectFilters = array(
		'all'			=> 'Tous les téléchargements',
		'inDl'		=> 'Les téléchargements en cours',
		'done'		=> 'Les téléchargements partagés',
		'last10'	=> 'Les 10 derniers téléchargements terminés',
		'noCat'		=> 'Les téléchargements non classés',
		'stopped'	=> 'Les téléchargements supprimables'
	);

	/**
	 * Liste des torrents
	 * @var array()
	 */
	protected $torrents = array();

	/**
	 * Session transmission
	 * @var TransSession
	 */
	protected $transSession = null;

	public function __construct(){
		// On établit la liaison avec le service RPC de Transmission
		$this->getTransSession();
	}

	/**
	 * @return object
	 */
	protected function getTransSession() {
		if (empty($this->transSession)){
			try{
				$this->transSession = new TransSession(Settings::TRANSMISSION_RPC_URL);
			} catch (TransmissionRPCException $e){
				//Components::Alert('danger', 'Impossible de se connecter au service de téléchargements Transmission !');
				return null;
			}
		}
		return $this->transSession;
	}

	/**
	 * @return array
	 */
	protected function getTorrents() {
		if (empty($this->torrents)){
			$transSession = $this->getTransSession();
			if (!empty($transSession)){
				// Voir https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt
				$torrents = $transSession->get(array(), array('id', 'name', 'addedDate', 'status', 'doneDate', 'totalSize', 'downloadDir', 'uploadedEver', 'isFinished', 'leftUntilDone', 'percentDone', 'files', 'eta', 'uploadRatio', 'comment'))->arguments->torrents;
				foreach ($torrents as $torrent){
					// On remplace le répertoire actuel de téléchargement par un chemin relatif, pour pouvoir plus facilement gérer celui-ci par la suite.
					$torrent->downloadDir = str_replace(Settings::DATA_PARTITION.DIRECTORY_SEPARATOR, '', $torrent->downloadDir);
					$this->torrents[$torrent->id] = new Torrent($torrent);
				}
			}
		}
		return $this->torrents;
	}

	/**
	 * Bascule entre l'activation et la désactivation du mode tortue
	 * @return bool
	 */
	protected function changeAltMode(){
		$this->transSession->altSpeedEnabled = ($this->transSession->altSpeedEnabled) ? false : true ;
		return $this->transSession->saveSession();
	}

	/**
	 * Déplace un téléchargement
	 * @return bool
	 */
	protected function moveTorrent(){
		if(!isset($_REQUEST['moveTo'])){
			Components::Alert('danger', 'Le répertoire de destination est manquant !');
			return false;
		}
		if(!isset($_REQUEST['torrentId'])){
			Components::Alert('danger', 'L\'identifiant du téléchargement est manquant !');
			return false;
		}

		$ret = $this->transSession->move((int)$_REQUEST['torrentId'], Settings::DATA_PARTITION.DIRECTORY_SEPARATOR.$_REQUEST['moveTo']);
		if ($ret->result == 'success'){
			Components::Alert('success', 'Le téléchargement a été déplacé !');
			return true;
		}else{
			Components::Alert('danger', 'Impossible de déplacer le téléchargement !');
			return false;
		}
	}

	/**
	 * Supprime un téléchargement
	 * @return bool
	 */
	protected function delTorrent(){
		if(!isset($_REQUEST['torrentId'])){
			Components::Alert('danger', 'L\'identifiant du téléchargement est manquant !');
			return false;
		}
		if(!isset($_REQUEST['deleteFiles'])) $_REQUEST['deleteFiles'] = true;

		$ts = $this->getTransSession();
		$ret = $ts->remove((int)$_REQUEST['torrentId'], $_REQUEST['deleteFiles']);
		if ($ret->result == 'success'){
			Components::Alert('success', 'Le téléchargement a été supprimé !');
			return true;
		}else{
			Components::Alert('danger', 'Impossible de supprimer le téléchargement !');
			return false;
		}
	}

	/**
	 * Sauvegarde des paramètres de serveur de téléchargements
	 *
	 * @return bool
	 */
	protected function saveServerSettings(){

		$props = array(
			'ratioLimit',
			'dlSpeed',
			'upSpeed',
			'altDlSpeed',
			'altUpSpeed',
			'altSpeedEnabled',
			'altBegin',
			'altEnd',
			'isRatioLimited',
			'altModeEnabled'
		);
		foreach ($props as $prop){
			if (isset($_REQUEST[$prop])) $this->transSession->{'set'.ucfirst($prop)}($_REQUEST[$prop]);
		}
		if (isset($_REQUEST['altDaysSchedule'])){
			$altDays = 0;
			if (!empty($_REQUEST['altDaysSchedule'])){
				// Calcul des jours planifiés
				rsort($_REQUEST['altDaysSchedule'], SORT_NUMERIC);
				$workDays = false;
				$weekEnd = false;
				foreach($_REQUEST['altDaysSchedule'] as $day){
					if ($day == 127) {
						$altDays = $day;
						break;
					}elseif($day == 65){
						$weekEnd = true;
						$altDays += $day;
					}elseif($day == 62){
						$workDays = true;
						$altDays += $day;
					}elseif(in_array($day, array(2, 4, 8, 16, 32))){
						if ((!$workDays)) $altDays += $day;
					}elseif(in_array($day, array(1, 64))){
						if ((!$weekEnd)) $altDays += $day;
					}
				}
			}else{
				// Aucun jour n'est sélectionné, on désactive la planification
				$this->transSession->altModeEnabled = false;
			}
			$this->transSession->altDaysSchedule = $altDays;
		}
		// Envoi des paramètres au serveur Transmission
		return $this->transSession->saveSession();
	}

	/**
	 * Affiche la liste des torrents filtrés
	 */
	protected function listTorrents(){
		$torrents = \Sanitize::sortObjectList($this->getTorrents(), 'name');
		if (!empty($torrents)){
			?>
			<h2><?php echo count($torrents); ?> téléchargements affichés</h2>
			<div class="table-responsive uk-box-shadow-medium uk-overlay-default uk-padding-small" id="salsifis-table-container">
				<table id="torrentBrowser" class="uk-table uk-table-divider uk-table-small uk-table-justify">
					<thead>
					<tr>
						<td>Nom</td>
						<td class="uk-visible@m">Statut</td>
						<td class="uk-visible@m">Emplacement</td>
						<td class="uk-visible@l">Ratio</td>
						<td class="uk-visible@l">Taille</td>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ($torrents as $torrentRPC){
						$torrent = new Torrent($torrentRPC);
						$this->displayTorrent($torrent);
					}
					?>
					</tbody>
				</table>
			</div>
			<?php
		}else{
			?>
			<br><br>
			<div class="uk-alert uk-alert-warning">Il n'y a aucun téléchargement.</div>
			<?php
		}

		//var_dump($torrents);
	}

	protected function displayTorrent(Torrent $torrent){
		?>
		<tr id="torrent_<?php echo $torrent->id; ?>">
			<td class="uk-table-expand uk-table-link uk-text-truncate"><?php echo $torrent->sanitizedName; ?></td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@m" data-order="<?php echo $torrent->statusInt; ?>"><span title="<?php echo $torrent->status; ?>" uk-tooltip="pos: bottom" class="fa fa-<?php echo $torrent->statusIcon; ?>"></span></td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@m"><?php echo $torrent->downloadDir; ?></td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@l" data-order="<?php echo $torrent->uploadRatio; ?>">
				<?php	echo ($this->transSession->isRatioLimited) ? '<abbr title="' . $torrent->ratioPercentDone . '% de la limite de ratio atteinte" uk-tooltip="pos: bottom">' . $torrent->uploadRatio . '</abbr>' : $torrent->uploadRatio; ?>
			</td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@l" data-order="<?php echo $torrent->totalSizeInt; ?>">
				<?php
				if ($torrent->isFinished) {
					echo $torrent->totalSize;
				} else {
					// En cours de téléchargement
					?>
					<progress class="uk-progress uk-border-rounded uk-box-shadow-medium" title="Téléchargement : <?php echo $torrent->leftUntilDone .'/'. $torrent->totalSize; ?>" value="<?php echo $torrent->leftUntilDone; ?>" max="<?php echo $torrent->totalSize; ?>" uk-tooltip></progress>
					<?php
					}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Contenu principal
	 */
	public function main() {
		if ($this->transSession){
			$ret = $this->runAction();
			if($this->transSession->altModeEnabled){
				Components::Alert('warning', 'Le mode tortue est activé : vos téléchargements sont bridés à '.$this->transSession->altDlSpeed.'/s en téléchargement et '.$this->transSession->altUpSpeed.'/s en partage.');
			} else {
				Components::Alert('primary', 'Le mode tortue est désactivé : vous êtes à '.$this->transSession->dlSpeed.'/s en téléchargement et '.$this->transSession->upSpeed.'/s en partage.');
			}
			// On affiche les torrents
			$this->listTorrents();
		}else {
			Components::Alert('danger', 'Impossible de se connecter au service de téléchargements Transmission !');
			?>
			<p>Il y a un souci de communication avec le service de téléchargement. Vous pouvez passer par l'interface web du service pour administrer vos téléchargements.</p>
			<div class="uk-text-center">
				<a class="uk-button uk-button-large uk-button-secondary" href="<?php echo Settings::TRANSMISSION_WEB_URL; ?>" title="Transmission est le composant qui permet de gérer les téléchargements du serveur.<br>Il possède une interface web un peu austère qui apporte plus de fonctionnalités que celle-ci." uk-tooltip="pos: bottom">Interface Web de Transmission</a>
			</div>
			<?php
		}
	}

	/**
	 * Menu latéral
	 */
	public function menu(){
		?>
		<ul class="uk-nav uk-nav-default uk-margin-auto-vertical">
			<li><a href=".">Retour à l'accueil</a></li>
			<li><a href="<?php echo Settings::TRANSMISSION_WEB_URL; ?>" title="Transmission est le composant qui permet de gérer les téléchargements du serveur.<br>Il possède une interface web un peu austère qui apporte plus de fonctionnalités que celle-ci." uk-tooltip="pos: bottom">Interface Web de Transmission</a></li>
		</ul>
		<?php
	}

	protected function runAction(){
		if (isset($_REQUEST['action'])){
			switch ($_REQUEST['action']){
				case 'changeAltMode':
					return $this->changeAltMode();
					break;
				case 'moveTorrent':
					return $this->moveTorrent();
					break;
				case 'delTorrent':
					return $this->delTorrent();
					break;
			}
		}
		return false;
	}
}