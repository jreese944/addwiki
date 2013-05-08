<?
require 'parser.php';

class Page {

	// construct the page (you probably want to call load after this)
	public function __construct($page,$wiki) {
		$this->page = preg_replace("/_/"," ",$page);
		$this->wiki = $wiki;
		$this->parseNamespace();
		$this->loadText();//load the wikitext from page
		$this->hadMI; //set default until checked
		$this->runMI = 0; //the number of time MI has been run
		$this->skip = false;
	}	
	
	// variables
	private $page;// page name (e.g. "User:Addshore")
	private $text;// page text
	private $checktext; //this is a temporary copy of the text that can be checked against with comments and nowiki content removed.
	private $namespace;// page namespace (No colon)
	private $wiki;// instance of wiki we are using
	private $parser;// instance of the parser.php class
	private $parsed;
	private $sigchange;//has a significant change happened to the page (enough to edit)?
	private $summary;//summary if edited
	private $hadMI; //did the page have an MI tag when we loaded it
	private $runMI; //the number of times MI has been run
	public $skip; //have we found something that means we should skip?
	
	// getters and setters
	public function getName() { return $this->page; }
	public function getText() { return $this->text;}
	public function getcheckText() { return $this->checktext;}
	public function setText($text) { $this->text = $text;}
	public function getNamespace() { if(!isset($this->namespace)){$this->parseNamespace();} return $this->namespace;}
	public function hasSigchange() { return $this->sigchange; }
	
	// public functions
	// create instance of parser class and parse
	public function parse() { $this->parser = new parser($this->getName(),$this->getText()); $this->parsed = $this->parser->parse(); return $this->parsed;} 
	
	// private functions
	private function loadText() { 
		$text = $this->wiki->getpage($this->getName());
		$this->text = $text;//our actual text
		$this->checktext = preg_replace("/(<nowiki>.*?<\/nowiki>|<!--.*?-->)/is","",$text); //text with nonwiki but wiki elements removed
	} // load the text from the wiki
	private function postPage() { $this->wiki->edit($this->getName(),$this->getText(),$this->getSummary(),true);} 
	private function parseNamespace()
	{
		$result = preg_match("/^((Talk|User|Wikipedia|File|Image|Mediawiki|Template|Help|Category|Portal|Book|Education( |_)program|TimedText)(( |_)talk)?):?/i",$this->page,$matches);
		if($result == 0){ $this->namespace = "";}// default to article namespace
		else{$this->namespace = $matches[1];}
		if($this->namespace == "Image"){ $this->namespace = "File";}// default Image namespace to file
	}
	
	public function addSummary($sum,$sig)
	{
		//split the summary
		$split = explode(" ",$sum,2);
		//if we CANNOT find the first bit (Adding, Removing, Dating) already in the summary
		if(!preg_match('/'.$split[0].'/i',$this->summary))
		{
			//just add the summary
			$this->summary = $this->summary.$sum." ";
		}
		//else we first bit is already there so we want to insert our second bit
		else
		{
			//replace the first bit in the summary with the first bit and the second bit (a sort of insert)
			$this->summary = preg_replace('/'.$split[0].'/i',$sum,$this->summary);
		}
		//if we want this to be a sig change say so
		if($sig)
		{
			$this->sigchange = true;//if we have a summary it muse be a sig change
		}
	}
	
	//returns the edit summary
	public function getSummary(){
	return "[[User:Addbot|Bot:]] ".$this->summary."([[User talk:Addbot|Report Errors]])";
	}
	
//	                  //
// Main bot functions //
//                    //

	//make matching easier
	public function matches($regex){return preg_match($regex,$this->getText());}
	
	//return a restricted estimate of words in an article
	public function wordcount()
	{
		//get a temp copy of the text to work with
		$text = $this->getText();
		//remove everything in sections that we dont want
		preg_match_all('/(={2,7})([^=]+)\1/',$text,$sections);
		foreach($sections[0] as $key => $header)
		{
			//if we match a section we dont want then remove it
			if(preg_match('/(External links?|References?|Notes?|See also|Bibliography)/i',$sections[2][$key]))
			{
				if(isset($sections[0][$key+1]))
				{
					$text = preg_replace('/'.preg_quote($header).'.*?'.$sections[0][$key+1].'/is',"",$text);
				}
				else
				{
					$parts = explode($header,$text);
					$text = $parts[0];
				}
			}
			
		}
		//remove templates, cats, interwikis and extlinks and refs
		$text = preg_replace("/(\{\{[^\}]*?\}\}|={1,6}[^=]*?={1,6}|\n\*{1,2} ?|\[https?[^\]]*?\]|\[\[(Category|Image|File|[a-z]{2,6}):[^\]]*?\]\]|\<references ?\/\>|<ref>.*?<\/ref>|<!--.*?-->|\{\|.*?\|-.*?\|.*?\|})/is","",$text);
		//fill all links in with a single word
		$text = preg_replace("/\[\[([^]:]*)\]\]/","WORD",$text);
		$text = trim($text);
		//return
		return str_word_count($text);
	}
	
