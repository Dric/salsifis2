<?php

namespace Transmission;

use Sanitize;
use Settings;

/**
 * CLasse de torrent
 *
 * Cette classe reprend une partie des propriétés de l'objet torrent transmis par la classe transmissionRPC
 *
 * @see <https://trac.transmissionbt.com/browser/trunk/extras/rpc-spec.txt>
 * @package Transmission
 *
 * @property-read int       $id                 ID du torrent
 * @property-read string    $name               Nom réel du torrent
 * @property-read string    $sanitizedName      Nom nettoyé
 * @property-read int       $addedDate          Date d'ajout
 * @property-read int       $doneDate           Date de fin de téléchargement
 * @property-read string    $status             Statut en texte
 * @property-read int       $statusInt          Statut en nombre entier
 * @property-read string[]  $statusLabels       Libellés des status
 * @property-read string    $statusIcon         Icône du statut
 * @property-read string    $totalSize          Taille totale (en format lisible ko, Mo, etc)
 * @property-read int       $totalSizeInt       Taille totale (en octets)
 * @property-read string    $downloadDir        Répertoire de téléchargement (relatif)
 * @property-read string    $rawDownloadDir     Répertoire de téléchargement (absolu)
 * @property-read string    $uploadedEver       Taille des données envoyées
 * @property-read bool      $isFinished         Téléchargement terminé
 * @property-read int       $leftUntilDone      Taille restante à télécharger
 * @property-read float     $percentDone        Pourcentage de téléchargement accompli
 * @property-read float     $ratioLimit         Limite de ratio avant d'arrêter le partage
 * @property-read float     $ratioPercentDone   Pourcentage avant d'atteindre le ratio
 * @property-read array     $files              Fichiers téléchargés par le torrent
 * @property-read int       $eta                Durée estimée avant la fin du téléchargement
 * @property-read float     $uploadRatio        Ratio de téléchargement/partage
 * @property-read string    $comment            Commentaire du torrent
 * @property-read string    $img                Image du torrent
 * @property-read string    $nfo                Fichier NFO descriptif du téléchargement
 * @property-read string[]  $trackers           Liste des trackers du torrent
 */
