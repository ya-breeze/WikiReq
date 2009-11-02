<?php
/**
 */

// Make sure we are being called properly
if( !defined( 'MEDIAWIKI' ) ) {
    echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
    die( -1 );
}

include_once dirname(__FILE__) . '/requirement.php';
include_once dirname(__FILE__) . '/dbreqconnect.php';
include_once dirname(__FILE__) . '/reqlist.php';


//Avoid unstubbing $wgParser too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) )
	$wgHooks['ParserFirstCallInit'][] = 'wfMetaReqExtension';
else
	$wgExtensionFunctions[] = 'wfMetaReqExtension';

$wgExtensionCredits['parserhook'][] = array(
        'name' => 'WikiReq',
        'version' => '0.0.1',
        'author' => 'Breeze',
        'url' => '',
        'description' => 'Renders a req model'
);

$image_format = "png";
$tmp_filename = md5(rand());
//$mpost_path   = "mpost.exe";
//$density      = "100%";
$redraw       = false;

function wfMetaReqExtension()
{
    global $wgParser;
    # register the extension with the WikiText parser
    # the first parameter is the name of the new tag.
    # In this case it defines the tag <uml> ... </uml>
    # the second parameter is the callback function for
    # processing the text between the tags
    $wgParser->setHook( 'reqview', 'renderReq' );
    $wgParser->setHook( 'req', 'renderRealReq' );
    $wgParser->setHook( 'req2', 'renderRealReq' ); // Old stuff
    $wgParser->setHook( 'reqlist', 'renderReqList' );
    $wgParser->setHook( 'reqgraph', 'renderGraph' );
    return true;
}

/////////////////////////////////////
# The callback function for converting the input text to HTML output
function renderReq( $input, $argv, $parser )
{
	$parser->disableCache();
	$db = new DBReqConnect();
	$req = $db->readByName($input);
	if( !$req->isValid() )
	{
		$req->name = $input;
		$req->body = "Not yet specified";
	}

	// Если выводим требование на его же странице, то выведем дополнительно ссылку на страницу префикса
	$showPrefix = false;
	if( $parser->getTitle()->getText()==$input )
		$showPrefix = true;

	return $parser->recursiveTagParse( $req->showReq($showPrefix) );
}

function renderRealReq( $input, $argv, $parser )
{
	global $wgRequest;

	$parser->disableCache();

    $action = $wgRequest->getVal('action');
    // Это preview режим?
    if( $action=="submit" )
    {
		$reqxml = new SimpleXMLElement('<req>'.$input.'</req>');
		$req = Requirement::reqFromXML($parser->getTitle()->getText(), $reqxml);
		$req->version = '<Preview mode>';

		return $parser->recursiveTagParse( $req->showReq() );
    }

    // Это обычный просмотр - надо вытаскивать из БД
    return renderReq( $parser->getTitle()->getText(), $argv, $parser );
}

# The callback function for return list of reqs
function renderReqList( $input, $argv, $parser )
{
	$parser->disableCache();

	$db = new DBReqConnect();
	$reqlist = $db->readAllReqs($input);
	return $parser->recursiveTagParse( $reqlist->reqList() );
}

function renderGraph( $input, $argv, $parser )
{
	$parser->disableCache();
	$db = new DBReqConnect();
	$reqlist = $db->readAllReqs($input);

	$layout = array_key_exists('layout', $argv)?$argv['layout']:"dot";
	$width = array_key_exists('width', $argv)?$argv['width']:"";
	
	return $parser->recursiveTagParse( $reqlist->createGraph($layout, $width) );
}