		//return a restricted estimate of words in an article
	public function wordcountlead()
	{
		//get a temp copy of the text to work with
		$text = $this->getText();
		$split = preg_split('/(={2,7})([^=]+)\1/',$text);
		$text = $split[0];

		//remove templates, cats, interwikis and extlinks and refs
		$text = preg_replace("/(\{\{[^\}]*?\}\}|={1,6}[^=]*?={1,6}|\n\*{1,2} ?|\[https?[^\]]*?\]|\[\[(Category|Image|File|[a-z]{2,6}):[^\]]*?\]\]|\<references ?\/\>|<ref>.*?<\/ref>|<!--.*?-->|\{\|.*?\|-.*?\|.*?\|})/is","",$text);
		//fill all links in with a single word
		$text = preg_replace("/\[\[([^]:]*)\]\]/","WORD",$text);
		$text = trim($text);
		//return
		return str_word_count($text);
	}
	
	//returns if the page is a redirect or not
	public function isRedirect()
	{
		if($this->matches('/(# ?REDIRECT ?\[\[.*?\]\]|\{\{Soft ?(redir(ect)?|link)\|)/i'))
		{
			return true;
		}
	}
	
	// returns false if the largest section size is smaller than 5000 chars (excluding certain sections)
	public function needsSections()
	{
		global $config;
		//init some vars
		$largestsection = 0;
		$sectioncount = 0;
		//find the sections
		preg_match_all('/\n==(=)? ?.* ?===?/i',$text, $sections, PREG_PATTERN_ORDER);
		$split = preg_split('/\n==(=)? ?.* ?===?/i',$text);
			
		//for each section found
		foreach($split as $id => $section){
			//if we are the lead
			if($id == 0){
				$largestsection = strlen($section);
				$sectioncount++;
			}
			//else we must have a name
			else{
				//make sure we ignore the sections below
				if (preg_match('/See ?also|(external( links)?|references|notes|bibliography|further( reading)?)/i',$sections[0][$id-1]) == 0){
					//if the length of this section is longer than our current largest
					if(strlen($section) > $largestsection){
						//then set it
						$largestsection = strlen($section);
					}
				//increment the section count
				$sectioncount++;
				}
			}
		}
		//if the page has 4+ sections and a largest section of 5000- then return false
		if($sectioncount >= $config['Sections']['remsections'] && $largestsection <= $config['Sections']['remlargest']){//was 2750 for AVG
			return false;
		}elseif($sectioncount <= $config['Sections']['addlargest']/*10000*/ && $largestsection >= $config['Sections']['addsections']/*2*/){//was 2750 for AVG
			return true;
		}elseif ($sectioncount = 1 && $this->wordcount() >= $config['Sections']['addleadonly']/*1000*/){
			return true;
		}
	}
	
	//returns the number of references that we have
	public function isReferenced()
	{
		$temp = $this->getText();
		//remove all ref tags in comments
		$temp = preg_replace('/<!--[^(-->)]*?(<\/?ref[^\/]*?>.*?<\/ref>).*?-->/is',"",$temp);
		//if we match a ref tag after the ones in comments have been ignored
		if(preg_match_all('/<\/?ref[^\/]*?>/is',$temp,$matches))
		{
			return count($matches);
		}
		return null;
	}
	
	//checks if a page is in a BLP category 
	public function isBLP()
	{
		$cats = $this->wiki->categories($this->getName());
		foreach ($cats as $cat)
		{
			//Regex to match cats that are definatly NOT BLP
			if(preg_match('/^Category:(Dead people$|[0-9]{0,4}(s BC)? deaths$|(place|year|date)of death (missing|unknown))/i',$cat))
			{
					return false;
			}
			//check if we were born over 115 years ago (return false if we are) per [[Wikipedia:Blp#Recently_dead_or_probably_dead]]
			if(preg_match("/Category:([0-9]{0,4}) births/i",$cat,$matches))
			{
				if($matches[1] < date("Y")-$config['BLP']['presumedead'])
				{
					return false;
				}
			}
		}
		foreach ($cats as $cat)
		{
			//If we are still running see if there is a BLP cat
			if (preg_match('/^Category:(Living people$|[0-9]{0,4} births$)/i',$cat))
			{
				return true;
			}
		}
		//Otherwise we cant guess
		return null;
	}
	
	//returns true if page is in a given category
	public function inCategory($category)
	{
		$cats = $this->wiki->categories($this->getName());
		foreach ($cats as $cat)
		{
			if ($cat == $category)
			{
				return true;
			}
		}
		return false;
	}
	
