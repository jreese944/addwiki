<?
/*
include.php v 1.2 © 2007-08 Nikola Smolenski <smolensk@eunet.yu>
Modified for use on wmflabs by Addshore 2013

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

function head() {
?><html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>
<body>
<?
}

function foot() {
?>

</body>
</html><?
}

function printselect($name,$arr,$sel) {
	echo "<select name=\"$name\">\n";
	foreach($arr as $k=>$v) {
		echo "\t<option value=\"$k\"".
		     ($sel==$k?" selected":"").
		     ">$v</option>\n";
	}
	echo "</select>\n";
}

$lang=array(
	"aa"=>"Afar",
	"ab"=>"Аҧсуа",
	"af"=>"Afrikaans",
	"ak"=>"Akana",
	"als"=>"Alemannisch",
	"am"=>"አማርኛ",
	"an"=>"Aragonés",
	"ang"=>"Englisc",
	"arc"=>"ܐܪܡܝܐ",
	"ar"=>"العربية",
	"as"=>"অসমীয়া",
	"ast"=>"Asturianu",
	"av"=>"Авар",
	"ay"=>"Aymar",
	"az"=>"Azərbaycan",
	"bar"=>"Boarisch",
	"bat-smg"=>"Žemaitėška",
	"ba"=>"Башҡорт",
	"be-x-old"=>"Беларуская (тарашкевіца)",
	"be"=>"Беларуская",
	"bg"=>"Български",
	"bh"=>"भोजपुरी",
	"bi"=>"Bislama",
	"bm"=>"Bamanankan",
	"bn"=>"বাংলা",
	"bo"=>"བོད་སྐད",
	"bpy"=>"ইমার ঠার/বিষ্ণুপ্রিয়া মণিপুরী",
	"br"=>"Brezhoneg",
	"bs"=>"Bosanski",
	"bug"=>"Basa Ugi",
	"bxr"=>"Буряад",
	"ca"=>"Català",
	"cbk-zam"=>"Chavacano de Zamboanga",
	"cdo"=>"Mìng-dĕ̤ng-ngṳ̄",
	"ceb"=>"Sinugboanong Binisaya",
	"ce"=>"Нохчийн",
	"ch"=>"Chamoru",
	"cho"=>"Choctaw",
	"chr"=>"ᏣᎳᎩ",
	"chy"=>"Tsetsêhestâhese",
	"co"=>"Corsu",
	"cr"=>"Nehiyaw",
	"csb"=>"Kaszëbsczi",
	"cs"=>"Čeština",
	"cu"=>"Словѣньскъ",
	"cv"=>"Чăваш",
	"cy"=>"Cymraeg",
	"da"=>"Dansk",
	"de"=>"Deutsch",
	"diq"=>"Zazaki",
	"dv"=>"ދިވެހިބަސް",
	"dz"=>"ཇོང་ཁ",
	"ee"=>"Eʋegbe",
	"el"=>"Ελληνικά",
	"eml"=>"Emilià",
	"en"=>"English",
	"eo"=>"Esperanto",
	"es"=>"Español",
	"et"=>"Eesti",
	"eu"=>"Euskara",
	"fa"=>"فارسی",
	"ff"=>"Fulfulde",
	"fi"=>"Suomi",
	"fiu-vro"=>"Võro",
	"fj"=>"Na Vosa Vakaviti",
	"fo"=>"Føroyskt",
	"fr"=>"Français",
	"frp"=>"Arpitan",
	"fur"=>"Furlan",
	"fy"=>"Frysk",
	"ga"=>"Gaeilge",
	"gd"=>"Gàidhlig",
	"gl"=>"Galego",
	"glk"=>"گیلکی",
	"gn"=>"Avañe'ẽ",
	"got"=>"𐌲𐌿𐍄𐌹𐍃𐌺",
	"gu"=>"ગુજરાતી",
	"gv"=>"Gaelg",
	"hak"=>"Hak-kâ-fa / 客家話",
	"haw"=>"Hawai`i",
	"ha"=>"هَوُسَ",
	"he"=>"עברית",
	"hi"=>"हिन्दी",
	"ho"=>"Hiri Motu",
	"hr"=>"Hrvatski",
	"hsb"=>"Hornjoserbsce",
	"ht"=>"Krèyol ayisyen",
	"hu"=>"Magyar",
	"hy"=>"Հայերեն",
	"hz"=>"Otsiherero",
	"ia"=>"Interlingua",
	"id"=>"Bahasa Indonesia",
	"ie"=>"Interlingue",
	"ig"=>"Igbo",
	"ii"=>"ꆇꉙ",
	"ik"=>"Iñupiak",
	"ilo"=>"Ilokano",
	"io"=>"Ido",
	"is"=>"Íslenska",
	"it"=>"Italiano",
	"iu"=>"ᐃᓄᒃᑎᑐᑦ",
	"ja"=>"日本語",
	"jbo"=>"Lojban",
	"jv"=>"Basa Jawa",
	"ka"=>"ქართული",
	"kab"=>"Taqbaylit",
	"kg"=>"KiKongo",
	"ki"=>"Gĩkũyũ",
	"kj"=>"Kuanyama",
	"kk"=>"Қазақша",
	"kl"=>"Kalaallisut",
	"km"=>"ភាសាខ្មែរ",
	"kn"=>"ಕನ್ನಡ",
	"ko"=>"한국어",
	"kr"=>"Kanuri",
	"ksh"=>"Ripoarisch",
	"ks"=>"कश्मीरी / كشميري",
	"ku"=>"Kurdî / كوردی",
	"kv"=>"Коми",
	"kw"=>"Kernewek/Karnuack",
	"ky"=>"Кыргызча",
	"lad"=>"Dzhudezmo",
	"la"=>"Latina",
	"lbe"=>"Лакку",
	"lb"=>"Lëtzebuergesch",
	"lg"=>"Luganda",
	"lij"=>"Líguru",
	"li"=>"Limburgs",
	"lmo"=>"Lumbaart",
	"ln"=>"Lingala",
	"lo"=>"ລາວ",
	"lt"=>"Lietuvių",
	"lv"=>"Latviešu",
	"map-bms"=>"Basa Banyumasan",
	"mg"=>"Malagasy",
	"mh"=>"Ebon",
	"mi"=>"Māori",
	"mk"=>"Македонски",
	"ml"=>"മലയാളം",
	"mn"=>"Монгол",
	"mo"=>"Молдовеняскэ",
	"mr"=>"मराठी",
	"ms"=>"Bahasa Melayu",
	"mt"=>"Malti",
	"mus"=>"Muskogee",
	"my"=>"မ္ရန္‌မာစာ",
	"mzn"=>"مَزِروني",
	"na"=>"dorerin Naoero",
	"nah"=>"Nāhuatl",
	"nap"=>"Nnapulitano",
	"nds-nl"=>"Nedersaksisch",
	"nds"=>"Plattdüütsch",
	"ne"=>"नेपाली",
	"new"=>"नेपाल भाषा",
	"ng"=>"Oshiwambo",
	"nl"=>"Nederlands",
	"nn"=>"Nynorsk",
	"no"=>"Norsk (Bokmål)",
	"nov"=>"Novial",
	"nrm"=>"Nouormand/Normaund",
	"nv"=>"Diné bizaad",
	"ny"=>"Chi-Chewa",
	"oc"=>"Occitan",
	"om"=>"Oromoo",
	"or"=>"ଓଡ଼ିଆ",
	"os"=>"Иронау",
	"pa"=>"ਪੰਜਾਬੀ",
	"pag"=>"Pangasinan",
	"pam"=>"Kapampangan",
	"pap"=>"Papiamentu",
	"pdc"=>"Deitsch",
	"pi"=>"पाऴि",
	"pih"=>"Norfuk",
	"pl"=>"Polski",
	"pms"=>"Piemontèis",
	"ps"=>"پښتو",
	"pt"=>"Português",
	"qu"=>"Runa Simi",
	"rm"=>"Rumantsch",
	"rmy"=>"romani - रोमानी",
	"rn"=>"Kirundi",
	"roa-rup"=>"Armãneashce",
	"roa-tara"=>"Tarandíne",
	"ro"=>"Română",
	"ru-sib"=>"Сибирской",
	"ru"=>"Русский",
	"rw"=>"Ikinyarwanda",
	"sa"=>"संस्कृतम्",
	"scn"=>"Sicilianu",
	"sco"=>"Scots",
	"sc"=>"Sardu",
	"sd"=>"سنڌي، سندھی ، सिन्ध",
	"se"=>"Sámegiella",
	"sg"=>"Sängö",
	"sh"=>"Srpskohrvatski / Српскохрватски",
	"si"=>"සිංහල",
	"simple"=>"Simple English",
	"sk"=>"Slovenčina",
	"sl"=>"Slovenščina",
	"sm"=>"Gagana Samoa",
	"sn"=>"chiShona",
	"so"=>"Soomaaliga",
	"sq"=>"Shqip",
	"sr"=>"Српски / Srpski",
	"ss"=>"SiSwati",
	"st"=>"Sesotho",
	"su"=>"Basa Sunda",
	"sv"=>"Svenska",
	"sw"=>"Kiswahili",
	"ta"=>"தமிழ்",
	"te"=>"తెలుగు",
	"tet"=>"Tetun",
	"tg"=>"Тоҷикӣ",
	"th"=>"ไทย",
	"ti"=>"ትግርኛ",
	"tk"=>"تركمن / Туркмен",
	"tlh"=>"tlhIngan-Hol",
	"tl"=>"Tagalog",
	"tn"=>"Setswana",
	"to"=>"faka Tonga",
	"tokipona"=>"Tokipona",
	"tpi"=>"Tok Pisin",
	"tr"=>"Türkçe",
	"ts"=>"Xitsonga",
	"tt"=>"Tatarça / Татарча",
	"tum"=>"chiTumbuka",
	"tw"=>"Twi",
	"ty"=>"Reo Mā`ohi",
	"udm"=>"Удмурт кыл",
	"ug"=>"Oyghurque",
	"uk"=>"Українська",
	"ur"=>"اردو",
	"uz"=>"Ўзбек",
	"vec"=>"Vèneto",
	"ve"=>"Tshivenda",
	"vi"=>"Tiếng Việt",
	"vls"=>"West-Vlams",
	"vo"=>"Volapük",
	"war"=>"Winaray",
	"wa"=>"Walon",
	"wo"=>"Wolof",
	"wuu"=>"吴语",
	"xal"=>"Хальмг",
	"xh"=>"isiXhosa",
	"yi"=>"ייִדיש",
	"yo"=>"Yorùbá",
	"za"=>"Cuengh",
	"zea"=>"Zeêuws",
	"zh-classical"=>"古文 / 文言文",
	"zh-min-nan"=>"Bân-lâm-gú",
	"zh-yue"=>"粵語",
	"zh"=>"中文",
	"zu"=>"isiZulu",
);

$wiki=array(
	"wiki"=>"Wikipedia",
	"wiktionary"=>"Wiktionary",
	"wikinews"=>"Wikinews",
	"wikisource"=>"Wikisource",
	"wikiquote"=>"Wikiquote",
	"wikibooks"=>"Wikibooks",
	"wikiversity"=>"Wikiversity",
	"wikimedia"=>"Wikimedia",
//	"commons"=>Commons,
);

$namespace=array(
//	-1	=>	'all',
	0	=>	'main',
	1	=>	'Talk',
	2	=>	'User',
	3	=>	'User talk',
	4	=>	'Project',
	5	=>	'Project talk',
	6	=>	'Media',
	7	=>	'Media talk',
	8	=>	'MediaWiki',
	9	=>	'MediaWiki talk',
	10	=>	'Template',
	11	=>	'Template talk',
	12	=>	'Help',
	13	=>	'Help talk',
	14	=>	'Category',
	15	=>	'Category talk',
);

?>
