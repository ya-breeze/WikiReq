<?php
/**

CREATE TABLE IF NOT EXISTS `breeze_Reqs` (
  `name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `status` enum('Unknown','Reserved','New','Agreed','Implemented','Tested','Deleted') collate utf8_unicode_ci NOT NULL default 'New',
  `title` text collate utf8_unicode_ci NOT NULL,
  `body` text collate utf8_unicode_ci NOT NULL,
  `test` text collate utf8_unicode_ci NOT NULL,
  `comment` text collate utf8_unicode_ci NOT NULL,
  `upward` text collate utf8_unicode_ci NOT NULL,
  `version` int(11) NOT NULL default '1',
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the extension file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install the ReqHook extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/ReqHook/ReqHook.php" );
EOT;
        exit(1);
}


include_once dirname(__FILE__) . '/requirement.php';
include_once dirname(__FILE__) . '/dbreqconnect.php';
include_once dirname(__FILE__) . '/reqlist.php';


$wgExtensionCredits['other'][] = array(
    'path'          => __FILE__,
    'name'          => 'ReqHook',
    'author'        => 'Breeze',
    'url'           => 'http://www.mediawiki.org/wiki/Extension:ReqHook',
    'version'       => '0.0.1',
    'description'   => 'Users without the editall right can only edit ' .
                'pages they\'ve created',
    'descriptionmsg' => 'ReqHook-desc',
);

//$wgExtensionMessagesFiles['ReqHook'] = dirname(__FILE__) . '/ReqHook.i18n.php';

$wgHooks['ArticleSave'][] = 'ReqHook';

//$wgReqHookExcludedNamespaces = array();
//$wgReqHookActions = array('edit');

function findReq( $text )
{
	// Old stuff
	$regex = '/<req2>(.*)<\/req2>/s';
	if( preg_match_all( $regex, $text, $matches, PREG_PATTERN_ORDER ) )
		return $matches[0][0];

	// New variant
	$regex = '/<req>(.*)<\/req>/s';
	if( preg_match_all( $regex, $text, $matches, PREG_PATTERN_ORDER ) )
		return $matches[0][0];

	return '';
}

function ReqHook($article, $user, $newtext)
{
	$reqtext = findReq($newtext);
	if( $reqtext=='' )
	{
		//There are no any requirements
		return true;
	}

	$reqxml = new SimpleXMLElement($reqtext);
	$req = Requirement::reqFromXML($article->getTitle()->getText(), $reqxml);

	$db = new DBReqConnect();
	if( !$db->saveReq($req) )
	{
		return 'Problems with DB';
	}

	return true;
}

