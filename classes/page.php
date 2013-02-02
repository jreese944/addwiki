<?
require 'parser.php';

class Page {

	// construct the page (you probably want to call load after this)
	public function __construct($page,$wiki) {
		$this->page = preg_replace("/_/"," ",$page);
		$this->wiki = $wiki;
		$this->parseNamespace();
		$this->loadText();//load the wikitext from page
	}	
	
	// variables
	private $page;// page name (e.g. "User:Addshore")
	private $text;// page text
	private $namespace;// page namespace (No colon)
	private $wiki;// instance of wiki we are using
	private $parser;// instance of the parser.php class
	private $parsed;
	private $sigchange;//has a significant change happened to the page (enough to edit)?
	private $summary;//summary if edited
	
	// getters and setters
	public function getName() { return $this->page; }
	public function getText() { return $this->text;}
	public function getNamespace() { if(!isset($this->namespace)){$this->parseNamespace();} return $this->namespace;}
	public function getSummary(){return "[[User:Addbot|Bot:]] v2 - ".$this->summary."([[User talk:Addbot|Report Errors]])";}
	public function hasSigchange() { return $this->sigchange; }
	
	// public functions
	// create instance of parser class and parse
	public function parse() { $this->parser = new parser($this->getName(),$this->getText()); $this->parsed = $this->parser->parse(); return $this->parsed;} 
	
	// private functions
	private function loadText() { $this->text = $this->wiki->getpage($this->getName());} // load the text from the wiki
	private function postPage() { $this->wiki->edit($this->getName(),$this->getText(),$this->getSummary(),true);} 
	private function parseNamespace()
	{
		$result = preg_match("/^((User|Wikipedia|File|Image|Mediawiki|Template|Help|Category|Portal|Book|Education( |_)program|TimedText)(( |_)talk)?):?/i",$this->page,$matches);
		if($result == 0){ $this->namespace = "";}// default to article namespace
		else{$this->namespace = $matches[1];}
		if($this->namespace == "Image"){ $this->namespace = "File";}// default Image namespace to file
	}
	private function addSummary($sum)
	{
		$this->sigchange = true;//if we have a summary it muse be a sig change
		$this->summary = $this->summary.$sum." ";
		echo $sum"\n";
	}
	
//	                  //
// Main bot functions //
//                    //

	//make matching easier
	public function matches($regex){return preg_match($regex,$this->getText())}
	
	//return a restricted estimate of words in an article
	public function wordcount()
	{
		$text = $this->getText();//get a temp copy of the text to work with
		$text = preg_replace("/(\{\{[^\}]*?\}\}|={1,6}[^=]*?={1,6}|\n\*{1,2} ?|\[https?[^\]]*?\]|\[\[(Category|Image|File|[a-z]{2,6}):[^\]]*?\]\]|\<references ?\/\>|<ref>.*?<\/ref>|<!--.*?-->)/is","",$text);//remove templates, cats, interwikis and extlinks and refs
		$text = preg_replace("/\[\[[^\]]*?\]\]/","WORD",$text);//fill all links in with a single word
		$text = trim($text);
		return str_word_count($text);
	}
	
	// returns false doesnt need sections
	public function needsSections()
	{
		$largestsection = 0;
		$sectioncount = 0;
		preg_match_all('/\n==(=)? ?.* ?===?/i',$text, $sections, PREG_PATTERN_ORDER);
		$split = preg_split('/\n==(=)? ?.* ?===?/i',$text);
			
		foreach($split as $id => $section){
			if($id == 0){
				$largestsection = strlen($section);
				$sectioncount++;
			}
			else{
				if (preg_match('/See ?also|(external( links)?|references|notes|bibliography|further( reading)?)/i',$sections[0][$id-1]) == 0){
					echo "-- IS a valid section per ".$sections[0][$id-1]." \n";
					if(strlen($section) > $largestsection){
						$largestsection = strlen($section);
					}
				$sectioncount++;
				}
			}
		}
		if($sectioncount >= 4 && $largestsection <= 5000){//was 2750 for AVG
			$text = preg_replace("/\{\{((cleanup|needs ?)?Sections)(\| ?(date) ?(= ?(January|February|March|April|May|June|July|August|September|October|November|December) ?20[0-9][0-9])? ?){0,1} *\}\}(\r\n|\n\n){0,3}/i","",$text);
			return false;
		}
	}
	