	public function interwikilinks()
	{
		//Make sure wikidta is used
		if(!preg_match('/\{\{noexternallanglinks\}\}/',$this->getText()))
		{
			//get the other links
			$r = $this->wiki->wikidatasitelinks($this->getName());
			$counter = 0;
			$id = "";
		
			//if there is only 1 entity (i.e. the wikidata stuff isnt broken somewhere)
			if(count($r) == 1)
			{
				//foreach entitiy found
				foreach($r as $ent)
				{
					$id = $ent['id'];
					//Check if we have site links
					if(isset($ent['sitelinks']))
					{
						//for each sitelink in the entity
						foreach ($ent['sitelinks'] as $l)
						{
							$lang = str_replace("_","-",str_replace("wiki","",$l['site']));
							//echo $lang.":".$l['title']."\n";
							$link = "\n[[".$lang.":".$l['title']."]]";
							if(preg_match('/'.preg_quote($link,'/').'/',$this->getText()))
							{
								//remove the link
								$this->setText(str_replace($link,"",$this->getText()));
								//incrememnt the counter
								$counter++;
							}
						}
					}
				}
				if($counter > 1)
				{
					$this->addSummary("Migrating $counter interwiki links, now provided by [[Wikipedia:Wikidata|Wikidata]] on [[d:$id]]",true);
				}
				elseif($counter > 0)
				{
					$this->addSummary("Migrating $counter interwiki links, now provided by [[Wikipedia:Wikidata|Wikidata]] on [[d:$id]]",true);
				}
			
				//Now we want to log any links left over
				preg_match_all('/\[\[(nostalgia|ten|test|aa|ab|ace|af|ak|als|am|an|ang|ar|arc|arz|as|ast|av|ay|az|ba|bar|bat-smg|bcl|be|be-x-old|bg|bh|bi|bjn|bm|bn|bo|bpy|br|bs|bug|bxr|ca|cbk-zam|cdo|ce|ceb|ch|cho|chr|chy|ckb|co|cr|crh|cs|csb|cu|cv|cy|da|de|diq|dsb|dv|dz|ee|el|eml|en|eo|es|et|eu|ext|fa|ff|fi|fiu-vro|fj|fo|fr|frp|frr|fur|fy|ga|gag|gan|gd|gl|glk|gn|got|gu|gv|ha|hak|haw|he|hi|hif|ho|hr|hsb|ht|hu|hy|hz|ia|id|ie|ig|ii|ik|ilo|io|is|it|iu|ja|jbo|jv|ka|kaa|kab|kbd|kg|ki|kj|kk|kl|km|kn|ko|koi|kr|krc|ks|ksh|ku|kv|kw|ky|la|lad|lb|lbe|lez|lg|li|lij|lmo|ln|lo|lt|ltg|lv|map-bms|mdf|mg|mh|mhr|mi|min|mk|ml|mn|mo|mr|mrj|ms|mt|mus|mwl|my|myv|mzn|na|nah|nap|nds|nds-nl|ne|new|ng|nl|nn|no|nov|nrm|nso|nv|ny|oc|om|or|os|pa|pag|pam|pap|pcd|pdc|pfl|pi|pih|pl|pms|pnb|pnt|ps|pt|qu|rm|rmy|rn|ro|roa-rup|roa-tara|ru|rue|rw|sa|sah|sc|scn|sco|sd|se|sg|sh|si|simple|sk|sl|sm|sn|so|sq|sr|srn|ss|st|stq|su|sv|sw|szl|ta|te|tet|tg|th|ti|tk|tl|tn|to|tpi|tr|ts|tt|tum|tw|ty|udm|ug|uk|ur|ve|vec|vep|vi|vls|vo|wa|war|wo|wuu|xal|xh|xmf|yi|yo|za|zea|zh|zh-classical|zh-min-nan|zh-yue|zu):([^\]]+)\]\]/i',$this->getText(),$matches);
				//if there are still links left over
				if(count($matches) > 0)
				{
					if(count($matches) > 4){$needlog = true;}
					$tolog = "";
					$needlog = false;
					if($id == ""){$tolog .= "=== ".$this->getName()." [https://www.wikidata.org/wiki/Special:CreateItem UNSET] ===\n";$needlog = true;}
					else{$tolog .= "=== [[d:$id]] ===\n";}
					$tolog .= "* en is  [[".$this->getName()."]]\n";
					foreach($matches[0] as $key => $match)
					{
							$tolog .= "** ".$matches[1][$key]." ->  ".$matches[2][$key]." [http://www.wikidata.org/w/index.php?title=Special%3AItemByTitle&site=".urlencode($matches[1][$key])."wiki&page=".urlencode($matches[2][$key])." check]\n";
					}
					//Log
					if($needlog)
					{
						$this->logevent('wikidata',$tolog);
					}
				}
			}
		}
	}

//TODO ADD THIS TO WIKI CLASS	
//Create log function
//This can be used to post output to User:Addbot/log/<PARAM>
//Data will be added to the top of the page in a bulleted list
private function logevent ($type,$what)
{
	global $config,$wiki;
	//if we are set to log this type
	if(isset($config['Log'][$type]))
	{
		$text = $wiki->getpage('User:'.$config['user'].'/log/'.$config['Log'][$type],null,true);// get previous page
		if(strlen($text) > 2)
			{
			$text = $text."\n".$what;// add our stuff
			$split = explode("\n",$what);
			if(strlen($text) < $config['Log']['wikidatamax'])
			{
				$wiki->edit('User:'.$config['user'].'/log/'.$config['Log'][$type],$text,"Adding ".$split[0],true,true,null,true,$config['General']['maxlag']);// save the page
			}
		}
	}
}
	
