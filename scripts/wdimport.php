<?php
/**
 * This file is the main script used for moving information
 * from projects to wikidata
 * @author Addshore
 **/

//Initiate the script
require_once( dirname(__FILE__).'/../init.php' );

//Create a site
$wm = new Family('wikimedia',new UserLogin('addbot','password'),'meta.wikimedia.org');
$wikidata = $wm->getSiteFromSiteid('wikidatawiki');
$wikidata->doLogin();

//$dbConfig = parse_ini_file('~/replica.my.cnf');
//$db = new Mysql('tools-db','3306',$dbConfig['user'],$dbConfig['password'],$dbConfig['user'].'wikidata_p');
//unset($dbConfig);
//$dbQuery = $db->select('iwlink','*',null,array('ORDER BY' => 'updated ASC', 'LIMIT' => '100'));
//$rows = $db->mysql2array($dbQuery);
$rows = array(
	//array('lang' => 'en','site' => 'wiki','namespace' => '0','title' => 'À Beira do Caminho'),
	array('lang' => 'en','site' => 'wiki','namespace' => '0','title' => 'Mechanical system'),
	//array('lang' => 'en','site' => 'wiki','namespace' => '0','title' => 'Pear'),
	//array('lang' => 'en','site' => 'wiki','namespace' => '0','title' => 'Banana'),
);
foreach($rows as $row){
	// Load our site
	$baseSite = $wm->getSiteFromSiteid($row['lang'].$row['site']);
	$baseSite->doLogin();

	// Get an array of all pages involved
	/* @var $usedPages Page[] */
	$usedPages = array();
	$usedPages[] = $baseSite->getPage($baseSite->getNamespaceFromId($row['namespace']).$row['title']);
	$usedPages = array_merge( $usedPages,$usedPages[0]->getPagesFromInterwikiLinks() );
	$usedPages = array_merge( $usedPages,$usedPages[0]->getPagesFromInterprojectLinks() );
	$usedPages = array_merge( $usedPages,$usedPages[0]->getPagesFromInterprojectTemplates() );
	//@todo remove duplicate pages

	// Try to find an entity to work on
	/* @var $page Page */
	foreach( $usedPages as $page ){
		if( $page->getEntity() instanceof WikibaseEntity ){
			$baseEntity = $page->entity;
			echo "Found entity ".$baseEntity->id."\n";
			break;
		}
	}

	// Are we still missing an entity?
	if( !isset($baseEntity) ){
		echo "Failed to find an entiy to work from\n";
		//We could create an entity to work on here... Instead we will continue;
		//We could also try 'linking titles' here?
		continue;
	}

	// Add everything to the entity
	foreach( $usedPages as $page ){
		$baseEntity->addSitelink($page->site->wikiid,$page->title);
		if($page->site->code == 'wiki'){
			$baseEntity->addLabel($page->site->lang,$page->title);
		}
	}

	$baseEntity->save();
	$baseEntity->load();

	$usedPages[0]->load();
	if ($usedPages[0]->removeEntityLinksFromText() == true){
		$usedPages[0]->save();
	}

}