<?php
/**
 * botclasses.php - Bot classes for interacting with mediawiki.
 *
 *  (c) 2008-2012 Chris G - http://en.wikipedia.org/wiki/User:Chris_G
 *  (c) 2009-2010 Fale - http://en.wikipedia.org/wiki/User:Fale
 *  (c) 2010      Kaldari - http://en.wikipedia.org/wiki/User:Kaldari
 *  (c) 2011      Gutza - http://en.wikipedia.org/wiki/User:Gutza
 *  (c) 2012      Sean - http://en.wikipedia.org/wiki/User:SColombo
 *  (c) 2012      Brain - http://en.wikipedia.org/wiki/User:Brian_McNeil
 *  (c) 2012-2013 Addshore - http://en.wikipedia.org/wiki/User:Addshore
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *  Developers (add yourself here if you worked on the code):
 *      Cobi     - [[User:Cobi]]         - Wrote the http class and some of the wikipedia class
 *      Chris    - [[User:Chris_G]]      - Wrote the most of the wikipedia class
 *      Fale     - [[User:Fale]]         - Polish, wrote the extended and some of the wikipedia class
 *      Kaldari  - [[User:Kaldari]]      - Submitted a patch for the imagematches function
 *      Gutza    - [[User:Gutza]]        - Submitted a patch for http->setHTTPcreds(), and http->quiet
 *      Sean     - [[User:SColombo]]     - Wrote the lyricwiki class (now moved to lyricswiki.php)
 *      Brain    - [[User:Brian_McNeil]] - Wrote wikipedia->getfileuploader() and wikipedia->getfilelocation
 * 		Addshore - [[User:Addshore]]     - Functions for Addbot (lastedit,lasteditonpage,firstedit,updateupdateupdate nobots)
 **/

/**
 * This class is designed to provide a simplified interface to cURL which maintains cookies.
 * @author Cobi
 **/
class http {
    private $ch;
    private $uid;
    public $cookie_jar;
    public $postfollowredirs;
    public $getfollowredirs;
    public $quiet=true;

    function data_encode ($data, $keyprefix = "", $keypostfix = "") {
        assert( is_array($data) );
        $vars=null;
        foreach($data as $key=>$value) {
            if(is_array($value))
                $vars .= $this->data_encode($value, $keyprefix.$key.$keypostfix.urlencode("["), urlencode("]"));
            else
                $vars .= $keyprefix.$key.$keypostfix."=".urlencode($value)."&";
        }
        return $vars;
    }

    function __construct () {
        $this->ch = curl_init();
        $this->uid = dechex(rand(0,99999999));
        curl_setopt($this->ch,CURLOPT_COOKIEJAR,'/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
        curl_setopt($this->ch,CURLOPT_COOKIEFILE,'/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
        curl_setopt($this->ch,CURLOPT_MAXCONNECTS,100);
        curl_setopt($this->ch,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
        $this->postfollowredirs = 0;
        $this->getfollowredirs = 1;
        $this->cookie_jar = array();
    }

    function post ($url,$data) {
        //echo 'POST: '.$url."\n";
        $time = microtime(1);
        curl_setopt($this->ch,CURLOPT_URL,$url);
        curl_setopt($this->ch,CURLOPT_USERAGENT,'Addbot Wikimedia Bot');
        /* Crappy hack to add extra cookies, should be cleaned up */
        $cookies = null;
        foreach ($this->cookie_jar as $name => $value) {
            if (empty($cookies))
                $cookies = "$name=$value";
            else
                $cookies .= "; $name=$value";
        }
        if ($cookies != null)
            curl_setopt($this->ch,CURLOPT_COOKIE,$cookies);
        curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->postfollowredirs);
        curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_TIMEOUT,30);
        curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($this->ch,CURLOPT_POST,1);
//      curl_setopt($this->ch,CURLOPT_FAILONERROR,1);
//    curl_setopt($this->ch,CURLOPT_POSTFIELDS, substr($this->data_encode($data), 0, -1) );
        curl_setopt($this->ch,CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($this->ch);
//    echo "Error: ".curl_error($this->ch);
//    var_dump($data);
//    global $logfd;
//    if (!is_resource($logfd)) {
//        $logfd = fopen('php://stderr','w');
    if (!$this->quiet)
            echo 'POST: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n";
//     }
        return $data;
    }

    function get ($url) {
        //echo 'GET: '.$url."\n";
        $time = microtime(1);
        curl_setopt($this->ch,CURLOPT_URL,$url);
        curl_setopt($this->ch,CURLOPT_USERAGENT,'php wikibot classes');
        /* Crappy hack to add extra cookies, should be cleaned up */
        $cookies = null;
        foreach ($this->cookie_jar as $name => $value) {
            if (empty($cookies))
                $cookies = "$name=$value";
            else
                $cookies .= "; $name=$value";
        }
        if ($cookies != null)
            curl_setopt($this->ch,CURLOPT_COOKIE,$cookies);
        curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->getfollowredirs);
        curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);
        curl_setopt($this->ch,CURLOPT_HEADER,0);
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_TIMEOUT,30);
        curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($this->ch,CURLOPT_HTTPGET,1);
        //curl_setopt($this->ch,CURLOPT_FAILONERROR,1);
        $data = curl_exec($this->ch);
        //echo "Error: ".curl_error($this->ch);
        //var_dump($data);
        //global $logfd;
        //if (!is_resource($logfd)) {
        //    $logfd = fopen('php://stderr','w');
        if (!$this->quiet)
            echo 'GET: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n";
        //}
        return $data;
    }

    function setHTTPcreds($uname,$pwd) {
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->ch, CURLOPT_USERPWD, $uname.":".$pwd);
    }

    function __destruct () {
        curl_close($this->ch);
        @unlink('/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
    }
}