	//returns true if we have a ref
	public function isReferenced()
	{
		//if we match a ref tag
		if($this->matches('/<\/?ref/i'))
		{
			return true;
		}
		return null;
	}
	
	// returns false if not orphan
	public function isOrphan()
	{
		$links = $this->wiki->whatlinkshere($this->getName(),"&blnamespace=0");
		if(count($links) == 0) {return true;}// if no links
		foreach($links as $link){
			if(preg_match("/((List|Index) of|\(disambig(uation)?\))/i",$link) == FALSE)// names to skip
			{
				if (preg_match("/(may refer to ?\:|# ?REDIRECT|\{\{Soft ?(Redir(ect)?|link)|\{\{.*((dis(amb?(ig(uation( page)?)?)?)?)(\-cleanup)?|d(big|ab|mbox)|sia|set index( articles)?).*\}\})/i",$this->wiki->getpage($link)) == FALSE)
				{return false;}
			}
		}
		return null;
	}
	
	// returns false if page is not deadend
	public function isDeadend()
	{
		preg_match_all('/\[\[([a-z\/ _\(\)\|\.0-9]*)\]\]/i',$this->getText(), $links, PREG_PATTERN_ORDER);// match links to articles
		foreach($links[1] as $link){
			if(preg_match('/\|/',$link) != 0){
				$split = preg_split('/\|/',$link);// get the link rather than text
				$link = $split[0];
			}
			if (preg_match('/:/',$link) == 0){
				return false;			
			}
		}
		if(count($links) == 0){ return true; }
	}
	
	// returns false if not uncat
	public function isUncat()
	{
		$cats = $this->wiki->categories($this->getName(),false);// get cats for this page
		if(count($cats) == 0){return true;}else{return false;}// tag as apropriate
	}
	
	//return true if the page is a pdf
	public function isPdf()
	{ 
		if( preg_match("/\.pdf$/i",$this->getName())) {return true; } 
	}
	
	//add the given template from the page if it doesnt already exist
	//passed $config['tag']['TEMPLATECODE'] (i.e. orphan)
	public function addTag($template,$section=null)
	{
		//if it doesnt already exist
		if(preg_match($template->regexTemplate(),$this->getText()) || preg_match($template->regexTempIssues(),$this->getText())){ return false; }
		if($section)// if we want to add below a section
		{
			if(preg_match ("/== ?".$section." ?==/i",$this->text)) // if the section exists
			{
				$matches = preg_match ("/== ?".$section." ?==/i",$this->getText());
				$pieces = preg_split("/== ?".$section." ?==/i",$this->getText());
				$this->text = $pieces[0]."==".$matches[1]."==\n{{".$template->getName()."}} ".$pieces[1];
			}
			else // else the section must exist
			{
				$this->text = "==".$section."==\n{{".$template->getName()."}}\n" .$this->getText();
			}
		}
		else// else just add it to the top of the page
		{
			$this->text = "{{".$template->getName()."}}\n" .$this->getText();
		}
		$this->addSummary("Adding",$template->getName());// add to the summary
	}
	
	//passed $config['tag']['TEMPLATECODE'] (i.e. orphan)
	public function removeTag($template)
	{
		$this->removeRegex($template->regexTemplate(),"Removing ".$template->getName());
	}
	
	public function removeRegex($regex,$summary = null)
	{
		if(preg_match($regex,$this->getText()))//make sure the regex is actually there
		{//if it is remove and say so
			$this->text = preg_replace($regex,"",$this->getText());
			if($summary != null)
			{//if summary not null then we can add a summary
				$this->addSummary($summary);
			}
		}
	}
	