	// returns true if there are 0 links to the page from the mainspace
	// returns false if there is at least 1 link that fits the criteria
	public function isOrphan()
	{
		global $config;
		//get the links to the page
		$links = $this->wiki->whatlinkshere($this->getName(),"&blnamespace=0");
		//if there are no links (i.e. is orphan)
		if(count($links) == $config['Orphans']['maxlinks']/*0*/) {
			//check the tag is allowed on such a page
			if(preg_match("/((List|Index) of|\(disambig(uation)?\))/i",$this->getName()) == FALSE)
			{
				if (preg_match('/(may refer to ?\:|# ?REDIRECT|\{\{Soft ?(Redir(ect)?|link)|\{\{.*((dis(amb?(ig(uation( page)?)?)?)?)(\-cleanup)?|d(big|ab|mbox)|given( |_)name|sia|set index( articles)?)(\|([0-9a-zA-Z _]*?)( ?= ?[0-9a-zA-Z _]*?)){0,6}\}\})/i',$this->getText()) == FALSE)
				{
					if(!$this->inCategory("Category:All set index articles"))
					{
						return true;
					}
				}
			}
		}
		//if there are links then check them
		foreach($links as $link){
			//regex names of links to ignore
			if(!preg_match("/((List|Index) of|\(disambig(uation)?\))/i",$link))
			{
				//regex of contents of pages to ignore
				if (!preg_match('/(may refer to ?\:|# ?REDIRECT|\{\{Soft ?(Redir(ect)?|link)|\{\{.*((dis(amb?(ig(uation( page)?)?)?)?)(\-cleanup)?|d(big|ab|mbox)|given( |_)name|sia|set index( articles)?)(\|([0-9a-zA-Z _]*?)( ?= ?[0-9a-zA-Z _]*?)){0,6}\}\})/i',$this->wiki->getpage($link)))
				{
					//Make sure the page is not in cat "All set index articles"
					if(!$this->inCategory("Category:All set index articles"))
					{
						//if we got this far it isnt an orphaned page
						return false;
					}
				}
			}
		}
		return null;
	}
	
	// If blue links are found it returns the number of blue links
	// returns true if 0 links are found
	public function isDeadend()
	{
		$count = 0;
		// match links to articles
		preg_match_all('/\[\[([^]]+)\]\]/i',$this->getcheckText(), $links, PREG_PATTERN_ORDER);
		foreach($links[1] as $link){
			//if this link has been renammed i.e. [[User:Addbot|Bot]]
			if(preg_match('/\|/',$link) != 0){
				// get the link rather than text name
				$split = preg_split('/\|/',$link);
				$link = $split[0];
			}
			//if it doesnt linked to another namespace
			if (!preg_match('/((Talk|User|Wikipedia|File|Image|Mediawiki|Template|Help|Category|Portal|Book|Education( |_)program|TimedText)(( |_)talk)?):/i',$link)){
				//and it does look like something we would expect
				if(preg_match('/(^:|^w:|^[^:]+$)/i',$link))
				{
					//count it
					$count++;
				}
			}
		}
		if($count == 0){return true;}
		else{return $count;}
	}
	
	// returns true is 0 categories are found
	// returns false if more than one is found
	public function isUncat()
	{
		// get cats for this page
		$cats = $this->wiki->categories($this->getName(),false);
		
		if(count($cats) == 0)
		{
			//if not cats at all then TRUE (IS UNCAT)
			return true;
		}
		else
		{
			foreach($cats as $cat)
			{
				if(!preg_match('/^Category:(.*?Proposed (for )?deletion.*?|(|.*? )stubs$)/i',$cat))
				{
					//if it is not a stub cat return FALSE (NOT UNCAT)
					return false;
				}
			}
			//If we haven't hit anything else then we must be uncat
			return true;
		}
	}
	