/**
 * This class is interacts with wikipedia using api.php
 * @author Chris G and Cobi
 **/
class wikipedia {
    public $http;
    public $token;
    private $ecTimestamp;
    public $url;
	public $loggedin;

	
	function hashbashandcrash () {
        $x = $this->query('?action=query&list=logevents&leuser=addshore&letype=delete&lelimit=500&format=php');
		return $x;
    }
	
    /**
     * This is our constructor.
     * @return void
     **/
    function __construct ($url='http://en.wikipedia.org/w/api.php',$hu=null,$hp=null) {
        $this->http = new http;
        $this->token = null;
        $this->url = $url;
        $this->ecTimestamp = null;
        if ($hu!==null)
            $this->http->setHTTPcreds($hu,$hp);
    }

    function __set($var,$val) {
    switch($var) {
          case 'quiet':
            $this->http->quiet=$val;
                 break;
           default:
                 echo "WARNING: Unknown variable ($var)!\n";
     }
    }

    /**
     * Sends a query to the api.
     * @param $query The query string.
     * @param $post POST data if its a post request (optional).
     * @return The api result.
     **/
    function query ($query,$post=null) {
        if ($post==null)
            $ret = $this->http->get($this->url.$query);
        else
            $ret = $this->http->post($this->url.$query,$post);
        return unserialize($ret);
    }

    /**
     * Gets the content of a page. Returns false on error.
     * @param $page The wikipedia page to fetch.
     * @param $revid The revision id to fetch (optional)
     * @return The wikitext for the page.
     **/
    function getpage ($page,$revid=null,$detectEditConflict=false) {
        $append = '';
        if ($revid!=null)
            $append = '&rvstartid='.$revid;
        $x = $this->query('?action=query&format=php&prop=revisions&titles='.urlencode($page).'&rvlimit=1&rvprop=content|timestamp'.$append);
        foreach ($x['query']['pages'] as $ret) {
            if (isset($ret['revisions'][0]['*'])) {
                if ($detectEditConflict)
                    $this->ecTimestamp = $ret['revisions'][0]['timestamp'];
                return $ret['revisions'][0]['*'];
            } else
                return false;
        }
    }

    /**
     * Gets the page id for a page.
     * @param $page The wikipedia page to get the id for.
     * @return The page id of the page.
     **/
    function getpageid ($page) {
        $x = $this->query('?action=query&format=php&prop=revisions&titles='.urlencode($page).'&rvlimit=1&rvprop=content');
        foreach ($x['query']['pages'] as $ret) {
            return $ret['pageid'];
        }
    }

    /**
     * Gets the number of contributions a user has.
     * @param $user The username for which to get the edit count.
     * @return The number of contributions the user has.
     **/
    function contribcount ($user) {
        $x = $this->query('?action=query&list=allusers&format=php&auprop=editcount&aulimit=1&aufrom='.urlencode($user));
        return $x['query']['allusers'][0]['editcount'];
    }
	
	/**
     * Gets the rights of a user
     * @param $user The username for which to get the edit count.
     * @return The number of contributions the user has.
     **/
    function getuserrights ($user) {
        $x = $this->query('?action=query&list=allusers&format=php&auprop=rights&aulimit=1&aufrom='.urlencode($user));
        return $x['query']['allusers'][0]['rights'];
    }

    /**
     * Returns an array with all the members of $category
     * @param $category The category to use.
     * @param $subcat (bool) Go into sub categories?
     * @return array
     **/
    function categorymembers ($category,$subcat=false) {
        $continue = '';
        $pages = array();
        while (true) {
            $res = $this->query('?action=query&list=categorymembers&cmtitle='.urlencode($category).'&format=php&cmlimit=500'.$continue);
            if (isset($x['error'])) {
                return false;
            }
            foreach ($res['query']['categorymembers'] as $x) {
                $pages[] = $x['title'];
            }
            if (empty($res['query-continue']['categorymembers']['cmcontinue'])) {
                if ($subcat) {
                    foreach ($pages as $p) {
                        if (substr($p,0,9)=='Category:') {
                            $pages2 = $this->categorymembers($p,true);
                            $pages = array_merge($pages,$pages2);
                        }
                    }
                }
                return $pages;
            } else {
                $continue = '&cmcontinue='.urlencode($res['query-continue']['categorymembers']['cmcontinue']);
            }
        }
    }
	
	/**
     * Returns an array with all the ctegories of a page
     * @param $page The page to check
     * @param $hidden (bool) Do you want to only show hiddencats or only show normalcats? (can be null)
     * @return array
     **/
    function categories($page,$hidden=null) {
		if($hidden === true){ $h = "&clshow=hidden";}
		elseif($hidden === false){$h = "&clshow=!hidden";}
		else{$h = "";}
        $continue = '';
        $pages = array();
        while (true) {
            $res = $this->query('?action=query&prop=categories&clprop=hidden&titles='.urlencode($page).$h.'&format=php&cllimit=1000'.$continue);
            if (isset($x['error'])) {
                return false;
            }
            foreach ($res['query']['pages'] as $p) {
				foreach ($p['categories'] as $x) {
					$pages[] = $x['title'];
				}
            }
            if (empty($res['query-continue']['categories']['clcontinue'])) {
                return $pages;
            } else {
                $continue = '&clcontinue='.urlencode($res['query-continue']['categories']['clcontinue']);
            }
        }
    }

