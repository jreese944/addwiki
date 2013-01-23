<?

require 'parser.php';

class Page {

	// construct the page (you probably want to call load after this)
	public function __construct($page,$wiki) {
		$this->page = $page;
		$this->parseNamespace();
		$this->wiki = $wiki;
	}	
	
	// variables
	private $page;// page name (e.g. "User:Addshore")
	private $text;// page text
	private $namespace;// page namespace (No colon)
	private $wiki;
	private $parser;// instance of the parser.php class
	
	// getters and setters
	public function getName() { return $page; }
	public function getText() { if(!isset($this->text)){$this->loadText();} return $this->text;}
	public function getNamespace() { if(!isset($namespace)){$this->parseNamespace();} return $namespace;}
	
	// public functions
	public function parse() { $this->parser = new parser($this->page,$this->getText()); $this->parser->parse();} // create instance of parser class and parse
	
	// private functions
	private function loadText() { $this->text = $this->wiki->getpage($this->page);} // load the text from the wiki
	private function parseNamespace()
	{
		$result = preg_match("/^((User|Wikipedia|File|Image|Mediawiki|Template|Help|Category|Portal|Book|Education( |_)program|TimedText)(( |_)talk)?):?/i",$this->page,$matches);
		if($result == 0){ $this->namespace = "";}//default to article namespace
		else{$this->namespace = $matches[1];}
	}
	
	//removeTag($tag,$arguments)
	//addMultipleissues()

}
	 
?>