	public function multipleIssues()
	{
		global $config;
		$removed = 0;
		$mi = "";
		$this->parse(); // work with $this->parsed;
		foreach($this->parsed['wikObject_templates'] as $x)
		{
			if(preg_match('/^(Multiple issues|Article issues|Issues|MI|Many Issues|Multiple|Multipleissues)/i',$x->name))
			{
				if(preg_match('/\{\{(multiple ?issues|article ?issues|mi)\s*\|([^{]+)\}\}/i',$x->rawCode))//if old style mi
				{
					foreach($x->arguments[1] as $tagarg)
					{
						$mi = $mi."{{".trim(preg_replace('/ ?= ?/','|date=',$tagarg))."}}\n";
					}
				}
				else//else we must be a new MI style
				{
					$mi = $mi.$x->arguments[1];
					$removed = $removed + $x->attributes['length'];
					$this->text = substr($this->getText(),"",$x->attributes['start']-$removed,$x->attributes['length']);
				}
			}
			else// else if we match a tag
			{
				foreach($config['tag'] as $tag)
				{
					if(preg_match("/^".$tag->regexName()."$/i",$x->name) == true)//if it is one of our tags
					{
						if(preg_match("/\|(sections|sect?)/i",$x->rawCode) == false)//if we have a section param ignore it
						{
							$mi = $mi.$x->rawCode;
							$this->text = substr_replace($this->getText(),"",$x->attributes['start']-$removed-1,$x->attributes['length']);
							$removed = $removed + $x->attributes['length'];
						}
					}
				}
			}
		}
		$mi = preg_replace('/\}\}/',"}}\n",$mi);//crappy way to make sure we split at every tag
		$split = preg_split("/\n/",$mi,0,PREG_SPLIT_NO_EMPTY);//split into each tag
		if(count($split) > 1)
		{
			$mi = "{{Multiple issues|\n";//start mi
			foreach ($split as $tag)
			{
				$mi = $mi.$tag."\n";//add each tag
			}
			$mi = $mi."}}";//add the end of the tag
		}
		elseif(count($split) == 1)//if only 1 we dont want to use multiple issues
		{
			$mi = $split[0];
		}
		else
		{
			return false;//we actually dont have any tags 
		}

		$this->text = $mi."\n".$this->getText();//add to origional text

	}
	
	//http://en.wikipedia.org/w/index.php?title=Wikipedia:AutoEd/whitespace.js&action=raw&ctype=text/javascript
	public function fixWhitespace()
	{
		$this->text = preg_replace('/\t/'," ", $this->getText() );
		
		$this->text = preg_replace('/^ ? ? \n/m',"\n", $this->getText() );
		$this->text = preg_replace('/(\n\n)\n+/',"$1", $this->getText() );
		$this->text = preg_replace('/== ? ?\n\n==/',"==\n==", $this->getText() );
		$this->text = preg_replace('/\n\n(\* ?\[?http)/',"\n$1", $this->getText() );
		
		$this->text = preg_replace('/^ ? ? \n/m',"\n", $this->getText() );
		$this->text = preg_replace('/\n\n\*/',"\n*", $this->getText() );
		$this->text = preg_replace('/([=\n]\n)\n+/',"$1", $this->getText() );
		$this->text = preg_replace('/ \n/',"\n", $this->getText() );
		
		$this->text = preg_replace('/^([\*#]+:*) /m',"$1", $this->getText() );
		$this->text = preg_replace('/^([\*#]+:*)/m',"$1 ", $this->getText() );
		
		$this->text = preg_replace('/^(={1,4} )[ ]*([^= ][^=]*[^= ])[ ]*( ={1,4})$/m',"$1$2$3", $this->getText() );
		$this->text = preg_replace('/^(={1,4})([^= ][^=]*[^= ])[ ]+(={1,4})$/m',"$1$2$3", $this->getText() );
		$this->text = preg_replace('/^(={1,4})[ ]+([^= ][^=]*[^= ])(={1,4})$/m',"$1$2$3", $this->getText() );
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
		
		//check each tag we have to see if it needs to be dated
		foreach ($config['tag'] as $tag)
		{
			//if the tag can be found without a date
			if($this->matches('/\{\{(Template:)?'.$tag->regexName().'\}\}/i'))
			{
				//then date it
				$text = preg_replace('/\{\{(Template:)?'.$tag->regexName().'\}\}/i',"{{".$tag->getName()."|date=$date}}",$text);
			}
		}
		
		//If a tag has been dated
		if(strlen($text) > strlen($this->getText())+5)
		{
			$this->text = $text;
			$this->addSummary("Dating","Maint tags");
		}
	}
	
	public function fixGeneral()
	{
	}
}
	 
?>