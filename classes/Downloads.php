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
	protected $url = '?page=downloads';

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
		// On établit la liaison avec le service RPC de Transmission et on récupère les téléchargements
		$this->getTorrents();
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
				$torrents = $transSession->get(array(), array('id', 'name', 'addedDate', 'status', 'doneDate', 'totalSize', 'downloadDir', 'uploadedEver', 'isFinished', 'leftUntilDone', 'percentDone', 'files', 'eta', 'uploadRatio', 'comment', 'seedRatioLimit'))->arguments->torrents;
				foreach ($torrents as $torrent){
					// On remplace le répertoire actuel de téléchargement par un chemin relatif, pour pouvoir plus facilement gérer celui-ci par la suite.
					$torrent->downloadDir = str_replace(Settings::DATA_PARTITION.DIRECTORY_SEPARATOR, '', $torrent->downloadDir);
					//echo Get::varDump($transSession);
					$this->torrents[$torrent->id] = new Torrent($torrent, $transSession->ratioLimit);
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
		//echo Get::varDump($this->transSession);
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
	//echo Get::varDump($_REQUEST);
		$error = false;
		$props = array(
			'ratioLimit'        => 'float',
			'dlSpeed'           => 'int',
			'upSpeed'           => 'int',
			'altDlSpeed'        => 'int',
			'altUpSpeed'        => 'int',
			'isRatioLimited'    => 'bool',
			'altModeEnabled'    => 'bool',
		  'blockList'         => 'string',
		  'blockListEnabled'  => 'bool'
		);
		foreach ($props as $prop => $propType){
			if (isset($_REQUEST[$prop])) {
				$value = $_REQUEST[$prop];
				if (settype($value, $propType)) {
					if (!$this->transSession->$prop = $value) $error = false;
				}
			}
		}
		if (isset($_REQUEST['altDaysSchedule'])){
			$altDays = 0;
			if (!empty($_REQUEST['altDaysSchedule'])){
				// Calcul des jours planifiés
				rsort($_REQUEST['altDaysSchedule'], SORT_NUMERIC);
				$workDays = false;
				$weekEnd = false;
				//echo Get::varDump($_REQUEST['altDaysSchedule']);
				foreach($_REQUEST['altDaysSchedule'] as $day){
					$day = (int)$day;
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
				if (!($this->transSession->altModeEnabled = false)) $error = false;
			}
			if (!($this->transSession->altDaysSchedule = $altDays)) $error = false;
		}
		if (!isset($_REQUEST['blockListEnabled'])) {
			if (!($this->transSession->blockListEnabled = false)) $error = false;
		}
		if (!isset($_REQUEST['altModeEnabled'])) {
			if (!($this->transSession->altModeEnabled = false)) $error = false;
		}
		if (!isset($_REQUEST['isRatioLimited'])) {
			if (!($this->transSession->isRatioLimited = false)) $error = false;
		}
		if (isset($_REQUEST['altBegin']) and !empty($_REQUEST['altBegin'])){
			if (!($this->transSession->altBegin = Sanitize::time($_REQUEST['altBegin']))) $error = false;
		}
		if (isset($_REQUEST['altEnd']) and !empty($_REQUEST['altEnd'])){
			if (!($this->transSession->altEnd = Sanitize::time($_REQUEST['altEnd']))) $error = false;
		}
		//echo Get::varDump($this->transSession);
		// Envoi des paramètres au serveur Transmission
		if (!$error){
			return $this->transSession->saveSession();
		}
		Components::Alert('danger', 'Erreur : Impossible de sauvegarder les paramètres !');
		return false;
	}

	protected function aSyncTable(){
		?>
		<div class="table-responsive uk-box-shadow-medium uk-overlay-default uk-padding-small" id="salsifis-table-container">
			<table id="torrentBrowser" class="uk-table uk-table-divider uk-table-small uk-table-justify" data-order="[[ 1, 'asc' ]]">
				<thead>
					<tr>
						<td data-class-name="uk-table-expand uk-table-link uk-text-truncate">Nom</td>
						<td class="uk-visible@m">Statut</td>
						<td class="uk-visible@m" data-class-name="uk-table-shrink uk-text-nowrap uk-visible@m">Emplacement</td>
						<td class="uk-visible@l" data-class-name="uk-table-shrink uk-text-nowrap uk-visible@l">Ratio</td>
						<td class="uk-visible@l" data-class-name="uk-table-shrink uk-text-nowrap uk-visible@l">Taille</td>
					</tr>
				</thead>
				<tbody>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Affiche la liste des torrents filtrés
	 */
	protected function listTorrents(){
		$torrents = \Sanitize::sortObjectList($this->torrents, 'name');
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
						$torrent = new Torrent($torrentRPC, $this->transSession->ratioLimit);
						$this->displayTorrent($torrent);
					}
					?>
					</tbody>
				</table>
			</div>
			<div id="torrentDetail" uk-modal>
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

	/**
	 * Affiche un torrent
	 *
	 * @param Torrent $torrent Torrent à afficher
	 */
	protected function displayTorrent(Torrent $torrent){
		?>
		<tr id="torrent_<?php echo $torrent->id; ?>">
			<td class="uk-table-expand uk-table-link uk-text-truncate"><a href="#torrentDetail" class="torrentDetailLink" data-id="<?php echo $torrent->id; ?>"><?php echo $torrent->sanitizedName; ?></a></td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@m" data-search="<?php echo $torrent->status; ?>" data-order="<?php echo $torrent->statusInt; ?>"><span title="<?php echo $torrent->status; ?>" uk-tooltip="pos: bottom" class="fa fa-<?php echo $torrent->statusIcon; ?>"></span></td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@m">
				<?php
				if (Settings::DATA_PARTITION . DIRECTORY_SEPARATOR . $torrent->downloadDir == $this->transSession->defaultDownloadDir) {
					echo '<span title="' . $torrent->downloadDir . '" class="uk-text-warning" uk-tooltip="pos: bottom">Non classé</span>';
				} else {
					echo $torrent->downloadDir;
				}
				?>
			</td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@l" data-order="<?php echo $torrent->uploadRatio; ?>">
				<abbr title="<?php if ($this->transSession->isRatioLimited) {
					echo $torrent->ratioPercentDone . '% de la limite de ratio atteinte - ';
				} ?><?php echo $torrent->uploadedEver; ?> envoyés" uk-tooltip="pos: bottom">
					<?php echo ($this->transSession->isRatioLimited) ? '<span class="uk-text-' . (($torrent->ratioPercentDone >= 100) ? 'success' : 'default') . '">' . $torrent->uploadRatio . '</span>' : $torrent->uploadRatio; ?>
				</abbr>
			</td>
			<td class="uk-table-shrink uk-text-nowrap uk-visible@l" data-order="<?php echo $torrent->totalSizeInt; ?>">
				<?php
				if ($torrent->isFinished) {
					echo $torrent->totalSize;
				} else {
					// En cours de téléchargement
					?>
					<progress class="uk-progress uk-border-rounded uk-box-shadow-medium" title="Téléchargement : <?php echo $torrent->leftUntilDone . '/' . $torrent->totalSize; ?>" value="<?php echo $torrent->leftUntilDone; ?>" max="<?php echo $torrent->totalSize; ?>" uk-tooltip></progress>
					<?php
				}
				?>
			</td>
		</tr>
		<?php
	}

	public function torrentDetail($id){
		/** @var Torrent $torrent */
		$torrent = $this->torrents[$id];
		// 'id', 'name', 'addedDate', 'status', 'doneDate', 'totalSize', 'downloadDir', 'uploadedEver', 'isFinished', 'leftUntilDone', 'percentDone', 'files', 'eta', 'uploadRatio', 'comment', 'seedRatioLimit'
		?>
		<div class="uk-modal-dialog">
      <button class="uk-modal-close-default" type="button" uk-close></button>
      <div class="uk-modal-header">
        <h2 class="uk-modal-title"><?php echo $torrent->name; ?></h2>
      </div>
      <div class="uk-modal-body">
	      <ul>
	        <li>
	        <?php
	         echo 'Début : ' . $torrent->addedDate . ', fin ';
	         echo ($torrent->isFinished) ? ' : '. $torrent->doneDate : 'estimée dans <span id="torrent_estimated_end_'. $torrent->id. '">' . $torrent->eta . '</span>';
	        ?>
					</li>
					<?php if ($torrent->isFinished){ ?>
					<li>Ratio d'envoi/réception : <span id="torrent-ratio_<?php echo $torrent->id; ?>"><?php echo $torrent->uploadRatio.' ('.$torrent->uploadedEver.' envoyés'. (($this->transSession->isRatioLimited) ? ', ' . $torrent->ratioPercentDone.'% du ratio atteint)' : ''); ?></span></li>
					<li>Taille : <?php echo $torrent->totalSize; ?></li>
					<?php }else{ ?>
					<li>Reste à télécharger : <span id="torrent-leftuntildone_<?php echo $torrent->id; ?>"><?php echo $torrent->leftUntilDone.'/'.$torrent->totalSize; ?></span></li>
					<?php } ?>
					<li>Téléchargé dans : <?php echo $torrent->downloadDir; ?></li>
					<?php if (!empty($torrent->comment)){ ?>
					<li>Commentaire : <?php echo $torrent->comment; ?></li>
					<?php } ?>
					<li>
						<a uk-toggle="target: #collapse_<?php echo $torrent->id; ?>" data-parent="#torrent_<?php echo $torrent->id; ?>" href="#collapse_<?php echo $torrent->id; ?>">Liste des fichiers</a>
						<ul class="uk-text-small" id="collapse_<?php echo $torrent->id; ?>">
						<?php
						foreach ($torrent->files as $file){
							?><li><?php echo $file->name; ?></li><?php
						}
						?>
						</ul>
					</li>
					<?php if (!empty($torrent->nfo)){ ?>
					<li>
						<a uk-toggle="target: #collapse_nfo_<?php echo $torrent->id; ?>"  data-parent="#torrent_<?php echo $torrent->id; ?>" href="#collapse_nfo_<?php echo $torrent->id; ?>">Informations sur le fichier principal</a>
						<div class="collapse" id="collapse_nfo_<?php echo $torrent->id; ?>" hidden><pre><?php echo $torrent->nfo; ?></pre></div>
					</li>
					<?php } ?>
				</ul>
			</div>
      <div class="uk-modal-footer">
				<button class="uk-button uk-button-default uk-modal-close" type="button">Annuler</button>
        <button class="uk-button uk-button-primary" type="button">Save</button>
			</div>
    </div>
		<?php
	}

	public function getAsyncDownloads(){
		$torrents = array();
		foreach ($this->torrents as $torrent) {

		}
		echo json_encode(array(
			'draw'            => 1,
			'recordsTotal'    => count($torrents),
			'recordsFiltered' => count($torrents),
			'data'            => $torrents
		));
	}

	/**
	 * Contenu principal
	 */
	public function main() {
		if ($this->transSession){
			$ret = $this->runAction();
			//echo Get::varDump($this->transSession);
			$this->serverSettings();
			if($this->transSession->altSpeedEnabled){
				Components::Alert('warning', '<span class="fa-stack fa-lg"><i class="fa fa-rocket fa-flip-horizontal fa-stack-1x"></i><i class="fa fa-ban fa-stack-2x uk-text-danger"></i></span> Le mode tortue est activé : vos téléchargements sont bridés à '.$this->transSession->altDlSpeed.'/s en téléchargement et '.$this->transSession->altUpSpeed.'/s en partage.');
			} else {
				Components::Alert('warning', '<span class="fa-stack fa-lg"><i class="fa fa-rocket fa-flip-horizontal fa-stack-1x"></i><i class="fa fa-circle-o fa-stack-2x uk-text-success"></i></span> Le mode tortue est désactivé : vous téléchargez à '.$this->transSession->dlSpeed.'/s et vous partagez à '.$this->transSession->upSpeed.'/s.');
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
			<?php
			if ($this->transSession){
				?>
				<li><a href="#modal-settings" uk-toggle>Paramètres de téléchargement</a></li>
				<li><a href="<?php echo $this->buildArgsURL(array('action' => 'changeAltMode')); ?>"><?php echo ($this->transSession->altSpeedEnabled) ? 'Désactiver' : 'Activer' ; ?> le mode tortue</a></li>
				<?php
			}
			?>
		</ul>
		<?php
	}

	protected function serverSettings(){
		?>
		<a class="uk-button uk-button-default" href="#modal-settings" uk-toggle>Paramètres</a>

			<div id="modal-settings" uk-modal="center: true">
				<div class="uk-modal-dialog">
					<button class="uk-modal-close-default" type="button" uk-close></button>
					<div class="uk-modal-header">
						<h2 class="uk-modal-title">Paramètres de téléchargement</h2>
					</div>
					<form method="post" action="<?php echo $this->url; ?>">
					<div class="uk-modal-body">
						<ul uk-tab="animation: uk-animation-fade">
							<li class="uk-active"><a href="#">Général</a></li>
							<li><a href="#">Mode tortue</a></li>
						</ul>
						<ul class="uk-switcher uk-margin">
							<li>
									<fieldset class="uk-fieldset">
										<a uk-toggle="target: #salsifis-help-vitesse; animation: uk-animation-fade"><span class="fa fa-question-circle"></span> Aide</a>
										<div id="salsifis-help-vitesse" class="uk-text-small uk-card uk-box-shadow-medium uk-card-body" hidden>
											Pour déterminer quelles sont les vitesses de téléchargement et partage que vous devez utiliser, le mieux est de vous rendre sur <a target="_blank" href="http://beta.speedtest.net/fr">SpeedTest</a> et d'effectuer un test.<br>
											Il faut ensuite prendre 80% de ces valeurs pour obtenir des vitesses qui ne saturent pas votre bande passante.<br>
											Exemple : pour un débit en téléchargement de 6Mo/s, prenez <code>6 x 0.80 = 4,8 Mo/s</code>, ce qui fait <abbr uk-tooltip="pos: bottom" title="Il faut en fait multiplier le nombre par 1024, mais contentez-vous de multiplier par 1000 vos Mo pour obtenir des ko.">environ</abbr> <code>4800 ko/s</code>.
										</div>
										<div class="uk-margin">
											<label class="uk-form-label">Vitesse de téléchargement (Ko/s) <?php Components::iconHelp('Bande passante maximum allouée aux téléchargements, en ko/s. Cette bande passante ne doit pas excéder 80% de votre bande passante descendante ADSL ou Fibre.'); ?></label>
											<input name="dlSpeed" class="uk-input" type="number" placeholder="800" value="<?php echo $this->transSession->getProp('dlSpeed', true); ?>" step="10" min="10">
										</div>
										<div class="uk-margin">
											<label class="uk-form-label">Vitesse de partage (Ko/s) <?php Components::iconHelp('Bande passante maximum allouée au partage (sens montant), en ko/s. Cette bande passante ne doit pas excéder 80% de votre bande passante montante ADSL ou Fibre.'); ?></label>
											<input name="upSpeed" class="uk-input" type="number" placeholder="100" value="<?php echo $this->transSession->getProp('upSpeed', true); ?>" step="10" min="10">
										</div>
									</fieldset>
									<div class="uk-margin">
										<label uk-tooltip="pos: bottom" title="Activer la limite de ratio"><input name="isRatioLimited" class="uk-checkbox" type="checkbox" <?php if ($this->transSession->isRatioLimited) { echo 'checked'; }?>></label>
										<label class="uk-form-label">Limite de ratio de téléchargement/partage <?php Components::iconHelp('Ratio maximum pour un fichier entre les données partagées (upload) et les données téléchargées. Certains sites de téléchargement demandent un ratio minimum, mettez <code>1,5</code> ou plus pour être tranquille.<br><br>Afin de ne pas occuper votre bande passante pour rien vous pouvez définir un ratio maximum, mais vous risquez de ne pas optimiser vos partages les plus populaires.'); ?></label>
										<input name="ratioLimit" class="uk-input" type="number" placeholder="2" value="<?php echo $this->transSession->ratioLimit; ?>" step="0.1" min="0">
									</div>
								<div class="uk-margin">
									<label uk-tooltip="pos: bottom" title="Activer la liste de blocage"><input name="blockListEnabled" class="uk-checkbox" type="checkbox" <?php if ($this->transSession->blockListEnabled) { echo 'checked'; }?>></label>
									<label class="uk-form-label">Liste de blocage  <?php Components::iconHelp('La liste de blocage permet d\'empêcher que des petits voyeurs surveillent vos activités de téléchargement. Ainsi, la plupart des sociétés de surveillance du piratage mais aussi des organismes de gouvernements sont bloqués, ainsi que des adresses de domaines malveillants.<br>Cette liste est automatiquement mise à jour.<br><br>Il est fortement conseillé de laisser la liste de blocage <samp>activée</samp>.'); ?> (<?php echo $this->transSession->blockListSize; ?> règles chargées)</label>
									<input name="blockList" class="uk-input" type="text" placeholder="http://list.iblocklist.com/?list=ydxerpxkpcfqjaybcssw&fileformat=p2p&archiveformat=gz"  value="<?php echo $this->transSession->blockList; ?>" <?php if (!$this->transSession->blockListEnabled) { echo 'disabled'; } ?>>
								</div>

							</li>
							<li>
								<fieldset class="uk-fieldset">
									<a uk-toggle="target: #salsifis-help-tortue; animation: uk-animation-fade"><span class="fa fa-question-circle"></span> Aide</a>
									<div id="salsifis-help-tortue" class="uk-text-small uk-card uk-box-shadow-medium uk-card-body" hidden>
										Le mode tortue vous permet de réduire les débits de partage et téléchargement pendant la journée, afin d'avoir de la bande passante disponible pour utiliser Internet.<br>
										Si vous laissez <?php echo Settings::TITLE; ?> télécharger à plein puissance, tout ce que vous ferez sur Internet à côté sera fortement ralenti. En activant le mode tortue pendant la journée, vous bridez votre serveur de téléchargements.<br>
										Je vous suggère d'utiliser entre 10 et 30% de votre bande passante en mode tortue. Pour un débit de 6Mo/s en téléchargement, ça fera 1,8Mo/s, soit <abbr uk-tooltip="pos: bottom" title="Il faut en fait multiplier le nombre par 1024, mais contentez-vous de multiplier par 1000 vos Mo pour obtenir des ko.">environ</abbr> <code>1800 ko/s</code>.
									</div>
									<div class="uk-margin">
										<label class="uk-form-label">Vitesse de téléchargement en mode tortue (Ko/s) <?php Components::iconHelp('Bande passante maximum allouée aux téléchargements lorsque le serveur est en mode tortue, en ko/s. Cette bande passante ne devrait pas excéder 30% de votre bande passante descendante ADSL ou Fibre, afin de ne pas pénaliser la navigation Internet ou la télévision.'); ?></label>
										<input name="altDlSpeed" class="uk-input" type="number" placeholder="200" value="<?php echo $this->transSession->getProp('altDlSpeed', true); ?>" step="10" min="10">
									</div>
									<div class="uk-margin">
										<label class="uk-form-label">Vitesse de partage en mode tortue (Ko/s) <?php Components::iconHelp('Bande passante maximum allouée au partage (sens montant) lorsque le serveur est en mode tortue, en ko/s. Cette bande passante ne devrait pas excéder 30% de votre bande passante montante ADSL ou Fibre, afin de ne pas pénaliser la navigation Internet ou la télévision.'); ?></label>
										<input name="altUpSpeed" class="uk-input" type="number" placeholder="50" value="<?php echo $this->transSession->getProp('altUpSpeed', true); ?>" step="10" min="10">
									</div>
								</fieldset>
								<fieldset class="uk-fieldset">
									<div class="uk-margin">
										<label class="uk-form-label">Planifier le mode tortue <?php Components::iconHelp('La planification du mode tortue peut se faire pendant certains jours et pendant une plage horaire définie.<br>Il est impossible de définir des horaires différentes en fonction des jours.'); ?></label><br>
									<?php
									/*
									* Dimanche					= 1			(binary: 0000001)
									* Lundi						= 2			(binary: 0000010)
									* Mardi						= 4			(binary: 0000100)
									* Mercredi					= 8			(binary: 0001000)
									* Jeudi						= 16		(binary: 0010000)
									* Vendredi					= 32		(binary: 0100000)
									* Samedi						= 64		(binary: 1000000)
									* Jours ouvrés			= 62		(binary: 0111110)
									* Weekend					= 65		(binary: 1000001)
									* Toute la semaine	= 127		(binary: 1111111)
									* Aucun						= 0			(binary: 0000000)
									*/
									$days = array(
									2   => 'Lundi',
									4   => 'Mardi',
									8   => 'Mercredi',
									16  => 'Jeudi',
									32  => 'Vendredi',
									64  => 'Samedi',
									1   => 'Dimanche',
									62  => 'Jours ouvrés',
									65  => 'Week-end',
									127 => 'Toute la semaine'
									);
									foreach ($days as $i => $day) {
										?>
										<label>
											<input name="altDaysSchedule[]" class="uk-checkbox" type="checkbox" <?php if (in_array($i, $this->transSession->altDaysSchedule)) { echo 'checked'; }?> value="<?php echo $i; ?>"> <?php echo $day; ?>
										</label>
										<br>
										<?php
									}
									?>
									</div>
									<div class="uk-margin uk-child-width-1-2" uk-grid>
										<div>
											<label class="uk-form-label">De <?php Components::iconHelp('Le mode tortue se déclenchera à cette heure chaque jour que vous aurez indiqué. Il est conseillé de le déclencher un peu avant que vous n\'ayez besoin de naviguer sur Internet, tôt le matin par exemple.'); ?></label>
											<input name="altBegin" class="uk-input" type="time" placeholder="07:30" value="<?php echo $this->transSession->altBegin; ?>">
										</div>
										<div>
											<label class="uk-form-label">à <?php Components::iconHelp('Le mode tortue sera arrêté à cette heure chaque jour que vous aurez indiqué. Il est conseillé de le déclencher tard le soir lorsque vous n\'avez plus besoin de naviguer sur Internet, afin que les téléchargements puissent occuper un maximum de bande passante .'); ?></label>
											<input name="altEnd" class="uk-input" type="time" placeholder="23:30" value="<?php echo $this->transSession->altEnd; ?>">
										</div>
									</div>
								</fieldset>
								<label><input name="altModeEnabled" class="uk-checkbox" type="checkbox" <?php if ($this->transSession->altModeEnabled) { echo 'checked'; }?>> Activer le mode tortue aux horaires spécifiés <?php Components::iconHelp('Quand le mode tortue est actif, la bande passante utilisée pour les téléchargements est réduite. Cela vous permet en journée de naviguer sur Internet sans ralentissements.'); ?></label>
							</li>
						</ul>

					</div>
					<div class="uk-modal-footer uk-text-right">
						<button class="uk-button uk-button-default uk-modal-close" type="button">Annuler</button>
						<input type="hidden" name="action" value="saveSettings">
						<button class="uk-button uk-button-primary" type="submit">Sauvegarder</button>
					</div>
					</form>
				</div>
			</div>


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
				case 'saveSettings':
					return $this->saveServerSettings();
			}
		}
		return false;
	}


}