    /**
     * Returns a list of pages that link to $page.
     * @param $page
     * @param $extra (defaults to null)
     * @return array
     **/
    function whatlinkshere ($page,$extra=null) {
        $continue = '';
        $pages = array();
        while (true) {
            $res = $this->query('?action=query&list=backlinks&bltitle='.urlencode($page).'&bllimit=1000&format=php'.$continue.$extra);
            if (isset($res['error'])) {
                return false;
            }
            foreach ($res['query']['backlinks'] as $x) {
                $pages[] = $x['title'];
            }
            if (empty($res['query-continue']['backlinks']['blcontinue'])) {
                return $pages;
            } else {
                $continue = '&blcontinue='.urlencode($res['query-continue']['backlinks']['blcontinue']);
            }
        }
    }

    /**
    * Returns a list of pages that include the image.
    * @param $image
    * @param $extra (defaults to null)
    * @return array
    **/
    function whereisincluded ($image,$extre=null) {
        $continue = '';
        $pages = array();
        while (true) {
            $res = $this->query('?action=query&list=imageusage&iutitle='.urlencode($image).'&iulimit=500&format=php'.$continue.$extra);
            if (isset($res['error']))
                return false;
            foreach ($res['query']['imageusage'] as $x) {
                $pages[] = $x['title'];
            }
            if (empty($res['query-continue']['imageusage']['iucontinue']))
                return $pages;
            else
                $continue = '&iucontinue='.urlencode($res['query-continue']['imageusage']['iucontinue']);
        }
    }
    
    /**
    * Returns a list of pages that use the $template.
    * @param $template the template we are intereste into
    * @param $extra (defaults to null)
    * @return array
    **/
    function whatusethetemplate ($template,$extra=null) {
        $continue = '';
        $pages = array();
        while (true) {
            $res = $this->query('?action=query&list=embeddedin&eititle=Template:'.urlencode($template).'&eilimit=500&format=php'.$continue.$extra);
            if (isset($res['error'])) {
                return false;
            }
            foreach ($res['query']['embeddedin'] as $x) {
                $pages[] = $x['title'];
            }
            if (empty($res['query-continue']['embeddedin']['eicontinue'])) {
                return $pages;
            } else {
                $continue = '&eicontinue='.urlencode($res['query-continue']['embeddedin']['eicontinue']);
            }
         }
     }
    
    /**
     * Returns an array with all the subpages of $page
     * @param $page
     * @return array
     **/
    function subpages ($page) {
        /* Calculate all the namespace codes */
        $ret = $this->query('?action=query&meta=siteinfo&siprop=namespaces&format=php');
        foreach ($ret['query']['namespaces'] as $x) {
            $namespaces[$x['*']] = $x['id'];
        }
        $temp = explode(':',$page,2);
        $namespace = $namespaces[$temp[0]];
        $title = $temp[1];
        $continue = '';
        $subpages = array();
        while (true) {
            $res = $this->query('?action=query&format=php&list=allpages&apprefix='.urlencode($title).'&aplimit=500&apnamespace='.$namespace.$continue);
            if (isset($x[error])) {
                return false;
            }
            foreach ($res['query']['allpages'] as $p) {
                $subpages[] = $p['title'];
            }
            if (empty($res['query-continue']['allpages']['apfrom'])) {
                return $subpages;
            } else {
                $continue = '&apfrom='.urlencode($res['query-continue']['allpages']['apfrom']);
            }
        }
    }

    /**
     * This function takes a username and password and logs you into wikipedia.
     * @param $user Username to login as.
     * @param $pass Password that corrisponds to the username.
     * @return array
     **/
    function login ($user,$pass) {
        $post = array('lgname' => $user, 'lgpassword' => $pass);
        $ret = $this->query('?action=login&format=php',$post);
        /* This is now required - see https://bugzilla.wikimedia.org/show_bug.cgi?id=23076 */
        if ($ret['login']['result'] == 'NeedToken') {
            $post['lgtoken'] = $ret['login']['token'];
            $ret = $this->query( '?action=login&format=php', $post );
        }
        if ($ret['login']['result'] != 'Success') {
            echo "Login error: \n";
            print_r($ret);
            die();
        } else {
			$this->loggedin = true;
            return $ret;
        }
    }

    /* crappy hack to allow users to use cookies from old sessions */
    function setLogin($data) {
        $this->http->cookie_jar = array(
        $data['cookieprefix'].'UserName' => $data['lgusername'],
        $data['cookieprefix'].'UserID' => $data['lguserid'],
        $data['cookieprefix'].'Token' => $data['lgtoken'],
        $data['cookieprefix'].'_session' => $data['sessionid'],
        );
    }

