<?php
/**
 * UTF8 support in PHP5.
 * PHP5 UTF8 is a UTF8 aware library of functions mirroring PHP's own string functions.
 *
 * The powerful solution/contribution for UTF-8 support in your framework/CMS, written on PHP.
 * This library is advance of http://sourceforge.net/projects/phputf8 (last updated in 2007).
 *
 * Features and benefits
 *   * Compatibility with the interface standard PHP functions that deal with single-byte encodings
 *   * Ability to work without PHP extensions ICONV and MBSTRING, if any, that are actively used!
 *     Uses the fastest available method between MBSTRING, ICONV, native on PHP and hacks.
 *   * Useful features are missing from the ICONV and MBSTRING
 *   * The methods that take and return a string, are able to take and return null.
 *     This useful for selects from a database.
 *   * Several methods are able to process arrays recursively:
 *     array_change_key_case(), convert_from(), convert_to(), strict(), is_utf8(), blocks_check(), convert_case(), lowercase(), uppercase(), unescape()
 *   * Validating method parameters to allowed types via reflection (You can disable it)
 *   * A single interface and encapsulation, You can inherit and override
 *   * Test coverage
 *   * PHP >= 5.3.x
 *
 * In Russian:
 *
 * Поддержка UTF-8 в PHP 5.
 *
 * Возможности и преимущества
 *   * Совместимость с интерфейсом стандартных PHP функций, работающих с однобайтовыми кодировками
 *   * Возможность работы без PHP расширений ICONV и MBSTRING, если они есть, то активно используются!
 *     Используется наиболее быстрый из доступных методов между MBSTRING, ICONV, родной реализацией на PHP и хаками.
 *   * Полезные функции, отсутствующие в ICONV и MBSTRING
 *   * Методы, которые принимают и возвращают строку, умеют принимать и возвращать null.
 *     Это удобно при выборках значений из базы данных.
 *   * Несколько методов умеют обрабатывать массивы рекурсивно:
 *     array_change_key_case(), convert_from(), convert_to(), strict(), is_utf8(), blocks_check(), convert_case(), lowercase(), uppercase(), unescape()
 *   * Проверка у методов входных параметров на допустимые типы через рефлексию (можно отключить)
 *   * Единый интерфейс и инкапсуляция, можно унаследоваться и переопределить методы
 *   * Покрытие тестами
 *   * PHP >= 5.3.x
 *
 * Example:
 *   $s = 'Hello, Привет';
 *   if (UTF8::is_utf8($s)) echo UTF8::strlen($s);
 *
 * UTF-8 encoding scheme:
 *   2^7   0x00000000 — 0x0000007F  0xxxxxxx
 *   2^11  0x00000080 — 0x000007FF  110xxxxx 10xxxxxx
 *   2^16  0x00000800 — 0x0000FFFF  1110xxxx 10xxxxxx 10xxxxxx
 *   2^21  0x00010000 — 0x001FFFFF  11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
 *   1-4 bytes length: 2^7 + 2^11 + 2^16 + 2^21 = 2 164 864
 *
 * If I was a owner of the world, I would leave only 2 encoding: UTF-8 and UTF-32 ;-)
 *
 * Useful links
 *   http://ru.wikipedia.org/wiki/UTF8
 *   http://www.madore.org/~david/misc/unitest/   A Unicode Test Page
 *   http://www.unicode.org/
 *   http://www.unicode.org/reports/
 *   http://www.unicode.org/reports/tr10/      Unicode Collation Algorithm
 *   http://www.unicode.org/Public/UCA/6.0.0/  Unicode Collation Algorithm
 *   http://www.unicode.org/reports/tr6/       A Standard Compression Scheme for Unicode
 *   http://www.fileformat.info/info/unicode/char/search.htm  Unicode Character Search
 *
 * @link     http://code.google.com/p/php5-utf8/
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat
 * @version  2.3.1
 */
class UTF8
{
	/**
	 * REPLACEMENT CHARACTER (for broken char)
	 *
	 * @var string
	 */
	const REPLACEMENT_CHAR = "\xEF\xBF\xBD"; #U+FFFD

	/**
	 * Byte order mark, http://en.wikipedia.org/wiki/Byte_Order_Mark
	 *
	 * @var string
	 */
	const BOM = "\xEF\xBB\xBF";