	//return true if the page is appended by .pdf
	public function isPdf()
	{ 
		if( preg_match("/\.pdf$/i",$this->getName()))
		{
			return true; 
		} 
	}
	
	//add the given template from the page if it doesnt already exist
	//passed $config['mitag']['TEMPLATECODE'] (i.e. orphan)
	public function addTag($template,$section=null)
	{
		//make sure the tag is not already on the page
		if(preg_match('/'.$template->regexTemplate().'/i',$this->getText()) || preg_match('/'.$template->regexTempIssues().'/i',$this->getText())){ return false; }
		//make sure the template's notif is not on the page
		if($template->regexNotif() != false) {if(preg_match("/".$template->regexNotif()."/i",$this->getText())){return false;}}
		//check if we want to add the tag below a section
		if($section)
		{
			//does the section exist?
			if(preg_match ("/== ?".$section." ?==/i",$this->text))
			{
				//then add the tag
				$matches = preg_match ("/== ?".$section." ?==/i",$this->getText());
				$pieces = preg_split("/== ?".$section." ?==/i",$this->getText());
				$this->text = $pieces[0]."==".$matches[1]."==\n".$template->getPost()." ".$pieces[1];
			}
			else // else we can just make the section
			{
				$this->text = "==".$section."==\n".$template->getPost()."\n" .$this->getText();
			}
		}
		else// else just add it to the top of the page
		{
			$this->text = $template->getPost()."\n" .$this->getText();
		}
		// add to the summary for the edit
		$this->addSummary("Adding {{".$template->getName()."}}",true);
	}
	
	//passed $config['mitag']['TEMPLATECODE'] (i.e. orphan)
	public function removeTag($template)
	{
		$this->removeRegex('/'.$template->regexTemplate().'/i',"Removing {{".$template->getName()."}}");
	}
	
	//remove the regex match from the page
	//if summary is set then add to edit summary
	public function removeRegex($regex,$summary = null)
	{
		if(preg_match($regex,$this->getText()))//make sure the regex is actually there
		{//if it is remove and say so
			$this->setText(preg_replace($regex,"",preg_replace($regex,"",$this->getText())));
			if($summary != null)
			{//if summary not null then we can add a summary
				$this->addSummary($summary,true);
			}
		}
	}
	
	//checks if a page is a sandbox
	public function isSandbox()
	{
		global $config;
		//check for each sandbox defined
		foreach($config['sandbox'] as $sandbox)
		{
			//if we hit one of our sandboxes
			if($sandbox['name'] == $this->getName())
			{
				return true;
			}
		}
	}
	
	//restores the header of a sandbox
	public function restoreHeader()
	{
		global $config;
		$sandbox = $config['sandbox'][$this->getName()];
		//get the shouldbe header
		$shouldbe = $this->wiki->getpage($sandbox['name'],$sandbox['id']);
		//If the required header is not at the top of the page
		if(!preg_match('/^'.preg_quote($shouldbe).'/s',$this->getText()))
		{
			//Post it to the top removing any other match of it
			$this->setText($shouldbe."\n".preg_replace('/'.preg_quote($shouldbe).'/is',"",$this->getText()));
			$this->addSummary("Restoring sandbox header",true);
			return true;
		}
	}
	
