<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 05/08/14
 * Time: 12:06
 */

namespace FileSystem;


/**
 * Objet fichier
 *
 * @package FileSystem
 *
 * @property-read string $name
 * @property-read string $cleanName
 * @property-read string $fullName
 * @property-read int $dateCreated
 * @property-read int $dateModified
 * @property-read int $size
 * @property-read string $extension
 * @property-read string $fullType
 * @property-read string $type
 * @property-read array $labels
 * @property-read int $chmod
 * @property-read int $advChmod
 * @property-read bool $writable
 * @property-read string $owner
 * @property-read bool $linuxHidden
 * @property-read string $parentFolder
 *
 */
class File {

	protected $name = null;
	protected $cleanName = null;
	protected $fullName = null;
	protected $dateCreated = 0;
	protected $dateModified = 0;
	protected $size = 0;
	protected $extension = null;
	protected $encoding = null;
	protected $fullType = null;
	protected $type = null;
	protected $labels = array();
	protected $chmod = 0;
	protected $advChmod = 0;
	protected $writable = false;
	protected $owner = null;
	protected $groupOwner = null;
	protected $linuxHidden = false;
	protected $parentFolder = null;

	/**
	 * Construit un objet fichier
	 *
	 * @param string  $mountName Répertoire du fichier
	 * @param string  $fileName Nom du fichier
	 * @param array   $filters Filtrage de propriétés, certaines d'entre elles pouvant être lentes à récupérer
	 */
	public function __construct($mountName, $fileName, array $filters = array()){
		$this->name = $fileName;
		$this->fullName = rtrim($mountName, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR. $fileName;
		if (file_exists($this->fullName)){
			if ((!empty($filters) and in_array('extension', $filters)) or empty($filters)){
				$this->extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
				if (empty($this->extension) and !is_dir($this->fullName)) {
					$nameTab = explode('.', $this->name);
					$this->extension = strtolower(end($nameTab));
				}
			}
			if ((!empty($filters) and in_array('writable', $filters)) or empty($filters)){
				$this->writable = is_writable($this->fullName);
			}
			if ((!empty($filters) and in_array('encoding', $filters)) or empty($filters)){
				exec('file -i ' . $this->fullName, $output);
				if (isset($output[0])){
					$ex = explode('charset=', $output[0]);
					$this->encoding = isset($ex[1]) ? $ex[1] : null;
				}else{
					$this->encoding = null;
				}
			}
			$this->linuxHidden = (substr($fileName, 0, 1) == '.') ? true : false;
			if (!empty($this->filters)) $this->filters[] = 'linuxHidden';
			$this->parentFolder = dirname($this->fullName);
			if (!empty($this->filters)) $this->filters[] = 'parentFolder';
			exec('stat -c "%s@%a@%U@%G@%W@%Y@%F" "'.$this->fullName.'"', $out);
			list($this->size, $this->chmod, $this->owner, $this->groupOwner, $this->dateCreated, $this->dateModified, $this->fullType) = explode('@', $out[0]);
			$this->fullType = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->fullName);
			$this->type();
			if (\Settings::DISPLAY_CLEAN_FILENAMES and $this->type == 'Vidéo') {
			//if (\Settings::DISPLAY_CLEAN_FILENAMES) {
				$this->extractNameAndLabels();
			} else {
				$this->cleanName = $this->name;
			}
		}else{
			\Components::Alert('warning', '<code>File Constructor</code> : le fichier <code>'.$this->fullName.'</code> n\'existe pas !');
			$this->name = null;
			$this->fullName = null;
		}
	}

	/**
	 * Récupère la taille d'un fichier
	 *
	 * Sur des systèmes 32bits, la taille des fichiers > 2 Go est mal retournée (nombre négatif)
	 * On passe donc par cette fonction pour obtenir la taille réelle.
	 *
	 * @link <http://stackoverflow.com/a/5501987/1749967>
	 *
	 * @return float
	 */
	protected function getFileSize() {
		$size = @filesize($this->fullName);
		if ($size === false) {
			$fp = @fopen($this->fullName, 'r');
			if (!$fp) {
				return 0;
			}
			$offset = PHP_INT_MAX - 1;
			$size = (float) $offset;
			if (!fseek($fp, $offset)) {
				return 0;
			}
			$chunksize = 8192;
			while (!feof($fp)) {
				$size += strlen(fread($fp, $chunksize));
			}
		} elseif ($size < 0) {
			// Handle overflowed integer...
			$size = sprintf("%u", $size);
		}
		return floatval($size);
	}
	
	public function __isset($prop){
		return isset($this->$prop);
	}
	
	public function __get($prop){
		if (isset($this->$prop)) return $this->$prop;
		return null;
	}

