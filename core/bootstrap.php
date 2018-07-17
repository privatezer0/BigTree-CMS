<?php
	ini_set("log_errors","false");
	
	if (!defined("BIGTREE_SITE_KEY")) {
		define("BIGTREE_SITE_TRUNK", 0);
		
		// Set some config vars automatically and setup some globals.
		$domain = rtrim($bigtree["config"]["domain"], "/");
		$www_root = $bigtree["config"]["www_root"];
		$static_root = isset($bigtree["config"]["static_root"]) ? $bigtree["config"]["static_root"] : $www_root;
	}
	
	$server_root = isset($server_root) ? $server_root : str_replace("core/bootstrap.php", "", strtr(__FILE__, "\\", "/"));
	$site_root = $server_root."site/";
	$secure_root = str_replace("http://", "https://", $www_root);
	$admin_root = $bigtree["config"]["admin_root"];
	
	define("WWW_ROOT", $www_root);
	define("STATIC_ROOT", $static_root);
	define("SECURE_ROOT", $secure_root);
	define("DOMAIN", $domain);
	define("SERVER_ROOT", $server_root);
	define("SITE_ROOT", $site_root);
	define("ADMIN_ROOT", $admin_root);

	// Adjust server parameters in case we're running on CloudFlare
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
		$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}

	// Set version
	include SERVER_ROOT."core/version.php";

	// Include required utility functions
	if (file_exists(SERVER_ROOT."custom/inc/bigtree/utils.php")) {
		include SERVER_ROOT."custom/inc/bigtree/utils.php";
	} else {
		include SERVER_ROOT."core/inc/bigtree/utils.php";
	}

	// Include Composer's autoloader
	if (!file_exists(SERVER_ROOT."vendor/autoload.php")) {
		BigTree::makeDirectory(SERVER_ROOT."vendor/");

		$path = str_replace("core/bootstrap.php", "", __FILE__);
		$off_path = str_replace(SERVER_ROOT, "", $path);

		symlink(SERVER_ROOT.$off_path."vendor/autoload.php", SERVER_ROOT."vendor/autoload.php");
		symlink(SERVER_ROOT.$off_path."vendor/aws", SERVER_ROOT."vendor/aws");
		symlink(SERVER_ROOT.$off_path."vendor/composer", SERVER_ROOT."vendor/composer");
		symlink(SERVER_ROOT.$off_path."vendor/guzzlehttp", SERVER_ROOT."vendor/guzzlehttp");
		symlink(SERVER_ROOT.$off_path."vendor/mtdowling", SERVER_ROOT."vendor/mtdowling");
		symlink(SERVER_ROOT.$off_path."vendor/oyejorge", SERVER_ROOT."vendor/oyejorge");
		symlink(SERVER_ROOT.$off_path."vendor/psr", SERVER_ROOT."vendor/psr");

		BigTree::copyFile(SERVER_ROOT.$off_path."vendor/autoload.php", SERVER_ROOT."vendor/autoload.php");
		BigTree::copyFile(SERVER_ROOT.$off_path."composer.lock", SERVER_ROOT."composer.lock");
		BigTree::copyFile(SERVER_ROOT.$off_path."composer.json", SERVER_ROOT."composer.json");
	}

	include SERVER_ROOT."vendor/autoload.php";
	
	// Connect to MySQL and include the shorterner functions
	include BigTree::path("inc/bigtree/sql.php");

	// Require PHP 5.4 to use the new class
	if (version_compare(PHP_VERSION, "5.4.0") >= 0) {
		include BigTree::path("inc/bigtree/sql-class.php");
	}
	
	// Setup our connections as disconnected by default.
	$bigtree["mysql_read_connection"] = "disconnected";
	$bigtree["mysql_write_connection"] = "disconnected";
	
	// Turn on debugging if we're in debug mode.
	if ($bigtree["config"]["debug"] === "full") {
		error_reporting(E_ALL);
		ini_set("display_errors","on");
	} elseif ($bigtree["config"]["debug"]) {
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
		ini_set("display_errors","on");
	} else {
		ini_set("display_errors","off");
	}
	
	// Load Up BigTree!
	include BigTree::path("inc/bigtree/cms.php");
	if (defined("BIGTREE_CUSTOM_BASE_CLASS") && BIGTREE_CUSTOM_BASE_CLASS) {
		include SITE_ROOT.BIGTREE_CUSTOM_BASE_CLASS_PATH;
		eval("class BigTreeCMS extends ".BIGTREE_CUSTOM_BASE_CLASS." {}");
	} else {
		class BigTreeCMS extends BigTreeCMSBase {};
	}
	$cms = new BigTreeCMS;

	// Lazy loading of modules
	$bigtree["module_list"] = $cms->ModuleClassList;
	$bigtree["other_classes"] = array(
		"BigTreeAdminBase" => "inc/bigtree/admin.php",
		"BigTreeAutoModule" => "inc/bigtree/auto-modules.php",
		"BigTreeModule" => "inc/bigtree/modules.php",
		"BigTreeFTP" => "inc/bigtree/ftp.php",
		"BigTreeSFTP" => "inc/bigtree/sftp.php",
		"BigTreeUpdater" => "inc/bigtree/updater.php",
		"BigTreeSessionHandler" => "inc/bigtree/sessions.php",
		"BigTreeGoogleAnalyticsAPI" => "inc/bigtree/apis/google-analytics.php",
		"BigTreePaymentGateway" => "inc/bigtree/apis/payment-gateway.php",
		"BigTreeUploadService" => "inc/bigtree/apis/storage.php", // Backwards compat
		"BigTreeStorage" => "inc/bigtree/apis/storage.php",
		"BigTreeCloudStorage" => "inc/bigtree/apis/cloud-storage.php",
		"BigTreeGeocoding" => "inc/bigtree/apis/geocoding.php",
		"BigTreeEmailService" => "inc/bigtree/apis/email-service.php",
		"BigTreeTwitterAPI" => "inc/bigtree/apis/twitter.php",
		"BigTreeInstagramAPI" => "inc/bigtree/apis/instagram.php",
		"BigTreeGooglePlusAPI" => "inc/bigtree/apis/google-plus.php",
		"BigTreeYouTubeAPI" => "inc/bigtree/apis/youtube.php",
		"BigTreeFlickrAPI" => "inc/bigtree/apis/flickr.php",
		"BigTreeSalesforceAPI" => "inc/bigtree/apis/salesforce.php",
		"BigTreeDisqusAPI" => "inc/bigtree/apis/disqus.php",
		"BigTreeFacebookAPI" => "inc/bigtree/apis/facebook.php",
		"S3" => "inc/lib/amazon-s3.php",
		"CF_Authentication" => "inc/lib/rackspace/cloud.php",
		"CSSMin" => "inc/lib/CSSMin.php",
		"PHPMailer" => "inc/lib/phpmailer.php",
		"JShrink" => "inc/lib/JShrink.php",
		"PasswordHash" => "inc/lib/PasswordHash.php",
		"TextStatistics" => "inc/lib/text-statistics.php",
		"lessc" => "inc/lib/less-compiler.php"
	);
	
	// Auto load classes	
	spl_autoload_register("BigTree::classAutoLoader");

	// Setup admin class if it's custom, but don't instantiate the $admin var.
	if (defined("BIGTREE_CUSTOM_ADMIN_CLASS") && BIGTREE_CUSTOM_ADMIN_CLASS) {
		include_once SITE_ROOT.BIGTREE_CUSTOM_ADMIN_CLASS_PATH;
		eval("class BigTreeAdmin extends ".BIGTREE_CUSTOM_ADMIN_CLASS." {}");
	} else {
		class BigTreeAdmin extends BigTreeAdminBase {};
	}
	
	// If we're in the process of logging into sites
	if (defined("BIGTREE_SITE_KEY") && isset($_GET["bigtree_login_redirect_session_key"])) {
		BigTreeAdmin::loginSession($_GET["bigtree_login_redirect_session_key"]);
	}

	// Load everything in the custom extras folder.
	$d = opendir(SERVER_ROOT."custom/inc/required/");
	$custom_required_includes = array();
	while ($f = readdir($d)) {
		if (substr($f,0,1) != "." && !is_dir(SERVER_ROOT."custom/inc/required/$f")) {
			$custom_required_includes[] = SERVER_ROOT."custom/inc/required/$f";
		}
	}
	closedir($d);
	
	foreach ($custom_required_includes as $r) {
		include $r;
	}
	
	// Clean up
	unset($d,$r,$custom_required_includes);