	//parse MI tag, add tags to MI, remove MI if not needed
	public function multipleIssues()
	{
		global $config;
		$this->runMI = $this->runMI + 1;
		$removed = 0;
		$hat = "";//for storing nay hat notes in
		$mi = "";//this will be used to store what we want to add to the page
		$hadMI = null;//did we have MI tag before this check?
		//parse the page
		$this->parse(); // work with $this->parsed;
		//for each template on the page
		foreach($this->parsed['wikObject_templates'] as $x)
		{
			//make sure the template is not surrounded by comment tags
			if(!preg_match('/<!--.*?'.preg_quote($x->rawCode,'/').'.*?-->/is',$this->getText()))
			{
			
				//does it match the MI template
				if(preg_match('/^(Multiple issues|Article issues|Issues|MI|Many Issues|Multiple|Multipleissues)$/i',$x->name))
				{
					//Update hadMI if not already set
					if($this->hadMI === null){$this->hadMI = true;}
					
					//IS the MI tag empty?
					if(preg_match('/\{\{(Multiple issues|Article issues|Issues|MI|Many Issues|Multiple|Multipleissues)\|?\s*?\}\}/is',$x->rawCode))
					{
						//remove and stop
						$this->text = preg_replace('/\{\{(Multiple issues|Article issues|Issues|MI|Many Issues|Multiple|Multipleissues)\|?\s*?\}\}/is',"",$this->getText());
						if($this->hadMI === true && $this->runMI == 2)
						{
							$this->addSummary("Removing {{Multiple issues}}",true);
						}
						return null;
					}
					else//else we must be a new MI style (or a mixture of both)
					{
						//the parse accordingly
						foreach($x->arguments as $tagarg)
						{
							if(!preg_match('/\{/',$tagarg))//if the arg is old style, add it correctly
							{
								//if the tagarg doesnt have a date i.e. 'expert = canada' we need to get the date in the 'date = <DATE>' parameter
								if(!preg_match('/(.*?) ?= ?((January|February|March|April|May|June|July|August|September|October|November|December) ?20[0-9][0-9])/i',$tagarg))
								{
									//add it with a <DATE> placeholder
									$mi = $mi."{{".trim(preg_replace('/ ?= ?/','|',$tagarg))."|date=<DATE>}}\n";
								}
								else//else it does have a date
								{
									//if it is not just a date
									if(!preg_match('/^date ?= ?/i',$tagarg))
									{
										//add it normally
										$mi = $mi."{{".trim(preg_replace('/ ?= ?/','|date=',$tagarg))."}}\n";
									}
									//else it must be a date
									else
									{
										//dont add the date but replace <DATE> in the string with the date
										$mi = str_replace("<DATE>",trim(preg_replace('/date ?= ?/','',$tagarg)),$mi);
									}
								}
							}
							else
							{
								//just add it 
								//After a lot of research and testing it turns out the MI tag is allowed 1 parameter with templates in
								//Although this can be in the same MI tag as non template 'old style' paramemters
								$mi = $mi.$tagarg;
							}
						}
						
						$removed = $removed + $x->attributes['length'];
						$this->text = str_replace($x->rawCode,'',$this->getText());
					}
					$mi = preg_replace("/\n/","",$mi);//get rid of new lines
				}
				//else do we match any hatnotes
				elseif(preg_match('/^(Template:)?(Hatnote|Reflink|Main(( |_)list)?|Details3?|See( |_)also2?|Further2?|About|Other( |_)uses-section|For|((Two|Three) )?Other( |_)uses|Other uses of|Redirect[0-1]?[0-9]|Redirect(-|_| )(synomym|text|distinguish2?)|Consider( |_)disambiguation|Other( |_)(uses|people|places|hurricanes|ships|)[1-5]?|(Redirect-)?Distinguish|Selfref|Category( |_)(see also|explanation|pair)|Cat( |_)main|cat(preceding|succeeding)|contrast|This( |_)user( |_)talk)/i',$x->name))
				{
					//make sure the hat note is not under a section
					if(!preg_match('/\n==.*?{{'.$x->name.'/is',$this->getText()))
					{
						if(strlen($x->rawCode) < 4)
						{
							//skip page
							$this->skip = true;
						}
						//remember our hatnotes
						$hat = $hat.$x->rawCode."\n";
						//remove the hatnote matched (we will re add later)
						$removed = $removed + $x->attributes['length'];
						$this->text = str_replace($x->rawCode,'',$this->getText());
					}
				}
				else// else if we match a tag to go in MI
				{
					//check for all of our defined tags
					foreach($config['mitag'] as $tag)
					{
						//if it is one of our tags
						if(preg_match("/^".$tag->regexName()."$/i",$x->name) == true)
						{
							//if we have a section param ignore the tag
							if(preg_match("/\|(sections|sect?)/i",$x->rawCode) == false)
							{
								//remove the tag from page and add to our output
								$mi = $mi.$x->rawCode;
								$this->text = substr_replace($this->getText(),"",$x->attributes['start']-$removed-1,$x->attributes['length']);
								$removed = $removed + $x->attributes['length'];
							}
						}
					}
				}
			}
		}
		
		//Update hadMI if not already set
		if($this->hadMI === null){$this->hadMI = false;}
		
		//crappy way to make sure we split at every tag
		$mi = preg_replace('/\}\}/',"}}\n",$mi);
		//split into each tag (might be joined if from MI)
		$split = preg_split("/\n/",$mi,0,PREG_SPLIT_NO_EMPTY);
		//If there is at least 2 tags
		if(count($split) > 1)
		{
			//add them to a MI tag
			$mi = "{{Multiple issues|\n";//start mi
			foreach ($split as $tag)
			{
				//Check the exact same thing is not already there
				if(!strstr($mi,$tag))
				{
					//TODO: now we want to make sure the same thing is not there just with a different date (also account for redirects)
						$mi = $mi.$tag."\n";//add each tag
				}
			}
			$mi = $mi."}}";//add the end of the tag
			if($this->hadMI === false && $this->runMI == 2)
			{
				//if we have already had a sig change or we have more than 2 tags
				if($this->sigchange == true || count($split) > 2)
				{
					//we can edit
					$this->addSummary("Adding {{Multiple issues}}",true);
				}
			}
		}
		//if only 1 we dont want to use multiple issues
		elseif(count($split) == 1)
		{
			//just add the single tag
			$mi = $split[0];
			if($this->hadMI === true && $this->runMI == 2)
			{
				$this->addSummary("Removing {{Multiple issues}}",true);
			}
			
		}

		//add to origional text with any hatnotes
		$this->text = $hat.$mi."\n".$this->getText();

	}
	