	/**
	 * Regular expression for a character in UTF-8.
	 * For engines, which don't support UTF8 mode.
	 * In PCRE use a dot (".") and the flag /u, it works much faster!
	 *
	 * @var string
	 */
	const CHAR_RE =
		'[\x09\x0A\x0D\x20-\x7E]            # ASCII strict
		# [\x00-\x7F]                       # ASCII non-strict (including control chars)
		| [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
		|  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
		|  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
		|  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
		|  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
		';

	/**
	 * Combining diactrical marks (Unicode 5.1).
	 * \p{M} in PCRE terms.
	 * For engines, which don't support UTF8 mode.
	 *
	 * For example, russian letters in composed form: "Ё" (U+0401), "Й" (U+0419),
	 * decomposed form: (U+0415 U+0308), (U+0418 U+0306)
	 *
	 * @link http://www.unicode.org/charts/PDF/U0300.pdf
	 * @link http://www.unicode.org/charts/PDF/U1DC0.pdf
	 * @link http://www.unicode.org/charts/PDF/UFE20.pdf
	 * @var  string
	 */
	const DIACTRICAL_RE =
		'   \xcc[\x80-\xb9]|\xcd[\x80-\xaf]  #UNICODE range: U+0300 — U+036F (for letters)
		  | \xe2\x83[\x90-\xbf]              #UNICODE range: U+20D0 — U+20FF (for symbols)
		  | \xe1\xb7[\x80-\xbf]              #UNICODE range: U+1DC0 — U+1DFF (supplement)
		  | \xef\xb8[\xa0-\xaf]              #UNICODE range: U+FE20 — U+FE2F (combining half marks)
		';

	/**
	 * \p{Lu} in PCRE terms.
	 * For engines, which don't support UTF8 mode.
	 *
	 * @var string
	 */
	const CHAR_UPPER_RE = '[\x41-\x5a]
							| \xc3[\x80-\x9e]
							| \xc4[\x80-\xbf]
							| \xc5[\x81-\xbd]
							| \xc6[\x81-\xbc]
							| \xc7[\x85-\xbe]
							| \xc8[\x80-\xb2]
							| \xce[\x86-\xab]
							| \xcf[\x98-\xae]
							| \xd0[\x80-\xaf]
							| \xd1[\xa0-\xbe]
							| \xd2[\x80-\xbe]
							| \xd3[\x81-\xb8]
							| \xd4[\x80-\xbf]
							| \xd5[\x80-\x96]
							| \xe1[\xb8\xb9\xba][\x80-\xbe]
							| \xe1\xbb[\x80-\xb8]
							| \xe1\xbc[\x88-\xbf]
							| \xe1\xbd[\x88-\xaf]
							| \xe1[\xbe\xbf][\x88-\xbc]
							| \xef\xbc[\xa1-\xba]
							';

	/**
	 * \p{Ll} in PCRE terms.
	 * For engines, which don't support UTF8 mode.
	 *
	 * @var string
	 */
	const CHAR_LOWER_RE = '[\x61-\x7a]
							| \xc2\xb5
							| \xc3[\xa0-\xbf]
							| \xc4[\x81-\xbe]
							| \xc5[\x80-\xbe]
							| \xc6[\x83-\xbf]
							| \xc7[\x86-\xbf]
							| \xc8[\x81-\xb3]
							| \xc9[\x93-\xb5]
							| \xca[\x80-\x92]
							| \xce[\xac-\xbf]
							| \xcf[\x80-\xaf]
							| \xd0[\xb0-\xbf]
							| \xd1[\x80-\xbf]
							| \xd2[\x81-\xbf]
							| \xd3[\x82-\xb9]
							| \xd4[\x81-\x8f]
							| \xd5[\xa1-\xbf]
							| \xd6[\x80-\x86]
							| \xe1[\xb8\xb9\xba][\x81-\xbf]
							| \xe1\xbb[\x81-\xb9]
							| \xe1\xbc[\x80-\xb7]
							| \xe1\xbd[\x80-\xbd]
							| \xe1\xbe[\x80-\xb3]
							| \xe1\xbf[\x83-\xb3]
							| \xef\xbd[\x81-\x9a]
							';

	/**
	 * HTML entities, examples: &gt; &Ouml; &#x02DC; &#34;
	 *
	 * @var string
	 */
	const HTML_ENTITY_RE = '&(?> [a-zA-Z][a-zA-Z\d]++
							   | \#(?> \d{1,4}+
									 | x[\da-fA-F]{2,4}+
								   )
							 );
							';

	/**
	 * Quotation marks.
	 * For engines, which don't support UTF8 mode.
	 *
	 * @var string
	 */
	const QUOTATION_MARK_RE = '\x22|\xc2[\xab\xbb]|\xe2\x80[\x98\x99\x9a\x9c\x9d\x9e\xb9\xba]';

	/**
	 *
	 * @var array
	 */
	public static $html_quotation_mark_table = array(
		'&quot;'   => "\x22",          #U+0022 ["] &#34; quotation mark = APL quote
		'&laquo;'  => "\xc2\xab",      #U+00AB [«] left-pointing double angle quotation mark = left pointing guillemet
		'&raquo;'  => "\xc2\xbb",      #U+00BB [»] right-pointing double angle quotation mark = right pointing guillemet
		'&lsquo;'  => "\xe2\x80\x98",  #U+2018 [‘] left single quotation mark
		'&rsquo;'  => "\xe2\x80\x99",  #U+2019 [’] right single quotation mark (and apostrophe!)
		'&sbquo;'  => "\xe2\x80\x9a",  #U+201A [‚] single low-9 quotation mark
		'&ldquo;'  => "\xe2\x80\x9c",  #U+201C [“] left double quotation mark
		'&rdquo;'  => "\xe2\x80\x9d",  #U+201D [”] right double quotation mark
		'&bdquo;'  => "\xe2\x80\x9e",  #U+201E [„] double low-9 quotation mark
		'&lsaquo;' => "\xe2\x80\xb9",  #U+2039 [‹] single left-pointing angle quotation mark
		'&rsaquo;' => "\xe2\x80\xba",  #U+203A [›] single right-pointing angle quotation mark
	);

	/**
	 * HTML special chars table
	 *
	 * @var array
	 */
	public static $html_special_chars_table = array(
		'&quot;' => "\x22",  #U+0022 ["] &#34; quotation mark = APL quote
		'&amp;'  => "\x26",  #U+0026 [&] &#38; ampersand
		'&lt;'   => "\x3c",  #U+003C [<] &#60; less-than sign
		'&gt;'   => "\x3e",  #U+003E [>] &#62; greater-than sign
		#&apos; entity is only available in XHTML/HTML5 and not in plain HTML, see http://www.w3.org/TR/xhtml1/#C_16
		#'&apos;' => "\x27",  #U+0027 ['] &#39; apostrophe
	);

	/**
	 * @link http://www.fileformat.info/format/w3c/entitytest.htm?sort=Unicode%20Character  HTML Entity Browser Test Page
	 * @var  array
	 */
	public static $html_entity_table = array(
		#Latin-1 Entities:
		'&nbsp;'   => "\xc2\xa0",  #U+00A0 [ ] no-break space = non-breaking space
		'&iexcl;'  => "\xc2\xa1",  #U+00A1 [¡] inverted exclamation mark
		'&cent;'   => "\xc2\xa2",  #U+00A2 [¢] cent sign
		'&pound;'  => "\xc2\xa3",  #U+00A3 [£] pound sign
		'&curren;' => "\xc2\xa4",  #U+00A4 [¤] currency sign
		'&yen;'    => "\xc2\xa5",  #U+00A5 [¥] yen sign = yuan sign
		'&brvbar;' => "\xc2\xa6",  #U+00A6 [¦] broken bar = broken vertical bar
		'&sect;'   => "\xc2\xa7",  #U+00A7 [§] section sign
		'&uml;'    => "\xc2\xa8",  #U+00A8 [¨] diaeresis = spacing diaeresis
		'&copy;'   => "\xc2\xa9",  #U+00A9 [©] copyright sign
		'&ordf;'   => "\xc2\xaa",  #U+00AA [ª] feminine ordinal indicator
		'&laquo;'  => "\xc2\xab",  #U+00AB [«] left-pointing double angle quotation mark = left pointing guillemet
		'&not;'    => "\xc2\xac",  #U+00AC [¬] not sign
		'&shy;'    => "\xc2\xad",  #U+00AD [ ] soft hyphen = discretionary hyphen
		'&reg;'    => "\xc2\xae",  #U+00AE [®] registered sign = registered trade mark sign
		'&macr;'   => "\xc2\xaf",  #U+00AF [¯] macron = spacing macron = overline = APL overbar
		'&deg;'    => "\xc2\xb0",  #U+00B0 [°] degree sign
		'&plusmn;' => "\xc2\xb1",  #U+00B1 [±] plus-minus sign = plus-or-minus sign
		'&sup2;'   => "\xc2\xb2",  #U+00B2 [²] superscript two = superscript digit two = squared
		'&sup3;'   => "\xc2\xb3",  #U+00B3 [³] superscript three = superscript digit three = cubed
		'&acute;'  => "\xc2\xb4",  #U+00B4 [´] acute accent = spacing acute
		'&micro;'  => "\xc2\xb5",  #U+00B5 [µ] micro sign
		'&para;'   => "\xc2\xb6",  #U+00B6 [¶] pilcrow sign = paragraph sign
		'&middot;' => "\xc2\xb7",  #U+00B7 [·] middle dot = Georgian comma = Greek middle dot
		'&cedil;'  => "\xc2\xb8",  #U+00B8 [¸] cedilla = spacing cedilla
		'&sup1;'   => "\xc2\xb9",  #U+00B9 [¹] superscript one = superscript digit one
		'&ordm;'   => "\xc2\xba",  #U+00BA [º] masculine ordinal indicator
		'&raquo;'  => "\xc2\xbb",  #U+00BB [»] right-pointing double angle quotation mark = right pointing guillemet
		'&frac14;' => "\xc2\xbc",  #U+00BC [¼] vulgar fraction one quarter = fraction one quarter
		'&frac12;' => "\xc2\xbd",  #U+00BD [½] vulgar fraction one half = fraction one half
		'&frac34;' => "\xc2\xbe",  #U+00BE [¾] vulgar fraction three quarters = fraction three quarters
		'&iquest;' => "\xc2\xbf",  #U+00BF [¿] inverted question mark = turned question mark
		#Latin capital letter
		'&Agrave;' => "\xc3\x80",  #Latin capital letter A with grave = Latin capital letter A grave
		'&Aacute;' => "\xc3\x81",  #Latin capital letter A with acute
		'&Acirc;'  => "\xc3\x82",  #Latin capital letter A with circumflex
		'&Atilde;' => "\xc3\x83",  #Latin capital letter A with tilde
		'&Auml;'   => "\xc3\x84",  #Latin capital letter A with diaeresis
		'&Aring;'  => "\xc3\x85",  #Latin capital letter A with ring above = Latin capital letter A ring
		'&AElig;'  => "\xc3\x86",  #Latin capital letter AE = Latin capital ligature AE
		'&Ccedil;' => "\xc3\x87",  #Latin capital letter C with cedilla
		'&Egrave;' => "\xc3\x88",  #Latin capital letter E with grave
		'&Eacute;' => "\xc3\x89",  #Latin capital letter E with acute
		'&Ecirc;'  => "\xc3\x8a",  #Latin capital letter E with circumflex
		'&Euml;'   => "\xc3\x8b",  #Latin capital letter E with diaeresis
		'&Igrave;' => "\xc3\x8c",  #Latin capital letter I with grave
		'&Iacute;' => "\xc3\x8d",  #Latin capital letter I with acute
		'&Icirc;'  => "\xc3\x8e",  #Latin capital letter I with circumflex
		'&Iuml;'   => "\xc3\x8f",  #Latin capital letter I with diaeresis
		'&ETH;'    => "\xc3\x90",  #Latin capital letter ETH
		'&Ntilde;' => "\xc3\x91",  #Latin capital letter N with tilde
		'&Ograve;' => "\xc3\x92",  #Latin capital letter O with grave
		'&Oacute;' => "\xc3\x93",  #Latin capital letter O with acute
		'&Ocirc;'  => "\xc3\x94",  #Latin capital letter O with circumflex
		'&Otilde;' => "\xc3\x95",  #Latin capital letter O with tilde
		'&Ouml;'   => "\xc3\x96",  #Latin capital letter O with diaeresis
		'&times;'  => "\xc3\x97",  #U+00D7 [×] multiplication sign
		'&Oslash;' => "\xc3\x98",  #Latin capital letter O with stroke = Latin capital letter O slash
		'&Ugrave;' => "\xc3\x99",  #Latin capital letter U with grave
		'&Uacute;' => "\xc3\x9a",  #Latin capital letter U with acute
		'&Ucirc;'  => "\xc3\x9b",  #Latin capital letter U with circumflex
		'&Uuml;'   => "\xc3\x9c",  #Latin capital letter U with diaeresis
		'&Yacute;' => "\xc3\x9d",  #Latin capital letter Y with acute
		'&THORN;'  => "\xc3\x9e",  #Latin capital letter THORN
		#Latin small letter
		'&szlig;'  => "\xc3\x9f",  #Latin small letter sharp s = ess-zed
		'&agrave;' => "\xc3\xa0",  #Latin small letter a with grave = Latin small letter a grave
		'&aacute;' => "\xc3\xa1",  #Latin small letter a with acute
		'&acirc;'  => "\xc3\xa2",  #Latin small letter a with circumflex
		'&atilde;' => "\xc3\xa3",  #Latin small letter a with tilde
		'&auml;'   => "\xc3\xa4",  #Latin small letter a with diaeresis
		'&aring;'  => "\xc3\xa5",  #Latin small letter a with ring above = Latin small letter a ring
		'&aelig;'  => "\xc3\xa6",  #Latin small letter ae = Latin small ligature ae
		'&ccedil;' => "\xc3\xa7",  #Latin small letter c with cedilla
		'&egrave;' => "\xc3\xa8",  #Latin small letter e with grave
		'&eacute;' => "\xc3\xa9",  #Latin small letter e with acute
		'&ecirc;'  => "\xc3\xaa",  #Latin small letter e with circumflex
		'&euml;'   => "\xc3\xab",  #Latin small letter e with diaeresis
		'&igrave;' => "\xc3\xac",  #Latin small letter i with grave
		'&iacute;' => "\xc3\xad",  #Latin small letter i with acute
		'&icirc;'  => "\xc3\xae",  #Latin small letter i with circumflex
		'&iuml;'   => "\xc3\xaf",  #Latin small letter i with diaeresis
		'&eth;'    => "\xc3\xb0",  #Latin small letter eth
		'&ntilde;' => "\xc3\xb1",  #Latin small letter n with tilde
		'&ograve;' => "\xc3\xb2",  #Latin small letter o with grave
		'&oacute;' => "\xc3\xb3",  #Latin small letter o with acute
		'&ocirc;'  => "\xc3\xb4",  #Latin small letter o with circumflex
		'&otilde;' => "\xc3\xb5",  #Latin small letter o with tilde
		'&ouml;'   => "\xc3\xb6",  #Latin small letter o with diaeresis
		'&divide;' => "\xc3\xb7",  #U+00F7 [÷] division sign
		'&oslash;' => "\xc3\xb8",  #Latin small letter o with stroke = Latin small letter o slash
		'&ugrave;' => "\xc3\xb9",  #Latin small letter u with grave
		'&uacute;' => "\xc3\xba",  #Latin small letter u with acute
		'&ucirc;'  => "\xc3\xbb",  #Latin small letter u with circumflex
		'&uuml;'   => "\xc3\xbc",  #Latin small letter u with diaeresis
		'&yacute;' => "\xc3\xbd",  #Latin small letter y with acute
		'&thorn;'  => "\xc3\xbe",  #Latin small letter thorn
		'&yuml;'   => "\xc3\xbf",  #Latin small letter y with diaeresis
		#Symbols and Greek Letters:
		'&fnof;'    => "\xc6\x92",  #U+0192 [ƒ] Latin small f with hook = function = florin
		'&Alpha;'   => "\xce\x91",  #Greek capital letter alpha
		'&Beta;'    => "\xce\x92",  #Greek capital letter beta
		'&Gamma;'   => "\xce\x93",  #Greek capital letter gamma
		'&Delta;'   => "\xce\x94",  #Greek capital letter delta
		'&Epsilon;' => "\xce\x95",  #Greek capital letter epsilon
		'&Zeta;'    => "\xce\x96",  #Greek capital letter zeta
		'&Eta;'     => "\xce\x97",  #Greek capital letter eta
		'&Theta;'   => "\xce\x98",  #Greek capital letter theta
		'&Iota;'    => "\xce\x99",  #Greek capital letter iota
		'&Kappa;'   => "\xce\x9a",  #Greek capital letter kappa
		'&Lambda;'  => "\xce\x9b",  #Greek capital letter lambda
		'&Mu;'      => "\xce\x9c",  #Greek capital letter mu
		'&Nu;'      => "\xce\x9d",  #Greek capital letter nu
		'&Xi;'      => "\xce\x9e",  #Greek capital letter xi
		'&Omicron;' => "\xce\x9f",  #Greek capital letter omicron
		'&Pi;'      => "\xce\xa0",  #Greek capital letter pi
		'&Rho;'     => "\xce\xa1",  #Greek capital letter rho
		'&Sigma;'   => "\xce\xa3",  #Greek capital letter sigma
		'&Tau;'     => "\xce\xa4",  #Greek capital letter tau
		'&Upsilon;' => "\xce\xa5",  #Greek capital letter upsilon
		'&Phi;'     => "\xce\xa6",  #Greek capital letter phi
		'&Chi;'     => "\xce\xa7",  #Greek capital letter chi
		'&Psi;'     => "\xce\xa8",  #Greek capital letter psi
		'&Omega;'   => "\xce\xa9",  #Greek capital letter omega
		'&alpha;'   => "\xce\xb1",  #Greek small letter alpha
		'&beta;'    => "\xce\xb2",  #Greek small letter beta
		'&gamma;'   => "\xce\xb3",  #Greek small letter gamma
		'&delta;'   => "\xce\xb4",  #Greek small letter delta
		'&epsilon;' => "\xce\xb5",  #Greek small letter epsilon
		'&zeta;'    => "\xce\xb6",  #Greek small letter zeta
		'&eta;'     => "\xce\xb7",  #Greek small letter eta
		'&theta;'   => "\xce\xb8",  #Greek small letter theta
		'&iota;'    => "\xce\xb9",  #Greek small letter iota
		'&kappa;'   => "\xce\xba",  #Greek small letter kappa
		'&lambda;'  => "\xce\xbb",  #Greek small letter lambda
		'&mu;'      => "\xce\xbc",  #Greek small letter mu
		'&nu;'      => "\xce\xbd",  #Greek small letter nu
		'&xi;'      => "\xce\xbe",  #Greek small letter xi
		'&omicron;' => "\xce\xbf",  #Greek small letter omicron
		'&pi;'      => "\xcf\x80",  #Greek small letter pi
		'&rho;'     => "\xcf\x81",  #Greek small letter rho
		'&sigmaf;'  => "\xcf\x82",  #Greek small letter final sigma
		'&sigma;'   => "\xcf\x83",  #Greek small letter sigma
		'&tau;'     => "\xcf\x84",  #Greek small letter tau
		'&upsilon;' => "\xcf\x85",  #Greek small letter upsilon
		'&phi;'     => "\xcf\x86",  #Greek small letter phi
		'&chi;'     => "\xcf\x87",  #Greek small letter chi
		'&psi;'     => "\xcf\x88",  #Greek small letter psi
		'&omega;'   => "\xcf\x89",  #Greek small letter omega
		'&thetasym;'=> "\xcf\x91",  #Greek small letter theta symbol
		'&upsih;'   => "\xcf\x92",  #Greek upsilon with hook symbol
		'&piv;'     => "\xcf\x96",  #U+03D6 [ϖ] Greek pi symbol

		'&bull;'    => "\xe2\x80\xa2",  #U+2022 [•] bullet = black small circle
		'&hellip;'  => "\xe2\x80\xa6",  #U+2026 […] horizontal ellipsis = three dot leader
		'&prime;'   => "\xe2\x80\xb2",  #U+2032 [′] prime = minutes = feet (для обозначения минут и футов)
		'&Prime;'   => "\xe2\x80\xb3",  #U+2033 [″] double prime = seconds = inches (для обозначения секунд и дюймов).
		'&oline;'   => "\xe2\x80\xbe",  #U+203E [‾] overline = spacing overscore
		'&frasl;'   => "\xe2\x81\x84",  #U+2044 [⁄] fraction slash
		'&weierp;'  => "\xe2\x84\x98",  #U+2118 [℘] script capital P = power set = Weierstrass p
		'&image;'   => "\xe2\x84\x91",  #U+2111 [ℑ] blackletter capital I = imaginary part
		'&real;'    => "\xe2\x84\x9c",  #U+211C [ℜ] blackletter capital R = real part symbol
		'&trade;'   => "\xe2\x84\xa2",  #U+2122 [™] trade mark sign
		'&alefsym;' => "\xe2\x84\xb5",  #U+2135 [ℵ] alef symbol = first transfinite cardinal
		'&larr;'    => "\xe2\x86\x90",  #U+2190 [←] leftwards arrow
		'&uarr;'    => "\xe2\x86\x91",  #U+2191 [↑] upwards arrow
		'&rarr;'    => "\xe2\x86\x92",  #U+2192 [→] rightwards arrow
		'&darr;'    => "\xe2\x86\x93",  #U+2193 [↓] downwards arrow
		'&harr;'    => "\xe2\x86\x94",  #U+2194 [↔] left right arrow
		'&crarr;'   => "\xe2\x86\xb5",  #U+21B5 [↵] downwards arrow with corner leftwards = carriage return
		'&lArr;'    => "\xe2\x87\x90",  #U+21D0 [⇐] leftwards double arrow
		'&uArr;'    => "\xe2\x87\x91",  #U+21D1 [⇑] upwards double arrow
		'&rArr;'    => "\xe2\x87\x92",  #U+21D2 [⇒] rightwards double arrow
		'&dArr;'    => "\xe2\x87\x93",  #U+21D3 [⇓] downwards double arrow
		'&hArr;'    => "\xe2\x87\x94",  #U+21D4 [⇔] left right double arrow
		'&forall;'  => "\xe2\x88\x80",  #U+2200 [∀] for all
		'&part;'    => "\xe2\x88\x82",  #U+2202 [∂] partial differential
		'&exist;'   => "\xe2\x88\x83",  #U+2203 [∃] there exists
		'&empty;'   => "\xe2\x88\x85",  #U+2205 [∅] empty set = null set = diameter
		'&nabla;'   => "\xe2\x88\x87",  #U+2207 [∇] nabla = backward difference
		'&isin;'    => "\xe2\x88\x88",  #U+2208 [∈] element of
		'&notin;'   => "\xe2\x88\x89",  #U+2209 [∉] not an element of
		'&ni;'      => "\xe2\x88\x8b",  #U+220B [∋] contains as member
		'&prod;'    => "\xe2\x88\x8f",  #U+220F [∏] n-ary product = product sign
		'&sum;'     => "\xe2\x88\x91",  #U+2211 [∑] n-ary sumation
		'&minus;'   => "\xe2\x88\x92",  #U+2212 [−] minus sign
		'&lowast;'  => "\xe2\x88\x97",  #U+2217 [∗] asterisk operator
		'&radic;'   => "\xe2\x88\x9a",  #U+221A [√] square root = radical sign
		'&prop;'    => "\xe2\x88\x9d",  #U+221D [∝] proportional to
		'&infin;'   => "\xe2\x88\x9e",  #U+221E [∞] infinity
		'&ang;'     => "\xe2\x88\xa0",  #U+2220 [∠] angle
		'&and;'     => "\xe2\x88\xa7",  #U+2227 [∧] logical and = wedge
		'&or;'      => "\xe2\x88\xa8",  #U+2228 [∨] logical or = vee
		'&cap;'     => "\xe2\x88\xa9",  #U+2229 [∩] intersection = cap
		'&cup;'     => "\xe2\x88\xaa",  #U+222A [∪] union = cup
		'&int;'     => "\xe2\x88\xab",  #U+222B [∫] integral
		'&there4;'  => "\xe2\x88\xb4",  #U+2234 [∴] therefore
		'&sim;'     => "\xe2\x88\xbc",  #U+223C [∼] tilde operator = varies with = similar to
		'&cong;'    => "\xe2\x89\x85",  #U+2245 [≅] approximately equal to
		'&asymp;'   => "\xe2\x89\x88",  #U+2248 [≈] almost equal to = asymptotic to
		'&ne;'      => "\xe2\x89\xa0",  #U+2260 [≠] not equal to
		'&equiv;'   => "\xe2\x89\xa1",  #U+2261 [≡] identical to
		'&le;'      => "\xe2\x89\xa4",  #U+2264 [≤] less-than or equal to
		'&ge;'      => "\xe2\x89\xa5",  #U+2265 [≥] greater-than or equal to
		'&sub;'     => "\xe2\x8a\x82",  #U+2282 [⊂] subset of
		'&sup;'     => "\xe2\x8a\x83",  #U+2283 [⊃] superset of
		'&nsub;'    => "\xe2\x8a\x84",  #U+2284 [⊄] not a subset of
		'&sube;'    => "\xe2\x8a\x86",  #U+2286 [⊆] subset of or equal to
		'&supe;'    => "\xe2\x8a\x87",  #U+2287 [⊇] superset of or equal to
		'&oplus;'   => "\xe2\x8a\x95",  #U+2295 [⊕] circled plus = direct sum
		'&otimes;'  => "\xe2\x8a\x97",  #U+2297 [⊗] circled times = vector product
		'&perp;'    => "\xe2\x8a\xa5",  #U+22A5 [⊥] up tack = orthogonal to = perpendicular
		'&sdot;'    => "\xe2\x8b\x85",  #U+22C5 [⋅] dot operator
		'&lceil;'   => "\xe2\x8c\x88",  #U+2308 [⌈] left ceiling = APL upstile
		'&rceil;'   => "\xe2\x8c\x89",  #U+2309 [⌉] right ceiling
		'&lfloor;'  => "\xe2\x8c\x8a",  #U+230A [⌊] left floor = APL downstile
		'&rfloor;'  => "\xe2\x8c\x8b",  #U+230B [⌋] right floor
		'&lang;'    => "\xe2\x8c\xa9",  #U+2329 [〈] left-pointing angle bracket = bra
		'&rang;'    => "\xe2\x8c\xaa",  #U+232A [〉] right-pointing angle bracket = ket
		'&loz;'     => "\xe2\x97\x8a",  #U+25CA [◊] lozenge
		'&spades;'  => "\xe2\x99\xa0",  #U+2660 [♠] black spade suit
		'&clubs;'   => "\xe2\x99\xa3",  #U+2663 [♣] black club suit = shamrock
		'&hearts;'  => "\xe2\x99\xa5",  #U+2665 [♥] black heart suit = valentine
		'&diams;'   => "\xe2\x99\xa6",  #U+2666 [♦] black diamond suit
		#Other Special Characters:
		'&OElig;'  => "\xc5\x92",  #U+0152 [Œ] Latin capital ligature OE
		'&oelig;'  => "\xc5\x93",  #U+0153 [œ] Latin small ligature oe
		'&Scaron;' => "\xc5\xa0",  #U+0160 [Š] Latin capital letter S with caron
		'&scaron;' => "\xc5\xa1",  #U+0161 [š] Latin small letter s with caron
		'&Yuml;'   => "\xc5\xb8",  #U+0178 [Ÿ] Latin capital letter Y with diaeresis
		'&circ;'   => "\xcb\x86",  #U+02C6 [ˆ] modifier letter circumflex accent
		'&tilde;'  => "\xcb\x9c",  #U+02DC [˜] small tilde
		'&ensp;'   => "\xe2\x80\x82",  #U+2002 [ ] en space
		'&emsp;'   => "\xe2\x80\x83",  #U+2003 [ ] em space
		'&thinsp;' => "\xe2\x80\x89",  #U+2009 [ ] thin space
		'&zwnj;'   => "\xe2\x80\x8c",  #U+200C [‌] zero width non-joiner
		'&zwj;'    => "\xe2\x80\x8d",  #U+200D [‍] zero width joiner
		'&lrm;'    => "\xe2\x80\x8e",  #U+200E [‎] left-to-right mark
		'&rlm;'    => "\xe2\x80\x8f",  #U+200F [‏] right-to-left mark
		'&ndash;'  => "\xe2\x80\x93",  #U+2013 [–] en dash
		'&mdash;'  => "\xe2\x80\x94",  #U+2014 [—] em dash
		'&lsquo;'  => "\xe2\x80\x98",  #U+2018 [‘] left single quotation mark
		'&rsquo;'  => "\xe2\x80\x99",  #U+2019 [’] right single quotation mark (and apostrophe!)
		'&sbquo;'  => "\xe2\x80\x9a",  #U+201A [‚] single low-9 quotation mark
		'&ldquo;'  => "\xe2\x80\x9c",  #U+201C [“] left double quotation mark
		'&rdquo;'  => "\xe2\x80\x9d",  #U+201D [”] right double quotation mark
		'&bdquo;'  => "\xe2\x80\x9e",  #U+201E [„] double low-9 quotation mark
		'&dagger;' => "\xe2\x80\xa0",  #U+2020 [†] dagger
		'&Dagger;' => "\xe2\x80\xa1",  #U+2021 [‡] double dagger
		'&permil;' => "\xe2\x80\xb0",  #U+2030 [‰] per mille sign
		'&lsaquo;' => "\xe2\x80\xb9",  #U+2039 [‹] single left-pointing angle quotation mark
		'&rsaquo;' => "\xe2\x80\xba",  #U+203A [›] single right-pointing angle quotation mark
		'&euro;'   => "\xe2\x82\xac",  #U+20AC [€] euro sign
	);

	/**
	 * This table contains the data on how cp1259 characters map into Unicode (UTF-8).
	 * The cp1259 map describes standart tatarish cyrillic charset and based on the cp1251 table.
	 * cp1259 -- this is an outdated one byte encoding of the Tatar language,
	 * which includes all the Russian letters from cp1251.
	 *
	 * @link  http://search.cpan.org/CPAN/authors/id/A/AM/AMICHAUER/Lingua-TT-Yanalif-0.08.tar.gz
	 * @link  http://www.unicode.org/charts/PDF/U0400.pdf
	 * @var   array
	 */
	public static $cp1259_table = array(
		#bytes from 0x00 to 0x7F (ASCII) saved as is
		"\x80" => "\xd3\x98",      #U+04d8 CYRILLIC CAPITAL LETTER SCHWA
		"\x81" => "\xd0\x83",      #U+0403 CYRILLIC CAPITAL LETTER GJE
		"\x82" => "\xe2\x80\x9a",  #U+201a SINGLE LOW-9 QUOTATION MARK
		"\x83" => "\xd1\x93",      #U+0453 CYRILLIC SMALL LETTER GJE
		"\x84" => "\xe2\x80\x9e",  #U+201e DOUBLE LOW-9 QUOTATION MARK
		"\x85" => "\xe2\x80\xa6",  #U+2026 HORIZONTAL ELLIPSIS
		"\x86" => "\xe2\x80\xa0",  #U+2020 DAGGER
		"\x87" => "\xe2\x80\xa1",  #U+2021 DOUBLE DAGGER
		"\x88" => "\xe2\x82\xac",  #U+20ac EURO SIGN
		"\x89" => "\xe2\x80\xb0",  #U+2030 PER MILLE SIGN
		"\x8a" => "\xd3\xa8",      #U+04e8 CYRILLIC CAPITAL LETTER BARRED O
		"\x8b" => "\xe2\x80\xb9",  #U+2039 SINGLE LEFT-POINTING ANGLE QUOTATION MARK
		"\x8c" => "\xd2\xae",      #U+04ae CYRILLIC CAPITAL LETTER STRAIGHT U
		"\x8d" => "\xd2\x96",      #U+0496 CYRILLIC CAPITAL LETTER ZHE WITH DESCENDER
		"\x8e" => "\xd2\xa2",      #U+04a2 CYRILLIC CAPITAL LETTER EN WITH HOOK
		"\x8f" => "\xd2\xba",      #U+04ba CYRILLIC CAPITAL LETTER SHHA
		"\x90" => "\xd3\x99",      #U+04d9 CYRILLIC SMALL LETTER SCHWA
		"\x91" => "\xe2\x80\x98",  #U+2018 LEFT SINGLE QUOTATION MARK
		"\x92" => "\xe2\x80\x99",  #U+2019 RIGHT SINGLE QUOTATION MARK
		"\x93" => "\xe2\x80\x9c",  #U+201c LEFT DOUBLE QUOTATION MARK
		"\x94" => "\xe2\x80\x9d",  #U+201d RIGHT DOUBLE QUOTATION MARK
		"\x95" => "\xe2\x80\xa2",  #U+2022 BULLET
		"\x96" => "\xe2\x80\x93",  #U+2013 EN DASH
		"\x97" => "\xe2\x80\x94",  #U+2014 EM DASH
		#"\x98"                    #UNDEFINED
		"\x99" => "\xe2\x84\xa2",  #U+2122 TRADE MARK SIGN
		"\x9a" => "\xd3\xa9",      #U+04e9 CYRILLIC SMALL LETTER BARRED O
		"\x9b" => "\xe2\x80\xba",  #U+203a SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
		"\x9c" => "\xd2\xaf",      #U+04af CYRILLIC SMALL LETTER STRAIGHT U
		"\x9d" => "\xd2\x97",      #U+0497 CYRILLIC SMALL LETTER ZHE WITH DESCENDER
		"\x9e" => "\xd2\xa3",      #U+04a3 CYRILLIC SMALL LETTER EN WITH HOOK
		"\x9f" => "\xd2\xbb",      #U+04bb CYRILLIC SMALL LETTER SHHA
		"\xa0" => "\xc2\xa0",      #U+00a0 NO-BREAK SPACE
		"\xa1" => "\xd0\x8e",      #U+040e CYRILLIC CAPITAL LETTER SHORT U
		"\xa2" => "\xd1\x9e",      #U+045e CYRILLIC SMALL LETTER SHORT U
		"\xa3" => "\xd0\x88",      #U+0408 CYRILLIC CAPITAL LETTER JE
		"\xa4" => "\xc2\xa4",      #U+00a4 CURRENCY SIGN
		"\xa5" => "\xd2\x90",      #U+0490 CYRILLIC CAPITAL LETTER GHE WITH UPTURN
		"\xa6" => "\xc2\xa6",      #U+00a6 BROKEN BAR
		"\xa7" => "\xc2\xa7",      #U+00a7 SECTION SIGN
		"\xa8" => "\xd0\x81",      #U+0401 CYRILLIC CAPITAL LETTER IO
		"\xa9" => "\xc2\xa9",      #U+00a9 COPYRIGHT SIGN
		"\xaa" => "\xd0\x84",      #U+0404 CYRILLIC CAPITAL LETTER UKRAINIAN IE
		"\xab" => "\xc2\xab",      #U+00ab LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
		"\xac" => "\xc2\xac",      #U+00ac NOT SIGN
		"\xad" => "\xc2\xad",      #U+00ad SOFT HYPHEN
		"\xae" => "\xc2\xae",      #U+00ae REGISTERED SIGN
		"\xaf" => "\xd0\x87",      #U+0407 CYRILLIC CAPITAL LETTER YI
		"\xb0" => "\xc2\xb0",      #U+00b0 DEGREE SIGN
		"\xb1" => "\xc2\xb1",      #U+00b1 PLUS-MINUS SIGN
		"\xb2" => "\xd0\x86",      #U+0406 CYRILLIC CAPITAL LETTER BYELORUSSIAN-UKRAINIAN I
		"\xb3" => "\xd1\x96",      #U+0456 CYRILLIC SMALL LETTER BYELORUSSIAN-UKRAINIAN I
		"\xb4" => "\xd2\x91",      #U+0491 CYRILLIC SMALL LETTER GHE WITH UPTURN
		"\xb5" => "\xc2\xb5",      #U+00b5 MICRO SIGN
		"\xb6" => "\xc2\xb6",      #U+00b6 PILCROW SIGN
		"\xb7" => "\xc2\xb7",      #U+00b7 MIDDLE DOT
		"\xb8" => "\xd1\x91",      #U+0451 CYRILLIC SMALL LETTER IO
		"\xb9" => "\xe2\x84\x96",  #U+2116 NUMERO SIGN
		"\xba" => "\xd1\x94",      #U+0454 CYRILLIC SMALL LETTER UKRAINIAN IE
		"\xbb" => "\xc2\xbb",      #U+00bb RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK
		"\xbc" => "\xd1\x98",      #U+0458 CYRILLIC SMALL LETTER JE
		"\xbd" => "\xd0\x85",      #U+0405 CYRILLIC CAPITAL LETTER DZE
		"\xbe" => "\xd1\x95",      #U+0455 CYRILLIC SMALL LETTER DZE
		"\xbf" => "\xd1\x97",      #U+0457 CYRILLIC SMALL LETTER YI
		"\xc0" => "\xd0\x90",      #U+0410 CYRILLIC CAPITAL LETTER A
		"\xc1" => "\xd0\x91",      #U+0411 CYRILLIC CAPITAL LETTER BE
		"\xc2" => "\xd0\x92",      #U+0412 CYRILLIC CAPITAL LETTER VE
		"\xc3" => "\xd0\x93",      #U+0413 CYRILLIC CAPITAL LETTER GHE
		"\xc4" => "\xd0\x94",      #U+0414 CYRILLIC CAPITAL LETTER DE
		"\xc5" => "\xd0\x95",      #U+0415 CYRILLIC CAPITAL LETTER IE
		"\xc6" => "\xd0\x96",      #U+0416 CYRILLIC CAPITAL LETTER ZHE
		"\xc7" => "\xd0\x97",      #U+0417 CYRILLIC CAPITAL LETTER ZE
		"\xc8" => "\xd0\x98",      #U+0418 CYRILLIC CAPITAL LETTER I
		"\xc9" => "\xd0\x99",      #U+0419 CYRILLIC CAPITAL LETTER SHORT I
		"\xca" => "\xd0\x9a",      #U+041a CYRILLIC CAPITAL LETTER KA
		"\xcb" => "\xd0\x9b",      #U+041b CYRILLIC CAPITAL LETTER EL
		"\xcc" => "\xd0\x9c",      #U+041c CYRILLIC CAPITAL LETTER EM
		"\xcd" => "\xd0\x9d",      #U+041d CYRILLIC CAPITAL LETTER EN
		"\xce" => "\xd0\x9e",      #U+041e CYRILLIC CAPITAL LETTER O
		"\xcf" => "\xd0\x9f",      #U+041f CYRILLIC CAPITAL LETTER PE
		"\xd0" => "\xd0\xa0",      #U+0420 CYRILLIC CAPITAL LETTER ER
		"\xd1" => "\xd0\xa1",      #U+0421 CYRILLIC CAPITAL LETTER ES
		"\xd2" => "\xd0\xa2",      #U+0422 CYRILLIC CAPITAL LETTER TE
		"\xd3" => "\xd0\xa3",      #U+0423 CYRILLIC CAPITAL LETTER U
		"\xd4" => "\xd0\xa4",      #U+0424 CYRILLIC CAPITAL LETTER EF
		"\xd5" => "\xd0\xa5",      #U+0425 CYRILLIC CAPITAL LETTER HA
		"\xd6" => "\xd0\xa6",      #U+0426 CYRILLIC CAPITAL LETTER TSE
		"\xd7" => "\xd0\xa7",      #U+0427 CYRILLIC CAPITAL LETTER CHE
		"\xd8" => "\xd0\xa8",      #U+0428 CYRILLIC CAPITAL LETTER SHA
		"\xd9" => "\xd0\xa9",      #U+0429 CYRILLIC CAPITAL LETTER SHCHA
		"\xda" => "\xd0\xaa",      #U+042a CYRILLIC CAPITAL LETTER HARD SIGN
		"\xdb" => "\xd0\xab",      #U+042b CYRILLIC CAPITAL LETTER YERU
		"\xdc" => "\xd0\xac",      #U+042c CYRILLIC CAPITAL LETTER SOFT SIGN
		"\xdd" => "\xd0\xad",      #U+042d CYRILLIC CAPITAL LETTER E
		"\xde" => "\xd0\xae",      #U+042e CYRILLIC CAPITAL LETTER YU
		"\xdf" => "\xd0\xaf",      #U+042f CYRILLIC CAPITAL LETTER YA
		"\xe0" => "\xd0\xb0",      #U+0430 CYRILLIC SMALL LETTER A
		"\xe1" => "\xd0\xb1",      #U+0431 CYRILLIC SMALL LETTER BE
		"\xe2" => "\xd0\xb2",      #U+0432 CYRILLIC SMALL LETTER VE
		"\xe3" => "\xd0\xb3",      #U+0433 CYRILLIC SMALL LETTER GHE
		"\xe4" => "\xd0\xb4",      #U+0434 CYRILLIC SMALL LETTER DE
		"\xe5" => "\xd0\xb5",      #U+0435 CYRILLIC SMALL LETTER IE
		"\xe6" => "\xd0\xb6",      #U+0436 CYRILLIC SMALL LETTER ZHE
		"\xe7" => "\xd0\xb7",      #U+0437 CYRILLIC SMALL LETTER ZE
		"\xe8" => "\xd0\xb8",      #U+0438 CYRILLIC SMALL LETTER I
		"\xe9" => "\xd0\xb9",      #U+0439 CYRILLIC SMALL LETTER SHORT I
		"\xea" => "\xd0\xba",      #U+043a CYRILLIC SMALL LETTER KA
		"\xeb" => "\xd0\xbb",      #U+043b CYRILLIC SMALL LETTER EL
		"\xec" => "\xd0\xbc",      #U+043c CYRILLIC SMALL LETTER EM
		"\xed" => "\xd0\xbd",      #U+043d CYRILLIC SMALL LETTER EN
		"\xee" => "\xd0\xbe",      #U+043e CYRILLIC SMALL LETTER O
		"\xef" => "\xd0\xbf",      #U+043f CYRILLIC SMALL LETTER PE
		"\xf0" => "\xd1\x80",      #U+0440 CYRILLIC SMALL LETTER ER
		"\xf1" => "\xd1\x81",      #U+0441 CYRILLIC SMALL LETTER ES
		"\xf2" => "\xd1\x82",      #U+0442 CYRILLIC SMALL LETTER TE
		"\xf3" => "\xd1\x83",      #U+0443 CYRILLIC SMALL LETTER U
		"\xf4" => "\xd1\x84",      #U+0444 CYRILLIC SMALL LETTER EF
		"\xf5" => "\xd1\x85",      #U+0445 CYRILLIC SMALL LETTER HA
		"\xf6" => "\xd1\x86",      #U+0446 CYRILLIC SMALL LETTER TSE
		"\xf7" => "\xd1\x87",      #U+0447 CYRILLIC SMALL LETTER CHE
		"\xf8" => "\xd1\x88",      #U+0448 CYRILLIC SMALL LETTER SHA
		"\xf9" => "\xd1\x89",      #U+0449 CYRILLIC SMALL LETTER SHCHA
		"\xfa" => "\xd1\x8a",      #U+044a CYRILLIC SMALL LETTER HARD SIGN
		"\xfb" => "\xd1\x8b",      #U+044b CYRILLIC SMALL LETTER YERU
		"\xfc" => "\xd1\x8c",      #U+044c CYRILLIC SMALL LETTER SOFT SIGN
		"\xfd" => "\xd1\x8d",      #U+044d CYRILLIC SMALL LETTER E
		"\xfe" => "\xd1\x8e",      #U+044e CYRILLIC SMALL LETTER YU
		"\xff" => "\xd1\x8f",      #U+044f CYRILLIC SMALL LETTER YA
	);

	/**
	 * UTF-8 Case lookup table
	 *
	 * This lookuptable defines the upper case letters to their correspponding
	 * lower case letter in UTF-8
	 *
	 * @author Andreas Gohr <andi@splitbrain.org>
	 * @var array
	 */
	public static $convert_case_table = array(
		#CASE_UPPER => case_lower
		"\x41" => "\x61", #A a
		"\x42" => "\x62", #B b
		"\x43" => "\x63", #C c
		"\x44" => "\x64", #D d
		"\x45" => "\x65", #E e
		"\x46" => "\x66", #F f
		"\x47" => "\x67", #G g
		"\x48" => "\x68", #H h
		"\x49" => "\x69", #I i
		"\x4a" => "\x6a", #J j
		"\x4b" => "\x6b", #K k
		"\x4c" => "\x6c", #L l
		"\x4d" => "\x6d", #M m
		"\x4e" => "\x6e", #N n
		"\x4f" => "\x6f", #O o
		"\x50" => "\x70", #P p
		"\x51" => "\x71", #Q q
		"\x52" => "\x72", #R r
		"\x53" => "\x73", #S s
		"\x54" => "\x74", #T t
		"\x55" => "\x75", #U u
		"\x56" => "\x76", #V v
		"\x57" => "\x77", #W w
		"\x58" => "\x78", #X x
		"\x59" => "\x79", #Y y
		"\x5a" => "\x7a", #Z z
		"\xc3\x80" => "\xc3\xa0",
		"\xc3\x81" => "\xc3\xa1",
		"\xc3\x82" => "\xc3\xa2",
		"\xc3\x83" => "\xc3\xa3",
		"\xc3\x84" => "\xc3\xa4",
		"\xc3\x85" => "\xc3\xa5",
		"\xc3\x86" => "\xc3\xa6",
		"\xc3\x87" => "\xc3\xa7",
		"\xc3\x88" => "\xc3\xa8",
		"\xc3\x89" => "\xc3\xa9",
		"\xc3\x8a" => "\xc3\xaa",
		"\xc3\x8b" => "\xc3\xab",
		"\xc3\x8c" => "\xc3\xac",
		"\xc3\x8d" => "\xc3\xad",
		"\xc3\x8e" => "\xc3\xae",
		"\xc3\x8f" => "\xc3\xaf",
		"\xc3\x90" => "\xc3\xb0",
		"\xc3\x91" => "\xc3\xb1",
		"\xc3\x92" => "\xc3\xb2",
		"\xc3\x93" => "\xc3\xb3",
		"\xc3\x94" => "\xc3\xb4",
		"\xc3\x95" => "\xc3\xb5",
		"\xc3\x96" => "\xc3\xb6",
		"\xc3\x98" => "\xc3\xb8",
		"\xc3\x99" => "\xc3\xb9",
		"\xc3\x9a" => "\xc3\xba",
		"\xc3\x9b" => "\xc3\xbb",
		"\xc3\x9c" => "\xc3\xbc",
		"\xc3\x9d" => "\xc3\xbd",
		"\xc3\x9e" => "\xc3\xbe",
		"\xc4\x80" => "\xc4\x81",
		"\xc4\x82" => "\xc4\x83",
		"\xc4\x84" => "\xc4\x85",
		"\xc4\x86" => "\xc4\x87",
		"\xc4\x88" => "\xc4\x89",
		"\xc4\x8a" => "\xc4\x8b",
		"\xc4\x8c" => "\xc4\x8d",
		"\xc4\x8e" => "\xc4\x8f",
		"\xc4\x90" => "\xc4\x91",
		"\xc4\x92" => "\xc4\x93",
		"\xc4\x94" => "\xc4\x95",
		"\xc4\x96" => "\xc4\x97",
		"\xc4\x98" => "\xc4\x99",
		"\xc4\x9a" => "\xc4\x9b",
		"\xc4\x9c" => "\xc4\x9d",
		"\xc4\x9e" => "\xc4\x9f",
		"\xc4\xa0" => "\xc4\xa1",
		"\xc4\xa2" => "\xc4\xa3",
		"\xc4\xa4" => "\xc4\xa5",
		"\xc4\xa6" => "\xc4\xa7",
		"\xc4\xa8" => "\xc4\xa9",
		"\xc4\xaa" => "\xc4\xab",
		"\xc4\xac" => "\xc4\xad",
		"\xc4\xae" => "\xc4\xaf",
		"\xc4\xb2" => "\xc4\xb3",
		"\xc4\xb4" => "\xc4\xb5",
		"\xc4\xb6" => "\xc4\xb7",
		"\xc4\xb9" => "\xc4\xba",
		"\xc4\xbb" => "\xc4\xbc",
		"\xc4\xbd" => "\xc4\xbe",
		"\xc4\xbf" => "\xc5\x80",
		"\xc5\x81" => "\xc5\x82",
		"\xc5\x83" => "\xc5\x84",
		"\xc5\x85" => "\xc5\x86",
		"\xc5\x87" => "\xc5\x88",
		"\xc5\x8a" => "\xc5\x8b",
		"\xc5\x8c" => "\xc5\x8d",
		"\xc5\x8e" => "\xc5\x8f",
		"\xc5\x90" => "\xc5\x91",
		"\xc5\x92" => "\xc5\x93",
		"\xc5\x94" => "\xc5\x95",
		"\xc5\x96" => "\xc5\x97",
		"\xc5\x98" => "\xc5\x99",
		"\xc5\x9a" => "\xc5\x9b",
		"\xc5\x9c" => "\xc5\x9d",
		"\xc5\x9e" => "\xc5\x9f",
		"\xc5\xa0" => "\xc5\xa1",
		"\xc5\xa2" => "\xc5\xa3",
		"\xc5\xa4" => "\xc5\xa5",
		"\xc5\xa6" => "\xc5\xa7",
		"\xc5\xa8" => "\xc5\xa9",
		"\xc5\xaa" => "\xc5\xab",
		"\xc5\xac" => "\xc5\xad",
		"\xc5\xae" => "\xc5\xaf",
		"\xc5\xb0" => "\xc5\xb1",
		"\xc5\xb2" => "\xc5\xb3",
		"\xc5\xb4" => "\xc5\xb5",
		"\xc5\xb6" => "\xc5\xb7",
		"\xc5\xb8" => "\xc3\xbf",
		"\xc5\xb9" => "\xc5\xba",
		"\xc5\xbb" => "\xc5\xbc",
		"\xc5\xbd" => "\xc5\xbe",
		"\xc6\x81" => "\xc9\x93",
		"\xc6\x82" => "\xc6\x83",
		"\xc6\x84" => "\xc6\x85",
		"\xc6\x86" => "\xc9\x94",
		"\xc6\x87" => "\xc6\x88",
		"\xc6\x89" => "\xc9\x96",
		"\xc6\x8a" => "\xc9\x97",
		"\xc6\x8b" => "\xc6\x8c",
		"\xc6\x8e" => "\xc7\x9d",
		"\xc6\x8f" => "\xc9\x99",
		"\xc6\x90" => "\xc9\x9b",
		"\xc6\x91" => "\xc6\x92",
		"\xc6\x94" => "\xc9\xa3",
		"\xc6\x96" => "\xc9\xa9",
		"\xc6\x97" => "\xc9\xa8",
		"\xc6\x98" => "\xc6\x99",
		"\xc6\x9c" => "\xc9\xaf",
		"\xc6\x9d" => "\xc9\xb2",
		"\xc6\x9f" => "\xc9\xb5",
		"\xc6\xa0" => "\xc6\xa1",
		"\xc6\xa2" => "\xc6\xa3",
		"\xc6\xa4" => "\xc6\xa5",
		"\xc6\xa6" => "\xca\x80",
		"\xc6\xa7" => "\xc6\xa8",
		"\xc6\xa9" => "\xca\x83",
		"\xc6\xac" => "\xc6\xad",
		"\xc6\xae" => "\xca\x88",
		"\xc6\xaf" => "\xc6\xb0",
		"\xc6\xb1" => "\xca\x8a",
		"\xc6\xb2" => "\xca\x8b",
		"\xc6\xb3" => "\xc6\xb4",
		"\xc6\xb5" => "\xc6\xb6",
		"\xc6\xb7" => "\xca\x92",
		"\xc6\xb8" => "\xc6\xb9",
		"\xc6\xbc" => "\xc6\xbd",
		"\xc7\x85" => "\xc7\x86",
		"\xc7\x88" => "\xc7\x89",
		"\xc7\x8b" => "\xc7\x8c",
		"\xc7\x8d" => "\xc7\x8e",
		"\xc7\x8f" => "\xc7\x90",
		"\xc7\x91" => "\xc7\x92",
		"\xc7\x93" => "\xc7\x94",
		"\xc7\x95" => "\xc7\x96",
		"\xc7\x97" => "\xc7\x98",
		"\xc7\x99" => "\xc7\x9a",
		"\xc7\x9b" => "\xc7\x9c",
		"\xc7\x9e" => "\xc7\x9f",
		"\xc7\xa0" => "\xc7\xa1",
		"\xc7\xa2" => "\xc7\xa3",
		"\xc7\xa4" => "\xc7\xa5",
		"\xc7\xa6" => "\xc7\xa7",
		"\xc7\xa8" => "\xc7\xa9",
		"\xc7\xaa" => "\xc7\xab",
		"\xc7\xac" => "\xc7\xad",
		"\xc7\xae" => "\xc7\xaf",
		"\xc7\xb2" => "\xc7\xb3",
		"\xc7\xb4" => "\xc7\xb5",
		"\xc7\xb6" => "\xc6\x95",
		"\xc7\xb7" => "\xc6\xbf",
		"\xc7\xb8" => "\xc7\xb9",
		"\xc7\xba" => "\xc7\xbb",
		"\xc7\xbc" => "\xc7\xbd",
		"\xc7\xbe" => "\xc7\xbf",
		"\xc8\x80" => "\xc8\x81",
		"\xc8\x82" => "\xc8\x83",
		"\xc8\x84" => "\xc8\x85",
		"\xc8\x86" => "\xc8\x87",
		"\xc8\x88" => "\xc8\x89",
		"\xc8\x8a" => "\xc8\x8b",
		"\xc8\x8c" => "\xc8\x8d",
		"\xc8\x8e" => "\xc8\x8f",
		"\xc8\x90" => "\xc8\x91",
		"\xc8\x92" => "\xc8\x93",
		"\xc8\x94" => "\xc8\x95",
		"\xc8\x96" => "\xc8\x97",
		"\xc8\x98" => "\xc8\x99",
		"\xc8\x9a" => "\xc8\x9b",
		"\xc8\x9c" => "\xc8\x9d",
		"\xc8\x9e" => "\xc8\x9f",
		"\xc8\xa0" => "\xc6\x9e",
		"\xc8\xa2" => "\xc8\xa3",
		"\xc8\xa4" => "\xc8\xa5",
		"\xc8\xa6" => "\xc8\xa7",
		"\xc8\xa8" => "\xc8\xa9",
		"\xc8\xaa" => "\xc8\xab",
		"\xc8\xac" => "\xc8\xad",
		"\xc8\xae" => "\xc8\xaf",
		"\xc8\xb0" => "\xc8\xb1",
		"\xc8\xb2" => "\xc8\xb3",
		"\xce\x86" => "\xce\xac",
		"\xce\x88" => "\xce\xad",
		"\xce\x89" => "\xce\xae",
		"\xce\x8a" => "\xce\xaf",
		"\xce\x8c" => "\xcf\x8c",
		"\xce\x8e" => "\xcf\x8d",
		"\xce\x8f" => "\xcf\x8e",
		"\xce\x91" => "\xce\xb1",
		"\xce\x92" => "\xce\xb2",
		"\xce\x93" => "\xce\xb3",
		"\xce\x94" => "\xce\xb4",
		"\xce\x95" => "\xce\xb5",
		"\xce\x96" => "\xce\xb6",
		"\xce\x97" => "\xce\xb7",
		"\xce\x98" => "\xce\xb8",
		"\xce\x99" => "\xce\xb9",
		"\xce\x9a" => "\xce\xba",
		"\xce\x9b" => "\xce\xbb",
		"\xce\x9c" => "\xc2\xb5",
		"\xce\x9d" => "\xce\xbd",
		"\xce\x9e" => "\xce\xbe",
		"\xce\x9f" => "\xce\xbf",
		"\xce\xa0" => "\xcf\x80",
		"\xce\xa1" => "\xcf\x81",
		"\xce\xa3" => "\xcf\x82",
		"\xce\xa4" => "\xcf\x84",
		"\xce\xa5" => "\xcf\x85",
		"\xce\xa6" => "\xcf\x86",
		"\xce\xa7" => "\xcf\x87",
		"\xce\xa8" => "\xcf\x88",
		"\xce\xa9" => "\xcf\x89",
		"\xce\xaa" => "\xcf\x8a",
		"\xce\xab" => "\xcf\x8b",
		"\xcf\x98" => "\xcf\x99",
		"\xcf\x9a" => "\xcf\x9b",
		"\xcf\x9c" => "\xcf\x9d",
		"\xcf\x9e" => "\xcf\x9f",
		"\xcf\xa0" => "\xcf\xa1",
		"\xcf\xa2" => "\xcf\xa3",
		"\xcf\xa4" => "\xcf\xa5",
		"\xcf\xa6" => "\xcf\xa7",
		"\xcf\xa8" => "\xcf\xa9",
		"\xcf\xaa" => "\xcf\xab",
		"\xcf\xac" => "\xcf\xad",
		"\xcf\xae" => "\xcf\xaf",
		"\xd0\x80" => "\xd1\x90",
		"\xd0\x81" => "\xd1\x91",
		"\xd0\x82" => "\xd1\x92",
		"\xd0\x83" => "\xd1\x93",
		"\xd0\x84" => "\xd1\x94",
		"\xd0\x85" => "\xd1\x95",
		"\xd0\x86" => "\xd1\x96",
		"\xd0\x87" => "\xd1\x97",
		"\xd0\x88" => "\xd1\x98",
		"\xd0\x89" => "\xd1\x99",
		"\xd0\x8a" => "\xd1\x9a",
		"\xd0\x8b" => "\xd1\x9b",
		"\xd0\x8c" => "\xd1\x9c",
		"\xd0\x8d" => "\xd1\x9d",
		"\xd0\x8e" => "\xd1\x9e",
		"\xd0\x8f" => "\xd1\x9f",
		"\xd0\x90" => "\xd0\xb0",
		"\xd0\x91" => "\xd0\xb1",
		"\xd0\x92" => "\xd0\xb2",
		"\xd0\x93" => "\xd0\xb3",
		"\xd0\x94" => "\xd0\xb4",
		"\xd0\x95" => "\xd0\xb5",
		"\xd0\x96" => "\xd0\xb6",
		"\xd0\x97" => "\xd0\xb7",
		"\xd0\x98" => "\xd0\xb8",
		"\xd0\x99" => "\xd0\xb9",
		"\xd0\x9a" => "\xd0\xba",
		"\xd0\x9b" => "\xd0\xbb",
		"\xd0\x9c" => "\xd0\xbc",
		"\xd0\x9d" => "\xd0\xbd",
		"\xd0\x9e" => "\xd0\xbe",
		"\xd0\x9f" => "\xd0\xbf",
		"\xd0\xa0" => "\xd1\x80",
		"\xd0\xa1" => "\xd1\x81",
		"\xd0\xa2" => "\xd1\x82",
		"\xd0\xa3" => "\xd1\x83",
		"\xd0\xa4" => "\xd1\x84",
		"\xd0\xa5" => "\xd1\x85",
		"\xd0\xa6" => "\xd1\x86",
		"\xd0\xa7" => "\xd1\x87",
		"\xd0\xa8" => "\xd1\x88",
		"\xd0\xa9" => "\xd1\x89",
		"\xd0\xaa" => "\xd1\x8a",
		"\xd0\xab" => "\xd1\x8b",
		"\xd0\xac" => "\xd1\x8c",
		"\xd0\xad" => "\xd1\x8d",
		"\xd0\xae" => "\xd1\x8e",
		"\xd0\xaf" => "\xd1\x8f",
		"\xd1\xa0" => "\xd1\xa1",
		"\xd1\xa2" => "\xd1\xa3",
		"\xd1\xa4" => "\xd1\xa5",
		"\xd1\xa6" => "\xd1\xa7",
		"\xd1\xa8" => "\xd1\xa9",
		"\xd1\xaa" => "\xd1\xab",
		"\xd1\xac" => "\xd1\xad",
		"\xd1\xae" => "\xd1\xaf",
		"\xd1\xb0" => "\xd1\xb1",
		"\xd1\xb2" => "\xd1\xb3",
		"\xd1\xb4" => "\xd1\xb5",
		"\xd1\xb6" => "\xd1\xb7",
		"\xd1\xb8" => "\xd1\xb9",
		"\xd1\xba" => "\xd1\xbb",
		"\xd1\xbc" => "\xd1\xbd",
		"\xd1\xbe" => "\xd1\xbf",
		"\xd2\x80" => "\xd2\x81",
		"\xd2\x8a" => "\xd2\x8b",
		"\xd2\x8c" => "\xd2\x8d",
		"\xd2\x8e" => "\xd2\x8f",
		"\xd2\x90" => "\xd2\x91",
		"\xd2\x92" => "\xd2\x93",
		"\xd2\x94" => "\xd2\x95",
		"\xd2\x96" => "\xd2\x97",
		"\xd2\x98" => "\xd2\x99",
		"\xd2\x9a" => "\xd2\x9b",
		"\xd2\x9c" => "\xd2\x9d",
		"\xd2\x9e" => "\xd2\x9f",
		"\xd2\xa0" => "\xd2\xa1",
		"\xd2\xa2" => "\xd2\xa3",
		"\xd2\xa4" => "\xd2\xa5",
		"\xd2\xa6" => "\xd2\xa7",
		"\xd2\xa8" => "\xd2\xa9",
		"\xd2\xaa" => "\xd2\xab",
		"\xd2\xac" => "\xd2\xad",
		"\xd2\xae" => "\xd2\xaf",
		"\xd2\xb0" => "\xd2\xb1",
		"\xd2\xb2" => "\xd2\xb3",
		"\xd2\xb4" => "\xd2\xb5",
		"\xd2\xb6" => "\xd2\xb7",
		"\xd2\xb8" => "\xd2\xb9",
		"\xd2\xba" => "\xd2\xbb",
		"\xd2\xbc" => "\xd2\xbd",
		"\xd2\xbe" => "\xd2\xbf",
		"\xd3\x81" => "\xd3\x82",
		"\xd3\x83" => "\xd3\x84",
		"\xd3\x85" => "\xd3\x86",
		"\xd3\x87" => "\xd3\x88",
		"\xd3\x89" => "\xd3\x8a",
		"\xd3\x8b" => "\xd3\x8c",
		"\xd3\x8d" => "\xd3\x8e",
		"\xd3\x90" => "\xd3\x91",
		"\xd3\x92" => "\xd3\x93",
		"\xd3\x94" => "\xd3\x95",
		"\xd3\x96" => "\xd3\x97",
		"\xd3\x98" => "\xd3\x99",
		"\xd3\x9a" => "\xd3\x9b",
		"\xd3\x9c" => "\xd3\x9d",
		"\xd3\x9e" => "\xd3\x9f",
		"\xd3\xa0" => "\xd3\xa1",
		"\xd3\xa2" => "\xd3\xa3",
		"\xd3\xa4" => "\xd3\xa5",
		"\xd3\xa6" => "\xd3\xa7",
		"\xd3\xa8" => "\xd3\xa9",
		"\xd3\xaa" => "\xd3\xab",
		"\xd3\xac" => "\xd3\xad",
		"\xd3\xae" => "\xd3\xaf",
		"\xd3\xb0" => "\xd3\xb1",
		"\xd3\xb2" => "\xd3\xb3",
		"\xd3\xb4" => "\xd3\xb5",
		"\xd3\xb8" => "\xd3\xb9",
		"\xd4\x80" => "\xd4\x81",
		"\xd4\x82" => "\xd4\x83",
		"\xd4\x84" => "\xd4\x85",
		"\xd4\x86" => "\xd4\x87",
		"\xd4\x88" => "\xd4\x89",
		"\xd4\x8a" => "\xd4\x8b",
		"\xd4\x8c" => "\xd4\x8d",
		"\xd4\x8e" => "\xd4\x8f",
		"\xd4\xb1" => "\xd5\xa1",
		"\xd4\xb2" => "\xd5\xa2",
		"\xd4\xb3" => "\xd5\xa3",
		"\xd4\xb4" => "\xd5\xa4",
		"\xd4\xb5" => "\xd5\xa5",
		"\xd4\xb6" => "\xd5\xa6",
		"\xd4\xb7" => "\xd5\xa7",
		"\xd4\xb8" => "\xd5\xa8",
		"\xd4\xb9" => "\xd5\xa9",
		"\xd4\xba" => "\xd5\xaa",
		"\xd4\xbb" => "\xd5\xab",
		"\xd4\xbc" => "\xd5\xac",
		"\xd4\xbd" => "\xd5\xad",
		"\xd4\xbe" => "\xd5\xae",
		"\xd4\xbf" => "\xd5\xaf",
		"\xd5\x80" => "\xd5\xb0",
		"\xd5\x81" => "\xd5\xb1",
		"\xd5\x82" => "\xd5\xb2",
		"\xd5\x83" => "\xd5\xb3",
		"\xd5\x84" => "\xd5\xb4",
		"\xd5\x85" => "\xd5\xb5",
		"\xd5\x86" => "\xd5\xb6",
		"\xd5\x87" => "\xd5\xb7",
		"\xd5\x88" => "\xd5\xb8",
		"\xd5\x89" => "\xd5\xb9",
		"\xd5\x8a" => "\xd5\xba",
		"\xd5\x8b" => "\xd5\xbb",
		"\xd5\x8c" => "\xd5\xbc",
		"\xd5\x8d" => "\xd5\xbd",
		"\xd5\x8e" => "\xd5\xbe",
		"\xd5\x8f" => "\xd5\xbf",
		"\xd5\x90" => "\xd6\x80",
		"\xd5\x91" => "\xd6\x81",
		"\xd5\x92" => "\xd6\x82",
		"\xd5\x93" => "\xd6\x83",
		"\xd5\x94" => "\xd6\x84",
		"\xd5\x95" => "\xd6\x85",
		"\xd5\x96" => "\xd6\x86",
		"\xe1\xb8\x80" => "\xe1\xb8\x81",
		"\xe1\xb8\x82" => "\xe1\xb8\x83",
		"\xe1\xb8\x84" => "\xe1\xb8\x85",
		"\xe1\xb8\x86" => "\xe1\xb8\x87",
		"\xe1\xb8\x88" => "\xe1\xb8\x89",
		"\xe1\xb8\x8a" => "\xe1\xb8\x8b",
		"\xe1\xb8\x8c" => "\xe1\xb8\x8d",
		"\xe1\xb8\x8e" => "\xe1\xb8\x8f",
		"\xe1\xb8\x90" => "\xe1\xb8\x91",
		"\xe1\xb8\x92" => "\xe1\xb8\x93",
		"\xe1\xb8\x94" => "\xe1\xb8\x95",
		"\xe1\xb8\x96" => "\xe1\xb8\x97",
		"\xe1\xb8\x98" => "\xe1\xb8\x99",
		"\xe1\xb8\x9a" => "\xe1\xb8\x9b",
		"\xe1\xb8\x9c" => "\xe1\xb8\x9d",
		"\xe1\xb8\x9e" => "\xe1\xb8\x9f",
		"\xe1\xb8\xa0" => "\xe1\xb8\xa1",
		"\xe1\xb8\xa2" => "\xe1\xb8\xa3",
		"\xe1\xb8\xa4" => "\xe1\xb8\xa5",
		"\xe1\xb8\xa6" => "\xe1\xb8\xa7",
		"\xe1\xb8\xa8" => "\xe1\xb8\xa9",
		"\xe1\xb8\xaa" => "\xe1\xb8\xab",
		"\xe1\xb8\xac" => "\xe1\xb8\xad",
		"\xe1\xb8\xae" => "\xe1\xb8\xaf",
		"\xe1\xb8\xb0" => "\xe1\xb8\xb1",
		"\xe1\xb8\xb2" => "\xe1\xb8\xb3",
		"\xe1\xb8\xb4" => "\xe1\xb8\xb5",
		"\xe1\xb8\xb6" => "\xe1\xb8\xb7",
		"\xe1\xb8\xb8" => "\xe1\xb8\xb9",
		"\xe1\xb8\xba" => "\xe1\xb8\xbb",
		"\xe1\xb8\xbc" => "\xe1\xb8\xbd",
		"\xe1\xb8\xbe" => "\xe1\xb8\xbf",
		"\xe1\xb9\x80" => "\xe1\xb9\x81",
		"\xe1\xb9\x82" => "\xe1\xb9\x83",
		"\xe1\xb9\x84" => "\xe1\xb9\x85",
		"\xe1\xb9\x86" => "\xe1\xb9\x87",
		"\xe1\xb9\x88" => "\xe1\xb9\x89",
		"\xe1\xb9\x8a" => "\xe1\xb9\x8b",
		"\xe1\xb9\x8c" => "\xe1\xb9\x8d",
		"\xe1\xb9\x8e" => "\xe1\xb9\x8f",
		"\xe1\xb9\x90" => "\xe1\xb9\x91",
		"\xe1\xb9\x92" => "\xe1\xb9\x93",
		"\xe1\xb9\x94" => "\xe1\xb9\x95",
		"\xe1\xb9\x96" => "\xe1\xb9\x97",
		"\xe1\xb9\x98" => "\xe1\xb9\x99",
		"\xe1\xb9\x9a" => "\xe1\xb9\x9b",
		"\xe1\xb9\x9c" => "\xe1\xb9\x9d",
		"\xe1\xb9\x9e" => "\xe1\xb9\x9f",
		"\xe1\xb9\xa0" => "\xe1\xb9\xa1",
		"\xe1\xb9\xa2" => "\xe1\xb9\xa3",
		"\xe1\xb9\xa4" => "\xe1\xb9\xa5",
		"\xe1\xb9\xa6" => "\xe1\xb9\xa7",
		"\xe1\xb9\xa8" => "\xe1\xb9\xa9",
		"\xe1\xb9\xaa" => "\xe1\xb9\xab",
		"\xe1\xb9\xac" => "\xe1\xb9\xad",
		"\xe1\xb9\xae" => "\xe1\xb9\xaf",
		"\xe1\xb9\xb0" => "\xe1\xb9\xb1",
		"\xe1\xb9\xb2" => "\xe1\xb9\xb3",
		"\xe1\xb9\xb4" => "\xe1\xb9\xb5",
		"\xe1\xb9\xb6" => "\xe1\xb9\xb7",
		"\xe1\xb9\xb8" => "\xe1\xb9\xb9",
		"\xe1\xb9\xba" => "\xe1\xb9\xbb",
		"\xe1\xb9\xbc" => "\xe1\xb9\xbd",
		"\xe1\xb9\xbe" => "\xe1\xb9\xbf",
		"\xe1\xba\x80" => "\xe1\xba\x81",
		"\xe1\xba\x82" => "\xe1\xba\x83",
		"\xe1\xba\x84" => "\xe1\xba\x85",
		"\xe1\xba\x86" => "\xe1\xba\x87",
		"\xe1\xba\x88" => "\xe1\xba\x89",
		"\xe1\xba\x8a" => "\xe1\xba\x8b",
		"\xe1\xba\x8c" => "\xe1\xba\x8d",
		"\xe1\xba\x8e" => "\xe1\xba\x8f",
		"\xe1\xba\x90" => "\xe1\xba\x91",
		"\xe1\xba\x92" => "\xe1\xba\x93",
		"\xe1\xba\x94" => "\xe1\xba\x95",
		"\xe1\xba\xa0" => "\xe1\xba\xa1",
		"\xe1\xba\xa2" => "\xe1\xba\xa3",
		"\xe1\xba\xa4" => "\xe1\xba\xa5",
		"\xe1\xba\xa6" => "\xe1\xba\xa7",
		"\xe1\xba\xa8" => "\xe1\xba\xa9",
		"\xe1\xba\xaa" => "\xe1\xba\xab",
		"\xe1\xba\xac" => "\xe1\xba\xad",
		"\xe1\xba\xae" => "\xe1\xba\xaf",
		"\xe1\xba\xb0" => "\xe1\xba\xb1",
		"\xe1\xba\xb2" => "\xe1\xba\xb3",
		"\xe1\xba\xb4" => "\xe1\xba\xb5",
		"\xe1\xba\xb6" => "\xe1\xba\xb7",
		"\xe1\xba\xb8" => "\xe1\xba\xb9",
		"\xe1\xba\xba" => "\xe1\xba\xbb",
		"\xe1\xba\xbc" => "\xe1\xba\xbd",
		"\xe1\xba\xbe" => "\xe1\xba\xbf",
		"\xe1\xbb\x80" => "\xe1\xbb\x81",
		"\xe1\xbb\x82" => "\xe1\xbb\x83",
		"\xe1\xbb\x84" => "\xe1\xbb\x85",
		"\xe1\xbb\x86" => "\xe1\xbb\x87",
		"\xe1\xbb\x88" => "\xe1\xbb\x89",
		"\xe1\xbb\x8a" => "\xe1\xbb\x8b",
		"\xe1\xbb\x8c" => "\xe1\xbb\x8d",
		"\xe1\xbb\x8e" => "\xe1\xbb\x8f",
		"\xe1\xbb\x90" => "\xe1\xbb\x91",
		"\xe1\xbb\x92" => "\xe1\xbb\x93",
		"\xe1\xbb\x94" => "\xe1\xbb\x95",
		"\xe1\xbb\x96" => "\xe1\xbb\x97",
		"\xe1\xbb\x98" => "\xe1\xbb\x99",
		"\xe1\xbb\x9a" => "\xe1\xbb\x9b",
		"\xe1\xbb\x9c" => "\xe1\xbb\x9d",
		"\xe1\xbb\x9e" => "\xe1\xbb\x9f",
		"\xe1\xbb\xa0" => "\xe1\xbb\xa1",
		"\xe1\xbb\xa2" => "\xe1\xbb\xa3",
		"\xe1\xbb\xa4" => "\xe1\xbb\xa5",
		"\xe1\xbb\xa6" => "\xe1\xbb\xa7",
		"\xe1\xbb\xa8" => "\xe1\xbb\xa9",
		"\xe1\xbb\xaa" => "\xe1\xbb\xab",
		"\xe1\xbb\xac" => "\xe1\xbb\xad",
		"\xe1\xbb\xae" => "\xe1\xbb\xaf",
		"\xe1\xbb\xb0" => "\xe1\xbb\xb1",
		"\xe1\xbb\xb2" => "\xe1\xbb\xb3",
		"\xe1\xbb\xb4" => "\xe1\xbb\xb5",
		"\xe1\xbb\xb6" => "\xe1\xbb\xb7",
		"\xe1\xbb\xb8" => "\xe1\xbb\xb9",
		"\xe1\xbc\x88" => "\xe1\xbc\x80",
		"\xe1\xbc\x89" => "\xe1\xbc\x81",
		"\xe1\xbc\x8a" => "\xe1\xbc\x82",
		"\xe1\xbc\x8b" => "\xe1\xbc\x83",
		"\xe1\xbc\x8c" => "\xe1\xbc\x84",
		"\xe1\xbc\x8d" => "\xe1\xbc\x85",
		"\xe1\xbc\x8e" => "\xe1\xbc\x86",
		"\xe1\xbc\x8f" => "\xe1\xbc\x87",
		"\xe1\xbc\x98" => "\xe1\xbc\x90",
		"\xe1\xbc\x99" => "\xe1\xbc\x91",
		"\xe1\xbc\x9a" => "\xe1\xbc\x92",
		"\xe1\xbc\x9b" => "\xe1\xbc\x93",
		"\xe1\xbc\x9c" => "\xe1\xbc\x94",
		"\xe1\xbc\x9d" => "\xe1\xbc\x95",
		"\xe1\xbc\xa9" => "\xe1\xbc\xa1",
		"\xe1\xbc\xaa" => "\xe1\xbc\xa2",
		"\xe1\xbc\xab" => "\xe1\xbc\xa3",
		"\xe1\xbc\xac" => "\xe1\xbc\xa4",
		"\xe1\xbc\xad" => "\xe1\xbc\xa5",
		"\xe1\xbc\xae" => "\xe1\xbc\xa6",
		"\xe1\xbc\xaf" => "\xe1\xbc\xa7",
		"\xe1\xbc\xb8" => "\xe1\xbc\xb0",
		"\xe1\xbc\xb9" => "\xe1\xbc\xb1",
		"\xe1\xbc\xba" => "\xe1\xbc\xb2",
		"\xe1\xbc\xbb" => "\xe1\xbc\xb3",
		"\xe1\xbc\xbc" => "\xe1\xbc\xb4",
		"\xe1\xbc\xbd" => "\xe1\xbc\xb5",
		"\xe1\xbc\xbe" => "\xe1\xbc\xb6",
		"\xe1\xbc\xbf" => "\xe1\xbc\xb7",
		"\xe1\xbd\x88" => "\xe1\xbd\x80",
		"\xe1\xbd\x89" => "\xe1\xbd\x81",
		"\xe1\xbd\x8a" => "\xe1\xbd\x82",
		"\xe1\xbd\x8b" => "\xe1\xbd\x83",
		"\xe1\xbd\x8c" => "\xe1\xbd\x84",
		"\xe1\xbd\x8d" => "\xe1\xbd\x85",
		"\xe1\xbd\x99" => "\xe1\xbd\x91",
		"\xe1\xbd\x9b" => "\xe1\xbd\x93",
		"\xe1\xbd\x9d" => "\xe1\xbd\x95",
		"\xe1\xbd\x9f" => "\xe1\xbd\x97",
		"\xe1\xbd\xa9" => "\xe1\xbd\xa1",
		"\xe1\xbd\xaa" => "\xe1\xbd\xa2",
		"\xe1\xbd\xab" => "\xe1\xbd\xa3",
		"\xe1\xbd\xac" => "\xe1\xbd\xa4",
		"\xe1\xbd\xad" => "\xe1\xbd\xa5",
		"\xe1\xbd\xae" => "\xe1\xbd\xa6",
		"\xe1\xbd\xaf" => "\xe1\xbd\xa7",
		"\xe1\xbe\x88" => "\xe1\xbe\x80",
		"\xe1\xbe\x89" => "\xe1\xbe\x81",
		"\xe1\xbe\x8a" => "\xe1\xbe\x82",
		"\xe1\xbe\x8b" => "\xe1\xbe\x83",
		"\xe1\xbe\x8c" => "\xe1\xbe\x84",
		"\xe1\xbe\x8d" => "\xe1\xbe\x85",
		"\xe1\xbe\x8e" => "\xe1\xbe\x86",
		"\xe1\xbe\x8f" => "\xe1\xbe\x87",
		"\xe1\xbe\x98" => "\xe1\xbe\x90",
		"\xe1\xbe\x99" => "\xe1\xbe\x91",
		"\xe1\xbe\x9a" => "\xe1\xbe\x92",
		"\xe1\xbe\x9b" => "\xe1\xbe\x93",
		"\xe1\xbe\x9c" => "\xe1\xbe\x94",
		"\xe1\xbe\x9d" => "\xe1\xbe\x95",
		"\xe1\xbe\x9e" => "\xe1\xbe\x96",
		"\xe1\xbe\x9f" => "\xe1\xbe\x97",
		"\xe1\xbe\xa9" => "\xe1\xbe\xa1",
		"\xe1\xbe\xaa" => "\xe1\xbe\xa2",
		"\xe1\xbe\xab" => "\xe1\xbe\xa3",
		"\xe1\xbe\xac" => "\xe1\xbe\xa4",
		"\xe1\xbe\xad" => "\xe1\xbe\xa5",
		"\xe1\xbe\xae" => "\xe1\xbe\xa6",
		"\xe1\xbe\xaf" => "\xe1\xbe\xa7",
		"\xe1\xbe\xb8" => "\xe1\xbe\xb0",
		"\xe1\xbe\xb9" => "\xe1\xbe\xb1",
		"\xe1\xbe\xba" => "\xe1\xbd\xb0",
		"\xe1\xbe\xbb" => "\xe1\xbd\xb1",
		"\xe1\xbe\xbc" => "\xe1\xbe\xb3",
		"\xe1\xbf\x88" => "\xe1\xbd\xb2",
		"\xe1\xbf\x89" => "\xe1\xbd\xb3",
		"\xe1\xbf\x8a" => "\xe1\xbd\xb4",
		"\xe1\xbf\x8b" => "\xe1\xbd\xb5",
		"\xe1\xbf\x8c" => "\xe1\xbf\x83",
		"\xe1\xbf\x98" => "\xe1\xbf\x90",
		"\xe1\xbf\x99" => "\xe1\xbf\x91",
		"\xe1\xbf\x9a" => "\xe1\xbd\xb6",
		"\xe1\xbf\x9b" => "\xe1\xbd\xb7",
		"\xe1\xbf\xa9" => "\xe1\xbf\xa1",
		"\xe1\xbf\xaa" => "\xe1\xbd\xba",
		"\xe1\xbf\xab" => "\xe1\xbd\xbb",
		"\xe1\xbf\xac" => "\xe1\xbf\xa5",
		"\xe1\xbf\xb8" => "\xe1\xbd\xb8",
		"\xe1\xbf\xb9" => "\xe1\xbd\xb9",
		"\xe1\xbf\xba" => "\xe1\xbd\xbc",
		"\xe1\xbf\xbb" => "\xe1\xbd\xbd",
		"\xe1\xbf\xbc" => "\xe1\xbf\xb3",
		"\xef\xbc\xa1" => "\xef\xbd\x81",
		"\xef\xbc\xa2" => "\xef\xbd\x82",
		"\xef\xbc\xa3" => "\xef\xbd\x83",
		"\xef\xbc\xa4" => "\xef\xbd\x84",
		"\xef\xbc\xa5" => "\xef\xbd\x85",
		"\xef\xbc\xa6" => "\xef\xbd\x86",
		"\xef\xbc\xa7" => "\xef\xbd\x87",
		"\xef\xbc\xa8" => "\xef\xbd\x88",
		"\xef\xbc\xa9" => "\xef\xbd\x89",
		"\xef\xbc\xaa" => "\xef\xbd\x8a",
		"\xef\xbc\xab" => "\xef\xbd\x8b",
		"\xef\xbc\xac" => "\xef\xbd\x8c",
		"\xef\xbc\xad" => "\xef\xbd\x8d",
		"\xef\xbc\xae" => "\xef\xbd\x8e",
		"\xef\xbc\xaf" => "\xef\xbd\x8f",
		"\xef\xbc\xb0" => "\xef\xbd\x90",
		"\xef\xbc\xb1" => "\xef\xbd\x91",
		"\xef\xbc\xb2" => "\xef\xbd\x92",
		"\xef\xbc\xb3" => "\xef\xbd\x93",
		"\xef\xbc\xb4" => "\xef\xbd\x94",
		"\xef\xbc\xb5" => "\xef\xbd\x95",
		"\xef\xbc\xb6" => "\xef\xbd\x96",
		"\xef\xbc\xb7" => "\xef\xbd\x97",
		"\xef\xbc\xb8" => "\xef\xbd\x98",
		"\xef\xbc\xb9" => "\xef\xbd\x99",
		"\xef\xbc\xba" => "\xef\xbd\x9a",
	);

	/**
	 * Unicode Character Database 6.0.0 (2010-06-04)
	 * Autogenerated by unicode_blocks_txt2php() PHP function at 2011-06-04 00:19:39, 209 blocks total
	 *
	 * @var array
	 */
	public static $unicode_blocks = array(
		'Basic Latin' => array(
			0 => 0x0000,
			1 => 0x007F,
			2 => 0,
		),
		'Latin-1 Supplement' => array(
			0 => 0x0080,
			1 => 0x00FF,
			2 => 1,
		),
		'Latin Extended-A' => array(
			0 => 0x0100,
			1 => 0x017F,
			2 => 2,
		),
		'Latin Extended-B' => array(
			0 => 0x0180,
			1 => 0x024F,
			2 => 3,
		),
		'IPA Extensions' => array(
			0 => 0x0250,
			1 => 0x02AF,
			2 => 4,
		),
		'Spacing Modifier Letters' => array(
			0 => 0x02B0,
			1 => 0x02FF,
			2 => 5,
		),
		'Combining Diacritical Marks' => array(
			0 => 0x0300,
			1 => 0x036F,
			2 => 6,
		),
		'Greek and Coptic' => array(
			0 => 0x0370,
			1 => 0x03FF,
			2 => 7,
		),
		'Cyrillic' => array(
			0 => 0x0400,
			1 => 0x04FF,
			2 => 8,
		),
		'Cyrillic Supplement' => array(
			0 => 0x0500,
			1 => 0x052F,
			2 => 9,
		),
		'Armenian' => array(
			0 => 0x0530,
			1 => 0x058F,
			2 => 10,
		),
		'Hebrew' => array(
			0 => 0x0590,
			1 => 0x05FF,
			2 => 11,
		),
		'Arabic' => array(
			0 => 0x0600,
			1 => 0x06FF,
			2 => 12,
		),
		'Syriac' => array(
			0 => 0x0700,
			1 => 0x074F,
			2 => 13,
		),
		'Arabic Supplement' => array(
			0 => 0x0750,
			1 => 0x077F,
			2 => 14,
		),
		'Thaana' => array(
			0 => 0x0780,
			1 => 0x07BF,
			2 => 15,
		),
		'NKo' => array(
			0 => 0x07C0,
			1 => 0x07FF,
			2 => 16,
		),
		'Samaritan' => array(
			0 => 0x0800,
			1 => 0x083F,
			2 => 17,
		),
		'Mandaic' => array(
			0 => 0x0840,
			1 => 0x085F,
			2 => 18,
		),
		'Devanagari' => array(
			0 => 0x0900,
			1 => 0x097F,
			2 => 19,
		),
		'Bengali' => array(
			0 => 0x0980,
			1 => 0x09FF,
			2 => 20,
		),
		'Gurmukhi' => array(
			0 => 0x0A00,
			1 => 0x0A7F,
			2 => 21,
		),
		'Gujarati' => array(
			0 => 0x0A80,
			1 => 0x0AFF,
			2 => 22,
		),
		'Oriya' => array(
			0 => 0x0B00,
			1 => 0x0B7F,
			2 => 23,
		),
		'Tamil' => array(
			0 => 0x0B80,
			1 => 0x0BFF,
			2 => 24,
		),
		'Telugu' => array(
			0 => 0x0C00,
			1 => 0x0C7F,
			2 => 25,
		),
		'Kannada' => array(
			0 => 0x0C80,
			1 => 0x0CFF,
			2 => 26,
		),
		'Malayalam' => array(
			0 => 0x0D00,
			1 => 0x0D7F,
			2 => 27,
		),
		'Sinhala' => array(
			0 => 0x0D80,
			1 => 0x0DFF,
			2 => 28,
		),
		'Thai' => array(
			0 => 0x0E00,
			1 => 0x0E7F,
			2 => 29,
		),
		'Lao' => array(
			0 => 0x0E80,
			1 => 0x0EFF,
			2 => 30,
		),
		'Tibetan' => array(
			0 => 0x0F00,
			1 => 0x0FFF,
			2 => 31,
		),
		'Myanmar' => array(
			0 => 0x1000,
			1 => 0x109F,
			2 => 32,
		),
		'Georgian' => array(
			0 => 0x10A0,
			1 => 0x10FF,
			2 => 33,
		),
		'Hangul Jamo' => array(
			0 => 0x1100,
			1 => 0x11FF,
			2 => 34,
		),
		'Ethiopic' => array(
			0 => 0x1200,
			1 => 0x137F,
			2 => 35,
		),
		'Ethiopic Supplement' => array(
			0 => 0x1380,
			1 => 0x139F,
			2 => 36,
		),
		'Cherokee' => array(
			0 => 0x13A0,
			1 => 0x13FF,
			2 => 37,
		),
		'Unified Canadian Aboriginal Syllabics' => array(
			0 => 0x1400,
			1 => 0x167F,
			2 => 38,
		),
		'Ogham' => array(
			0 => 0x1680,
			1 => 0x169F,
			2 => 39,
		),
		'Runic' => array(
			0 => 0x16A0,
			1 => 0x16FF,
			2 => 40,
		),
		'Tagalog' => array(
			0 => 0x1700,
			1 => 0x171F,
			2 => 41,
		),
		'Hanunoo' => array(
			0 => 0x1720,
			1 => 0x173F,
			2 => 42,
		),
		'Buhid' => array(
			0 => 0x1740,
			1 => 0x175F,
			2 => 43,
		),
		'Tagbanwa' => array(
			0 => 0x1760,
			1 => 0x177F,
			2 => 44,
		),
		'Khmer' => array(
			0 => 0x1780,
			1 => 0x17FF,
			2 => 45,
		),
		'Mongolian' => array(
			0 => 0x1800,
			1 => 0x18AF,
			2 => 46,
		),
		'Unified Canadian Aboriginal Syllabics Extended' => array(
			0 => 0x18B0,
			1 => 0x18FF,
			2 => 47,
		),
		'Limbu' => array(
			0 => 0x1900,
			1 => 0x194F,
			2 => 48,
		),
		'Tai Le' => array(
			0 => 0x1950,
			1 => 0x197F,
			2 => 49,
		),
		'New Tai Lue' => array(
			0 => 0x1980,
			1 => 0x19DF,
			2 => 50,
		),
		'Khmer Symbols' => array(
			0 => 0x19E0,
			1 => 0x19FF,
			2 => 51,
		),
		'Buginese' => array(
			0 => 0x1A00,
			1 => 0x1A1F,
			2 => 52,
		),
		'Tai Tham' => array(
			0 => 0x1A20,
			1 => 0x1AAF,
			2 => 53,
		),
		'Balinese' => array(
			0 => 0x1B00,
			1 => 0x1B7F,
			2 => 54,
		),
		'Sundanese' => array(
			0 => 0x1B80,
			1 => 0x1BBF,
			2 => 55,
		),
		'Batak' => array(
			0 => 0x1BC0,
			1 => 0x1BFF,
			2 => 56,
		),
		'Lepcha' => array(
			0 => 0x1C00,
			1 => 0x1C4F,
			2 => 57,
		),
		'Ol Chiki' => array(
			0 => 0x1C50,
			1 => 0x1C7F,
			2 => 58,
		),
		'Vedic Extensions' => array(
			0 => 0x1CD0,
			1 => 0x1CFF,
			2 => 59,
		),
		'Phonetic Extensions' => array(
			0 => 0x1D00,
			1 => 0x1D7F,
			2 => 60,
		),
		'Phonetic Extensions Supplement' => array(
			0 => 0x1D80,
			1 => 0x1DBF,
			2 => 61,
		),
		'Combining Diacritical Marks Supplement' => array(
			0 => 0x1DC0,
			1 => 0x1DFF,
			2 => 62,
		),
		'Latin Extended Additional' => array(
			0 => 0x1E00,
			1 => 0x1EFF,
			2 => 63,
		),
		'Greek Extended' => array(
			0 => 0x1F00,
			1 => 0x1FFF,
			2 => 64,
		),
		'General Punctuation' => array(
			0 => 0x2000,
			1 => 0x206F,
			2 => 65,
		),
		'Superscripts and Subscripts' => array(
			0 => 0x2070,
			1 => 0x209F,
			2 => 66,
		),
		'Currency Symbols' => array(
			0 => 0x20A0,
			1 => 0x20CF,
			2 => 67,
		),
		'Combining Diacritical Marks for Symbols' => array(
			0 => 0x20D0,
			1 => 0x20FF,
			2 => 68,
		),
		'Letterlike Symbols' => array(
			0 => 0x2100,
			1 => 0x214F,
			2 => 69,
		),
		'Number Forms' => array(
			0 => 0x2150,
			1 => 0x218F,
			2 => 70,
		),
		'Arrows' => array(
			0 => 0x2190,
			1 => 0x21FF,
			2 => 71,
		),
		'Mathematical Operators' => array(
			0 => 0x2200,
			1 => 0x22FF,
			2 => 72,
		),
		'Miscellaneous Technical' => array(
			0 => 0x2300,
			1 => 0x23FF,
			2 => 73,
		),
		'Control Pictures' => array(
			0 => 0x2400,
			1 => 0x243F,
			2 => 74,
		),
		'Optical Character Recognition' => array(
			0 => 0x2440,
			1 => 0x245F,
			2 => 75,
		),
		'Enclosed Alphanumerics' => array(
			0 => 0x2460,
			1 => 0x24FF,
			2 => 76,
		),
		'Box Drawing' => array(
			0 => 0x2500,
			1 => 0x257F,
			2 => 77,
		),
		'Block Elements' => array(
			0 => 0x2580,
			1 => 0x259F,
			2 => 78,
		),
		'Geometric Shapes' => array(
			0 => 0x25A0,
			1 => 0x25FF,
			2 => 79,
		),
		'Miscellaneous Symbols' => array(
			0 => 0x2600,
			1 => 0x26FF,
			2 => 80,
		),
		'Dingbats' => array(
			0 => 0x2700,
			1 => 0x27BF,
			2 => 81,
		),
		'Miscellaneous Mathematical Symbols-A' => array(
			0 => 0x27C0,
			1 => 0x27EF,
			2 => 82,
		),
		'Supplemental Arrows-A' => array(
			0 => 0x27F0,
			1 => 0x27FF,
			2 => 83,
		),
		'Braille Patterns' => array(
			0 => 0x2800,
			1 => 0x28FF,
			2 => 84,
		),
		'Supplemental Arrows-B' => array(
			0 => 0x2900,
			1 => 0x297F,
			2 => 85,
		),
		'Miscellaneous Mathematical Symbols-B' => array(
			0 => 0x2980,
			1 => 0x29FF,
			2 => 86,
		),
		'Supplemental Mathematical Operators' => array(
			0 => 0x2A00,
			1 => 0x2AFF,
			2 => 87,
		),
		'Miscellaneous Symbols and Arrows' => array(
			0 => 0x2B00,
			1 => 0x2BFF,
			2 => 88,
		),
		'Glagolitic' => array(
			0 => 0x2C00,
			1 => 0x2C5F,
			2 => 89,
		),
		'Latin Extended-C' => array(
			0 => 0x2C60,
			1 => 0x2C7F,
			2 => 90,
		),
		'Coptic' => array(
			0 => 0x2C80,
			1 => 0x2CFF,
			2 => 91,
		),
		'Georgian Supplement' => array(
			0 => 0x2D00,
			1 => 0x2D2F,
			2 => 92,
		),
		'Tifinagh' => array(
			0 => 0x2D30,
			1 => 0x2D7F,
			2 => 93,
		),
		'Ethiopic Extended' => array(
			0 => 0x2D80,
			1 => 0x2DDF,
			2 => 94,
		),
		'Cyrillic Extended-A' => array(
			0 => 0x2DE0,
			1 => 0x2DFF,
			2 => 95,
		),
		'Supplemental Punctuation' => array(
			0 => 0x2E00,
			1 => 0x2E7F,
			2 => 96,
		),
		'CJK Radicals Supplement' => array(
			0 => 0x2E80,
			1 => 0x2EFF,
			2 => 97,
		),
		'Kangxi Radicals' => array(
			0 => 0x2F00,
			1 => 0x2FDF,
			2 => 98,
		),
		'Ideographic Description Characters' => array(
			0 => 0x2FF0,
			1 => 0x2FFF,
			2 => 99,
		),
		'CJK Symbols and Punctuation' => array(
			0 => 0x3000,
			1 => 0x303F,
			2 => 100,
		),
		'Hiragana' => array(
			0 => 0x3040,
			1 => 0x309F,
			2 => 101,
		),
		'Katakana' => array(
			0 => 0x30A0,
			1 => 0x30FF,
			2 => 102,
		),
		'Bopomofo' => array(
			0 => 0x3100,
			1 => 0x312F,
			2 => 103,
		),
		'Hangul Compatibility Jamo' => array(
			0 => 0x3130,
			1 => 0x318F,
			2 => 104,
		),
		'Kanbun' => array(
			0 => 0x3190,
			1 => 0x319F,
			2 => 105,
		),
		'Bopomofo Extended' => array(
			0 => 0x31A0,
			1 => 0x31BF,
			2 => 106,
		),
		'CJK Strokes' => array(
			0 => 0x31C0,
			1 => 0x31EF,
			2 => 107,
		),
		'Katakana Phonetic Extensions' => array(
			0 => 0x31F0,
			1 => 0x31FF,
			2 => 108,
		),
		'Enclosed CJK Letters and Months' => array(
			0 => 0x3200,
			1 => 0x32FF,
			2 => 109,
		),
		'CJK Compatibility' => array(
			0 => 0x3300,
			1 => 0x33FF,
			2 => 110,
		),
		'CJK Unified Ideographs Extension A' => array(
			0 => 0x3400,
			1 => 0x4DBF,
			2 => 111,
		),
		'Yijing Hexagram Symbols' => array(
			0 => 0x4DC0,
			1 => 0x4DFF,
			2 => 112,
		),
		'CJK Unified Ideographs' => array(
			0 => 0x4E00,
			1 => 0x9FFF,
			2 => 113,
		),
		'Yi Syllables' => array(
			0 => 0xA000,
			1 => 0xA48F,
			2 => 114,
		),
		'Yi Radicals' => array(
			0 => 0xA490,
			1 => 0xA4CF,
			2 => 115,
		),
		'Lisu' => array(
			0 => 0xA4D0,
			1 => 0xA4FF,
			2 => 116,
		),
		'Vai' => array(
			0 => 0xA500,
			1 => 0xA63F,
			2 => 117,
		),
		'Cyrillic Extended-B' => array(
			0 => 0xA640,
			1 => 0xA69F,
			2 => 118,
		),
		'Bamum' => array(
			0 => 0xA6A0,
			1 => 0xA6FF,
			2 => 119,
		),
		'Modifier Tone Letters' => array(
			0 => 0xA700,
			1 => 0xA71F,
			2 => 120,
		),
		'Latin Extended-D' => array(
			0 => 0xA720,
			1 => 0xA7FF,
			2 => 121,
		),
		'Syloti Nagri' => array(
			0 => 0xA800,
			1 => 0xA82F,
			2 => 122,
		),
		'Common Indic Number Forms' => array(
			0 => 0xA830,
			1 => 0xA83F,
			2 => 123,
		),
		'Phags-pa' => array(
			0 => 0xA840,
			1 => 0xA87F,
			2 => 124,
		),
		'Saurashtra' => array(
			0 => 0xA880,
			1 => 0xA8DF,
			2 => 125,
		),
		'Devanagari Extended' => array(
			0 => 0xA8E0,
			1 => 0xA8FF,
			2 => 126,
		),
		'Kayah Li' => array(
			0 => 0xA900,
			1 => 0xA92F,
			2 => 127,
		),
		'Rejang' => array(
			0 => 0xA930,
			1 => 0xA95F,
			2 => 128,
		),
		'Hangul Jamo Extended-A' => array(
			0 => 0xA960,
			1 => 0xA97F,
			2 => 129,
		),
		'Javanese' => array(
			0 => 0xA980,
			1 => 0xA9DF,
			2 => 130,
		),
		'Cham' => array(
			0 => 0xAA00,
			1 => 0xAA5F,
			2 => 131,
		),
		'Myanmar Extended-A' => array(
			0 => 0xAA60,
			1 => 0xAA7F,
			2 => 132,
		),
		'Tai Viet' => array(
			0 => 0xAA80,
			1 => 0xAADF,
			2 => 133,
		),
		'Ethiopic Extended-A' => array(
			0 => 0xAB00,
			1 => 0xAB2F,
			2 => 134,
		),
		'Meetei Mayek' => array(
			0 => 0xABC0,
			1 => 0xABFF,
			2 => 135,
		),
		'Hangul Syllables' => array(
			0 => 0xAC00,
			1 => 0xD7AF,
			2 => 136,
		),
		'Hangul Jamo Extended-B' => array(
			0 => 0xD7B0,
			1 => 0xD7FF,
			2 => 137,
		),
		'High Surrogates' => array(
			0 => 0xD800,
			1 => 0xDB7F,
			2 => 138,
		),
		'High Private Use Surrogates' => array(
			0 => 0xDB80,
			1 => 0xDBFF,
			2 => 139,
		),
		'Low Surrogates' => array(
			0 => 0xDC00,
			1 => 0xDFFF,
			2 => 140,
		),
		'Private Use Area' => array(
			0 => 0xE000,
			1 => 0xF8FF,
			2 => 141,
		),
		'CJK Compatibility Ideographs' => array(
			0 => 0xF900,
			1 => 0xFAFF,
			2 => 142,
		),
		'Alphabetic Presentation Forms' => array(
			0 => 0xFB00,
			1 => 0xFB4F,
			2 => 143,
		),
		'Arabic Presentation Forms-A' => array(
			0 => 0xFB50,
			1 => 0xFDFF,
			2 => 144,
		),
		'Variation Selectors' => array(
			0 => 0xFE00,
			1 => 0xFE0F,
			2 => 145,
		),
		'Vertical Forms' => array(
			0 => 0xFE10,
			1 => 0xFE1F,
			2 => 146,
		),
		'Combining Half Marks' => array(
			0 => 0xFE20,
			1 => 0xFE2F,
			2 => 147,
		),
		'CJK Compatibility Forms' => array(
			0 => 0xFE30,
			1 => 0xFE4F,
			2 => 148,
		),
		'Small Form Variants' => array(
			0 => 0xFE50,
			1 => 0xFE6F,
			2 => 149,
		),
		'Arabic Presentation Forms-B' => array(
			0 => 0xFE70,
			1 => 0xFEFF,
			2 => 150,
		),
		'Halfwidth and Fullwidth Forms' => array(
			0 => 0xFF00,
			1 => 0xFFEF,
			2 => 151,
		),
		'Specials' => array(
			0 => 0xFFF0,
			1 => 0xFFFF,
			2 => 152,
		),
		'Linear B Syllabary' => array(
			0 => 0x10000,
			1 => 0x1007F,
			2 => 153,
		),
		'Linear B Ideograms' => array(
			0 => 0x10080,
			1 => 0x100FF,
			2 => 154,
		),
		'Aegean Numbers' => array(
			0 => 0x10100,
			1 => 0x1013F,
			2 => 155,
		),
		'Ancient Greek Numbers' => array(
			0 => 0x10140,
			1 => 0x1018F,
			2 => 156,
		),
		'Ancient Symbols' => array(
			0 => 0x10190,
			1 => 0x101CF,
			2 => 157,
		),
		'Phaistos Disc' => array(
			0 => 0x101D0,
			1 => 0x101FF,
			2 => 158,
		),
		'Lycian' => array(
			0 => 0x10280,
			1 => 0x1029F,
			2 => 159,
		),
		'Carian' => array(
			0 => 0x102A0,
			1 => 0x102DF,
			2 => 160,
		),
		'Old Italic' => array(
			0 => 0x10300,
			1 => 0x1032F,
			2 => 161,
		),
		'Gothic' => array(
			0 => 0x10330,
			1 => 0x1034F,
			2 => 162,
		),
		'Ugaritic' => array(
			0 => 0x10380,
			1 => 0x1039F,
			2 => 163,
		),
		'Old Persian' => array(
			0 => 0x103A0,
			1 => 0x103DF,
			2 => 164,
		),
		'Deseret' => array(
			0 => 0x10400,
			1 => 0x1044F,
			2 => 165,
		),
		'Shavian' => array(
			0 => 0x10450,
			1 => 0x1047F,
			2 => 166,
		),
		'Osmanya' => array(
			0 => 0x10480,
			1 => 0x104AF,
			2 => 167,
		),
		'Cypriot Syllabary' => array(
			0 => 0x10800,
			1 => 0x1083F,
			2 => 168,
		),
		'Imperial Aramaic' => array(
			0 => 0x10840,
			1 => 0x1085F,
			2 => 169,
		),
		'Phoenician' => array(
			0 => 0x10900,
			1 => 0x1091F,
			2 => 170,
		),
		'Lydian' => array(
			0 => 0x10920,
			1 => 0x1093F,
			2 => 171,
		),
		'Kharoshthi' => array(
			0 => 0x10A00,
			1 => 0x10A5F,
			2 => 172,
		),
		'Old South Arabian' => array(
			0 => 0x10A60,
			1 => 0x10A7F,
			2 => 173,
		),
		'Avestan' => array(
			0 => 0x10B00,
			1 => 0x10B3F,
			2 => 174,
		),
		'Inscriptional Parthian' => array(
			0 => 0x10B40,
			1 => 0x10B5F,
			2 => 175,
		),
		'Inscriptional Pahlavi' => array(
			0 => 0x10B60,
			1 => 0x10B7F,
			2 => 176,
		),
		'Old Turkic' => array(
			0 => 0x10C00,
			1 => 0x10C4F,
			2 => 177,
		),
		'Rumi Numeral Symbols' => array(
			0 => 0x10E60,
			1 => 0x10E7F,
			2 => 178,
		),
		'Brahmi' => array(
			0 => 0x11000,
			1 => 0x1107F,
			2 => 179,
		),
		'Kaithi' => array(
			0 => 0x11080,
			1 => 0x110CF,
			2 => 180,
		),
		'Cuneiform' => array(
			0 => 0x12000,
			1 => 0x123FF,
			2 => 181,
		),
		'Cuneiform Numbers and Punctuation' => array(
			0 => 0x12400,
			1 => 0x1247F,
			2 => 182,
		),
		'Egyptian Hieroglyphs' => array(
			0 => 0x13000,
			1 => 0x1342F,
			2 => 183,
		),
		'Bamum Supplement' => array(
			0 => 0x16800,
			1 => 0x16A3F,
			2 => 184,
		),
		'Kana Supplement' => array(
			0 => 0x1B000,
			1 => 0x1B0FF,
			2 => 185,
		),
		'Byzantine Musical Symbols' => array(
			0 => 0x1D000,
			1 => 0x1D0FF,
			2 => 186,
		),
		'Musical Symbols' => array(
			0 => 0x1D100,
			1 => 0x1D1FF,
			2 => 187,
		),
		'Ancient Greek Musical Notation' => array(
			0 => 0x1D200,
			1 => 0x1D24F,
			2 => 188,
		),
		'Tai Xuan Jing Symbols' => array(
			0 => 0x1D300,
			1 => 0x1D35F,
			2 => 189,
		),
		'Counting Rod Numerals' => array(
			0 => 0x1D360,
			1 => 0x1D37F,
			2 => 190,
		),
		'Mathematical Alphanumeric Symbols' => array(
			0 => 0x1D400,
			1 => 0x1D7FF,
			2 => 191,
		),
		'Mahjong Tiles' => array(
			0 => 0x1F000,
			1 => 0x1F02F,
			2 => 192,
		),
		'Domino Tiles' => array(
			0 => 0x1F030,
			1 => 0x1F09F,
			2 => 193,
		),
		'Playing Cards' => array(
			0 => 0x1F0A0,
			1 => 0x1F0FF,
			2 => 194,
		),
		'Enclosed Alphanumeric Supplement' => array(
			0 => 0x1F100,
			1 => 0x1F1FF,
			2 => 195,
		),
		'Enclosed Ideographic Supplement' => array(
			0 => 0x1F200,
			1 => 0x1F2FF,
			2 => 196,
		),
		'Miscellaneous Symbols And Pictographs' => array(
			0 => 0x1F300,
			1 => 0x1F5FF,
			2 => 197,
		),
		'Emoticons' => array(
			0 => 0x1F600,
			1 => 0x1F64F,
			2 => 198,
		),
		'Transport And Map Symbols' => array(
			0 => 0x1F680,
			1 => 0x1F6FF,
			2 => 199,
		),
		'Alchemical Symbols' => array(
			0 => 0x1F700,
			1 => 0x1F77F,
			2 => 200,
		),
		'CJK Unified Ideographs Extension B' => array(
			0 => 0x20000,
			1 => 0x2A6DF,
			2 => 201,
		),
		'CJK Unified Ideographs Extension C' => array(
			0 => 0x2A700,
			1 => 0x2B73F,
			2 => 202,
		),
		'CJK Unified Ideographs Extension D' => array(
			0 => 0x2B740,
			1 => 0x2B81F,
			2 => 203,
		),
		'CJK Compatibility Ideographs Supplement' => array(
			0 => 0x2F800,
			1 => 0x2FA1F,
			2 => 204,
		),
		'Tags' => array(
			0 => 0xE0000,
			1 => 0xE007F,
			2 => 205,
		),
		'Variation Selectors Supplement' => array(
			0 => 0xE0100,
			1 => 0xE01EF,
			2 => 206,
		),
		'Supplementary Private Use Area-A' => array(
			0 => 0xF0000,
			1 => 0xFFFFF,
			2 => 207,
		),
		'Supplementary Private Use Area-B' => array(
			0 => 0x100000,
			1 => 0x10FFFF,
			2 => 208,
		),
	);

	#calling the methods of this class only statically!
	private function __construct() {}

	/**
	 * Remove combining diactrical marks, with possibility of the restore
	 * Удаляет диакритические знаки в тексте, с возможностью восстановления (опция)
	 *
	 * @param   string|null       $s
	 * @param   array|null        $additional_chars   for example: "\xc2\xad"  #soft hyphen = discretionary hyphen
	 * @param   bool              $is_can_restored
	 * @param   array|null        &$restore_table
	 * @return  string|bool|null  Returns FALSE if error occurred
	 */
	public static function diactrical_remove($s, $additional_chars = null, $is_can_restored = false, &$restore_table = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		if ($additional_chars)
		{
			foreach ($additional_chars as $k => &$v) $v = preg_quote($v, '/');
			$re = '/((?>' . self::DIACTRICAL_RE . '|' . implode('|', $additional_chars) . ')+)/sxSX';
		}
		else $re = '/((?>' . self::DIACTRICAL_RE . ')+)/sxSX';
		if (! $is_can_restored) return preg_replace($re, '', $s);

		$restore_table = array();
		$a = preg_split($re, $s, -1, PREG_SPLIT_DELIM_CAPTURE);
		$c = count($a);
		if ($c === 1) return $s;
		$pos = 0;
		$s2 = '';
		for ($i = 0; $i < $c - 1; $i += 2)
		{
			$s2 .= $a[$i];
			#запоминаем символьные (не байтовые!) позиции
			$pos += self::strlen($a[$i]);
			$restore_table['offsets'][$pos] = $a[$i + 1];
		}
		$restore_table['length'] = $pos + self::strlen(end($a));
		return $s2 . end($a);
	}

	/**
	 * Restore combining diactrical marks, removed by self::diactrical_remove()
	 * In Russian:
	 * Восстанавливает диакритические знаки в тексте, при условии, что их символьные позиции и кол-во символов не изменились!
	 *
	 * @see     self::diactrical_remove()
	 * @param   string|null       $s
	 * @param   array             $restore_table
	 * @return  string|bool|null  Returns FALSE if error occurred (broken $restore_table)
	 */
	public static function diactrical_restore($s, array $restore_table)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		if (! $restore_table) return $s;
		if (! is_int(@$restore_table['length']) ||
			! is_array(@$restore_table['offsets']) ||
			$restore_table['length'] !== self::strlen($s)) return false;
		$a = array();
		$length = $offset = 0;
		$s2 = '';
		foreach ($restore_table['offsets'] as $pos => $diactricals)
		{
			$length = $pos - $offset;
			$s2 .= self::substr($s, $offset, $length) . $diactricals;
			$offset = $pos;
		}
		return $s2 . self::substr($s, $offset, strlen($s));
	}

	/**
	 * Encodes data from another character encoding to UTF-8.
	 *
	 * @param   array|scalar|null  $data
	 * @param   string             $charset
	 * @return  array|scalar|null  Returns FALSE if error occurred
	 */
	public static function convert_from($data, $charset = 'cp1251')
	{
		if (! ReflectionTypeHint::isValid()) return false;
		$charset = strtoupper($charset);
		return self::_convert($data, $charset, 'UTF-8');
	}

	/**
	 * Encodes data from UTF-8 to another character encoding.
	 *
	 * @param   array|scalar|null  $data
	 * @param   string             $charset
	 * @return  array|scalar|null  Returns FALSE if error occurred
	 */
	public static function convert_to($data, $charset = 'cp1251')
	{
		if (! ReflectionTypeHint::isValid()) return false;
		$charset = strtoupper($charset);
		return self::_convert($data, 'UTF-8', $charset);
	}

	/**
	 * Recoding the data of any structure to/from UTF-8.
	 * Arrays traversed recursively, recoded keys and values.
	 *
	 * @see mb_encoding_aliases()
	 * @param   array|scalar|null  $data
	 * @param   string             $charset_from
	 * @param   string             $charset_to
	 * @return  array|scalar|null  Returns FALSE if error occurred
	 */
	private static function _convert($data, $charset_from, $charset_to)
	{
		if (! ReflectionTypeHint::isValid()) return false;  #for recursive calls
		if ($charset_from === $charset_to) return $data; #speed improve
		if (is_array($data))
		{
			$d = array();
			foreach ($data as $k => &$v)
			{
				if (is_string($k))
				{
					$k = self::_convert($k, $charset_from, $charset_to);
					if (! is_string($k)) return false;
				}
				$d[$k] = self::_convert($v, $charset_from, $charset_to);
				if ($d[$k] === false && ! is_bool($v)) return false;
			}
			return $d;
		}
		if (is_string($data))
		{
			#smart behaviour for errors protected + speed improve
			if ($charset_from === 'UTF-8' && ! self::is_utf8($data)) return $data;
			if ($charset_to === 'UTF-8' && self::is_utf8($data)) return $data;

			#since PHP-5.3.x iconv() faster then mb_convert_encoding()
			if (function_exists('iconv')) return iconv($charset_from, $charset_to . '//IGNORE//TRANSLIT', $data);
			if (function_exists('mb_convert_encoding')) return mb_convert_encoding($data, $charset_to, $charset_from);

			#charset_from
			if ($charset_from === 'ISO-8859-1') return utf8_encode($data);
			if ($charset_from === 'UTF-16' || $charset_from === 'UCS-2') return self::_convert_from_utf16($data);
			if ($charset_from === 'CP1251' || $charset_from === 'CP1259') return strtr($data, self::$cp1259_table);
			if ($charset_from === 'KOI8-R') return strtr(convert_cyr_string($data, 'k', 'w'), self::$cp1259_table);
			if ($charset_from === 'ISO-8859-5') return strtr(convert_cyr_string($data, 'i', 'w'), self::$cp1259_table);
			if ($charset_from === 'CP866') return strtr(convert_cyr_string($data, 'a', 'w'), self::$cp1259_table);
			if ($charset_from === 'MAC-CYRILLIC') return strtr(convert_cyr_string($data, 'm', 'w'), self::$cp1259_table);

			#charset_to
			if ($charset_to === 'ISO-8859-1') return utf8_decode($data);
			if ($charset_to === 'CP1251' || $charset_to === 'CP1259') return strtr($data, array_flip(self::$cp1259_table));

			#last trying
			if (function_exists('recode_string'))
			{
				$s = @recode_string($charset_from . '..' . $charset_to, $data);
				if (is_string($s)) return $s;
			}

			trigger_error('Convert "' . $charset_from . '" --> "' . $charset_to . '" is not supported native, "iconv" or "mbstring" extension required', E_USER_WARNING);
			return false;
		}
		if (is_scalar($data) || is_null($data)) return $data;  #~ null, integer, float, boolean
		return false; #object or resource
	}

	/**
	 * Convert UTF-16 / UCS-2 encoding string to UTF-8.
	 * Surrogates UTF-16 are supported!
	 *
	 * In Russian:
	 * Преобразует строку из кодировки UTF-16 / UCS-2 в UTF-8.
	 * Суррогаты UTF-16 поддерживаются!
	 *
	 * @param    string        $s
	 * @param    string        $type      'BE' -- big endian byte order
	 *                                    'LE' -- little endian byte order
	 * @param    bool          $to_array  returns array chars instead whole string?
	 * @return   string|array|bool        UTF-8 string, array chars or FALSE if error occurred
	 */
	private static function _convert_from_utf16($s, $type = 'BE', $to_array = false)
	{
		static $types = array(
			'BE' => 'n',  #unsigned short (always 16 bit, big endian byte order)
			'LE' => 'v',  #unsigned short (always 16 bit, little endian byte order)
		);
		if (! array_key_exists($type, $types))
		{
			trigger_error('Unexpected value in 2-nd parameter, "' . $type . '" given!', E_USER_WARNING);
			return false;
		}
		#the fastest way:
		if (function_exists('iconv') || function_exists('mb_convert_encoding'))
		{
			if (function_exists('iconv'))                   $s = iconv('UTF-16' . $type, 'UTF-8', $s);
			elseif (function_exists('mb_convert_encoding')) $s = mb_convert_encoding($s, 'UTF-8', 'UTF-16' . $type);
			if (! $to_array) return $s;
			return self::str_split($s);
		}

		/*
		http://en.wikipedia.org/wiki/UTF-16

		The improvement that UTF-16 made over UCS-2 is its ability to encode
		characters in planes 1-16, not just those in plane 0 (BMP).

		UTF-16 represents non-BMP characters (those from U+10000 through U+10FFFF)
		using a pair of 16-bit words, known as a surrogate pair.
		First 1000016 is subtracted from the code point to give a 20-bit value.
		This is then split into two separate 10-bit values each of which is represented
		as a surrogate with the most significant half placed in the first surrogate.
		To allow safe use of simple word-oriented string processing, separate ranges
		of values are used for the two surrogates: 0xD800-0xDBFF for the first, most
		significant surrogate and 0xDC00-0xDFFF for the second, least significant surrogate.

		For example, the character at code point U+10000 becomes the code unit sequence 0xD800 0xDC00,
		and the character at U+10FFFD, the upper limit of Unicode, becomes the sequence 0xDBFF 0xDFFD.
		Unicode and ISO/IEC 10646 do not, and will never, assign characters to any of the code points
		in the U+D800-U+DFFF range, so an individual code value from a surrogate pair does not ever
		represent a character.

		http://www.russellcottrell.com/greek/utilities/SurrogatePairCalculator.htm
		http://www.russellcottrell.com/greek/utilities/UnicodeRanges.htm

		Conversion of a Unicode scalar value S to a surrogate pair <H, L>:
		  H = Math.floor((S - 0x10000) / 0x400) + 0xD800;
		  L = ((S - 0x10000) % 0x400) + 0xDC00;
		The conversion of a surrogate pair <H, L> to a scalar value:
		  N = ((H - 0xD800) * 0x400) + (L - 0xDC00) + 0x10000;
		*/
		$a = array();
		$hi = false;
		foreach (unpack($types[$type] . '*', $s) as $codepoint)
		{
			#surrogate process
			if ($hi !== false)
			{
				$lo = $codepoint;
				if ($lo < 0xDC00 || $lo > 0xDFFF) $a[] = "\xEF\xBF\xBD"; #U+FFFD REPLACEMENT CHARACTER (for broken char)
				else
				{
					$codepoint = (($hi - 0xD800) * 0x400) + ($lo - 0xDC00) + 0x10000;
					$a[] = self::chr($codepoint);
				}
				$hi = false;
			}
			elseif ($codepoint < 0xD800 || $codepoint > 0xDBFF) $a[] = self::chr($codepoint); #not surrogate
			else $hi = $codepoint; #surrogate was found
		}
		return $to_array ? $a : implode('', $a);
	}

	/**
	 * Strips out device control codes in the ASCII range.
	 *
	 * @param   array|scalar|null  Data to clean
	 * @return  array|scalar|null  Returns FALSE if error occurred
	 */
	public static function strict($data)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (is_array($data))
		{
			$d = array();
			foreach ($data as $k => &$v)
			{
				if (is_string($k))
				{
					$k = self::strict($k);
					if (! is_string($k)) return false;
				}
				$d[$k] = self::strict($v);
				if ($d[$k] === false && ! is_bool($v)) return false;
			}
			return $d;
		}
		if (is_string($data)) return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/sSX', '', $data);
		if (is_scalar($data) || is_null($data)) return $data;  #int/float/bool/null
		return false; #object or resource
	}

	/**
	 * Check the data accessory to the class of control characters in ASCII.
	 * For non string always returns FALSE.
	 *
	 * @param   scalar|null  $data
	 * @param   int|null     $found_char_offset  Returns the offset for the first found binary symbol
	 * @return  bool
	 */
	public static function has_binary($data, &$found_char_offset = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		#[\t\n\r] = [\x09\x0a\x0d]
		#[\x00-\x1f\x7f](?<![\t\n\r]) = [\x00-\x08\x0b\x0c\x0e-\x1f\x7f] = [^\x09\x0a\x0d\x20-\x7e\x80-\xff]
		if (! is_string($data) ||
			#search a binary char
			! preg_match('~[\x00-\x1f\x7f](?<![\t\n\r])~sSX', $data, $m, PREG_OFFSET_CAPTURE)) return false;
		$found_char_offset = self::strlen(substr($data, 0, $m[0][1]));
		return true;
	}

	/**
	 * Check the data accessory to the class of characters ASCII.
	 * For non string/int/float always returns FALSE
	 *
	 * @param   scalar|null  $data
	 * @param   int|null     $error_char_offset  Returns the offset for the first found non ASCII symbol
	 * @return  bool
	 */
	public static function is_ascii($data, &$error_char_offset = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (is_string($data))
		{
			if (! preg_match('~[\x80-\xff]~sSX', $data, $m, PREG_OFFSET_CAPTURE)) return true;
			$error_char_offset = $m[0][1];
			return false;
		}
		if (is_int($data) || is_float($data)) return true;
		return false;
	}

	/**
	 * Returns true if data is valid UTF-8 and false otherwise.
	 * For null, integer, float, boolean returns TRUE.
	 *
	 * The arrays are traversed recursively, if At least one element of the array
	 * its value is not in UTF-8, returns FALSE.
	 *
	 * @link    http://www.w3.org/International/questions/qa-forms-utf-8.html
	 * @link    http://ru3.php.net/mb_detect_encoding
	 * @link    http://webtest.philigon.ru/articles/utf8/
	 * @link    http://unicode.coeurlumiere.com/
	 * @param   array|scalar|null  $data
	 * @param   bool               $is_strict  strict the range of ASCII?
	 * @return  bool
	 */
	public static function is_utf8($data, $is_strict = true)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (is_array($data))
		{
			foreach ($data as $k => &$v)
			{
				if (! self::is_utf8($k, $is_strict) || ! self::is_utf8($v, $is_strict)) return false;
			}
			return true;
		}
		if (is_string($data))
		{
			if (! preg_match('~~suSX', $data)) return false;
			if (function_exists('preg_last_error') && preg_last_error() !== PREG_NO_ERROR) return false;
			#preg_match('~~suSX') much faster (up to 4 times), then mb_check_encoding($data, 'UTF-8')!
			#if (function_exists('mb_check_encoding') && ! mb_check_encoding($data, 'UTF-8')) return false; #DEPRECATED
			if ($is_strict && preg_match('/[^\x09\x0A\x0D\x20-\xBF\xC2-\xF7]/sSX', $data)) return false;
			return true;
		}
		if (is_scalar($data) || is_null($data)) return true;  #int/float/bool/null
		return false; #object or resource
	}

	/**
	 * Tries to detect if a string is in Unicode encoding
	 *
	 * @deprecated  Slowly, use self::is_utf8() instead
	 * @see     self::is_utf8()
	 * @param   string   $s          текст
	 * @param   bool     $is_strict  строгая проверка диапазона ASCII?
	 * @return  bool
	 */
	public static function check($s, $is_strict = true)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		for ($i = 0, $len = strlen($s); $i < $len; $i++)
		{
			$c = ord($s[$i]);
			if ($c < 0x80) #1 byte  0bbbbbbb
			{
				if ($is_strict === false || ($c > 0x1F && $c < 0x7F) || $c == 0x09 || $c == 0x0A || $c == 0x0D) continue;
			}
			if (($c & 0xE0) == 0xC0) $n = 1; #2 bytes 110bbbbb 10bbbbbb
			elseif (($c & 0xF0) == 0xE0) $n = 2; #3 bytes 1110bbbb 10bbbbbb 10bbbbbb
			elseif (($c & 0xF8) == 0xF0) $n = 3; #4 bytes 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
			elseif (($c & 0xFC) == 0xF8) $n = 4; #5 bytes 111110bb 10bbbbbb 10bbbbbb 10bbbbbb 10bbbbbb
			elseif (($c & 0xFE) == 0xFC) $n = 5; #6 bytes 1111110b 10bbbbbb 10bbbbbb 10bbbbbb 10bbbbbb 10bbbbbb
			else return false; #does not match any model
			#n bytes matching 10bbbbbb follow ?
			for ($j = 0; $j < $n; $j++)
			{
				$i++;
				if ($i == $len || ((ord($s[$i]) & 0xC0) != 0x80) ) return false;
			}
		}
		return true;
	}

	/**
	 * Check the data in UTF-8 charset on given ranges of the standard UNICODE.
	 * The suitable alternative to regular expressions.
	 *
	 * For null, integer, float, boolean returns TRUE.
	 *
	 * Arrays traversed recursively (keys and values).
	 * At least if one array element value is not passed checking, it returns FALSE.
	 *
	 * @example
	 *   #A simple check the standard named ranges:
	 *   UTF8::blocks_check('поисковые системы Google и Yandex', array('Basic Latin', 'Cyrillic'));
	 *   #You can check the named, direct ranges or codepoints together:
	 *   UTF8::blocks_check('поисковые системы Google и Yandex', array(array(0x20, 0x7E),     #[\x20-\x7E]
	 *                                                                 array(0x0410, 0x044F), #[A-Яa-я]
	 *                                                                 0x0401, #russian yo (Ё)
	 *                                                                 0x0451, #russian ye (ё)
	 *                                                                 'Arrows',
	 *                                                                ));
	 *
	 * @link    http://www.unicode.org/charts/
	 * @param   array|scalar|null  $data
	 * @param   array|string       $blocks
	 * @return  bool               Возвращает TRUE, если все символы из текста принадлежат указанным диапазонам
	 *                             и FALSE в противном случае или для разбитого UTF-8.
	 */
	public static function blocks_check($data, $blocks)
	{
		if (! ReflectionTypeHint::isValid()) return false;

		if (is_array($data))
		{
			foreach ($data as $k => &$v)
			{
				if (! self::blocks_check($k, $blocks) || ! self::blocks_check($v, $blocks)) return false;
			}
			return true;
		}

		if (is_int($data)) $data = strval($data);
		elseif (is_float($data)) $data = str_replace(',', '.', strval($data));
		elseif (! is_string($data)) return false;

		$chars = self::str_split($data);
		if ($chars === false) return false; #broken UTF-8
		unset($data); #memory free
		$skip = array(); #save to cache already checked symbols
		foreach ($chars as $i => $char)
		{
			if (array_key_exists($char, $skip)) continue; #speed improve
			$codepoint = self::ord($char);
			if (! is_int($codepoint)) return false; #broken UTF-8?
			$is_valid = false;
			$blocks = (array)$blocks;
			foreach ($blocks as $j => $block)
			{
				if (is_string($block))
				{
					if (! array_key_exists($block, self::$unicode_blocks))
					{
						trigger_error('Unknown block "' . $block . '"!', E_USER_WARNING);
						return false;
					}
					list ($min, $max) = self::$unicode_blocks[$block];
				}
				elseif (is_array($block)) list ($min, $max) = $block;
				elseif (is_int($block)) $min = $max = $block;
				else trigger_error('A string/array/int type expected for block[' . $j . ']!', E_USER_ERROR);
				if ($codepoint >= $min && $codepoint <= $max)
				{
					$is_valid = true;
					break;
				}
			}
			if (! $is_valid) return false;
			$skip[$char] = null;
		}
		return true;
	}

	/**
	 * Сравнение строк
	 *
	 * @param   string|null    $s1
	 * @param   string|null    $s2
	 * @param   string         $locale   For example, 'en_CA', 'ru_RU'
	 * @return  int|bool|null  Returns FALSE if error occurred
	 *                         Returns < 0 if $s1 is less than $s2;
	 *                                 > 0 if $s1 is greater than $s2;
	 *                                 0 if they are equal.
	 */
	public static function strcmp($s1, $s2, $locale = '')
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s1) || ! is_string($s2)) return null;
		if (! function_exists('collator_create')) return strcmp($s1, $s2);
		# PHP 5 >= 5.3.0, PECL intl >= 1.0.0
		# If empty string ("") or "root" are passed, UCA rules will be used.
		$c = new Collator($locale);
		if (! $c)
		{
			# Returns an "empty" object on error. You can use intl_get_error_code() and/or intl_get_error_message() to know what happened.
			trigger_error(intl_get_error_message(), E_USER_WARNING);
			return false;
		}
		return $c->compare($s1, $s2);
	}

	/**
	 * Сравнение строк для N первых символов
	 *
	 * @param   string|null    $s1
	 * @param   string|null    $s2
	 * @param   int            $length
	 * @return  int|bool|null  Returns FALSE if error occurred
	 *                         Returns < 0 if $s1 is less than $s2;
	 *                                 > 0 if $s1 is greater than $s2;
	 *                                 0 if they are equal.
	 */
	public static function strncmp($s1, $s2, $length)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s1) || ! is_string($s2)) return null;
		return self::strcmp(self::substr($s1, 0, $length), self::substr($s2, 0, $length));
	}

	/**
	 * Implementation strcasecmp() function for UTF-8 encoding string.
	 *
	 * @param   string|null    $s1
	 * @param   string|null    $s2
	 * @return  int|bool|null  Returns FALSE if error occurred
	 *                         Returns < 0 if $s1 is less than $s2;
	 *                                 > 0 if $s1 is greater than $s2;
	 *                                 0 if they are equal.
	 */
	public static function strcasecmp($s1, $s2)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s1) || ! is_string($s2)) return null;
		return self::strcmp(self::lowercase($s1), self::lowercase($s2));
	}

	/**
	 * Converts a UTF-8 string to a UNICODE codepoints
	 *
	 * @param   string|null     $s  UTF-8 string
	 * @return  array|bool|null     Unicode codepoints
	 *                              Returns FALSE if $s broken (not UTF-8)
	 */
	public static function to_unicode($s)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		$s2 = null;
		#since PHP-5.3.x iconv() little faster then mb_convert_encoding()
		if (function_exists('iconv')) $s2 = @iconv('UTF-8', 'UCS-4BE', $s);
		elseif (function_exists('mb_convert_encoding')) $s2 = @mb_convert_encoding($s, 'UCS-4BE', 'UTF-8');
		if (is_string($s2)) return array_values(unpack('N*', $s2));
		if ($s2 !== null) return false;

		$a = self::str_split($s);
		if (! is_array($a)) return false;
		return array_map(array(__CLASS__, 'ord'), $a);
	}

	/**
	 * Converts a UNICODE codepoints to a UTF-8 string
	 *
	 * @param   array|null       $a  Unicode codepoints
	 * @return  string|bool|null     UTF-8 string
	 *                               Returns FALSE if error occurred
	 */
	public static function from_unicode($a)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_array($a)) return $a;

		#since PHP-5.3.x iconv() little faster then mb_convert_encoding()
		if (function_exists('iconv'))
		{
			array_walk($a, function(&$cp) { $cp = pack('N', $cp); });
			$s = @iconv('UCS-4BE', 'UTF-8', implode('', $a));
			if (! is_string($s)) return false;
			return $s;
		}
		if (function_exists('mb_convert_encoding'))
		{
			array_walk($a, function(&$cp) { $cp = pack('N', $cp); });
			$s = mb_convert_encoding(implode('', $a), 'UTF-8', 'UCS-4BE');
			if (! is_string($s)) return false;
			return $s;
		}

		return implode('', array_map(array(__CLASS__, 'chr'), $a));
	}

	/**
	 * Converts a UTF-8 character to a UNICODE codepoint
	 *
	 * @param   string|null    $char  UTF-8 character
	 * @return  int|bool|null         Unicode codepoint
	 *                                Returns FALSE if $char broken (not UTF-8)
	 */
	public static function ord($char)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($char)) return $char;

		static $cache = array();
		if (array_key_exists($char, $cache)) return $cache[$char]; #speed improve

		switch (strlen($char))
		{
			case 1 : return $cache[$char] = ord($char);
			case 2 : return $cache[$char] = (ord($char{1}) & 63) |
											((ord($char{0}) & 31) << 6);
			case 3 : return $cache[$char] = (ord($char{2}) & 63) |
											((ord($char{1}) & 63) << 6) |
											((ord($char{0}) & 15) << 12);
			case 4 : return $cache[$char] = (ord($char{3}) & 63) |
											((ord($char{2}) & 63) << 6) |
											((ord($char{1}) & 63) << 12) |
											((ord($char{0}) & 7)  << 18);
			default :
				trigger_error('Character 0x' . bin2hex($char) . ' is not UTF-8!', E_USER_WARNING);
				return false;
		}
	}

	/**
	 * Converts a UNICODE codepoint to a UTF-8 character
	 *
	 * @param   int|digit|null  $cp  Unicode codepoint
	 * @return  string|bool|null     UTF-8 character
	 *                               Returns FALSE if error occurred
	 */
	public static function chr($cp)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_int($cp) && ! ctype_digit($cp)) return $cp;

		static $cache = array();
		if (array_key_exists($cp, $cache)) return $cache[$cp]; #speed improve

		if ($cp <= 0x7f)     return $cache[$cp] = chr($cp);
		if ($cp <= 0x7ff)    return $cache[$cp] = chr(0xc0 | ($cp >> 6))  .
												  chr(0x80 | ($cp & 0x3f));
		if ($cp <= 0xffff)   return $cache[$cp] = chr(0xe0 | ($cp >> 12)) .
												  chr(0x80 | (($cp >> 6) & 0x3f)) .
												  chr(0x80 | ($cp & 0x3f));
		if ($cp <= 0x10ffff) return $cache[$cp] = chr(0xf0 | ($cp >> 18)) .
												  chr(0x80 | (($cp >> 12) & 0x3f)) .
												  chr(0x80 | (($cp >> 6) & 0x3f)) .
												  chr(0x80 | ($cp & 0x3f));
		#U+FFFD REPLACEMENT CHARACTER
		return $cache[$cp] = "\xEF\xBF\xBD";
	}

	/**
	 * Implementation chunk_split() function for UTF-8 encoding string.
	 *
	 * @param   string|null       $s
	 * @param   int|digit|null    $length
	 * @param   string|null       $glue
	 * @return  string|bool|null  Returns FALSE if error occurred
	 */
	public static function chunk_split($s, $length = null, $glue = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		$length = intval($length);
		$glue   = strval($glue);
		if ($length < 1) $length = 76;
		if ($glue === '') $glue = "\r\n";
		$a = self::str_split($s, $length);
		if (! is_array($a)) return false;
		return implode($glue, $a);
	}

	/**
	 * Changes all keys in an array
	 *
	 * @param   array|null       $a
	 * @param   int              $mode  {CASE_LOWER|CASE_UPPER}
	 * @param   bool             $is_recursive
	 * @return  array|bool|null  Returns FALSE if error occurred
	 */
	public static function array_change_key_case($a, $mode, $is_recursive = false)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_array($a)) return $a;

		$a2 = array();
		foreach ($a as $k => $v)
		{
			if (is_string($k))
			{
				$k = self::convert_case($k, $mode);
				if ($k === false) return false;
			}
			if ($is_recursive && is_array($v)) #recursive support
			{
				$v = self::array_change_key_case($v, $mode, $is_recursive);
				if (! is_array($v)) return false;
			}
			$a2[$k] = $v;
		}
		return $a2;
	}

	/**
	 * Конвертирует регистр букв в данных в кодировке UTF-8.
	 * Массивы обходятся рекурсивно, при этом конвертируются только значения
	 * в элементах массива, а ключи остаются без изменений.
	 * Для конвертирования только ключей используйте метод self::array_change_key_case().
	 *
	 * @see     self::array_change_key_case()
	 * @link    http://www.unicode.org/charts/PDF/U0400.pdf
	 * @link    http://ru.wikipedia.org/wiki/ISO_639-1
	 * @param   array|scalar|null $data  Данные произвольной структуры
	 * @param   int               $mode  {CASE_LOWER|CASE_UPPER}
	 * @param   bool              $is_ascii_optimization    for speed improve
	 * @return  scalar|bool|null  Returns FALSE if error occurred
	 */
	public static function convert_case($data, $mode, $is_ascii_optimization = true)
	{
		if (! ReflectionTypeHint::isValid()) return false;

		if (is_array($data)) #recursive support
		{
			foreach ($data as $k => $v)
			{
				$data[$k] = self::convert_case($v, $mode);
				if ($data[$k] === false && ! is_bool($v)) return false;
			}
			return $data;
		}
		if (! is_string($data) || ! $data) return $data;

		if ($mode === CASE_UPPER)
		{
			if ($is_ascii_optimization && self::is_ascii($data)) return strtoupper($data); #speed improve!
			#deprecated, since PHP-5.3.x strtr() 2-3 times faster then mb_strtolower()
			#if (function_exists('mb_strtoupper')) return mb_strtoupper($data, 'utf-8');
			return strtr($data, array_flip(self::$convert_case_table));
		}
		if ($mode === CASE_LOWER)
		{
			if ($is_ascii_optimization && self::is_ascii($data)) return strtolower($data); #speed improve!
			#deprecated, since PHP-5.3.x strtr() 2-3 times faster then mb_strtolower()
			#if (function_exists('mb_strtolower')) return mb_strtolower($data, 'utf-8');
			return strtr($data, self::$convert_case_table);
		}
		trigger_error('Parameter 2 should be a constant of CASE_LOWER or CASE_UPPER!', E_USER_WARNING);
		return $data;
	}

	/**
	 * Convert a data to lower case
	 *
	 * @param   array|scalar|null  $data
	 * @return  scalar|bool|null   Returns FALSE if error occurred	 */
	public static function lowercase($data)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		return self::convert_case($data, CASE_LOWER);
	}

	/**
	 * Convert a data to upper case
	 *
	 * @param   array|scalar|null  $data
	 * @return  scalar|null        Returns FALSE if error occurred
	 */
	public static function uppercase($data)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		return self::convert_case($data, CASE_UPPER);
	}

	/**
	 * Convert a data to lower case
	 *
	 * @param   array|scalar|null  $data
	 * @return  scalar|bool|null   Returns FALSE if error occurred
	 */
	public static function strtolower($data)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		return self::convert_case($data, CASE_LOWER);
	}

	/**
	 * Convert a data to upper case
	 *
	 * @param   array|scalar|null  $data
	 * @return  scalar|null        Returns FALSE if error occurred
	 */
	public static function strtoupper($data)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		return self::convert_case($data, CASE_UPPER);
	}


	/**
	 * Convert all HTML entities to native UTF-8 characters
	 * Функция декодирует гораздо больше именованных сущностей, чем стандартная html_entity_decode()
	 * Все dec и hex сущности так же переводятся в UTF-8.
	 *
	 * Example: '&quot;' or '&#34;' or '&#x22;' will be converted to '"'.
	 *
	 * @link  http://www.htmlhelp.com/reference/html40/entities/
	 * @link  http://www.alanwood.net/demos/ent4_frame.html (HTML 4.01 Character Entity References)
	 * @link  http://msdn.microsoft.com/workshop/author/dhtml/reference/charsets/charset1.asp?frame=true
	 * @link  http://msdn.microsoft.com/workshop/author/dhtml/reference/charsets/charset2.asp?frame=true
	 * @link  http://msdn.microsoft.com/workshop/author/dhtml/reference/charsets/charset3.asp?frame=true
	 *
	 * @param   scalar|null  $s
	 * @param   bool         $is_special_chars   Дополнительно обрабатывать специальные html сущности? (&lt; &gt; &amp; &quot; &apos;)
	 * @return  scalar|null  Returns FALSE if error occurred
	 */
	public static function html_entity_decode($s, $is_special_chars = false)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		#speed improve
		if (strlen($s) < 4  #по минимальной длине сущности - 4 байта: &#d; &xx;
			|| ($pos = strpos($s, '&') === false) || strpos($s, ';', $pos) === false) return $s;

		$table = self::$html_entity_table;
		if ($is_special_chars)
		{
			$table += self::$html_special_chars_table
					+ array(
						#&apos; entity is only available in XHTML/HTML5 and not in plain HTML, see http://www.w3.org/TR/xhtml1/#C_16
						'&apos;' => "\x27",  #U+0027 ['] &#39; apostrophe
					);  
		}
		#replace named entities
		$s = strtr($s, $table);
		#block below deprecated, since PHP-5.3.x strtr() 1.5 times faster
		if (0 && preg_match_all('/&[a-zA-Z]++\d*+;/sSX', $s, $m, null, $pos))
		{
			foreach (array_unique($m[0]) as $entity)
			{
				if (array_key_exists($entity, $table)) $s = str_replace($entity, $table[$entity], $s);
			}
		}

		#заменяем числовые dec и hex сущности:
		if (strpos($s, '&#') !== false)  #speed improve
		{
			$class = __CLASS__;
			$html_special_chars_table_flipped = array_flip(self::$html_special_chars_table);
			$s = preg_replace_callback('/&#((x)[\da-fA-F]{1,6}+|\d{1,7}+);/sSX',
										function (array $m) use ($class, $html_special_chars_table_flipped, $is_special_chars)
										{
											$codepoint = isset($m[2]) && $m[2] === 'x' ? hexdec($m[1]) : $m[1];
											if (! $is_special_chars)
											{
												$char = pack('C', $codepoint);
												if (array_key_exists($char, $html_special_chars_table_flipped)) return $html_special_chars_table_flipped[$char];
											}
											return $class::chr($codepoint);
										}, $s);
		}
		return $s;
	}

	/**
	 * Convert special UTF-8 characters to HTML entities.
	 * Функция кодирует гораздо больше именованных сущностей, чем стандартная htmlentities()
	 *
	 * @link  http://www.htmlhelp.com/reference/html40/entities/
	 * @link  http://www.alanwood.net/demos/ent4_frame.html (HTML 4.01 Character Entity References)
	 * @link  http://msdn.microsoft.com/workshop/author/dhtml/reference/charsets/charset1.asp?frame=true
	 * @link  http://msdn.microsoft.com/workshop/author/dhtml/reference/charsets/charset2.asp?frame=true
	 * @link  http://msdn.microsoft.com/workshop/author/dhtml/reference/charsets/charset3.asp?frame=true
	 *
	 * @param   scalar|null  $s
	 * @param   bool         $is_special_chars_only          Обрабатывать только специальные html сущности? (&lt; &gt; &amp; &quot;)
	 * @return  scalar|null  Returns FALSE if error occurred
	 */
	public static function html_entity_encode($s, $is_special_chars_only = false)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		if ($is_special_chars_only) return strtr($s, array_flip(self::$html_special_chars_table));  #binary support
		#if ($is_special_chars_only) return htmlspecialchars($s);  #DEPRECATED, charset dependent

		#replace UTF-8 chars to named entities:
		$s = strtr($s, array_flip(self::$html_entity_table));

		#block below deprecated, since PHP-5.3.x strtr() 3 times faster
		if (0 && preg_match_all('~(?>	[\xc2\xc3\xc5\xc6\xcb\xce\xcf][\x80-\xbf]  #2 bytes
									|	\xe2[\x80-\x99][\x82-\xac]                 #3 bytes
								  )
								~sxSX', $s, $m))
		{
			$table = array_flip(self::$html_entity_table);
			foreach (array_unique($m[0]) as $char)
			{
				if (array_key_exists($char, $table)) $s = str_replace($char, $table[$char], $s);
			}
		}

		return $s;
	}

	/**
	 * Make regular expression for case insensitive match
	 * Example (only digits): "123" => "123"
	 * Example (only ASCII):  "123_test" => "(?i:123_test)"
	 * Example (upper ASCII): "123_слово_test" => "123_(с|С)(л|Л)(о|О)(в|В)(о|О)_[tT][eE][sS][tT]"
	 *
	 * @param  string|null $s
	 * @param  string|null $delimiter  If the optional delimiter is specified, it will also be escaped.
	 *                                 This is useful for escaping the delimiter that is required by the PCRE functions.
	 *                                 The / is the most commonly used delimiter.
	 * @return string|bool|null        Returns FALSE if error occurred
	 */
	public static function preg_quote_case_insensitive($s, $delimiter = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		if (ctype_digit($s)) return preg_quote($s, $delimiter); #speed improve
		if (self::is_ascii($s)) return '(?i:' . preg_quote($s, $delimiter) . ')'; #speed improve

		$s_lc = self::convert_case($s, CASE_LOWER, false); if ($s_lc === false) return false;
		$s_uc = self::convert_case($s, CASE_UPPER, false); if ($s_uc === false) return false;
		if ($s_lc === $s_uc) return preg_quote($s, $delimiter); #speed improve

		$chars_lc = self::str_split($s_lc); if ($chars_lc === false) return false;
		$chars_uc = self::str_split($s_uc); if ($chars_uc === false) return false;

		$s_re = '';
		foreach ($chars_lc as $i => $char)
		{
			if ($chars_lc[$i] === $chars_uc[$i])
				$s_re .= preg_quote($chars_lc[$i], $delimiter);
			elseif (strlen($chars_lc[$i]) === 1 /*self::is_ascii($chars_lc[$i])*/)
				$s_re .= '[' . self::_preg_quote_class($chars_lc[$i] . $chars_uc[$i], $delimiter) . ']';
			else
				#для русских и др. букв, т. к. флаг /u и (?i:слово) не помогают :(
				$s_re .= '(' . preg_quote($chars_lc[$i], $delimiter) . '|'
							 . preg_quote($chars_uc[$i], $delimiter) . ')';
		}
		return $s_re;
	}

	/**
	 * Call preg_match_all() and convert byte offsets into character offsets for PREG_OFFSET_CAPTURE flag.
	 * This is regardless of whether you use /u modifier.
	 *
	 * @link  http://bolknote.ru/2010/09/08/~2704
	 *
	 * @param   string           $pattern
	 * @param   string|null      $subject
	 * @param   array            $matches
	 * @param   int              $flags
	 * @param   int              $char_offset
	 * @return  array|bool|null  Returns FALSE if error occurred
	 */
	public static function preg_match_all($pattern, $subject, &$matches, $flags = PREG_PATTERN_ORDER, $char_offset = 0)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($subject)) return $subject;

		$byte_offset = ($char_offset > 0) ? strlen(self::substr($subject, 0, $char_offset)) : $char_offset;

		$return = preg_match_all($pattern, $subject, $matches, $flags, $byte_offset);
		if ($return === false) return false;

		if ($flags & PREG_OFFSET_CAPTURE)
		{
			foreach ($matches as &$match)
			{
				foreach ($match as &$a) $a[1] = self::strlen(substr($subject, 0, $a[1]));
			}
		}

		return $return;
	}

	#alias for self::str_limit()
	public static function truncate($s, $maxlength = null, $continue = "\xe2\x80\xa6", &$is_cutted = null, $tail_min_length = 20)
	{
		return self::str_limit($s, $maxlength, $continue, $is_cutted, $tail_min_length);
	}

	/**
	 * Обрезает текст в кодировке UTF-8 до заданной длины,
	 * причём последнее слово показывается целиком, а не обрывается на середине.
	 * Html сущности корректно обрабатываются.
	 *
	 * @param   string|null     $s                Текст в кодировке UTF-8
	 * @param   int|null|digit  $maxlength        Ограничение длины текста
	 * @param   string          $continue         Завершающая строка, которая будет вставлена после текста, если он обрежется
	 * @param   bool|null       &$is_cutted       Текст был обрезан?
	 * @param   int|digit       $tail_min_length  Если длина "хвоста", оставшегося после обрезки текста, меньше $tail_min_length,
	 *                                            то текст возвращается без изменений
	 * @return  string|bool|null                  Returns FALSE if error occurred
	 */
	public static function str_limit($s, $maxlength = null, $continue = "\xe2\x80\xa6", &$is_cutted = null, $tail_min_length = 20) #"\xe2\x80\xa6" = "&hellip;"
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		$is_cutted = false;
		if ($continue === null) $continue = "\xe2\x80\xa6";
		if (! $maxlength) $maxlength = 256;

		#speed improve block
		#{{{
		if (strlen($s) <= $maxlength) return $s;
		$s2 = str_replace("\r\n", '?', $s);
		$s2 = preg_replace('~' . self::HTML_ENTITY_RE . '~sxSX', '?', $s2);
		if (strlen($s2) <= $maxlength || self::strlen($s2) <= $maxlength) return $s;
		#}}}

		$r = preg_match_all('~(?> \r\n   # next line
								   | ' . self::HTML_ENTITY_RE . '
								   | .
								 )
								~sxuSX', $s, $m);
		if ($r === false) return false;

		#d($m);
		if (count($m[0]) <= $maxlength) return $s;

		$left = implode('', array_slice($m[0], 0, $maxlength));
		#из диапазона ASCII исключаем буквы, цифры, открывающие парные символы [a-zA-Z\d\(\{\[] и некоторые др. символы
		#нельзя вырезать в конце строки символ ";", т.к. он используются в сущностях &xxx;
		$left2 = rtrim($left, "\x00..\x28\x2A..\x2F\x3A\x3C..\x3E\x40\x5B\x5C\x5E..\x60\x7B\x7C\x7E\x7F");
		if (strlen($left) !== strlen($left2)) $return = $left2 . $continue;
		else
		{
			#добавляем остаток к обрезанному слову
			$right = implode('', array_slice($m[0], $maxlength));
			preg_match('/^(?>
							#цифры, закрывающие парные символы, дефис для составных слов, дата, время, IP-адреса, URL типа www.ya.ru:80!
								[\d\)\]\}\-\.:]+
							#letters
							|	\p{L}+
							#quotation marks
							|	[' . implode('', self::$html_quotation_mark_table) . ']+
						  )+
						/suxSX', $right, $m);
			#d($m);
			$right = isset($m[0]) ? rtrim($m[0], '.-') : '';
			$return = $left . $right;
			if (strlen($return) !== strlen($s)) $return .= $continue;
		}
		if (self::strlen($s) - self::strlen($return) < $tail_min_length) return $s;

		$is_cutted = true;
		return $return;
	}

	/**
	 * Implementation str_split() function for UTF-8 encoding string.
	 *
	 * @param   string|null      $s
	 * @param   int|null|digit   $length
	 * @return  array|bool|null  Returns FALSE if error occurred
	 */
	public static function str_split($s, $length = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		$length = ($length === null) ? 1 : intval($length);
		if ($length < 1) return false;
		#there are limits in regexp for {min,max}!
		if (preg_match_all('~.~suSX', $s, $m) === false) return false;
		if (function_exists('preg_last_error') && preg_last_error() !== PREG_NO_ERROR) return false;
		if ($length === 1) $a = $m[0];
		else
		{
			$a = array();
			for ($i = 0, $c = count($m[0]); $i < $c; $i += $length) $a[] = implode('', array_slice($m[0], $i, $length));
		}
		return $a;
	}

	/**
	 * Implementation strlen() function for UTF-8 encoding string.
	 *
	 * @param   string|null    $s
	 * @return  int|bool|null  Returns FALSE if error occurred
	 */
	public static function strlen($s)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		//since PHP-5.3.x mb_strlen() faster then strlen(utf8_decode())
		if (function_exists('mb_strlen')) return mb_strlen($s, 'utf-8');

		/*
		  utf8_decode() converts characters that are not in ISO-8859-1 to '?', which, for the purpose of counting, is quite alright.
		  It's much faster than iconv_strlen()
		  Note: this function does not count bad UTF-8 bytes in the string - these are simply ignored
		*/
		return strlen(utf8_decode($s));

		/*
		#iconv_strlen() slowly then strlen(utf8_decode())
		if (function_exists('iconv_strlen')) return iconv_strlen($s, 'utf-8');

		#Do not count UTF-8 continuation bytes
		#return strlen(preg_replace('/[\x80-\xBF]/sSX', '', $s));

		#slowly then strlen(utf8_decode())
		preg_match_all('~.~suSX', $str, $m);
		return count($m[0]);

		#slowly then preg_match_all() + count()
		$n = 0;
		for ($i = 0, $len = strlen($s); $i < $len; $i++)
		{
			$c = ord(substr($s, $i, 1));
			if ($c < 0x80) $n++;                 #single-byte (0xxxxxx)
			elseif (($c & 0xC0) == 0xC0) $n++;   #multi-byte starting byte (11xxxxxx)
		}
		return $n;
		*/
	}

	/**
	 * Implementation strpos() function for UTF-8 encoding string
	 *
	 * @param   string|null    $s       The entire string
	 * @param   string|int     $needle  The searched substring
	 * @param   int|null       $offset  The optional offset parameter specifies the position from which the search should be performed
	 * @return  int|bool|null           Returns the numeric position of the first occurrence of needle in haystack.
	 *                                  If needle is not found, will return FALSE.
	 */
	public static function strpos($s, $needle, $offset = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		if ($offset === null || $offset < 0) $offset = 0;
		#mb_strpos() faster then iconv_strpos()
		if (function_exists('mb_strpos')) return mb_strpos($s, $needle, $offset, 'utf-8');
		#iconv_strpos() deprecated, because slowly than self::strlen(substr())
		#if (function_exists('iconv_strpos')) return iconv_strpos($s, $needle, $offset, 'utf-8');
		$byte_pos = $offset;
		do if (($byte_pos = strpos($s, $needle, $byte_pos)) === false) return false;
		while (($char_pos = self::strlen(substr($s, 0, $byte_pos++))) < $offset);
		return $char_pos;
	}

	/**
	 * Find position of first occurrence of a case-insensitive string.
	 *
	 * @param   string|null    $s       The entire string
	 * @param   string|int     $needle  The searched substring
	 * @param   int|null       $offset  The optional offset parameter specifies the position from which the search should be performed
	 * @return  int|bool|null           Returns the numeric position of the first occurrence of needle in haystack.
	 *                                  If needle is not found, will return FALSE.
	 */
	public static function stripos($s, $needle, $offset = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		if ($offset === null || $offset < 0) $offset = 0;
		if (function_exists('mb_stripos')) return mb_stripos($s, $needle, $offset, 'utf-8');

		#optimization block (speed improve)
		#{{{
		$ascii_int = intval(self::is_ascii($s)) + intval(self::is_ascii($needle));
		if ($ascii_int === 1) return false;
		if ($ascii_int === 2) return stripos($s, $needle, $offset);
		#}}}

		$s = self::convert_case($s, CASE_LOWER, false);
		if ($s === false) return false;
		$needle = self::convert_case($needle, CASE_LOWER, false);
		if ($needle === false) return false;
		return self::strpos($s, $needle, $offset);
	}

	/**
	 * Implementation strrev() function for UTF-8 encoding string
	 *
	 * @param   string|null       $s
	 * @return  string|bool|null  Returns FALSE if error occurred
	 */
	public static function strrev($s)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		if (0) #TODO test speed
		{
			$s = self::_convert($s, 'UTF-8', 'UTF-32');
			if (! is_string($s)) return false;
			$s = implode('', array_reverse(str_split($s, 4)));
			return self::_convert($s, 'UTF-32', 'UTF-8');
		}

		if (! is_array($a = self::str_split($s))) return false;
		return implode('', array_reverse($a));
	}

	/**
	 * Implementation substr() function for UTF-8 encoding string.
	 *
	 * @link     http://www.w3.org/International/questions/qa-forms-utf-8.html
	 * @param    string|null       $s
	 * @param    int|digit         $offset
	 * @param    int|null|digit    $length
	 * @return   string|bool|null             Returns FALSE if error occurred
	 */
	public static function substr($s, $offset, $length = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		#since PHP-5.3.x mb_substr() faster then iconv_substr()
		if (function_exists('mb_substr'))
		{
			if ($length === null) $length = self::strlen($s);
			return mb_substr($s, $offset, $length, 'utf-8');
		}
		if (function_exists('iconv_substr'))
		{
			if ($length === null) $length = self::strlen($s);
			return iconv_substr($s, $offset, $length, 'utf-8');
		}

		static $_s = null;
		static $_a = null;

		if ($_s !== $s) $_a = self::str_split($_s = $s);
		if (! is_array($_a)) return false;
		if ($length !== null) $a = array_slice($_a, $offset, $length);
		else                  $a = array_slice($_a, $offset);
		return implode('', $a);
	}

	/**
	 * Implementation substr_replace() function for UTF-8 encoding string.
	 *
	 * @param   string|null       $s
	 * @param   string|int        $replacement
	 * @param   int|digit         $start
	 * @param   int|null          $length
	 * @return  string|bool|null  Returns FALSE if error occurred
	 */
	public static function substr_replace($s, $replacement, $start, $length = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;

		$a = self::str_split($s);
		if (! is_array($a)) return false;
		array_splice($a, $start, $length, $replacement);
		return implode('', $a);
	}

	/**
	 * Implementation ucfirst() function for UTF-8 encoding string.
	 * Преобразует первый символ строки в кодировке UTF-8 в верхний регистр.
	 * Корректно обрабатывает слова в кавычках, например: «северный поток» --> «Северный поток»
	 *
	 * @param   string|null       $s
	 * @param   bool              $is_other_to_lowercase  остальные символы преобразуются в нижний регистр?
	 * @return  string|bool|null  Returns FALSE if error occurred
	 */
	public static function ucfirst($s, $is_other_to_lowercase = true)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if ($s === '' || ! is_string($s)) return $s;

		if (! preg_match('/^([' . implode('', self::$html_quotation_mark_table) . ']{1,2}+)  #1 quotation marks
							(\p{L})     #2 first letter
							(.*+)       #3 next letters
							$/sxuSX', $s, $m)) return $s; #letters not found
		return $m[1] . self::uppercase($m[2]) . ($is_other_to_lowercase ? self::lowercase($m[3]) : $m[3]);
	}

	/**
	 * Implementation ucwords() function for UTF-8 encoding string.
	 * Преобразует в верхний регистр первый символ каждого слова в строке в кодировке UTF-8,
	 * остальные символы каждого слова преобразуются в нижний регистр.
	 *
	 * @param   string|null       $s
	 * @param   bool              $is_other_to_lowercase  остальные символы преобразуются в нижний регистр?
	 * @param   string            $spaces_re
	 * @return  string|bool|null  Returns FALSE if error occurred
	 */
	public static function ucwords($s, $is_other_to_lowercase = true, $spaces_re = '~([\p{Z}\s]+)~suSX')
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if ($s === '' || ! is_string($s)) return $s;

		$words = preg_split($spaces_re, $s, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		foreach ($words as $k => $word)
		{
			$words[$k] = self::ucfirst($word, $is_other_to_lowercase);
			if ($words[$k] === false) return false;
		}
		return implode('', $words);
	}

	/**
	 * Decodes a string to UTF-8 string from some formats (can be mixed)
	 * Examples
	 *   '%D1%82%D0%B5%D1%81%D1%82'        => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #binary (regular)
	 *   '0xD182D0B5D181D182'              => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #binary (compact)
	 *   '%u0442%u0435%u0441%u0442'        => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #UCS-2  (U+0 — U+FFFF)
	 *   '%u{442}%u{435}%u{0441}%u{00442}' => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #UTF-8  (U+0 — U+FFFFFF)
	 *
	 * It is used to decode the data in the format %uXXXX, encoded deprecated
	 * javascript's function encode(). Recommended to use encodeURIComponent().
	 * Obsolete format %uXXXX allows unicode only in the range of UCS-2, ie, U+0 to U+FFFF.
	 *
	 * @see     urldecode()
	 * @param   array|scalar|null  $data
	 * @param   bool               $is_hex2bin  Decode the HEX-data?
	 *                                          Example: '0xD182D0B5D181D182' => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"
	 *                                          Hint: parameters in the URL address is sometimes
	 *                                          convenient to encode not function rawurlencode($string),
	 *                                          and use the following mechanism (encoded data is more compact):
	 *                                          '0x' . bin2hex($string)
	 * @param   bool               $is_urldecode
	 * @return  array|scalar|null  Returns FALSE if error occurred
	 */
	public static function unescape($data, $is_hex2bin = false, $is_urldecode = true)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (is_array($data))
		{
			$d = array();
			foreach ($data as $k => &$v)
			{
				if (is_string($k))
				{
					$k = self::unescape($k, $is_hex2bin, $is_urldecode);
					if (! is_string($k)) return false;
				}
				$d[$k] = self::unescape($v, $is_hex2bin, $is_urldecode);
				if ($d[$k] === false && ! is_bool($v)) return false;
			}
			return $d;
		}
		if (is_string($data))
		{
			#use strpos() for speed improving of regexp
			if ($is_hex2bin && strpos($data, '0x') !== false)
			{
				$data = preg_replace_callback(
							'~0x((?:[\da-fA-F]{2})+)~sSX',
							function (array $m)
							{
								$s = pack('H' . strlen($m[1]), $m[1]); #hex2bin()
								return rawurlencode($s);
							},
							$data);
			}
			if (strpos($data, '%u') !== false)
			{
				$class = __CLASS__;
				$data = preg_replace_callback(
							'~%u(   [\da-fA-F]{4}+          #%uXXXX     only UCS-2
								  | \{ [\da-fA-F]{1,6}+ \}  #%u{XXXXXX} extended form for all UNICODE charts
								)
							 ~sxSX',
							function (array $m) use ($class)
							{
								$codepoint = hexdec(trim($m[1], '{}'));
								$char = $class::chr($codepoint);
								return rawurlencode($char);
							},
							$data);
			}
			return $is_urldecode ? urldecode($data) : $data;
		}
		if (is_scalar($data) || is_null($data)) return $data;  #~ null, integer, float, boolean
		return false; #object or resource
	}

	/**
	 * 1) Corrects the global arrays $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES
	 *    decoded values ​​from %XX and extended %uXXXX / %u{XXXXXX} format,
	 *    for example, through an outdated javascript function escape().
	 *    Standard PHP5 cannot do it.
	 * 2) Recode $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES from $charset
	 *    encoding to UTF-8, if necessary.
	 *    A side effect is a positive protection against XSS attacks with
	 *    non-printable characters on the vulnerable PHP function.
	 *    Thus web forms can be sent to the server in 2-encoding: $charset and UTF-8.
	 *    For example: ?тест[тест]=тест
	 * 3) If in the HTTP_COOKIE there are parameters with the same name,
	 *    takes the last value (as in the QUERY_STRING), not the first.
	 * 4) Creates an array of $_POST for non-standard Content-Type, for example,
	 *    "Content-Type: application/octet-stream". Standard PHP5 creates
	 *    an array for "Content-Type: application/x-www-form-urlencoded"
	 *    and "Content-Type: multipart/form-data".
	 *
	 * Examples
	 *   '%F2%E5%F1%F2'                    => 'тест'  #CP1251 (regular)
	 *   '0xF2E5F1F2'                      => 'тест'  #CP1251 (compact)
	 *   '%D1%82%D0%B5%D1%81%D1%82'        => 'тест'  #UTF-8 (regular)
	 *   '0xD182D0B5D181D182'              => 'тест'  #UTF-8 (compact)
	 *   '%u0442%u0435%u0441%u0442'        => 'тест'  #UCS-2 (U+0 — U+FFFF)
	 *   '%u{442}%u{435}%u{0441}%u{00442}' => 'тест'  #UTF-8 (U+0 — U+FFFFFF)
	 *
	 * Сессии, куки и независимая авторизация на поддоменах.
	 *
	 * ПРИМЕР 1
	 * У рабочего сайта http://domain.com появились поддомены.
	 * Для кроссдоменной авторизации через механизм сессий имя хоста для COOKIE было изменено с "domain.com" на ".domain.com"
	 * В результате авторизация не работает. Решение: поменять имя сессии.
	 * Ещё помогает очистка COOKIE, но их принудительная очистка на тысячах пользовательских компьютеров проблематична.
	 * PHP не правильно (?) обрабатывает заголовок HTTP_COOKIE, если там встречаются параметры с одинаковым именем, но разными значениями.
	 * Пример запроса HTTP-заголовка клиентом: "Cookie: sid=chpgs2fiak-330mzqza; sid=cmz5tnp5zz-xlbbgqp"
	 * В этом случае сервер берёт первое значение, а не последнее.
	 * Хотя если в QUERY_STRING есть такая ситуация, всегда берётся последний параметр.
	 * В HTTP_COOKIE два параметра с одинаковым именем могут появиться, если отправить клиенту следующие HTTP-заголовки:
	 * "Set-Cookie: sid=chpgs2fiak-330mzqza; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=domain.com"  (только domain.com)
	 * "Set-Cookie: sid=cmz6uqorzv-1bn35110; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=.domain.com" (domain.com и все его поддомены)
	 *
	 * ПРИМЕР 2
	 * Есть рабочие сайты: http://domain.com (основной), http://admin.domain.com (админка),
	 * http://sub1.domain.com (подпроект 1), http://sub2.domain.com, (подпроект 2).
	 * Так же имеется сервер разработки http://dev.domain.com, на котором м. б. свои поддомены.
	 * Требуется сделать независимую кросс-доменную авторизацию для http://*.domain.com и http://*.dev.domain.com.
	 * Для сохранения статуса авторизации будем использовать сессию, имя и значение которой пишется в COOKIE.
	 * Т. к. домены http://*.dev.domain.com имеют пересечение с доменами http://*.domain.com,
	 * для независимой авторизации	нужно использовать разные имена сессий!
	 * Пример HTTP заголовков ответа сервера:
	 * "Set-Cookie: sid=chpgs2fiak-330mzqza; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=.domain.com" (.domain.com и все его поддомены)
	 * "Set-Cookie: sid.dev=cmz6uqorzv-1bn35110; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=.dev.domain.com" (dev.domain.com и все его поддомены)
	 *
	 * @link    http://tools.ietf.org/html/rfc2965  RFC 2965 - HTTP State Management Mechanism
	 * @param   bool               $is_hex2bin  Decode the HEX-data?
	 *                                          Example: '0xD182D0B5D181D182' => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"
	 *                                          Hint: parameters in the URL address is sometimes
	 *                                          convenient to encode not function rawurlencode($string),
	 *                                          and use the following mechanism (encoded data is more compact):
	 *                                          '0x' . bin2hex($string)
	 * @param   string  $charset
	 * @return  bool
	 */
	public static function unescape_request($is_hex2bin = false, $charset = 'ISO-8859-1')
	{
		$fixed = false;
		#ATTENTION! HTTP_RAW_POST_DATA is only accessible when Content-Type of POST request is NOT default "application/x-www-form-urlencoded"!
		$HTTP_RAW_POST_DATA = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ? (isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : @file_get_contents('php://input')) : null;
		if (ini_get('always_populate_raw_post_data')) $GLOBALS['HTTP_RAW_POST_DATA'] = $HTTP_RAW_POST_DATA;
		foreach (array( '_GET'    => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null,
						'_POST'   => $HTTP_RAW_POST_DATA,
						'_COOKIE' => isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : null,
						'_FILES'  => isset($_FILES) ? $_FILES : null,
						) as $k => $v)
		{
			if (! is_string($v)) continue;

			if ($k === '_COOKIE')
			{
				$v = preg_replace('/; *+/sSX', '&', $v);
				unset($_COOKIE); #будем парсить HTTP_COOKIE сами, чтобы сделать обработку как у QUERY_STRING
			}

			$v = self::unescape($v, $is_hex2bin, false);
			if ($v === false) return false;
			parse_str($v, $GLOBALS[$k]);

			$GLOBALS[$k] = self::convert_from($GLOBALS[$k], $charset);
			if ($GLOBALS[$k] === false)
			{
				trigger_error('Array $' . $k . ' does not have keys/values in UTF-8 charset!', E_USER_WARNING);
				return false;
			}

			$fixed = true;
		}
		if ($fixed)
		{
			$_REQUEST =
				(isset($_COOKIE) ? $_COOKIE : array()) +
				(isset($_POST) ? $_POST : array()) +
				(isset($_GET) ? $_GET : array());
		}
		return true;
	}

	/**
	 * Calculates the height of the edit text in <textarea> html tag by value and width.
	 *
	 * В большинстве случаев будет корректно работать для моноширинных шрифтов.
	 * Т.к. браузер переносит последнее слово, которое не умещается на строке,
	 * на следующую строку, высота м.б. меньше ожидаемой.
	 * Этот алгоритм явл. простым (и быстрым) и не отслеживает переносы слов.
	 *
	 * @param   string|null     $s         Текст
	 * @param   int|digit       $cols      Ширина области редактирования (колонок)
	 * @param   int|digit       $min_rows  Минимальное кол-во строк
	 * @param   int|digit       $max_rows  Максимальное кол-во строк
	 * @return  int|bool|null              Number of rows (lines)
	 */
	public static function textarea_rows($s, $cols, $min_rows = 3, $max_rows = 32)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		if (strlen($s) == 0) return $min_rows;  #speed improve
		$rows = 0;
		#utf8_decode() converts characters that are not in ISO-8859-1 to '?'
		foreach (preg_split('/\r\n|[\r\n]/sSX', utf8_decode($s)) as $line)
		{
			$rows += ceil((strlen($line) + 1) / $cols);
			if ($rows > $max_rows) return $max_rows;
		}
		return ($rows < $min_rows) ? $min_rows : $rows;
	}

	/**
	 * @param   string|null       $s
	 * @param   string|null       $charlist
	 * @return  string|bool|null
	 */
	public static function ltrim($s, $charlist = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;
		if ($charlist === null || self::is_ascii($charlist)) return ltrim($s);
		return preg_replace('~^[' . self::_preg_quote_class($charlist, '~') . ']+~suSX', '', $s);
	}

	/**
	 * @param   string|null       $s
	 * @param   string|null       $charlist
	 * @return  string|bool|null
	 */
	public static function rtrim($s, $charlist = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;
		if ($charlist === null || self::is_ascii($charlist)) return rtrim($s);
		return preg_replace('~[' . self::_preg_quote_class($charlist, '~') . ']+$~suSX', '', $s);
	}

	/**
	 * @param   scalar|null  $s
	 * @param   string|null  $charlist
	 * @return  scalar|null
	 */
	public static function trim($s, $charlist = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;
		if ($charlist === null || self::is_ascii($charlist)) return trim($s);
		$charlist_re = self::_preg_quote_class($charlist, '~');
		$s = preg_replace('~^[' . $charlist_re . ']+~suSX', '', $s);
		return preg_replace('~[' . $charlist_re . ']+$~suSX', '', $s);
	}

	/**
	 * @param  string      $charlist
	 * @param  string|null $delimiter
	 * @return string
	 */
	private static function _preg_quote_class($charlist, $delimiter = null)
	{
		#return preg_quote($charlist, $delimiter); #DEPRECATED
		$quote_table = array(
			'\\' => '\\\\',
			'-'  => '\-',
			']'  => '\]',
		);
		if (is_string($delimiter)) $quote_table[$delimiter] = '\\' . $delimiter;
		return strtr($charlist, $quote_table);
	}

	/**
	 * @param   string|null       $s
	 * @param   int|digit         $length
	 * @param   string            $pad_str
	 * @param   int               $type     STR_PAD_LEFT, STR_PAD_RIGHT or STR_PAD_BOTH
	 * @return  string|bool|null
	 */
	public static function str_pad($s, $length, $pad_str = ' ', $type = STR_PAD_RIGHT)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s)) return $s;

		$input_len = self::strlen($s);
		if ($length <= $input_len) return $s;

		$pad_str_len = self::strlen($pad_str);
		$pad_len = $length - $input_len;

		if ($type == STR_PAD_RIGHT)
		{
			$repeat_num = ceil($pad_len / $pad_str_len);
			return self::substr($s . str_repeat($pad_str, $repeat_num), 0, $length);
		}

		if ($type == STR_PAD_LEFT)
		{
			$repeat_num = ceil($pad_len / $pad_str_len);
			return self::substr(str_repeat($pad_str, $repeat_num), 0, intval(floor($pad_len))) . $s;
		}

		if ($type == STR_PAD_BOTH)
		{
			$pad_len /= 2;
			$pad_amount_left  = intval(floor($pad_len));
			$pad_amount_right = intval(ceil($pad_len));
			$repeat_times_left  = ceil($pad_amount_left  / $pad_str_len);
			$repeat_times_right = ceil($pad_amount_right / $pad_str_len);

			$padding_left  = self::substr(str_repeat($pad_str, $repeat_times_left),  0, $pad_amount_left);
			$padding_right = self::substr(str_repeat($pad_str, $repeat_times_right), 0, $pad_amount_right);
			return $padding_left . $s . $padding_right;
		}

		trigger_error('Parameter 4 should be a constant of STR_PAD_RIGHT, STR_PAD_LEFT or STR_PAD_BOTH!', E_USER_WARNING);
		return false;
	}

	/**
	 * @param   string    $str
	 * @param   string    $mask
	 * @param   int|null  $start
	 * @param   int|null  $length
	 * @return  int|bool
	 */
	public static function strspn($str, $mask, $start = null, $length = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		#if (self::is_ascii($str) && self::is_ascii($mask)) return strspn($str, $mask, $start, $length);
		if ($start !== null || $length !== null) $str = self::substr($str, $start, $length);
		if (preg_match('~^[' . preg_quote($mask, '~') . ']+~uSX', $str, $m)) self::strlen($m[0]);
		return 0;
	}

	/**
	 * Recode the text files in a specified folder in the UTF-8
	 * In the processing skipped binary files, files encoded in UTF-8, files that could not convert.
	 * So method works reliably enough.
	 *
	 *
	 * @param   string       $dir             Директория для сканирования
	 * @param   string|null  $files_re        Регул. выражение для шаблона имён файлов,
	 *                                        например: '~\.(?:txt|sql|php|pl|py|sh|tpl|xml|xsl|html|xhtml|phtml|htm|js|json|css|conf|cfg|ini|htaccess)$~sSX'
	 * @param   bool         $is_recursive    Обрабатывать вложенные папки и файлы?
	 * @param   string       $charset         Исходная кодировка
	 * @param   string|null  $dirs_ignore_re  Регул. выражение для исключения папок из обработки
	 *                                        например: '~^(?:cache|images?|photos?|fonts?|img|ico|\.svn|\.hg|\.cvs)$~siSX'
	 * @param   bool         $is_echo         Печать имён обработанных файлов и статус обработки в выходной поток?
	 * @param   bool         $is_simulate     Сымитировать работу без реальной перезаписи файлов?
	 * @return  int|bool                      Возвращает кол-во перекодированных файлов
	 *                                        Returns FALSE if error occurred
	 */
	public static function convert_files_from(
		$dir,
		$files_re = null,
		$is_recursive = true,
		$charset = 'CP1251',
		$dirs_ignore_re = null,
		$is_echo = false,
		$is_simulate = false)
	{
		if (! ReflectionTypeHint::isValid()) return false;

		$dh = opendir($dir);
		if (! is_resource($dh)) return false;
		$counter = 0;
		while (($name = readdir($dh)) !== false)
		{
			if ($name == '.' || $name == '..') continue;
			$file = $dir . '/' . $name;
			if (is_file($file))
			{
				if (is_string($files_re) && ! preg_match($files_re, $name)) continue;
				if ($is_echo) echo $file;

				$s = @file_get_contents($file);
				if (! is_string($s))
				{
					if ($is_echo) echo '  Error to reading' . PHP_EOL;
					return false;
				}

				if (self::is_utf8($s))
				{
					if ($is_echo) echo '  Already UTF-8, skipped' . PHP_EOL;
					continue;
				}

				if (self::has_binary($s))
				{
					if ($is_echo) echo '  Вinary file, skipped' . PHP_EOL;
					continue;
				}

				$s = self::convert_from($s, $charset);
				if (! is_string($s) || ! self::is_utf8($s))
				{
					if ($is_echo) echo '  Error to converting (source file not in ' . $charset . '?)' . PHP_EOL;
					continue;
				}

				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				if ($ext === 'htm' || $ext === 'html' || $ext === 'xhtml' || $ext === 'phtml' || $ext === 'tpl')
				{
					$s = preg_replace('~(<meta  [\x00-\x20]++
												(?:  content="text/html; [\x00-\x20]++ charset= #HTML4
												  |  charset="                                  #HTML5
												)
										)               #1
											[-a-z\d]++  #charset name
										(" [^>]* >)     #2
										~sixSX', '$1utf-8$2', $s);
				}
				if ($ext === 'xml' || $ext === 'xsl' || $ext === 'tpl')
				{
					$s = preg_replace('~(<\?xml [\x00-\x20]++ encoding=") #1
											[-a-z\d]++                    #charset name
										(" .*? \?>)                       #2
										~sixSX', '$1utf-8$2', $s);
				}

				if (! $is_simulate)
				{
					$bytes = @file_put_contents($file, $s);
					if ($bytes === false)
					{
						if ($is_echo) echo '  Error to writing' . PHP_EOL;
						return false;
					}
				}
				if ($is_echo) echo '  ' . $charset . ' to UTF-8 converted' . PHP_EOL;
				$counter++;
			}
			elseif ($is_recursive && is_dir($file))
			{
				if (! is_string($dirs_ignore_re) || ! preg_match($dirs_ignore_re, $name))
				{
					$c = self::convert_files_from($file, $files_re, $is_recursive, $charset, $dirs_ignore_re, $is_echo, $is_simulate);
					if ($c === false) return false;
					$counter += $c;
				}
			}
		}
		closedir($dh);
		return $counter;
	}

	/**
	 *
	 * @param   int|string  $low
	 * @param   int|string  $high
	 * @param   int         $step
	 * @return  array|bool         Returns FALSE if error occurred
	 */
	public static function range($low, $high, $step = 1)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (is_int($low) || is_int($high)) return range($low, $high, $step);  #speed improve
		$low_cp  = self::ord($low);
		$high_cp = self::ord($high);
		if (! is_int($low_cp) || ! is_int($high_cp)) return false;
		$a = range($low_cp, $high_cp, $step);
		return array_map(array('self', 'chr'), $a);
	}

	/**
	 *
	 * @param   string|null       $s
	 * @param   string|array      $from
	 * @param   string|null       $to
	 * @return  string|bool|null         Returns FALSE if error occurred
	 */
	public static function strtr($s, $from, $to = null)
	{
		if (! ReflectionTypeHint::isValid()) return false;
		if (! is_string($s) || $s === '') return $s;
		if (is_array($from)) return strtr($s, $from); #speed improve
		$keys   = self::str_split($from);
		$values = self::str_split($to);
		if (! is_array($keys) || ! is_array($values)) return false;
		$table = array_combine($keys, $values);
		if (! is_array($table)) return false;
		return strtr($s, $table);
	}

	public static function tests()
	{
		assert_options(ASSERT_ACTIVE,   true);
		assert_options(ASSERT_BAIL,     true);
		assert_options(ASSERT_WARNING,  true);
		assert_options(ASSERT_QUIET_EVAL, false);
		$a = array(
			'self::html_entity_decode("&quot;&amp;&lt;&gt;", true) === "\"&<>"',
			'self::html_entity_decode("&quot;&amp;&lt;&gt;", false) === "&quot;&amp;&lt;&gt;"',
			'self::html_entity_decode("&amp;amp;", true) === "&amp;"',
			'self::html_entity_decode("&amp;amp;", false) === "&amp;amp;"',
			'self::html_entity_decode("&#034;", true) === "\""',
			'self::html_entity_decode("&#034;", false) === "&quot;"',
			'self::html_entity_decode("&#039;", true) === "\'"',
			'self::html_entity_decode("&#039;", false) === "\'"',
			'self::html_entity_decode("&#x22;", true) === "\""',
			'self::html_entity_decode("&#x22;", false) === "&quot;"',

			'self::array_change_key_case(array("АБВГД" => "АБВГД"), CASE_LOWER) === array("абвгд" => "АБВГД")',
			'self::array_change_key_case(array("абвгд" => "абвгд"), CASE_UPPER) === array("АБВГД" => "абвгд")',

			'self::blocks_check("Яндекс", "Cyrillic") === true',
			'self::blocks_check("Google", "Basic Latin") === true',
			'self::blocks_check("Google & Яндекс", array("Basic Latin", "Cyrillic")) === true',
			'self::blocks_check("Ё-моё, Yandex!", array(array(0x20, 0x7E),    #[\x20-\x7E]
														array(0x0410, 0x044F), #[A-Яa-я]
														0x0401, #russian yo (Ё)
														0x0451, #russian ye (ё)
													)) === true',

			'self::chunk_split("абвг", 2) === "аб\r\nвг"',
			'self::chunk_split("абвг", 2, "|") === "аб|вг"',

			'self::lowercase("1234-ABCD-АБВГ") === "1234-abcd-абвг"',
			'self::lowercase(array("1234-ABCD-АБВГ" => "1234-ABCD-АБВГ")) === array("1234-ABCD-АБВГ" => "1234-abcd-абвг")',
			'self::uppercase("1234-abcd-абвг") === "1234-ABCD-АБВГ"',
			'self::uppercase(array("1234-abcd-абвг" => "1234-abcd-абвг")) === array("1234-abcd-абвг" => "1234-ABCD-АБВГ")',

			'self::convert_from(self::convert_to("123-ABC-abc-АБВ-абв", $charset = "cp1251"), $charset = "cp1251") === "123-ABC-abc-АБВ-абв"',

			'self::diactrical_remove("вдох\xc2\xadно\xc2\xadве\xcc\x81\xc2\xadние") === "вдох\xc2\xadно\xc2\xadве\xc2\xadние"',
			'self::diactrical_remove("вдох\xc2\xadно\xc2\xadве\xcc\x81\xc2\xadние", array("\xc2\xad")) === "вдохновение"',
			'self::diactrical_remove("вдох\xc2\xadно\xc2\xadве\xcc\x81\xc2\xadние", array("\xc2\xad"), true, $restore_table) === "вдохновение"',
			'self::diactrical_restore("вдохновение", $restore_table) === "вдох\xc2\xadно\xc2\xadве\xcc\x81\xc2\xadние"',

			'self::is_utf8(file_get_contents(' . var_export(__FILE__, true) . ', true)) === true',
			'self::is_utf8(file_get_contents(' . var_export(__FILE__, true) . ', false)) === true',
			'self::is_ascii(file_get_contents(' . var_export(__FILE__, true) . ')) === false',
			'self::is_ascii("_\x01\x02абв", $error_char_offset) === false && $error_char_offset === 3',
			'self::has_binary(file_get_contents(' . var_export(__FILE__, true) . ')) === false',
			'self::has_binary("_аб\x01вг", $found_char_offset) === true && $found_char_offset === 3',

			#range() uses ord() and chr()
			'self::range("A", "D") === array("A", "B", "C", "D")',
			'self::range("а", "г") === array("а", "б", "в", "г")',
			'self::range(1, 3) === array(1, 2, 3)',

			'"↔" === self::chr(self::ord("↔"))',
			'"123-ABC-abc-АБВ-абв" === self::from_unicode(self::to_unicode("123-ABC-abc-АБВ-абв"))',
			'self::strpos("123-ABC-abc-абв-АБВ-где", "АБВ") === 16',
			'self::stripos("123-ABC-abc-абд-АБВ-где", "абв") === 16',
			'self::strpos("123-ABC-abc", "АБВ") === false',
			'self::strpos("123-АБВ-абв", "abc") === false',

			'self::preg_quote_case_insensitive("123_слово_test") === "123_(с|С)(л|Л)(о|О)(в|В)(о|О)_[tT][eE][sS][tT]"',
			'self::preg_quote_case_insensitive("123_test") === "(?i:123_test)"',
			'self::preg_quote_case_insensitive("123") === "123"',

			'self::unescape("%D1%82%D0%B5%D1%81%D1%82")        === "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"',
			'self::unescape("0xD182D0B5D181D182", true)        === "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"',
			'self::unescape("%u0442%u0435%u0441%u0442")        === "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"',
			'self::unescape("%u{442}%u{435}%u{0441}%u{00442}") === "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"',
			'self::unescape("%u0025%u0032%u0035+%25%75%30%30%32%35") === "%25 %u0025"',

			'self::ucfirst("!@#$", true)      === "!@#$"',
			'self::ucfirst("!@#$ test", true) === "!@#$ test"',
			'self::ucfirst("«северный Поток»", true)  === "«Северный поток»"',
			'self::ucfirst("«северный Поток»", false) === "«Северный Поток»"',

			//'self::strlen(file_get_contents(' . var_export(__FILE__, true) . ', true))'
		);
		foreach ($a as $k => $v) if (! assert($v)) return false;

		//$start_time = microtime(true);
		//$s = file_get_contents(__FILE__);
		//for ($i = 0; $i < 10; $i++) $r = self::html_entity_encode($s);
		//$time = microtime(true) - $start_time;
		//d($time, $r);

		return true;
	}

}