	/**
	 * Détermine le type "commun" du fichier suivant son type MIME et/ou son extension de fichier.
	 *
	 * @warning Le véritable format du fichier n'est pas vérifié.
	 */
	protected function type(){
		if ($this->fullType == 'application/octet-stream'){
			$this->hackTypes();
		}
		switch ($this->fullType){
			case 'directory':
			case 'inode/directory':
				$ext = 'Répertoire';
				break;
			case 'symbolic link':
				$ext = 'Lien symbolique';
				break;
			case 'text/plain':
				switch ($this->extension){
					case 'ini':
					case 'cfg':
						$ext = 'Paramétrage';
						break;
					case 'nfo':
						$ext = 'Information';
						break;
					default:
						$ext = 'Fichier texte';
				}
				break;
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$ext = 'Document Word';
				break;
			case 'application/pdf':
				$ext = 'PDF';
				break;
			case 'application/vnd.ms-excel':
				$ext = 'Document Excel';
				break;
			case 'application/vnd.ms-powerpoint':
				$ext = 'Document Powerpoint';
				break;
			case 'application/zip':
			case 'application/x-gzip':
				$ext = 'Archive';
				break;
			case 'application/x-iso9660-image':
				$ext = 'Image ISO';
				break;
			case 'application/x-executable':
				$ext = 'Exécutable';
				break;
			case 'application/x-dosexec':
				if (strtolower($this->extension) == 'exe'){
					$ext = 'Exécutable';
				}else{
					$ext = 'Composant';
				}
				break;
			case 'application/octet-stream':
				switch ($this->extension){
					case 'lnk':
						$ext = 'Raccourci';
						break;
					case 'iso':
						$ext = 'Image ISO';
						break;
					case 'pst':
						$ext = 'Archives Outlook';
						break;
					default:
						$ext = 'Fichier';
						break;
				}
				break;
			case 'application/pgp-keys':
				$ext = 'Certificat';
				break;
			case 'application/x-debian-package':
				$ext = 'Installeur';
				break;
			case 'audio/mpeg':
				$ext = 'Musique';
				break;
			default:
				if (preg_match('/text\/(html|x-.*)/i', $this->fullType)){
					$ext = 'Fichier code';
				}elseif (preg_match('/image\/.*/i', $this->fullType)){
					$ext = 'Image';
				}elseif (preg_match('/video\/.*/i', $this->fullType)){
					$ext = 'Vidéo';
				}else{
					$ext = 'Fichier';
				}

		}
		$this->type = $ext;
	}

	/**
	 * Permet d'affecter les bons types aux vidéos quand elles sont trop grandes pour un système 32bits et que les types MIME du système ne sont pas à jour.
	 * Cette fonction ne devrait en théorie pas être utilisée.
	 */
	protected function hackTypes(){
		if ($this->fullType == 'application/octet-stream'){
			switch ($this->extension){
				case 'mkv':
					$this->fullType = 'video/x-matroska';
					break;
				case 'mp4':
					$this->fullType = 'video/mp4';
			}
		}
	}

	/**
	 * Retourne la classe Font Awesome de l'icône de fichier
	 *
	 * @return string
	 */
	public function getIcon(){
		switch ($this->type){
			case 'Répertoire':
				return 'folder';
			case 'Fichier texte':
				return 'file-alt';
			case 'Archive':
				return 'file-archive';
			case 'Archives Outlook':
				return 'envelope';
			case 'Exécutable':
				return 'cog';
			case 'Composant':
				return 'cube';
			case 'Certificat':
				return 'key';
			case 'Fichier code':
				return 'file-code';
			case 'Paramétrage':
				return 'sliders';
			case 'Installateur':
				return 'download';
			case 'Image ISO':
				return 'hdd';
			case 'Image':
				return 'image';
			case 'Information':
				return 'info-circle';
			case 'Raccourci':
				return 'share-square';
			case 'Document Word':
				return 'file-word';
			case 'Document Excel':
				return 'file-excel';
			case 'Document Powerpoint':
				return 'file-powerpoint';
			case 'Musique':
				return 'music';
			case 'PDF':
				return 'file-pdf';
			case 'Vidéo':
				return 'film';
			case 'Lien symbolique':
				return 'external-link-alt';
			default:
				return 'file';
		}
	}