	//parse MI tag, add tags to MI, remove MI if not needed
	public function multipleIssuesDupes()
	{
		global $config;
		//parse the page
		$mi['name'] = Array();
		$mi['date'] = Array();
		$mi['params'] = Array();
		$this->parse(); // work with $this->parsed;
		//for each template on the page
		foreach($this->parsed['wikObject_templates'] as $x)
		{
			//make sure the template is not surrounded by comment tags
			if(!preg_match('/<!--.*?'.preg_quote($x->rawCode,'/').'.*?-->/is',$this->getText()))
			{
				//does it match the MI template
				if(preg_match('/^(Multiple issues|Article issues|Issues|MI|Many Issues|Multiple|Multipleissues)/i',$x->name))
				{					
					$c = 0;
					//the parse accordingly
					foreach($x->arguments as $tagarg)
					{
						$each = explode("\n",$tagarg);
						foreach ($each as $tag)
						{
							if(preg_match('/\{\{([^\|]+)(.*?)\|date ?= ?((January|February|March|April|May|June|July|August|September|October|November|December) ?20[0-9][0-9])(.*?)\}\}/i',$tag,$matches))
							{
								//if its not already in the arry or it matches a template to ignore
								if(!in_array($matches[1],$mi['name']) || preg_match('/((cleanup-)?expert(_| |-|)(attention|subject|article|portal|verify|))/i',$matches[1]))
								{
									//add it
									$mi['name'][$c] = $matches[1];
									$mi['date'][$c] = $matches[3];
									$mi['params'][$c] = $matches[2].$matches[5];
									$c++;
								}
								else
								{
									$search = array_search($matches[1],$mi['name']);
									//find which date is oldest and keep it
									if(strtotime($matches[3]) < strtotime($mi['date'][$search]))
									{
										//change the date
										$mi['date'][$search] = $matches[3];
										//and if no parameters were given before
										if($mi['params'][$search] == "")
										{
											//give ours
											$mi['params'][$search] = $matches[2].$matches[5];
										}
									}
								}
							}
							//else it doesnt match a nice date format so just ignore it for now
							else
							{
								//add it
								$mi['name'][$c] = $matches[1];
								$mi['date'][$c] = $matches[3];
								$mi['params'][$c] = $matches[2].$matches[5];
								$c++;
							}
						}
					}
					//find our new MI tag
					$torep = "{{Multiple issues|\n";
					foreach($mi['name'] as $key => $name)
					{
						$torep.= "{{".$mi['name'][$key].$mi['params'][$key]."|date=".$mi['date'][$key]."}}\n";
					}
					$torep .= "}}";
					//replace the old with the new
					if(strlen($x->rawCode)-10 > strlen($torep))
					{
						$this->setText(str_replace($x->rawCode,$torep,$this->getText()));
						$this->addSummary("Removing Duplicate tags",true);
					}
				}
			}
		}

	}
	
	//http://en.wikipedia.org/w/index.php?title=Wikipedia:AutoEd/whitespace.js&action=raw&ctype=text/javascript
	public function fixWhitespace()
	{
		$this->text = preg_replace('/(\n\n)\n+/',"$1", $this->getText() );
		//remove leading white space
		$this->text = preg_replace('/^(\n|\r){0,5}/',"", $this->getText() );
	}
	