    /**
     * Check if we're allowed to edit $page.
     * See http://en.wikipedia.org/wiki/Template:Bots
     * for more info.
     * @param $page The page we want to edit.
     * @param $user The bot's username.
     * @return bool
     **/
    function nobots ($page,$user=null,$text=null) {
        if ($text == null) {
            $text = $this->getpage($page);
        }
		if (preg_match('/\{\{(nobots|bots\|allow=none|bots\|deny=all|bots\|optout=all|bots\|deny=.*?'.preg_quote($user,'/').'.*?)\}\}/iS',$text))
			return false;
		if (preg_match('/\{\{(bots\|allow=all|bots\|allow=.*?'.preg_quote($user,'/').'.*?)\}\}/iS', $text))
			return true;
		if (preg_match('/\{\{(bots\|allow=.*?)\}\}/iS', $text))
			return false;
		return true;
    }

    /**
     * This function returns the edit token for the current user.
     * @return edit token.
     **/
    function getedittoken () {
        $x = $this->query('?action=query&prop=info&intoken=edit&titles=Main%20Page&format=php');
        foreach ($x['query']['pages'] as $ret) {
            return $ret['edittoken'];
        }
    }

    /**
     * Purges the cache of $page.
     * @param $page The page to purge.
     * @return Api result.
     **/
    function purgeCache($page) {
        return $this->query('?action=purge&titles='.urlencode($page).'&format=php');
    }

    /**
     * Checks if $user has email enabled.
     * Uses index.php.
     * @param $user The user to check.
     * @return bool.
     **/
    function checkEmail($user) {
        $x = $this->query('?action=query&meta=allmessages&ammessages=noemailtext|notargettext&amlang=en&format=php');
        $messages[0] = $x['query']['allmessages'][0]['*'];
        $messages[1] = $x['query']['allmessages'][1]['*'];
        $page = $this->http->get(str_replace('api.php','index.php',$this->url).'?title=Special:EmailUser&target='.urlencode($user));
        if (preg_match('/('.preg_quote($messages[0],'/').'|'.preg_quote($messages[1],'/').')/i',$page)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns all the pages $page is transcluded on.
     * @param $page The page to get the transclusions from.
     * @param $sleep The time to sleep between requets (set to null to disable).
     * @return array.
     **/
    function getTransclusions($page,$sleep=null,$extra=null) {
        $continue = '';
        $pages = array();
        while (true) {
            $ret = $this->query('?action=query&list=embeddedin&eititle='.urlencode($page).$continue.$extra.'&eilimit=500&format=php');
            if ($sleep != null) {
                sleep($sleep);
            }
            foreach ($ret['query']['embeddedin'] as $x) {
                $pages[] = $x['title'];
            }
            if (isset($ret['query-continue']['embeddedin']['eicontinue'])) {
                $continue = '&eicontinue='.$ret['query-continue']['embeddedin']['eicontinue'];
            } else {
                return $pages;
            }
        }
    }

    /**
     * Edits a page.
     * @param $page Page name to edit.
     * @param $data Data to post to page.
     * @param $summary Edit summary to use.
     * @param $minor Whether or not to mark edit as minor.  (Default false)
     * @param $bot Whether or not to mark edit as a bot edit.  (Default true)
     * @return api result
     **/
    function edit ($page,$data,$summary = '',$minor = false,$bot = true,$section = null,$detectEC=false,$maxlag='') {
		if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'title' => $page,
            'text' => $data,
            'token' => $this->token,
            'summary' => $summary,
            ($minor?'minor':'notminor') => '1',
            ($bot?'bot':'notbot') => '1'
        );
        if ($section != null) {
            $params['section'] = $section;
        }
        if ($this->ecTimestamp != null && $detectEC == true) {
            $params['basetimestamp'] = $this->ecTimestamp;
            $this->ecTimestamp = null;
        }
        if ($maxlag!='') {
            $maxlag='&maxlag='.$maxlag;
        }
		$sleep=10;
		do{
        $return = $this->query('?action=edit&format=php'.$maxlag,$params);
		//if we didnt get a maxlag error we are done
		//print_r($return);
		if(isset($return['success'])){ if($return['success'] == 1){
			return $return;
		}}
		else if($return['error']['code'] != "maxlag"){
			return $return;
		}
		if($sleep < 30) {$sleep = $sleep + 1;}
		echo "\nSleeping for $sleep due to Maxlag!\n";
		sleep($sleep);
		} while(true);
    }
	
	/**
     * Edits a page on a sepcific lang wiki
     * @param $page Page name to edit.
     * @param $data Data to post to page.
     * @param $summary Edit summary to use.
     * @param $minor Whether or not to mark edit as minor.  (Default false)
     * @param $bot Whether or not to mark edit as a bot edit.  (Default true)
     * @return api result
     **/
    function langedit ($lang,$page,$data,$summary = '',$minor = false,$bot = true,$section = null,$detectEC=false,$maxlag='') {
	
		//CHANGE URL TO NEW URL
		$cacheurl = $this->url;
		$this->url = 'http://'.str_replace("_","-",$lang).'.wikipedia.org/w/api.php';
	
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'title' => $page,
            'text' => $data,
            'token' => $this->token,
            'summary' => $summary,
            ($minor?'minor':'notminor') => '1',
            ($bot?'bot':'notbot') => '1'
        );
        if ($section != null) {
            $params['section'] = $section;
        }
        if ($this->ecTimestamp != null && $detectEC == true) {
            $params['basetimestamp'] = $this->ecTimestamp;
            $this->ecTimestamp = null;
        }
        if ($maxlag!='') {
            $maxlag='&maxlag='.$maxlag;
        }
		$sleep=10;
		do{
        $return = $this->query('?action=edit&format=php'.$maxlag,$params);
		//if we didnt get a maxlag error we are done
		if($return['error']['code'] != "maxlag"){
		
			//SET URL BACK
			$this->url = $cacheurl;
			
			return $return;
		}
		if($sleep < 30) {$sleep = $sleep + 1;}
		echo "\nSleeping for $sleep due to Maxlag!\n";
		sleep($sleep);
		} while(true);
    }

