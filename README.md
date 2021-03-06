Addwiki MediaWiki Framework
=======

This is the Addframe framework for Mediawiki sites
The framework is currently under development (feel free to hack along)!

If you have feature requests please file a bug

How to use
-------------

Take a look at some example scripts in scripts/HelloWorld to see basic use.

```php
use Addframe\Config;
use Addframe\Mediawiki\Family;
use Addframe\Mediaqiki\UserLogin;
require_once( dirname( __FILE__ ) . '/../Init.php' );

$wm = new Family(
	new UserLogin( Config::get( 'wikiuser', 'username'),
		Config::get( 'wikiuser', 'password') ), Config::get( 'wikiuser', 'home') );
$enwiki = $wm->getSite( 'en.wikipedia.org' );
$sandbox = $enwiki->newPageFromTitle( 'Wikipedia:Sandbox' );
$sandbox->wikiText->appendText( "\nThis is a simple edit to this page!" );
$sandbox->save( 'This is a simply summary');
```


Directory Structure
-------------

* Configs - For config files
* Includes - All framework classes and extensions are here
* Scripts - Scripts that use the framework
* Tests - Tests for the core framework

Tests
-------------

* The framework is tested using PHPUnit tests.
* The configuration file for the tests can be found at /tests/phpunit.xml
* The bootstrap file for the tests can be found at /Tests/Bootstrap.php
* On any push, branch or pull request Travis will run all tests
* If Travis reports failing tests please try to fix them ASAP
* https://travis-ci.org/addshore/addwiki/builds
* When writing new code please add tests for the code!