	public function fixTemplates()
	{
		$this->text = preg_replace( '/\{\{(?:Template:)?(Dab|Disamb|Disambiguation)\}\}/iS', "{{Disambig}}", $this->text );
		$this->text = preg_replace( '/\{\{(?:Template:)?(Bio-dab|Hndisambig)/iS', "{{Hndis", $this->text );
		$this->text = preg_replace( '/\{\{(?:Template:)?(Prettytable|Prettytable100)\}\}/iS', "{{subst:Prettytable}}", $this->text );
		$this->text = preg_replace( '/\{\{(?:[Tt]emplate:)?((?:BASE)?PAGENAMEE?\}\}|[Ll]ived\||[Bb]io-cats\|)/iS', "{{subst:$1", $this->text );
		$this->text = preg_replace( '/({{\s*[Aa]rticle ?issues\s*(?:\|[^{}]*|\|)\s*[Dd]o-attempt\s*=\s*)[^{}\|]+\|\s*att\s*=\s*([^{}\|]+)(?=\||}})/iS', "$1$2", $this->text );
		$this->text = preg_replace( '/({{\s*[Aa]rticle ?issues\s*(?:\|[^{}]*|\|)\s*[Cc]opyedit\s*)for\s*=\s*[^{}\|]+\|\s*date(\s*=[^{}\|]+)(?=\||}})/iS', "$1$2", $this->text );
		$this->text = preg_replace( '/\{\{[Aa]rticle ?issues(?:\s*\|\s*(?:section|article)\s*=\s*[Yy])?\s*\}\}/iS', "", $this->text );
		$this->text = preg_replace( '/\{\{[Cc]ommons\|\s*[Cc]ategory:\s*([^{}]+?)\s*\}\}/iS', "{{Commons category|$1}}", $this->text );
		$this->text = preg_replace( '/(?!{{[Cc]ite wikisource)(\{\{\s*(?:[Cc]it[ae]|[Aa]rticle ?issues)[^{}]*)\|\s*(\}\}|\|)/iS', "$1$2", $this->text );
		$this->text = preg_replace( '/({{\s*[Aa]rticle ?issues[^{}]*\|\s*)(\w+)\s*=\s*([^\|}{]+?)\s*\|((?:[^{}]*?\|)?\s*)\2(\s*=\s*)\3(\s*(\||\}\}))/iS', "$1$4$2$5$3$6", $this->text );
		$this->text = preg_replace( '/(\{\{\s*[Aa]rticle ?issues[^{}]*\|\s*)(\w+)(\s*=\s*[^\|}{]+(?:\|[^{}]+?)?)\|\s*\2\s*=\s*(\||\}\})/iS', "$1$2$3$4", $this->text );
		$this->text = preg_replace( '/(\{\{\s*[Aa]rticle ?issues[^{}]*\|\s*)(\w+)\s*=\s*\|\s*((?:[^{}]+?\|)?\s*\2\s*=\s*[^\|}{\s])/iS', "$1$3", $this->text );
		$this->text = preg_replace( '/{{\s*(?:[Cc]n|[Ff]act|[Pp]roveit|[Cc]iteneeded|[Uu]ncited)(?=\s*[\|}])/S', "{{Citation needed", $this->text );
	}
	
	public function fixDateTags()
	{
		global $config;
		//get a copy of the text to change
		$text = $this->getText();
		//get the current month and year
		$date = date("F Y");
		$month = date("F");
		
		//check each tag we have to see if it needs to be dated
		foreach ($config['mitag'] as $tag)
		{
			//if the tag can be found without a date
			if($this->matches('/\{\{(Template:)?'.$tag->regexName().'/i'))
			{
				//date tags with no args at all
				$text = preg_replace('/\{\{(Template:)?'.$tag->regexName().'\}\}/i',"{{".$tag->getName()."|date=$date}}",$text);
				//$this->setText(preg_replace('/\{\{(Template:)?'.$tag->regexName().'([^}]*?)\}\}/is',"{{".$tag->getName()."|date=$date$3}}",$text));
			}
		}
		
		//If a tag has been dated
		if(strlen($text) > strlen($this->getText())+5)
		{
			$this->text = $text;
			echo "+";
			$this->addSummary("Dating Tags",true);
		}
	}
	
	public function fixSectionTags()
	{
		global $config;
		//check each tag we have to see if it needs to be dated
		foreach ($config['mitag'] as $tag)
		{
			//if it matches something that can be under a section and not matter
			if(!preg_match('/(unreferenced|emptysection|refimprove|unsourced|footnotes|uncategorized)/i',$tag->getName()))
			{
				//if the tag can be found below a section
				if($this->matches('/(==.*?)\{\{(Template:)?'.$tag->regexName().'(?!sect(ions?)?)[^}]*?\}\}/is'))//todo
				{
					//add the section parameter to the template if it isnt there already
					$this->setText(preg_replace('/(==.*?)\{\{(Template:)?'.$tag->regexName().'((?!sect(ions?)?)[^}]*?)\}\}/is',"$1{{".$tag->getName()."$4|section}}",$this->getText()));
				}
			}
		}
	}
	
	public function fixGeneral()
	{
		global $config;
		//Fix headers
		//TODO FIX THESE
		//$this->text = preg_replace('/== ?External ?links? ?==/i', "==External links==", $this->text );
		//$this->text = preg_replace('/== ?Further ?readings? ?==/i', "==Further reading==", $this->text );
		//Templates
		$this->text = preg_replace('/\{\{'.$config['mitag']['unreferenced']->regexName().'\|section\}\}/i', "{{Unreferenced section}}", $this->text );
		
		if(!$this->matches('/\{\{reflist/i'))
		{$this->text = preg_replace('/<references ?\/>/i',"{{reflist}}", $this->text );}
	
	}
	
	public function preChecks()
	{
		$this->text = str_ireplace("<!-- Automatically added by User:SoxBot. If this is an error, please contact User:Soxred93 -->","",$this->text);
		$this->text = preg_replace("/<!-- ?interwikis?( links?)? ?-->/i","",$this->text);
	}
}
	 
?>