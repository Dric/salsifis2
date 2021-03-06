<?php
/**
 * Salsifis²
 *
 * https://github.com/Dric/salsifis2
 *
 * Interface web pour serveur de téléchargements et diffusion multimedia.
 *
 * Essuyez vos pieds avant d'entrer.
 * Le code contenu dans ces fichiers n'est pas forcément des plus propres, vous voilà prévenu(e).
 */

if (version_compare(phpversion(), '7.0.0', '<')) {
	die('<h1>Erreur fatale et grossière !</h1><p>Votre version de PHP <code>'.phpversion().'</code> est trop ancienne, '.Settings::TITLE.' nécessite PHP 7 minimum !</p>');
}

session_start();

/**
 * Auto-Loading des classes
 */
spl_autoload_register(function ($class) {
	$tab = explode('\\', $class);
	// Si le fichier de config personnalisé n'existe pas, on ruse et on prend les paramètres par défaut
	if ($tab[0] == 'Settings' and !file_exists('classes/Settings.php')) {
		class Settings extends DefaultSettings {}
	}
	@include_once 'classes/' . str_replace("\\", "/", $class) . '.php';
});
if (\Settings::DEBUG) {
	/*
	* Permet de faire du profilage avec XDebug et (<http://github.com/jokkedk/webgrind/>), à condition d'avoir activé le profilage XDebug dans php.ini (ou conf.d/20-xdebug.ini) avec les commandes :
	*   xdebug.profiler_enable = 0
	*   xdebug.profiler_enable_trigger = 1
	*/
	setcookie('XDEBUG_PROFILE', true);
	// Pour obtenir le temps passé à générer la page.
	$startTime = microtime(true);
}

$absolutePath = dirname(__FILE__);


// Actions

// Authentification
if (Settings::USE_AUTH) {
	// On récupère le nom de l'utilsiateur connecté via son cookie
	$user = Auth::isLoggedIn();
	if ($user === false) {
		$page = new Login;
	}
	$isGuest = true;
	// Les utilisateurs en accès complet sont préfixés avec `@@@_`. Les autres sont des invités.
	if (substr($user, 0, 4) === '@@@_') {
		$isGuest = false;
		$user = substr($user, 4);
	}
} else {
	$isGuest = false;
}

if (!isset($page)) {
	if(isset($_REQUEST['logoff'])){
		Auth::deleteCookie();
		header('location: .');
		exit;
	}elseif (isset($_REQUEST['aSync'])){
		Async::getAsyncRequest();
		exit;
	}elseif(isset($_REQUEST['page'])){
		switch ($_REQUEST['page']){
			case 'downloads':
				$page = new Downloads;
				break;
			case 'files':
				$page = new Files;
				break;
			case 'reboot':
				$page = new Reboot;
				break;
			default:
				$page = new Page;
		}
	}else{
		// Page par défaut
		$page = new Page;
	}
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<title>
		<?php
		echo Settings::TITLE;
		If (!empty($page->subTitle())){
			echo ' - '.$page->subTitle();
		}
		?>
	</title>
	<!--<script defer src="https://use.fontawesome.com/releases/v5.0.6/js/all.js"></script>
	<script defer src="https://use.fontawesome.com/releases/v5.0.6/js/v4-shims.js"></script>-->
	<link rel="stylesheet" href="css/salsifis2.css" />
	<link rel="apple-touch-icon" sizes="57x57" href="img/favicons/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="img/favicons/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="img/favicons/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="img/favicons/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="img/favicons/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="img/favicons/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="img/favicons/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="img/favicons/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="img/favicons/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="img/favicons/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="img/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="img/favicons/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="img/favicons/favicon-16x16.png">
	<link rel="manifest" href="img/favicons/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">
</head>
<style>
	html{
		background: url('img/backgrounds/<?php echo Settings::BG_IMG; ?>') no-repeat fixed bottom;
		background-size: cover;
	}
</style>
<body>
	<div class="uk-offcanvas-content">
		<header class="">
			<h1 class="uk-heading-hero uk-heading-line uk-text-center">
				<span>
					<a href="." title="Accueil" class="uk-link-reset">
						<?php echo Settings::TITLE; ?><?php if (Settings::USE_AUTH) { ?><sup class="uk-label uk-label-warning" style="vertical-align: super;"><?php echo $user; ?></sup><?php } ?>
					</a>
					<br>
					<span class="uk-text-lead uk-align-right salsifis-sub-title"><?php echo $page->subTitle(); ?></span>
				</span>
			</h1>
		</header>

		<!-- Bouton affichant le menu -->
		<a title="Menu" uk-tooltip="pos: bottom" href="#offcanvas-usage" uk-toggle="target: #menu" type="button" uk-icon="icon: menu; ratio: 2" class="uk-position-top-right uk-margin-right uk-margin-top salsifis-menu-button"></a>
		<!-- Contenu principal -->
		<div class="uk-visible@l uk-padding">&nbsp;</div>
		<div class="uk-section uk-padding-remove-top">
			<div class="uk-container uk-container-small">
				<?php
				if (isset($_SESSION['alerts']) and !empty($_SESSION['alerts'])){
					foreach ($_SESSION['alerts'] as $alert){
						Components::Alert($alert['type'], $alert['message']);
					}
					unset($_SESSION['alerts']);
				}
				?>
				<?php $page->main(); ?>
			</div>
		</div>
		<div id="serverVersion" uk-modal></div>
		<!-- Fin contenu principal -->

		<!-- Menu latéral -->
		<div id="menu" uk-offcanvas="overlay: true">
			<div class="uk-offcanvas-bar">
				<div style="position:fixed;top:10px;left:10px;" class="salsifis-version uk-text-meta uk-text-small"><a href="#serverVersion" class="uk-link-reset serverVersionLink" uk-toggle>Salsifis<code>²</code></a></div>
				<h3 class="uk-margin-remove-top">Menu</h3>
				<button class="uk-offcanvas-close" type="button" uk-close></button>
				<?php $page->menu(); ?>
				<?php
				if (Settings::USE_AUTH){
					?>
					<ul class="uk-nav uk-nav-default uk-margin-auto-vertical">
						<li><a class="" href="?logoff">Déconnexion</a></li>
					</ul>
					<?php
				}
				?>
			</div>
		</div>
		<!-- Fin Menu latéral -->
		<a href="#" class="tothetop" title="Retour en haut de la page" uk-totop uk-scroll uk-tooltip="pos: top"></a>
	</div>
	<!-- Le Javascript -->
	<script src="node_modules/jquery/dist/jquery.min.js"></script>
	<script src="node_modules/uikit/dist/js/uikit.min.js"></script>
	<script src="node_modules/uikit/dist/js/uikit-icons.min.js"></script>
	<?php
	if (isset($_REQUEST['page']) and in_array($_REQUEST['page'], array('downloads', 'files'))) {
		?><script src="node_modules/datatables.net/js/jquery.dataTables.min.js"></script><?php
	}
	?>
	<script type="text/javascript">
		var isHomePage = <?php echo (!isset($_REQUEST['page'])) ? 'true' : 'false' ; ?>;
		var displayExternalIP = <?php echo (Settings::DISPLAY_EXTERNAL_IP) ? 'true' : 'false' ; ?>;
	</script>
	<script src="js/salsifis2.js"></script>
	<?php
	if (Settings::DEBUG) {
		$endTime = microtime(true);
		?><!-- Page générée <?php echo Sanitize::timeDuration($endTime - $startTime); ?>. --><?php
	}
	?>
</body>
</html>