<?

//Load
error_reporting(E_ERROR | E_WARNING | E_PARSE);
$options = getopt("",Array("lang::"));
echo "loading...";
sleep(1);

//Classes and configs
require '/data/project/addbot/classes/botclasses.php';
require '/data/project/addbot/classes/database.php';
require '/data/project/addbot/config/database.php';
require '/data/project/addbot/config/wiki.php';

//Options
$config['General']['maxlag'] = "0";
$glang = $options['lang'];

//Initialise the wiki
$wiki = new wikipedia;
$wiki->url = "http://$glang.wikipedia.org/w/api.php";
global $wiki;
echo "\nLogging in...";
sleep(1);echo "..";
$wiki->login($config['user'],$config['password']);
unset($config['password']);

//Connect to the DB
echo "\nConnecting to database...";
$db = new Database( $config['dbhost'], $config['dbport'], $config['dbuser'], $config['dbpass'], $config['dbname'], false);
echo "done";

//Get a list from the DB and remove
$result = $db->select('iwlinked','*',"lang = '$glang'",array("LIMIT" => 100));
$list = Database::mysql2array($result);
echo "\nGot ".count($list)." articles from $glang pending";
echo "\nRemoving";
$r = "DELETE FROM iwlinked WHERE";
$t = 0;
foreach ($list as $item){
	$t++;
	$r .= " (`article`= '".$db->mysqlEscape($item['article'])."' AND `Lang`= '".$db->mysqlEscape($glang)."') OR";
	if($t >= 10)
	{
		$r = preg_replace('/ OR$/','',$r);//remove final OR
		$res = $db->doQuery($r);
		if( !$res  ){echo $db->errorStr();}
		$r = "DELETE FROM iwlinked WHERE";
		$t = 0;
		echo "R";
	}
	echo "r";
}
if($t >= 1)
{
	$r = preg_replace('/ OR$/','',$r);//remove final OR
	$res = $db->doQuery($r);
	if( !$res  ){echo $db->errorStr();}
	echo "R";
}
unset ($r);

