<?php
use FileSystem\File;
use FileSystem\Fs;

/**
 * Creator: Dric
 * Date: 29/05/2017
 * Time: 14:34
 */
class Files extends Page{
	var $url = '?page=files';
	protected $title = 'Fichiers';
	protected $tmdbUrl = 'https://www.themoviedb.org/';

	public function __construct() {
		parent::__construct();
		if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'displayImage'){
			$this->displayImage();
		}
	}

	public function main() {
		global $isGuest;
		$file = null;
		if (isset($_REQUEST['file'])){
			$file = urldecode($_REQUEST['file']);
			if (!file_exists($file)){
				Components::Alert('danger', 'Le fichier <code>'.$file.'</code> n\'existe pas !');
				$file = null;
			}elseif ((!$isGuest and strpos($file, Settings::DATA_PARTITION) === false) or ($isGuest  and strpos($file, Settings::GUEST_DATA_PARTITION) === false)){
				Components::Alert('danger', 'Vous n\'avez pas l\'autorisation de visualiser ce fichier !');
				$file = null;
			}
		}
		if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'fileDownload'){
			$this->fileDownload();
		}
		
		if (empty($file)){
			$folder = (isset($_REQUEST['folder'])) ? urldecode($_REQUEST['folder']): ((!$isGuest) ? Settings::DATA_PARTITION : Settings::GUEST_DATA_PARTITION);
			if (!file_exists($folder)){
				Components::Alert('danger', 'Le répertoire <code>'.$folder.'</code> n\'existe pas !');
				$folder = (!$isGuest) ? Settings::DATA_PARTITION : Settings::GUEST_DATA_PARTITION;
			}elseif ((!$isGuest and strpos($folder, Settings::DATA_PARTITION) === false) or ($isGuest  and strpos($folder, Settings::GUEST_DATA_PARTITION) === false)){
				Components::Alert('danger', 'Vous n\'avez pas l\'autorisation de visualiser ce répertoire !');
				$folder = (!$isGuest) ? Settings::DATA_PARTITION : Settings::GUEST_DATA_PARTITION;
			}
			$this->displayFolder($folder);
		}else{
			$this->displayFile($file);
		}
	}

	public function menu(){
		?>
		<ul class="uk-nav uk-nav-default uk-margin-auto-vertical">
			<li><a href=".">Retour à l'accueil</a></li>
		</ul>


		<?php
	}

	protected function displayFolder($folder){
		global $isGuest;
		$rootFolder = (!$isGuest) ? realpath(Settings::DATA_PARTITION) : realpath(Settings::GUEST_DATA_PARTITION);
		$fs = new Fs($folder);
		if (!$fs->getIsMounted()){
			Components::Alert('danger', 'Impossible d\'afficher le contenu du répertoire !');
		} else {
			$filesInDir = $fs->getFilesInDir(null, null, array('dateModified', 'type', 'size', 'extension'));
			// On classe les items, les répertoires sont en premier
			$files = $folders = array();
			/**
			 * @var File $item
			 */
			foreach ($filesInDir as $item) {
				if ($item->type == 'Répertoire') {
					$folders[] = $item;
				} else {
					$files[] = $item;
				}
			}
			unset($filesInDir);
			$folders      = \Sanitize::sortObjectList($folders, 'name');
			$files        = \Sanitize::sortObjectList($files, 'name');
			$files        = array_merge($folders, $files);
			$parentFolder = dirname($folder);
			?><?php echo $this->breadcrumbTitle($folder); ?><?php if (strpos($parentFolder, $rootFolder) !== false and $folder != $parentFolder) { ?><p><a class="uk-link-reset" href="<?php echo $this->buildArgsURL(array('folder' => urlencode($parentFolder))); ?>" uk-icon="icon: arrow-up"> Remonter d'un niveau</a></p><?php } ?>
			<div class="table-responsive uk-box-shadow-medium uk-overlay-default uk-padding-small" id="salsifis-table-container">
				<table id="fileBrowser" class="uk-table uk-table-divider uk-table-small uk-table-justify">
					<thead>
					<tr>
						<td>Nom</td>
						<td class="uk-visible@m">Type</td>
						<td class="uk-visible@m">Taille</td>
						<td class="uk-visible@l">Dernière modification</td>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ($files as $i => $item) {
						if ($item->type != 'Répertoire') {
							$itemUrl = $this->buildArgsURL(array('file' => urlencode($item->fullName), 'folder' => urlencode($item->parentFolder)
								));
						} else {
							$itemUrl = $this->buildArgsURL(array('folder' => urlencode($item->fullName)
								));
						}

						?>
						<tr class="<?php echo $item->colorClass(); ?>">
							<td class="uk-table-link uk-text-truncate" data-order="<?php echo $i; ?>">
								<a href="<?php echo $itemUrl; ?>" class="<?php echo $item->colorClass(); ?> uk-link-reset">
									<?php $item->display(); ?>
								</a>
							</td>
							<td class="uk-table-shrink uk-text-nowrap uk-visible@m"><?php echo ($item->type != 'Répertoire') ? '<abbr title="' . ((!empty($item->fullType)) ? $item->fullType : 'Format inconnu') . '" uk-tooltip="pos: bottom">' . $item->type . '</abbr>': $item->type; ?></td>
							<td class="uk-table-shrink uk-text-nowrap uk-visible@m" data-order="<?php echo ($item->type == 'Répertoire') ? 0 : $item->size; ?>"><?php if ($item->type != 'Répertoire') echo \Sanitize::readableFileSize($item->size); ?></td>
							<td class="uk-table-shrink uk-text-nowrap uk-visible@l" data-order="<?php echo $item->dateModified; ?>"><?php echo \Sanitize::date($item->dateModified, 'dateTime'); ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
			<?php
		}
	}

	protected function displayFile($file){
		$path = dirname($file).DIRECTORY_SEPARATOR;
		$fullFilePath = $file;
		$file = str_replace($path, '', $file);
		$fs = new Fs($path);
		$fileMeta = $fs->getFileMeta($file);
		?>
		<h2><span class="uk-icon-button" uk-icon="icon: <?php echo $fileMeta->getIcon(); ?>; ratio: 2"></span>&nbsp;<?php echo $file; ?></h2>
		<div class="uk-box-shadow-medium uk-overlay-default uk-padding-small">
			<p>Dans <a title="retourner au répertoire" class="uk-link-reset uk-icon" uk-tooltip="pos: bottom" href="<?php echo $this->buildArgsURL(array('folder' => urlencode($fileMeta->parentFolder))); ?>" uk-icon="icon: arrow-up"><?php echo $fileMeta->parentFolder; ?></a></p>
			<ul>
				<li>Date de création : <?php echo \Sanitize::date($fileMeta->dateCreated, 'dateTime'); ?></li>
				<li>Date de dernière modification : <?php echo \Sanitize::date($fileMeta->dateModified, 'dateTime'); ?></li>
				<li>Taille : <?php echo \Sanitize::readableFileSize($fileMeta->size); ?></li>
				<li>Lien : <a class="uk-link-reset" href="<?php echo $this->buildArgsURL(array('file' => $fullFilePath, 'action' => 'fileDownload')); ?>">Télécharger le fichier</a></li>
				<li>
					Contenu :<br>
					<?php
					switch ($fileMeta->type){
						case 'Image':
							?><img class="img-thumbnail" alt="<?php echo $file; ?>" src="<?php echo $this->url.'&action=displayImage&file='.$fileMeta->fullName; ?>"><?php
							break;
						case 'Fichier texte':
						case 'Information':
							$fileContent = $fs->readFile($file, 'string');
							if (!\Check::isUtf8($fileContent)){
								$fileContent = mb_convert_encoding($fileContent, "UTF-8", "ASCII, ISO-8859-1, Windows-1252");
							}
							?><pre class="uk-box-shadow-medium uk-overlay-default uk-padding-small uk-margin-right"><?php echo htmlentities($fileContent, ENT_NOQUOTES|ENT_SUBSTITUTE); ?></pre><?php
							break;
						case 'Vidéo':
							$this->getTMDBData($file);
							break;
						case 'Fichier code':
						case 'Fichier de paramétrage':
							$fileContent = $fs->readFile($file, 'string');
							if (!\Check::isUtf8($fileContent)){
								$fileContent = mb_convert_encoding($fileContent, "UTF-8", "ASCII, ISO-8859-1, Windows-1252");
							}
							?><pre class="uk-box-shadow-medium uk-overlay-default uk-padding-small"><code class="no-background"><?php echo htmlentities($fileContent, ENT_NOQUOTES|ENT_SUBSTITUTE); ?></code></pre><?php
							break;
						default:
							?><div class="alert alert-info">Vous ne pouvez pas visualiser ce type de contenu.</div><?php
					}
					?>
				</li>
			</ul>
		</div>
		<?php
	}

	protected function fileDownload(){
		$file = null;
		global $isGuest;

		if (isset($_REQUEST['file'])){
			$file = urldecode($_REQUEST['file']);
			if (!file_exists($file)){
				Components::Alert('danger', 'Le fichier <code>'.$file.'</code> n\'existe pas !');
				$file = null;
			}elseif ((!$isGuest and strpos($file, Settings::DATA_PARTITION) === false) or ($isGuest and strpos($file, Settings::GUEST_DATA_PARTITION) === false)){
				Components::Alert('danger', 'Vous n\'avez pas l\'autorisation de visualiser ce fichier !');
				$file = null;
			}
		}
		if (empty($file)){
			$folder = (isset($_REQUEST['folder'])) ? urldecode($_REQUEST['folder']): ((!$isGuest) ? Settings::DATA_PARTITION : Settings::GUEST_DATA_PARTITION);
			if (!file_exists($folder)){
				Components::Alert('danger', 'Le répertoire <code>'.$folder.'</code> n\'existe pas !');
				$folder = (!$isGuest) ? Settings::DATA_PARTITION : Settings::GUEST_DATA_PARTITION;
			}elseif ((!$isGuest and strpos($folder, Settings::DATA_PARTITION) === false) or ($isGuest and strpos($folder, Settings::GUEST_DATA_PARTITION) === false)){
				Components::Alert('danger', 'Vous n\'avez pas l\'autorisation de visualiser ce répertoire !');
				$folder = (!$isGuest) ? Settings::DATA_PARTITION : Settings::GUEST_DATA_PARTITION;
			}
		}
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $file);
		if($mime){
			header("Content-type: $mime");
		}else{
			header("Content-type: application/octet-stream");
		}
		header('Content-Disposition: attachment; filename="' . basename($file) . '"');
		header("X-Sendfile: " . $file);
		header("Content-Length: " . filesize($file));

		exit;
	}

	public static function cleanName($fileName) {
		$search = array(
			'.mkv'        	=> '',
			'.mp4'       	=> '',
			'.avi'			=> '',
			'x264'        	=> '',
			'H264'        	=> '',
			'720p'        	=> 'HD',
			'1080p'       	=> 'FullHD',
			'dvdrip'      	=> '',
			'Divx'			=> '',
			'h.264'       	=> '',
			'BluRay'      	=> '',
			'Blu-Ray'     	=> '',
			'XviD'        	=> '',
			'BRRip'       	=> '',
			'BDRip'       	=> '',
			'HDrip'       	=> '',
			' HD '        	=> '',
			'mHDgz'			=> '',
			'mHD'         	=> '',
			'HDLIGHT'     	=> 'LIGHT',
			'WEB.DL'      	=> '',
			'WEB-DL'      	=> '',
			'WEBRIP'		=> '',
			'PS3'         	=> '',
			'XBOX360'     	=> '',
			'V.longue'    	=> '',
			'TRUEFRENCH'	=> 'VFF',
			'TRUEFR'		=> 'VFF',
			'french'    	=> 'VF',
			'vff'			=> 'VFF',
			'vf2'			=> 'VFF',
			'vfi'			=> 'VF',
			'vfq'			=> 'VFQ',
			'vo'			=> 'ENG',
			'vf'        	=> 'VF',
			'[FR]'			=> 'VF',
			'-eng'			=> 'ENG',
			' EN '			=> 'ENG',
			'subforces' 	=> '',
			' MULTI '   	=> 'VF',
			'.MULTI.'		=> 'VF',
			'ac3'       	=> '',
			'aac'       	=> '',
			'DTS'			=> '',
			'5.1'       	=> '',
			'6ch'			=> '',
			'3D'			=> '3D',
			'side by side'	=> 'SBS'
		);
		// On vire les éventuels numéros aux débuts des films, mais seulement ceux qui sont suivis immédiatement par un `. `
		$name = preg_replace('/^(\d+)\. /i', '', $fileName);
		$name = str_ireplace(array_keys($search), '', $name);
		$name = preg_replace('/(\.|_|\s{2,})/i', ' ', $name);
		// On convertit les chiffres romains en nombres (mais pas au delà de 3, car `IV` peut ne pas être un chiffre romain dans la chaîne de caractères)
		$name = str_replace('III', '3', $name);
		$name = str_replace('II', '2', $name);
		// Détection d'un épisode de série TV
		$season = $episode = $year = null;
		preg_match_all('/\sS(\d{1,2})E(\d{1,2})/im', $name, $matches);
		if (!empty($matches[0])){
			$season = intval($matches[1][0]);
			$episode = intval($matches[2][0]);
			unset($matches);
			$name = preg_replace('/\sS(\d{1,2})E(\d{1,2})/i', '', $name);
			// On vire les indications de qualité ou de compatibilité entre crochets
			$name = preg_replace('/\[.*\]/i', '', $name);
			$type = 'tv';
		}else{
			$type = 'movie';
			// Il est compliqué d'isoler la date vu que certains films en ont dans leur titre. De plus, la date peut être confondue avec la résolution (sur 4 chiffres).
			// On ne récupère donc que les dates qui n'ont pas de lettre immédiatement derrière
			if (preg_match('/^(.+?)[^a-z](\d{4})(?>[^@]+?(\d{4}[^a-z])|)/i', $name, $matches)) {
				// Si le nom du film comporte une année (ex : `Blade Runner 2049`), on regarde si l'année n'est pas spécifiée plus loin
				if (isset($matches[3])) {
					$year = $matches[3];
					$name = trim($matches[1].' '.$matches[2]);
				} else {
					$year = $matches[2];
					$name = trim($matches[1]);
				}
			}
		}
		// Et on vire les noms à la noix en fin de torrent
		$name = preg_replace('/\s((\.|-|~)\S+?)$/i', '', $name);
		// On supprime tout ce qui est entre parenthèses, crochets, espaces multiples
		$name = preg_replace('/\[(.*?)\]|\((.*?)\)|\s{2,}(.*?)\s{1,}|(-\s){2,}/i', '', $name);
		$name = trim($name, '[]() .');

		$labels = array();
		foreach ($search as $searched => $foundLabel) {
			if (!empty($foundLabel) and preg_match('/'.preg_quote($searched).'/i', $fileName)) {
				$labels[$foundLabel] = $foundLabel;
  			} 
		}
		if (isset($labels['VFF']) and isset($labels['VF'])) {
			unset($labels['VF']);
		}

		return array(
			'name'		=> $name,
			'type'		=> $type,
			'labels'	=> $labels,
			'year'		=> $year,
			'season'	=> $season,
			'episode'	=> $episode
		);
	}

	/**
	 * Récupère les informations d'un film ou d'un épisode de série TV auprès de TheMovieDataBase.org
	 *
	 * Un filtre est effectué pour nettoyer le nom du fichier et augmenter les chances de récupérer le bon film ou épisode TV.
	 *
	 * @param string $fileName Nom du fichier
	 */
	protected function getTMDBData($fileName){

		$fileDetails = $self::cleanName($fileName);
		//$name = \Sanitize::removeAccents($fileDetails['name']);
		$name = $fileDetails['name'];
		$type = $fileDetails['type'];
		$year = $fileDetails['year'];
		$season = $fileDetails['season'];
		$episode = $fileDetails['episode'];
		
		//var_dump($name);
		echo '<!-- name : '.\Get::varDump($name).' -->'."\n";
		echo '<!-- type : '.\Get::varDump($type).' -->'."\n";
		echo '<!-- year : '.\Get::varDump($year).' -->'."\n";
		if ($type =='tv') echo '<!-- saison : '.\Get::varDump($season).' - épisode : '.\Get::varDump($episode).' -->'."\n";
		/** @var \TMDB\Client $tmdb */
		$tmdb = \TMDB\Client::getInstance('dfac51ae8cfdf42455ba6b01f392940f');
		$tmdb->language ='fr';
		$tmdb->paged = true;
		$filter = array(
			'query' => $name
		);
		if (!empty($year)) $filter['year'] = (int)$year;
		$results = $tmdb->search($type, $filter);
		if (empty($results)){
			// On tente avec le premier mot du film
			$filter['query'] = explode(' ', $name)[0];
			$results = $tmdb->search($type, $filter);
		}

		if (!empty($results)){
			$class = "\\TMDB\\structures\\".ucfirst($type);
			$result = null;
			foreach ($results as $id => $movie){
				if (\Sanitize::sanitizeFilename($movie->title) == $name or \Sanitize::sanitizeFilename($movie->original_title) == $name){
					$result = $movie;
					break;
				}
			}
			//var_dump($results);
			if (is_null($result)) $result = reset($results);
			/** @var \TMDB\structures\Movie $movie */
			$movie = new $class($result->id);
			if ($type == 'movie'){
				// On récupère la date de sortie en France
				$release = \Get::getObjectsInList($movie->releases()->countries, 'iso_3166_1', 'FR');
				if (empty($release)){
					// Si la date de sortie en France n'est pas renseignée, on prend celle des USA
					$release = \Get::getObjectsInList($movie->releases()->countries, 'iso_3166_1', strtoupper($movie->original_language));
				}

				$release = $release[0];
			}else{
				/** @var \TMDB\structures\Tv $movie */
				$release = new \StdClass();
				$release->release_date = $movie->first_air_date;
			}
			//echo Get::varDump($movie);
			?>
			<div class="uk-box-shadow-medium uk-overlay-default uk-padding-small">

				<h2><a href="<?php echo $this->tmdbUrl.$type.'/'.$movie->id; ?>"><?php echo ($type == 'movie') ? $movie->title : $movie->name; ?></a></h2>
				<p><i>Il est possible que le film indiqué ci-dessous ne soit pas le bon. L'interrogation de la base IMDB retourne parfois des résultats étranges...</i></p>
				<img class="uk-align-right" alt="<?php echo $movie->title; ?>" src="<?php echo $movie->poster('300'); ?>">
				<ul>
					<li>Date de <?php echo ($type == 'movie') ? 'sortie en France' : 'première diffusion aux USA'; ?> : <?php echo date("d/m/Y", strtotime($release->release_date)); ?></li>
					<?php if ($type == 'movie'){ ?>
						<li>Classification : <span class="uk-badge" uk-tooltip="pos: bottom" title="<?php echo (is_numeric($release->certification)) ? 'Interdit aux moins de '.$release->certification.' ans' : (in_array($release->certification, array('U', 'PG'))) ? 'Tout public' : 'Classification : '.$release->certification; ?>"><?php echo (is_numeric($release->certification)) ? '-'.$release->certification : $release->certification; ?></span></li>
					<?php }else{ ?>
						<li>Série<?php echo ($movie->status == 'Ended') ? '' : ' non'; ?> terminée</li>
						<li>Nombre de saisons : <?php echo $movie->number_of_seasons; ?></li>
					<?php } ?>
				</ul>
				<h3>Résumé</h3>
				<p><?php echo $movie->overview; ?></p>
				<h3>Genre(s)</h3>
				<?php
				foreach ($movie->genres as $genre){
					?><span class="uk-badge badge-info"><?php echo $genre->name; ?></span>&nbsp;<?php
				}
				?>
				<?php if ($type == 'movie'){ ?>
					<h3>Réalisateur</h3>
					<?php
					$casting = $movie->casts();
					$director = \Get::getObjectsInList($casting['crew'], 'job', 'Director')[0];
					?>
					<div class="media uk-panel">
						<a class="pull-left" href="<?php echo $this->tmdbUrl.'person/'.$director->id; ?>">
							<img class="media-object uk-border-circle" src="<?php echo (!empty($director->profile_path)) ? $tmdb->image_url('poster', 80, $director->profile_path) : \Settings::AVATAR_PATH.DIRECTORY_SEPARATOR.\Settings::AVATAR_DEFAULT; ?>" alt="<?php echo $director->name; ?>">
						</a>
						<div class="media-body">
							<h4 class="media-heading"><a href="<?php echo $this->tmdbUrl.'person/'.$director->id; ?>"><?php echo $director->name; ?></a></h4>
						</div>
					</div>
					<h3>Casting</h3>
					<?php
					foreach ($casting['cast'] as $actor){
						?>
						<div class="media uk-panel">
							<a class="pull-left" href="<?php echo $this->tmdbUrl.'person/'.$actor->id; ?>">
								<img class="media-object uk-border-circle" src="<?php echo (!empty($actor->profile_path)) ? $tmdb->image_url('poster', 80, $actor->profile_path) : \Settings::AVATAR_PATH.DIRECTORY_SEPARATOR.\Settings::AVATAR_DEFAULT; ?>" alt="<?php echo $actor->name; ?>">
							</a>
							<div class="media-body">
								<h4 class="media-heading"><a href="<?php echo $this->tmdbUrl.'person/'.$actor->id; ?>"><?php echo $actor->name; ?></a></h4>
								Personnage : <strong><?php echo $actor->character; ?></strong>
							</div>
						</div>
						<?php
					}
					?>
					<?php
				}else{
					$episodeData = $movie->episode($season, $episode);
					?>
					<h2><a href="<?php echo $this->tmdbUrl.'tv/'.$movie->id.'/season/'.$season.'/episode/'.$episode; ?>">Saison <?php echo $season; ?>, épisode <?php echo $episode; ?> : <?php echo $episodeData->name; ?></a></h2>
					<ul>
						<li>Date de première diffusion dans le pays d'origine : <?php echo date("d/m/Y", strtotime($episodeData->air_date)); ?></li>
					</ul>
					<h3>Résumé</h3>
					<p><?php echo (!empty($episodeData->overview)) ? $episodeData->overview : 'Pas de résumé.'; ?></p>
				<?php } ?>
			</div>
			<?php
		}else{
			?><div class="alert alert-warning">Aucune correspondance n'a pu être trouvée pour <code><?php echo $name; ?></code> sur <a href="http://www.themoviedb.org/">The Movie DataBase</a> !</div><?php
		}
	}

	/**
	 * Affiche une image distante (méthode utilisée en asynchrone - AJAX)
	 */
	protected function displayImage(){
		$fileName = $_REQUEST['file'];
		$file = new File('', $fileName);
		header('content-type: '. $file->fullType);
		header('content-disposition: inline; filename="'.$fileName.'";');
		readfile($fileName);
		/*$imageData = base64_encode(file_get_contents($fileName));
		$src = 'data: '.$file->fullType.';base64,'.$imageData;
		echo '<img src="' . $src . '">';*/
		exit();
	}

	/**
	 * Crée un fil d'ariane à partir du chemin du dossier
	 *
	 * @param string $folder Répertoire
	 *
	 * @return string
	 */
	protected function breadcrumbTitle($folder){
		global $isGuest;
		$rootFolder = (!$isGuest) ? realpath(Settings::DATA_PARTITION) : realpath(Settings::GUEST_DATA_PARTITION);
		$breadcrumb = '</ol>';
		do{
			$currentFolderPath = $folder;
			$folder = dirname($folder);
			$currentFolderName = str_replace($folder.DIRECTORY_SEPARATOR, '', $currentFolderPath);
			if ($currentFolderName != '/'){
				$currentFolderName = ltrim($currentFolderName, '/');
			}
			$breadcrumb = '<li><a href="'.$this->buildArgsURL(array('folder' => urlencode($currentFolderPath))).'">'.$currentFolderName.'</a></li>'.$breadcrumb;
		} while (strpos($folder, $rootFolder) !== false and $folder != $currentFolderPath);
		$breadcrumb = '<ol class="uk-breadcrumb uk-box-shadow-medium uk-overlay-default uk-padding-small">'.$breadcrumb;
		return $breadcrumb;
	}
}