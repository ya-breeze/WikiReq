<?php
/*
 * Extension to allow Graphviz to work inside MediaWiki.
 * This Version is based on CoffMan's nice Graphviz Extension.
 *
 * Licence: http://www.gnu.org/copyleft/fdl.html
 *
 * Configuration
 *
 *  These settings can be overwritten in LocalSettings.php.
 *  Configuration must be done AFTER including this extension using
 *  require("extensions/Graphviz.php");
 *
 *  $wgGraphVizSettings->dotCommand
 *    Describes where your actual dot executable remains.
 *
 *    Windows Default: C:/Programme/ATT/Graphviz/bin/dot.exe
 *    Other Platform : /usr/local/bin/dot
 *
 *  $wgGraphVizSettings->named
 *    Describes the way graph-files are named.
 *
 *    named: name of your graph and its type determine its filename
 *    md5  : name of your graph is based on a md5 hash of its source.
 *    sha1 : name of your graph is based on a SHA1 hash of its source.
 *
 *    Default : named
 *
 *  $wgGraphVizSettings->install
 *    Gets you an errormessage if something fails, but maybe ruins your
 *    wiki's look. This message is in English, always.
 *
 *    Default : false
 *
 * Improvements
 * - Selects defaults for Windows or Unix-like automatically.
 * - should runs out of the box
 * - Creates PNG + MAP File
 * - additional storage modes (see discussion below)
 *   - Meaningful filename
 *   - Hash based filename
 *   - Configurable (name/md5/sha1)
 *
 * Storage Modes:
 * MD5:
 * + don't worry about graphnames
 * + pretty fast hash
 * - permanent cleanup necesary (manually or scripted)
 * - md5 is buggy - possibility that 2 graphs have the same hash but
 *   are not the same
 * SHA1:
 * + don't worry about graphnames
 * + no hash-bug as md5
 * - permanent cleanup necessary (manually or scripted)
 * - not so fast as md5
 * Named:
 * + Graphs have a name, now it's used
 * + no permanent cleanup necessary.
 * - Naming Conflicts
 *   a) if you have multiple graphs of the same name in the same
 *      article, you will only get 1 picture - independently if they're
 *		the same or not.
 *   b) possible naming conflicts in obscure cases (that should not happen)
 *      Read code for possibilities / exploits
 * 
 */

class GraphVizSettings {
	public $dotCommand, $named, $install;
};

$wgGraphVizSettings = new GraphVizSettings();

// Config
// ------
if ( ! (stristr (PHP_OS, 'WIN' ) === FALSE) ) {
	$wgGraphVizSettings->dotCommand = 'C:/Programme/ATT/Graphviz/bin/dot.exe';
} else {
	$wgGraphVizSettings->dotCommand = '/usr/bin/dot';
}
$wgGraphVizSettings->named = 'md5';
$wgGraphVizSettings->install = false;

// Media Wiki Plugin Stuff
// -----------------------
$wgExtensionFunctions[] = "wfGraphVizExtension";

$wgExtensionCredits['parserhook'][] = array(
  'name'=>'Graphviz',
  'author'=>'CoffMan <http://wickle.com>, MasterOfDesaster <mailto://arno.venner@gmail.com>',
  'url'=> 'http://www.mediawiki.org/wiki/Extension:GraphViz',
  'description'=>'Graphviz (http://www.graphviz.org) is a program/language that allows the creation of numerous types of graphs.  This extension allows the embedding of graphviz markup in MediaWiki pages and generates inline images to display.',
  'version'=>'0.4'
);

function wfGraphVizExtension() {
	global $wgParser;
	$wgParser->setHook( "graphviz", "renderGraphviz" );
}

function renderGraphviz( $timelinesrc, $argv )	// Raw Script data
{
	global
		$wgUploadDirectory,	// Storage of the final png & map
		$wgUploadPath,			// HTML Reference
		$wgGraphVizSettings;	// Plugin Config

	// Prepare Directories
	$dest = $wgUploadDirectory."/graphviz/";
	if ( ! is_dir( $dest ) ) { mkdir( $dest, 0777 ); }

	$storagename = urldecode($_GET['title']).'---';
	$storagename = str_replace("&",'_amp_',$storagename);
	$storagename = str_replace("#",'_shrp_',$storagename);
	$storagename = str_replace("/",'_sd_',$storagename);
	$storagename = str_replace("\\",'_sd_',$storagename);
	
	$wgGraphVizSettings->named = strtolower($wgGraphVizSettings->named);

	if($wgGraphVizSettings->named == 'md5') {
		$storagename .= md5($timelinesrc);
	} else if ($wgGraphVizSettings->named == 'sha1') {
		$storagename .= sha1($timelinesrc);
	} else {
		$storagename .= trim(
			str_replace("\n",'',
				str_replace("\\",'/',
					substr($timelinesrc, 0, strpos($timelinesrc,'{') )
				)
			)
		);
	}

	$src = $dest . $storagename;

	$imgn = $dest . $storagename . '.png';
	$mapn = $dest . $storagename . '.map';

	if ( $wgGraphVizSettings->named == 'named' || ! ( file_exists( $src.".png" ) || file_exists( $src.".err" ) ) )
	{
		$handle = fopen($src, "w");
		fwrite($handle, $timelinesrc);
		fclose($handle);

		$cmdline    = wfEscapeShellArg($wgGraphVizSettings->dotCommand).
			' -Tpng   -o '.wfEscapeShellArg($imgn).' '.wfEscapeShellArg($src);
		$cmdlinemap = wfEscapeShellArg($wgGraphVizSettings->dotCommand).
			' -Tcmapx -o '.wfEscapeShellArg($mapn).' '.wfEscapeShellArg($src);

		$ret = shell_exec($cmdline);
		if ($wgGraphVizSettings->install && $ret == "" ) {
			echo '<div id="toc"><tt>Timeline error: Executable not found.'.
				"\n".'Command line was: '.$cmdline.'</tt></div>';
			exit;
		}

		$ret = shell_exec($cmdlinemap);
		if ($wgGraphVizSettings->install && $ret == "" ) {
			echo '<div id="toc"><tt>Timeline error: Executable not found.'.
				"\n".'Command line was: '.$cmdlinemap.'</tt></div>';
			exit;
		}

		unlink($src);
	}
	
	@$err=file_get_contents( $src.".err" ); 

	if ( $err != "" ) {
		$txt = '<div id="toc"><tt>'.$err.'</tt></div>';
	} else {
		@$map = file_get_contents( $mapn );
		$map  = preg_replace('#<ma(.*)>#',' ',$map);
		$map  = str_replace('</map>','',$map);
		$width = "";
		if( !empty($argv['width']) )
		{
		    $width = "width=" . $argv['width'];
		}
		
		$src = $wgUploadPath.'/graphviz/'.$storagename.'.png"';

		$txt  = '<map name="'.$storagename.'">'.$map.'</map>'.
		        '<img '.$width.' src="'.$src.
					' usemap="#'.$storagename.'" /><br><a href="'.$src.'">see image</a>';
	}
	return $txt;
}
?>