    /**
    * Add a text at the bottom of a page
    * @param $page The page we're working with.
    * @param $text The text that you want to add.
    * @param $summary Edit summary to use.
    * @param $minor Whether or not to mark edit as minor.  (Default false)
    * @param $bot Whether or not to mark edit as a bot edit.  (Default true)
    * @return api result
    **/
    function addtext( $page, $text, $summary = '', $minor = false, $bot = true )
    {
        $data = $this->getpage( $page );
        $data.= "\n" . $text;
        return $this->edit( $page, $data, $summary, $minor, $bot );
    }
    
    /**
     * Moves a page.
     * @param $old Name of page to move.
     * @param $new New page title.
     * @param $reason Move summary to use.
     * @param $movetalk Move the page's talkpage as well.
     * @return api result
     **/
    function move ($old,$new,$reason,$options=null) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'from' => $old,
            'to' => $new,
            'token' => $this->token,
            'reason' => $reason
        );
        if ($options != null) {
            $option = explode('|',$options);
            foreach ($option as $o) {
                $params[$o] = true;
            }
        }
        return $this->query('?action=move&format=php',$params);
    }

    /**
     * Rollback an edit.
     * @param $title Title of page to rollback.
     * @param $user Username of last edit to the page to rollback.
     * @param $reason Edit summary to use for rollback.
     * @param $bot mark the rollback as bot.
     * @return api result
     **/
    function rollback ($title,$user,$reason=null,$bot=false) {
        $ret = $this->query('?action=query&prop=revisions&rvtoken=rollback&titles='.urlencode($title).'&format=php');
        foreach ($ret['query']['pages'] as $x) {
            $token = $x['revisions'][0]['rollbacktoken'];
            break;
        }
        $params = array(
            'title' => $title,
            'user' => $user,
            'token' => $token
        );
        if ($bot) {
            $params['markbot'] = true;
        }
        if ($reason != null) { $params['summary'] = $reason; }
            return $this->query('?action=rollback&format=php',$params);
        }
		
    /**
     * Blocks a user.
     * @param $user The user to block.
     * @param $reason The block reason.
     * @param $expiry The block expiry.
     * @param $options a piped string containing the block options.
     * @return api result
     **/
    function block ($user,$reason='vand',$expiry='infinite',$options=null,$retry=true) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'expiry' => $expiry,
            'user' => $user,
            'reason' => $reason,
            'token' => $this->token
        );
        if ($options != null) {
            $option = explode('|',$options);
            foreach ($option as $o) {
                $params[$o] = true;
            }
        }
        $ret = $this->query('?action=block&format=php',$params);
        /* Retry on a failed token. */
        if ($retry and $ret['error']['code']=='badtoken') {
            $this->token = $this->getedittoken();
            return $this->block($user,$reason,$expiry,$options,false);
        }
        return $ret;
    }

    /**
     * Unblocks a user.
     * @param $user The user to unblock.
     * @param $reason The unblock reason.
     * @return api result
     **/
    function unblock ($user,$reason) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'user' => $user,
            'reason' => $reason,
            'token' => $this->token
        );
        return $this->query('?action=unblock&format=php',$params);
    }

    /**
     * Emails a user.
     * @param $target The user to email.
     * @param $subject The email subject.
     * @param $text The body of the email.
     * @param $ccme Send a copy of the email to the user logged in.
     * @return api result
     **/
    function email ($target,$subject,$text,$ccme=false) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'target' => $target,
            'subject' => $subject,
            'text' => $text,
            'token' => $this->token
        );
        if ($ccme) {
            $params['ccme'] = true;
        }
        return $this->query('?action=emailuser&format=php',$params);
    }

    /**
     * Deletes a page.
     * @param $title The page to delete.
     * @param $reason The delete reason.
     * @return api result
     **/
    function delete ($title,$reason) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'title' => $title,
            'reason' => $reason,
            'token' => $this->token
        );
        return $this->query('?action=delete&format=php',$params);
    }
	
	/**
     * Deletes a page.
     * @param $pageid Page id of the page
     * @param $reason The delete reason.
     * @return api result
     **/
    function deletepageid ($pageid,$reason) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'pageid' => $pageid,
            'reason' => $reason,
            'token' => $this->token
        );
        return $this->query('?action=delete&format=php',$params);
    }

    /**
     * Undeletes a page.
     * @param $title The page to undelete.
     * @param $reason The undelete reason.
     * @return api result
     **/
    function undelete ($title,$reason) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'title' => $title,
            'reason' => $reason,
            'token' => $this->token
        );
        return $this->query('?action=undelete&format=php',$params);
    }
	
	/**
     * Undeletes a page.
     * @param $title The page to undelete.
     * @param $reason The undelete reason.
     * @return api result
     **/
    function undeletepageid ($pageid,$reason) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'pageid' => $pageid,
            'reason' => $reason,
            'token' => $this->token
        );
        return $this->query('?action=undelete&format=php',$params);
    }

    /**
     * (Un)Protects a page.
     * @param $title The page to (un)protect.
     * @param $protections The protection levels (e.g. 'edit=autoconfirmed|move=sysop')
     * @param $expiry When the protection should expire (e.g. '1 day|infinite')
     * @param $reason The (un)protect reason.
     * @param $cascade Enable cascading protection? (defaults to false)
     * @return api result
     **/
    function protect ($title,$protections,$expiry,$reason,$cascade=false) {
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        $params = array(
            'title' => $title,
            'protections' => $protections,
            'expiry' => $expiry,
            'reason' => $reason,
            'token' => $this->token
        );
        if ($cascade) {
            $params['cascade'] = true;
        }
        return $this->query('?action=protect&format=php',$params);
    }

    /**
     * Uploads an image.
     * @param $page The destination file name.
     * @param $file The local file path.
     * @param $desc The upload discrption (defaults to '').
     **/
     function upload ($page,$file,$desc='') {
        if ($this->token == null) {
                $this->token = $this->getedittoken();
        }
        $params = array(
                'filename'        => $page,
                'comment'         => $desc,
                'text'            => $desc,
                'token'           => $this->token,
                'ignorewarnings'  => '1',
                'file'            => '@'.$file
        );
        return $this->query('?action=upload&format=php',$params);
     }
    
    /*
    $page - page
    $revs - rev ids to delete (seperated with ,)
    $comment - delete comment
    */
    function revdel ($page,$revs,$comment) {
        
        if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
        
        $post = array(
            'wpEditToken'       => $this->token,
            'ids' => $revs,
            'target' => $page,
            'type' => 'revision',
            'wpHidePrimary' => 1,
            'wpHideComment' => 1,
            'wpHideUser' => 0,
            'wpRevDeleteReasonList' => 'other',
            'wpReason' => $comment,
            'wpSubmit' => 'Apply to selected revision(s)'
        );
        return $this->http->post(str_replace('api.php','index.php',$this->url).'?title=Special:RevisionDelete&action=submit',$post);
    }

    /**
     * Creates a new account.
     * Uses index.php as there is no api to create accounts yet :(
     * @param $username The username the new account will have.
     * @param $password The password the new account will have.
     * @param $email The email the new account will have.
     **/
    function createaccount ($username,$password,$email=null) {
        $post = array(
            'wpName' => $username,
            'wpPassword' => $password,
            'wpRetype' => $password,
            'wpEmail' => $email,
            'wpRemember' => 0,
            'wpIgnoreAntiSpoof' => 0,
            'wpCreateaccount' => 'Create account',
        );
        return $this->http->post(str_replace('api.php','index.php',$this->url).'?title=Special:UserLogin&action=submitlogin&type=signup',$post);
    }

    /**
     * Changes a users rights.
     * @param $user   The user we're working with.
     * @param $add    A pipe-separated list of groups you want to add.
     * @param $remove A pipe-separated list of groups you want to remove.
     * @param $reason The reason for the change (defaults to '').
     **/
    function userrights ($user,$add,$remove,$reason='') {
        // get the userrights token
        $token = $this->query('?action=query&list=users&ususers='.urlencode($user).'&ustoken=userrights&format=php');
        $token = $token['query']['users'][0]['userrightstoken'];
        $params = array(
            'user' => $user,
            'token' => $token,
            'add' => $add,
            'remove' => $remove,
            'reason' => $reason
        );
        return $this->query('?action=userrights&format=php',$params);
    }
    
    /**
     * Gets the number of images matching a particular sha1 hash.
     * @param $hash The sha1 hash for an image.
     * @return The number of images with the same sha1 hash.
     **/
    function imagematches ($hash) {
        $x = $this->query('?action=query&list=allimages&format=php&aisha1='.$hash);
        return count($x['query']['allimages']);
    }

    /**  BMcN 2012-09-16
     * Retrieve a media file's actual location.
     * @param $page The "File:" page on the wiki which the URL of is desired.
     * @return The URL pointing directly to the media file (Eg http://upload.mediawiki.org/wikipedia/en/1/1/Example.jpg)
     **/
    function getfilelocation ($page) {
        $x = $this->query('?action=query&format=php&prop=imageinfo&titles='.urlencode($page).'&iilimit=1&iiprop=url');
        foreach ($x['query']['pages'] as $ret ) {
            if (isset($ret['imageinfo'][0]['url'])) {
                return $ret['imageinfo'][0]['url'];
            } else
                return false;
        }
    }
 
    /**  BMcN 2012-09-16
     * Retrieve a media file's uploader.
     * @param $page The "File:" page
     * @return The user who uploaded the topmost version of the file.
     **/
    function getfileuploader ($page) {
        $x = $this->query('?action=query&format=php&prop=imageinfo&titles='.urlencode($page).'&iilimit=1&iiprop=user');
        foreach ($x['query']['pages'] as $ret ) {
            if (isset($ret['imageinfo'][0]['user'])) {
                return $ret['imageinfo'][0]['user'];
            } else
                return false;
        }
    }
	
	/**
     * Gets the date of the last edit the user has made
     * @param $user The username for which to get the last edit
     * @return The timestamp of the last edit e.g. 2012-02-28T13:10:12Z
	 * @author Addshore
     **/
    function lastedit ($user) {
        $x = $this->query('?action=query&list=usercontribs&uclimit=1&format=php&acuser='.urlencode($user));
        return $x['query']['usercontribs'][0]['timestamp'];
    }
	
	/**
     * Gets the time stamp of the last edit a user made on an article
     * @param $user The username for which to get the last edit
	 * @param $page The page we want to check
     * @return The timestamp of the last edit e.g. 2012-02-28T13:10:12Z
	 * @author Addshore
     **/
	function lasteditonpage ($user,$page) {
		$x = $this->query('?action=query&prop=revisions&format=php&titles='.urlencode($page).'&rvlimit=1&rvuser='.urlencode($user));
		return $x['query']['pages'][0]['revisions'][0]['timestamp'];
	}
	
	function pageidhistory ($pageid) {
		$x = $this->query('?action=query&prop=revisions&rvlimit=50&pageids='.urlencode($pageid).'&rvprop=user|comment|size&format=php');
		return $x['query']['pages'][$pageid]['revisions'];
	}
	
	/**
     * Gets the date of the last edit the user has made
     * @param $user The username for which to get the first edit
     * @return The timestamp of the last edit e.g. 2012-02-28T13:10:12Z
	 * @author Addshore
     **/
    function firstedit ($user) {
        $x = $this->query('?action=query&list=usercontribs&uclimit=1&format=php&acuser='.urlencode($user));
        return $x['query-continue'][0]['ucstart'];
    }
	
	/**
     * Gets the date of the last edit the user has made
     * @param $page The name of the page to be checks
     * @return Array of each protection status ([0]/[1]/[2])([type],[level],[expiry])
	 * @author Addshore
     **/
	function protectionstatus ($page){
	//http://en.wikipedia.org/w/api.php?action=query&titles=Cyprus&prop=info&inprop=protection
	    $x = $this->query('?action=query&format=php&prop=info&inprop=protection&titles='.urlencode($page));
		foreach ($x['query']['pages'] as $page)
		{
			return $page['protection'];
		}
	
	}
	
	/**
     * Gets the date of the last edit the user has made
     * @param $page The name of the page to be checked
     * @return Array of entities the page can be found in on wikidata
	 * @author Addshore
     **/
	function wikidatasitelinks ($page,$lang = "enwiki"){
		$x = $this->http->get("http://wikidata.org/w/api.php?action=wbgetentities&sites=".urlencode($lang)."&props=sitelinks&format=php&titles=".urlencode($page));
		$x = unserialize($x);
		if($x['success'] == 1)
		{
			return $x['entities'];
		}
	}
	
}