	protected function extractNameAndLabels() {
		$labels = array();
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
			'mHD'         	=> '',
			'HDLIGHT'     	=> 'LIGHT',
			'WEB.DL'      	=> '',
			'WEB-DL'      	=> '',
			'WEBRIP'		=> '',
			'PS3'         	=> '',
			'XBOX360'     	=> '',
			'V.longue'    	=> '',
			'TRUEFRENCH'	=> 'VFF',
			'french'    	=> 'VF',
			'vff'			=> 'VFF',
			'vf2'			=> 'VFF',
			'vfi'			=> 'VF',
			'vfq'			=> 'VFQ',
			'vo'			=> 'ENG',
			'vf'        	=> 'VF',
			'[FR]'			=> 'VF',
			'-eng'			=> 'ENG',
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
		$name = preg_replace('/^(\d+)\. /i', '', $this->name);
		$name = str_ireplace(array_keys($search), '', $name);
		$name = str_replace('.', ' ', $name);
		$name = str_replace(' - ', ' ', $name);
		$name = str_replace('  ', ' ', $name);
		// On convertit les chiffres romains en nombres (mais pas au delà de 3, car `IV` peut ne pas être un chiffre romain dans la chaîne de caractères)
		$name = str_replace('III', '3', $name);
		$name = str_replace('II', '2', $name);
		// Détection d'un épisode de série TV
		preg_match_all('/\sS(\d{1,2})E(\d{1,2})/im', $name, $matches);
		if (!empty($matches[0])){
			$season = intval($matches[1][0]);
			$episode = intval($matches[2][0]);
			unset($matches);
			$name = preg_replace('/\sS(\d{1,2})E(\d{1,2})/i', '', $name);
			// On vire les indications de qualité ou de compatibilité entre crochets
			$name = preg_replace('/\[.*\]/i', '', $name);
			// Et on vire les noms à la noix en fin de torrent
			$name = trim(preg_replace('/(-.\S*)$/i', '', $name), ' -');
			$labels['type'] = 'tv';
		}else{
			$labels['type'] = 'movie';
			if (preg_match('/^(.+?)(\d{4})/i', $name, $matches)) {
				$labels['year'] = $matches[2];
				$name = trim($matches[1]);
			}
			// Et on vire les noms à la noix en fin de torrent
			$name = trim(preg_replace('/((?>\.|-|~).\S+?)$/i', '', $name), ' -');
		}
		// On supprime tout ce qui est entre parenthèses ou entre crochets
		$name = preg_replace('/\[(.*?)\]|\((.*?)\)/i', '', $name);
		$name = trim($name, '[]() .');
		foreach ($search as $searched => $foundLabel) {
			if (!empty($foundLabel) and preg_match('/'.preg_quote($searched).'/i', $this->name)) {
				$labels['labels'][$foundLabel] = $foundLabel;
  			} 
		}
		if (isset($labels['labels']['VFF']) and isset($labels['labels']['VF'])) {
			unset($labels['labels']['VF']);
		}
		$this->cleanName = $name;
		$this->labels = $labels;
	}

	/**
	 * Affiche l'icône en rapport avec le fichier
	 */
	public function displayIcon(){
		?><span class="fas fa-<?php echo $this->getIcon(); ?>"></span>&nbsp;<?php
	}

	/**
	 * Affiche le nom et l'icône du fichier
	 */
	public function display(){
		$this->displayIcon();
		echo '&nbsp;'.$this->cleanName;
		if (\Settings::DISPLAY_CLEAN_FILENAMES) {
			echo '<span class="uk-align-right uk-margin-remove-bottom">';
			if (!empty($this->labels['labels'])) {
				foreach ($this->labels['labels'] as $label) {
					switch ($label) {
						case 'VF':
						case 'VFI':
							$tooltip = 'Version française internationale (certaines voix peuvent avoir été changées)';
							break;
						case 'VFF':
							$tooltip = 'Version française (TrueFrench)';
							break;
						case 'VFQ':
							$tooltip = 'Version québecquoise';
							break;
						case 'ENG':
							$tooltip = 'Langue anglaise';
							break;
						case 'FullHD':
							echo '&nbsp;<span class="uk-label uk-label-success" title="1080p" uk-tooltip>Full HD</span>';
							break;
						case 'HD':
							echo '&nbsp;<span class="uk-label uk-label-warning" title="720p" uk-tooltip>HD</span>';
							break;
						case '3D':
							echo '&nbsp;<span class="uk-label" '.((isset($this->labels['labels']['SBS'])) ? 'title="Image côte à côte (SBS)" uk-tooltip':'').'>3D</span>';
							break;

					}
					if (in_array($label, array('VF', 'VFF', 'VFI', 'ENG'))) echo '&nbsp;<img title="'.$tooltip.'" class="movie-flag" src="./img/flags/'.$label.'.svg"alt="'.$label.'" uk-tooltip>';
				}
			}
			if (!empty($this->labels['year'])) {
				echo '&nbsp;<span title="Film sorti au cinéma en '.$this->labels['year'].'" class="uk-label" uk-tooltip>'.$this->labels['year'].'</span>';
			}
			echo '</span>';
		}
	}

	/**
	 * Retourne la couleur à appliquer au fichier lors de l'affichage
	 * @return string
	 */
	public function colorClass(){
		$class = '';
		switch ($this->type){
			case 'Répertoire':
				$class = 'uk-text-warning';
		}
		if ($this->linuxHidden) $class = 'text-transparent';
		return $class;
	}
} 