//For each item in the list
foreach ($list as $item)
{
	$name = $item['article'];
	$summary = "";
	echo ".";
	
	//Get the page and wikidata links
	$text = $wiki->getpage($name,null,true);
	if (strlen($text) < 1){echo "-";continue;}
		$wdlinks = $wiki->wikidatasitelinks($name,$glang);
		$counter = 0;
		$id = "";
		
		//If we have returned a result
		if(count($wdlinks) == 1)
		{
			//Use the 1 returned entity
			foreach($wdlinks as $ent)
			{
				$id = $ent['id'];
				//If it has a list of site links
				if(isset($ent['sitelinks']))
				{
					//Check each site link on our page
					foreach ($ent['sitelinks'] as $l)
					{
						//Format the language in the may it is used in IW links
						$lang = str_replace("_","-",str_replace("wiki","",$l['site']));
						//Create the regex matching the link we are looking for
						$link = "\n ?\[\[".preg_quote($lang,'/')." ?: ?".str_replace(" ","( |_)",preg_quote($l['title'],'/'))." ?\]\] ?";
						//If we can find said link
						if(preg_match('/'.$link.'/',$text))
						{
							//Remove it and increment the counter
							$text = preg_replace('/'.$link.'/',"",$text);
							$counter++;
						}
					}
				}
			}
			//If we have removed more than one links
			if($counter > 0)
			{
			//Set language specific options
			switch($glang) {
				case "en":
					$summary = "[[User:Addbot|Bot:]] Migrating $counter interwiki links, now provided by [[Wikipedia:Wikidata|Wikidata]] on [[d:$id]]";
					$remove = "<!-- ?interwikis?( links?)? ?-->";
					break;
				case "de":$summary = "Bot: Verschiebe $counter Interwikilinks, die nun in [[d:|Wikidata]] unter [[d:$id]] bereitgestellt werden"; break;
				case "es":$summary = "Quitando $counter enlaces entre-wiki, proviendo ahora por [[d:|Wikidata]] en la p�gina [[d:$id]]."; break; 
				case "fr":
					$summary = "Suis retirer $counter liens entre les wikis, actuellement fournis par [[d:|Wikidata]] sur la page [[d:$id]]";
					$remove = "<!-- ?((liens? )?inter(wiki|langue)s?|other languages) ?-->";
					break;
				case "hu":$summary = "Bot: $counter interwiki link migr�lva a [[d:|Wikidata]] [[d:$id]] adat�ba"; break; 
				case "it":$summary = "migrazione di $counter interwiki links su [[d:Wikidata:Pagina_principale|Wikidata]] - [[d:$id]]"; break;
				case "he":$summary = "???: ????? ?????? ??????? ?[[????????:??????????|??????????]] - [[d:$id]]"; break;
				case "nl":$summary = "Verplaatsing van $counter interwikilinks die op [[d:|Wikidata]] beschikbaar zijn op [[d:$id]]"; break;
				case "no":$summary = "bot: Fjerner $counter interwikilenker som n� hentes fra [[d:$id]] p� [[d:|Wikidata]]"; break;
				case "sl":$summary = "Bot: Migracija $counter interwikija/-ev, od zdaj gostuje(-jo) na [[Wikipedija:Wikipodatki|Wikipodatkih]], na [[d:$id]]"; break; 
				case "ca":$summary = "Bot: Traient $counter enlla�os interwiki, ara proporcionats per [[Wikipedia:Wikidata|Wikidata]] a [[d:$id]]"; break; 
				case "is":$summary = "Bot: Flyt $counter tungum�latengla, sem eru n�na s�ttir fr� [[Wikipedia:Wikidata|Wikidata]] � [[d:$id]]"; break;
				case "ar":$summary = "[[??????:Addbot|???:]] ????? $counter ???? ????????, ?????? ???? ?? [[d:|???? ??????]] ??? [[d:$id]]"; break;
				case "sv":$summary = "Bot �verf�r $counter interwikil�nk(ar), som nu �terfinns p� sidan [[d:$id]] p� [[Wikipedia:Wikidata|Wikidata]]"; break;
				case "tet":$summary = "Bot: Hasai $counter ligasaun interwiki, ne'eb� agora mai husi [[d:$id]] iha [[Wikipedia:Wikidata|Wikidata]] "; break; 
				default:$summary = "Bot: Migrating $counter interwiki links, now provided by [[d:|Wikidata]] on [[d:$id]]";
				}
			//Remove any comments we have been asked to
			if(isset($remove))
			{
				$text = preg_replace("/".$remove."/i","",$text);
				unset($remove);
			}
			
			//Remove any new lines left at the end of the article
			$text = preg_replace('/(\n\n)\n+$/',"$1", $text);
			$text = preg_replace('/^(\n|\r){0,5}$/',"", $text );
				
			}
		}			
	
	//If we are set to edit
	if($summary != "")
	{
		//edit
		echo "E";
		$wiki->edit($name,$text,$summary,true,true,null,true,"0");
		sleep(1);
	}
	
	//Match any remaining links
	preg_match_all('/\n ?(\[\[(nostalgia|ten|test|aa|ab|ace|af|ak|als|am|an|ang|ar|arc|arz|as|ast|av|ay|az|ba|bar|bat-smg|bcl|be|be-x-old|bg|bh|bi|bjn|bm|bn|bo|bpy|br|bs|bug|bxr|ca|cbk-zam|cdo|ce|ceb|ch|cho|chr|chy|ckb|co|cr|crh|cs|csb|cu|cv|cy|da|de|diq|dsb|dv|dz|ee|el|eml|en|eo|es|et|eu|ext|fa|ff|fi|fiu-vro|fj|fo|fr|frp|frr|fur|fy|ga|gag|gan|gd|gl|glk|gn|got|gu|gv|ha|hak|haw|he|hi|hif|ho|hr|hsb|ht|hu|hy|hz|ia|id|ie|ig|ii|ik|ilo|io|is|it|iu|ja|jbo|jv|ka|kaa|kab|kbd|kg|ki|kj|kk|kl|km|kn|ko|koi|kr|krc|ks|ksh|ku|kv|kw|ky|la|lad|lb|lbe|lez|lg|li|lij|lmo|ln|lo|lt|ltg|lv|map-bms|mdf|mg|mh|mhr|mi|min|mk|ml|mn|mo|mr|mrj|ms|mt|mus|mwl|my|myv|mzn|na|nah|nap|nds|nds-nl|ne|new|ng|nl|nn|no|nov|nrm|nso|nv|ny|oc|om|or|os|pa|pag|pam|pap|pcd|pdc|pfl|pi|pih|pl|pms|pnb|pnt|ps|pt|qu|rm|rmy|rn|ro|roa-rup|roa-tara|ru|rue|rw|sa|sah|sc|scn|sco|sd|se|sg|sh|si|simple|sk|sl|sm|sn|so|sq|sr|srn|ss|st|stq|su|sv|sw|szl|ta|te|tet|tg|th|ti|tk|tl|tn|to|tpi|tr|ts|tt|tum|tw|ty|udm|ug|uk|ur|ve|vec|vep|vi|vls|vo|wa|war|wo|wuu|xal|xh|xmf|yi|yo|za|zea|zh|zh-classical|zh-min-nan|zh-yue|zu) ?: ?([^\]]+) ?\]\])/i',$text,$left);
	
	//if there are still links left over
	if(count($left[1]) > 0)
	{
		//Insert back into the DB with the number of linkes leftover
		$res = $db->doQuery("INSERT INTO iwlinked (lang, article, links) VALUES ('$glang', '$name', ".count($left[1]).")");
		if( !$res  ){echo $db->errorStr();}
		
	}
}

?>