/**
 * This class extends the wiki class to provide an high level API for the most commons actions.
 * @author Fale
 **/
class extended extends wikipedia
{
    /**
     * Add a category to a page
     * @param $page The page we're working with.
     * @param $category The category that you want to add.
     * @param $summary Edit summary to use.
     * @param $minor Whether or not to mark edit as minor.  (Default false)
     * @param $bot Whether or not to mark edit as a bot edit.  (Default true)
     * @return api result
     **/
    function addcategory( $page, $category, $summary = '', $minor = false, $bot = true )
    {
        $data = $this->getpage( $page );
        $data.= "\n[[Category:" . $category . "]]";
        return $this->edit( $page, $data, $summary, $minor, $bot );
    }

    /**
     * Find a string
     * @param $page The page we're working with.
     * @param $string The string that you want to find.
     * @return bool value (1 found and 0 not-found)
     **/
    function findstring( $page, $string )
    {
        $data = $this->getpage( $page );
        if( strstr( $data, $string ) )
            return 1;
        else
            return 0;
    }

    /**
     * Replace a string
     * @param $page The page we're working with.
     * @param $string The string that you want to replace.
     * @param $newstring The string that will replace the present string.
     * @return the new text of page
     **/
    function replacestring( $page, $string, $newstring )
    {
        $data = $this->getpage( $page );
        return str_replace( $string, $newstring, $data );
    }
    