Class Torrent{
	
	/**
	* ID du torrent
	* @var int
	*/
	protected $id = 0;
	
	/**
	* Nom du torrent
	* @var string
	*/
	protected $name = '';
	
	/**
	* Timestamp de date d'ajout du torrent dans le client bt
	* @var int
	*/
	protected $addedDate = 0;
	
	/**
	* Timestamp de date de fin de téléchargement
	* @var int
	*/
	protected $doneDate = 0;
	
	/**
	* Statut du torrent
	* @var int
	* 
	* 0: Arrêté (aucune activité)
  * 1: En attente de vérification
  * 2: En cours de vérification
  * 3: En attente de téléchargement
  * 4: En cours de téléchargement
  * 5: En attente de partage
  * 6: En cours de partage
	*/
	protected $status = 0;
	
	/**
	* Libellés du statut
	* @var array()
	*/
	protected $statusLabels = array(
		0 => 'Arrêté',
		1 => 'En attente de vérification',
  	2 => 'En cours de vérification',
		3 => 'En attente de téléchargement',
		4 => 'En cours de téléchargement',
		5 => 'En attente de partage',
		6 => 'En cours de partage'
	);
	
	/**
	* Classe CSS à affecter au statut
	* @var array
	*/
	protected $statusIcons = array(
		0 => 'pause',
		1 => 'spinner',
  	2 => 'warning',
		3 => 'spinner',
		4 => 'cloud-download',
		5 => 'spinner',
		6 => 'cloud-upload'
	);
	
	/**
	* Taille totale en octets
	* @var int
	*/
	protected $totalSize = 0;
	
	/**
	* Répertoire réel de téléchargement
	* @var string
	*/
	protected $downloadDir = '';

	/**
	* Nombre d'octets partagés (envoyés vers d'autres peers)
	* @var int
	*/
	protected $uploadedEver = 0;
	
	/**
	* Torrent terminé ou non
	* @var bool
	*/
	protected $isFinished = false;
	
	/**
	* Nombre d'octets avant la fin du téléchargement
	* @var int
	*/
	protected $leftUntilDone = 0;
	
	/**
	* Pourcentage de téléchargement en décimal (de 0 à 1)
	* @var float
	*/
	protected $percentDone = 0;
	
	/**
	* Limite max de ratio partage/téléchargement
	* @var float
	*/
	protected $ratioLimit = 1;
	
	/**
	* Pourcentage d'accomplissement du ratio partage/téléchargement en décimal (de 0 à 1)
	* @var float
	*/
	protected $ratioPercentDone = 0;
	
	/**
	* Fichiers téléchargés par le torrent
	* @var array
	*/
	protected $files = array();
	
	/**
	* Temps estimé avant la fin du téléchargement
	* @var int
	*/
	protected $eta = 0;
	
	/**
	* Pourcentage de partage en décimal (de 0 à 1)
	* @var float
	*/
	protected $uploadRatio = 0;
	
	/**
	* Commentaire du torrent
	* @var string
	*/
	protected $comment = '';
	
	/**
	* Image du torrent (si présente)
	* @var string
	*/
	protected $img = '';
	
	/**
	* NFO du torrent (fichier explicatif, si présent)
	* @var string
	*/
	protected $nfo = '';

	/**
	 * Liste des trackers du torrent
	 * @var string[]
	 */
	protected $trackers = array();

	/**
	 * Construction de la classe
	 *
	 * @param object $RPCTorrent Objet de torrent renvoyé par la classe RPCTransmission
	 * @param Float  $ratioLimit
	 */
	public function __construct($RPCTorrent, Float $ratioLimit){
		$RPCprops = get_object_vars($RPCTorrent);
		//echo '<pre><code>';var_dump($RPCprops);echo '</code></pre>';
		foreach ($RPCprops as $prop => $value){
			if (isset($this->$prop)){
				$this->$prop = $value;
			}
		}
		$this->ratioLimit = $ratioLimit;
		$fileDesc = array();
		$torrentImg = array();
		$this->files = Sanitize::sortObjectList($this->files, 'name');
		foreach ($this->files as $file){
			$fileInfo = pathinfo($file->name);
			$level = count(explode('/', $fileInfo['dirname']));
			if (isset($fileInfo['extension'])){
				switch ($fileInfo['extension']){
					case 'nfo':
						if ((empty($fileDesc['source']) or $fileDesc['level'] > $level) and file_exists($this->downloadDir.'/'.$file->name)){
							$fileDesc['source'] = file_get_contents($this->downloadDir.'/'.$file->name);
							$fileDesc['level'] = $level;
						}
						break;
					case 'jpg':
					case 'jpeg':
					case 'png':
					case 'gif':
						if ((empty($torrentImg['source']) or $torrentImg['level'] > $level)  and file_exists($this->downloadDir.'/'.$file->name)){
							$torrentImg['source'] = $this->downloadDir.'/'.$file->name;
							$torrentImg['level'] = $level;
						}
						break;
				}
			}
			$this->img = (!empty($torrentImg['source'])) ? urlencode($torrentImg['source']) : '';
			$this->nfo = (!empty($fileDesc['source'])) ? $fileDesc['source'] : '';
		}
		//echo '<pre><code>';var_dump($this);echo '</code></pre>';
	}
	
	/**
	* Permet d'accéder aux propriétés de la classe
	* @param string $prop Propriété
	* 
	* @return mixed
	*/
	public function __get($prop){
		return $this->get($prop);
	}

	public function __isset($prop){
		switch ($prop){
			case 'rawDoneDate':
			case 'rawDownloadDir':
				return true;
			default:
				return isset($this->$prop);
		}
	}
	
	/**
	* Met en forme et retourne les propriétés de la classe
	* 
	* Les propriétés de la classe étant privées, pour y accéder il suffit de demander la variable sans le préfixe '_'.
	* Ex : Pour obtenir la taille totale du torrent, qui est la propriété $totalSize, il suffit de demander $torrent->totalsize ou encore $this->get('totalSize') à l'intérieur de la classe
	* @param string $prop Propriété à retourner.
	* 
	* @return mixed
	*/
	protected function get($prop){
		switch ($prop){
			case 'addedDate':
			case 'doneDate':
				if ($this->$prop === 0){
					return 'Inconnu';
				}
				return Sanitize::date($this->$prop, 'dateTime');
			case 'rawDoneDate':
				return $this->doneDate;
			case 'totalSize':
			case 'leftUntilDone':
			case 'uploadedEver':
				return Sanitize::readableFileSize($this->$prop);
			case 'totalSizeInt':
				return $this->totalSize;
			case 'eta':
				return ($this->eta != -1) ? Sanitize::timeDuration($this->$prop) : 'Inconnu';
			case 'isFinished':
				return ($this->isFinished or $this->percentDone === 1) ? true : false;
			case 'uploadRatio':
				return round($this->uploadRatio, 2);
			case 'percentDone':
				return ($this->percentDone != -1) ? round($this->percentDone*100, 1) : 0;
			case 'comment':
				return $msg = preg_replace('/((http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?)/', '<a href="\1" target="_blank">\1</a>', $this->comment);
			case 'id':
			case 'name':
			case 'nfo':
			case 'img':
			case 'trackers':
				return $this->$prop;
			case 'sanitizedName':
				return $this->sanitizeTorrentName();
			case 'files':
				return $this->files;
			case 'status':
				return $this->statusLabels[$this->status];
			case 'statusInt':
				return $this->status;
			case 'downloadDir':
				return (isset(Settings::DOWNLOAD_DIRS[$this->downloadDir])) ? Settings::DOWNLOAD_DIRS[$this->downloadDir] : $this->downloadDir;
			case 'rawDownloadDir':
				return $this->downloadDir;
			case 'statusIcon':
				return $this->statusIcons[$this->status];
			case 'ratioPercentDone':
				$ratioPercentDone = round(($this->uploadRatio/$this->ratioLimit)*100, 0);
				return ($ratioPercentDone > 100) ? 100 : $ratioPercentDone;
			default:
				// Certaines propriétés étant des booléens, impossible de retourner false en cas de propriété inexistante.
				return 'Property not set !';
		}
	}

	/**
	 * Affiche les information d'un torrent
	 *
	 * @param int  $moduleId Id du module Transmission (pour les permissions des formulaires)
	 *
	 * @param bool $altColor Colorer le fond
	 *
	 * @return void
	 */
	public function display($moduleId = 0, $altColor = false){
		?>
		<noscript>
			<style type="text/css">
				.collapse{
					display: block;
				}
			</style>
		</noscript>
		<div class="panel torrent-wrap <?php if ($altColor) echo 'alt-bg-background'; ?>" id="torrent_<?php echo $this->id; ?>">
			<div class="panel-heading torrents">
				<h4><a data-toggle="collapse" data-parent="#torrent_<?php echo $this->id; ?>" href="#collapse_details_<?php echo $this->id; ?>"><?php echo $this->sanitizeTorrentName(); ?></a> <span class="label <?php echo $this->get('statusIcons'); ?>"><?php echo $this->get('status'); ?></span> <span class="label label-primary"><?php echo (isset($this->downloadDirs[$this->get('downloadDir')])) ? $this->downloadDirs[$this->get('downloadDir')] : $this->get('downloadDir'); ?></span></h4>
				<?php
				?>
				<div id="torrent-progress-bar-title_<?php echo $this->id; ?>" class="progress tooltip-bottom progress-torrents" title="Terminé à <?php echo $this->get('percentDone'); ?>%">
					<?php
					if ($this->get('percentDone') == 100){
						if ($this->get('ratioPercentDone') == 100){
							$barColor = 'default';
						}else{
							$barColor = 'warning';
						}
					?>
					<div id="torrent-progress-bar-seed_<?php echo $this->id; ?>" class="progress-bar progress-bar-<?php echo $barColor; ?>" role="progressbar" aria-valuenow="<?php echo $this->get('ratioPercentDone'); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $this->get('ratioPercentDone'); ?>%;">
						<span><?php echo $this->get('ratioPercentDone'); ?>%</span>
					</div>
					<?php }else{ ?>
					<div id="torrent-progress-bar-dl_<?php echo $this->id; ?>" class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="<?php echo $this->get('percentDone'); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $this->get('percentDone'); ?>%;">
						<span><?php echo $this->get('percentDone'); ?>%</span>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel-body torrents collapse" id="collapse_details_<?php echo $this->id; ?>">

				<ul class="">
					<li>Nom réel : <i><?php echo $this->name; ?></i></li>
					<li>Début : <?php echo $this->get('addedDate'); ?>, fin <?php echo ($this->get('isFinished'))?': '.$this->get('doneDate'):'estimée dans <span id="torrent_estimated_end_'.$this->id.'">'.$this->get('eta').'</span>'; ?></li>
					<?php if ($this->get('isFinished')){ ?>
					<li>Ratio d'envoi/réception : <span id="torrent-ratio_<?php echo $this->id; ?>"><?php echo $this->get('uploadRatio').' ('.$this->get('uploadedEver').' envoyés, '.$this->get('ratioPercentDone').'% du ratio atteint)'; ?></span></li>
					<li>Taille : <?php echo $this->get('totalSize'); ?></li>
					<?php }else{ ?>
					<li>Reste à télécharger : <span id="torrent-leftuntildone_<?php echo $this->id; ?>"><?php echo $this->get('leftUntilDone').'/'.$this->get('totalSize'); ?></span></li>
					<?php } ?>
					<li>Téléchargé dans : <?php echo $this->get('downloadDir'); ?></li>
					<?php if (!empty($this->comment)){ ?>
					<li>Commentaire : <?php echo $this->get('comment'); ?></li>
					<?php } ?>
					<li>
						<a data-toggle="collapse" data-parent="#torrent_<?php echo $this->id; ?>" href="#collapse_<?php echo $this->id; ?>">Liste des fichiers</a>
						<ul class="collapse small" id="collapse_<?php echo $this->id; ?>">
						<?php
						foreach ($this->files as $file){
							?><li><?php echo $file->name; ?></li><?php
						}
						?>
						</ul>
					</li>
					<?php if (!empty($this->nfo)){ ?>
					<li>
						<a data-toggle="collapse" data-parent="#torrent_<?php echo $this->id; ?>" href="#collapse_nfo_<?php echo $this->id; ?>">Informations sur le fichier principal</a>
						<ul class="collapse" id="collapse_nfo_<?php echo $this->id; ?>"><pre><?php echo $this->nfo; ?></pre></ul>
					</li>
					<?php } ?>
				</ul>
				<div class="">
					<!-- Actions sur les téléchargements -->
					<?php
					$disableNotFinishedTorrent = (!in_array($this->status, array(3, 4)) and ACL::canModify('module', $moduleId)) ? '' : 'disabled';
					$disableAll = (ACL::canModify('module', $moduleId)) ? '' : 'disabled';
					?>
					<form method="post" name="form_torrentActions_<?php echo $this->id; ?>" class="form-inline">
						<div class="input-group">
							<span class="input-group-btn">
	              Déplacer vers
	            </span>
							<select class="form-control" id="field_select_moveTo_<?php echo $this->id; ?>" name="field_select_moveTo" <?php echo $disableNotFinishedTorrent; ?>>
								<option></option>
								<?php
								foreach ($this->downloadDirs as $label => $downloadDir){
									?>
									<option value="<?php echo $downloadDir; ?>" <?php if ($downloadDir == $this->get('downloadDir')) echo 'selected'; ?>><?php echo $label; ?></option>
								<?php
								}
								?>
							</select>
							<span class="input-group-btn">
	              <button type="submit" class="btn btn-default" id="action" name="action" value="moveTorrent" <?php echo $disableNotFinishedTorrent; ?>>Déplacer</button>
	            </span>
						</div>
						<!--<button type="submit" class="btn btn-default btn-xs" id="action" name="action" value="moveTorrent" <?php echo $disableNotFinishedTorrent; ?>>Déplacer</button>-->
						<button type="submit" class="btn btn-default btn-sm" id="action" name="action" value="delTorrent" <?php echo $disableAll; ?>>Supprimer</button>
						<div class="form-group">
							<input class="form-control checkboxSwitch" id="field_bool_deleteFiles_checkbox_<?php echo $this->id; ?>" name="field_bool_deleteFiles_checkbox" value="1" checked="" <?php echo $disableNotFinishedTorrent; ?> data-on-text="Tout" data-off-text="Torrent" data-on-color="primary" data-off-color="default" type="checkbox">
							<input name="field_bool_deleteFiles_hidden" value="0" type="hidden">
						</div>
						<input type="hidden" name="field_hidden_torrentId" value="<?php echo $this->id; ?>">
						<input type="hidden" name="field_hidden_token" value="<?php echo PostedData::setToken('form_torrentActions_'.$this->id); ?>">
						<input type="hidden" name="field_hidden_formName" value="form_torrentActions_<?php echo $this->id; ?>">
					</form>
					<br>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Nettoie le nom d'un téléchargement
	 * @return string
	 */
	protected function sanitizeTorrentName(){
		$replace = array(
			'.mkv'        => '',
		  '.mp4'        => '',
		  'x264'        => '',
		  'x265'        => '',
		  'H264'        => '',
		  'H265'        => '',
		  'h.264'       => '',
			'h.265'       => '',
			'HEVC'        => '',
		  'BluRay'      => '',
		  'Blu-Ray'     => '',
		  'XviD'        => '',
		  'BRRip'       => '',
		  'BDRip'       => '',
		  'HDrip'       => '',
			'RERiP'       => '',
		  'mHD'         => '',
			' HD '        => '',
			'HDLIGHT'     => '',
		  'WEB.DL'      => '',
			'WEB-DL'      => '',
			'PS3'         => '',
			'XBOX360'     => '',
		  'TRUEFRENCH'  => 'Fr',
		  'french'      => 'Fr',
			'.'           => ' ',
		  '  '          => ' '
		);
		$name =  str_ireplace(array_keys($replace), array_values($replace), $this->name);
		// On vire les indications de qualité indiquées au début du nom (elles sont présentes en milieu de nom)
		$name = preg_replace('/^\[\s?\d*p\]/i', '', $name);
		// On vire les noms à la noix entre accolades à la fin du fichier
		$name = preg_replace('/\[[^\]\[\d]*\]$/i', '', $name);
		// Et on vire les noms à la noix en fin de torrent
		return trim(preg_replace('/(-.\S*)$/i', '', $name));
	}
}
?>