    /**
     * Get a template from a page
     * @param $page The page we're working with
     * @param $template The name of the template we are looking for
     * @return the searched (NULL if the template has not been found)
     **/
    function gettemplate( $page, $template ) {
       $data = $this->getpage( $page );
       $template = preg_quote( $template, " " );
       $r = "/{{" . $template . "(?:[^{}]*(?:{{[^}]*}})?)+(?:[^}]*}})?/i";
       preg_match_all( $r, $data, $matches );
       if( isset( $matches[0][0] ) )
           return $matches[0][0];
       else
           return NULL;
     }    
}

/**
 * This class extends the wiki class to provide an high level API for the most commons actions.
 * @author Sean
 */
class lyricwiki extends extended
{
	/**
	 * Constructor. Note that the parameters are in different order (primarily because it
	 * is very unlikely the user will want to change the URL of LyricWiki, and fairly likely
	 * that they may want to set a username/password).
	 *
	 * If not logging in, the caller can safely call this with no parameters.
	 */
	function __construct ($hu=null, $hp=null, $url='http://lyrics.wikia.com/api.php') {
		parent::__construct($url,$hu,$hp);
	}

	/**
	 * Returns basic info about a song as well as a fair-use snippet of
	 * lyrics and a link to the LyricWiki page where the full lyrics can
	 * be viewed (full lyrics are no longer available via the API because
	 * of licensing issues - publishers won't allow that for free).
	 *
	 * @return associative array containing the song data. (TODO: Document what happens on No Match)
	 */
	function getSong($artist, $song){
		return $this->query('?action=lyrics&func=getSong&fmt=php&artist='.urlencode($artist).'&song='.urlencode($song));
	} // end getSong()

	/**
	 * Returns basic info about an artist as well as their
	 * entire discography.
	 *
	 * @param - artist - a string containing the name of the artist (spaces or underscores are fine)
	 * @return an associative array containing the artist data (TODO: Document what happens on No Match)
	 */
	function getArtist($artist){
		return $this->query('?action=lyrics&func=getArtist&fmt=php&artist='.urlencode($artist));
	} // end getArtist()
	
	/**
	 * Returns hometown information (country, state/province, hometown) for
	 * the given artist if it can be found.
	 *
	 *
	 * @param - artist - a string containing the name of the artist (spaces or underscores are fine)
	 * @return an associative array whose keys are 'country', 'state', and 'hometown'. If the
	 * artist's hometown could not be found, then the values corresponding to those keys will
	 * be empty strings.
	 */
	function getHometown($artist){
		return $this->query('?action=lyrics&func=getHometown&fmt=php&artist='.urlencode($artist));
	} // end getHometown()
	
	/**
	 * Gets information about the Song Of The Day (SOTD).
	 *
	 * @return an associative array representing the current Song of the Day. The
	 * fields returned include {artist, song, lyrics (just a snippet), url of song,
	 * page_namespace, page_id, isOnTakedownList, nominatedBy, reason}
	 */
	function getSotd(){
		return $this->query('?action=lyrics&func=getSotd&fmt=php');
	} // end getSotd()

	/**
         * Returns information about the currently most-popular songs.
	 *
	 * @return an array containing associative arrays of the data for each top song.
	 */
	function getTopSongs(){
		return $this->query('?action=lyrics&func=getTopSongs&fmt=php');
	} // end getTopSongs()

	/**
	 * Allows easy submission of lyrics for a new song to LyricWiki.
	 *
	 * @param artist - the artist of the song
	 * @param song - the name of the song (following page naming conventions: http://lyrics.wikia.com/LW:PN ).
	 * @param lyrics - the full lyrics to submit
	 * @param language - optional string for the language (the language name should be in English - eg: "Swedish" not "Svenska").
	 * @param onAlbums - an array which contains strings which are album titles.
	 * @param overwriteIfExists - true means that this page data (the lyrics and onAlbums) will overwrite
	 *                     the page if it already exists. false means that nothing will be changed if the
	 *                     page already exists.
	 */
	function postSong($artist, $song, $lyrics, $language="", $onAlbums=array(), $overwriteIfExists=false){
		$params = array(
            		'artist' => $artist,
            		'song' => $song,
           		 'lyrics' => $lyrics,
			'overwriteIfExists' => ($overwriteIfExists ? "1" : "0"),
        	);
		if(!empty($language)){
			$params['language'] = $language;
		}
		if(count($onAlbums) > 0){
			$params['onAlbums'] = implode("|", $onAlbums);
		}
        	return $this->query('?action=lyrics&func=postSong&fmt=php',$params);
	} // end postSong()

} // end class lyricwiki

class wikidata extends extended
{

	/**
     * Links two articles together on wikidata
	 * @author Addshore
     **/
	 //http://www.wikidata.org/w/api.php?action=wblinktitles&fromsite=enwiki&fromtitle=Hydrogen&tosite=dewiki&totitle=Wasserstoff
	function linktitles ($flang,$ftitle,$tlang,$ttitle){
	    if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
	    $post = array(
            'token'       => $this->token,
            'fromsite' => $flang."wiki",
            'fromtitle' => $ftitle,
            'tosite' => $tlang."wiki",
            'totitle' => $ttitle
        );
		return $this->query('?action=wblinktitles&format=php',$post);

	}
	
	function editentity ($data,$id="",$site="",$title=""){
	    if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
	    $post = array(
            'token'       => $this->token,
			'bot'  => "1",
            'data' => json_encode($data)
        );
		
		$more = "";
		if($id != "")
		{
			$more = "&id=$id";
		}
		else if ($site != "" && $title != "")
		{
			$more = "&site=$site&title=$title";
		}
		
		$x = $this->http->post($this->url.'?action=wbeditentity&format=json'.$more,$post);
		return json_decode($x,true);
    }
	
	function overwriteentity ($data,$id="",$site="",$title=""){
	    if ($this->token==null) {
            $this->token = $this->getedittoken();
        }
	    $post = array(
            'token'       => $this->token,
			'bot'  => "1",
            'data' => json_encode($data),
			'clear' => "1"
        );
		
		$more = "";
		if($id != "")
		{
			$more = "&id=$id";
		}
		else if ($site != "" && $title != "")
		{
			$more = "&site=$site&title=$title";
		}
		
		$x = $this->http->post($this->url.'?action=wbeditentity&format=json'.$more,$post);
		return json_decode($x,true);
    }
	
	function getentity ($id){
		return $this->query('?action=wbgetentities&ids='.urlencode($id).'&format=php');
	}
	
	function getentityfor ($site,$title){
		return $this->query('?action=wbgetentities&sites='.urlencode($site).'&titles='.urlencode($title).'&format=php');
	}
	
	/*
	function setsitelink ($id,$lang,$title){
		$x = $this->http->post("http://wikidata.org/w/api.php?action=wblinktitles&id=".urlencode($id)."&site=".urlencode($lang)."wiki&title=".urlencode($title)."&format=php");
		$x = unserialize($x);
		return $x;
	}
	*/

}

 