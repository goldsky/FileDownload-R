# UTF8 support in PHP5
PHP5 UTF8 is a UTF-8 aware library of functions mirroring PHP's own string functions.
The powerful solution/contribution for UTF-8 support in your framework/CMS, written on PHP.
This library is advance of http://sourceforge.net/projects/phputf8 (last updated in 2007).

## Features and benefits

1. Compatibility with the interface standard PHP functions that deal with single-byte encodings
1. Ability to work without PHP extensions ICONV and MBSTRING, if any, that are actively used! Uses the fastest available method between MBSTRING, ICONV, native on PHP and hacks.
1. Useful features are missing from the ICONV and MBSTRING
1. The methods that take and return a string, are able to take and return null. This useful for selects from a database.
1. Several methods are able to process arrays recursively: `array_change_key_case()`, `convert_from()`, `convert_to()`, `strict()`, `is_utf8()`, `blocks_check()`, `convert_case()`, `lowercase()`, `uppercase()`, `unescape()`
1. Validating method parameters to allowed types via reflection (You can disable it)
1. A single interface and encapsulation, You can inherit and override
1. Test coverage
1. PHP >= 5.3.x

Example:

    $s = 'Hello, Привет';
    if (UTF8::is_utf8($s)) echo UTF8::strlen($s);

## Standard PHP functions, implemented for UTF-8 encoding string

### Alphabetical order list

1. `array_change_key_case()`
1. `chr()` — Converts a UNICODE codepoint to a UTF-8 character
1. `chunk_split()`
1. `ltrim()`
1. `ord()` — Converts a UTF-8 character to a UNICODE codepoint
1. `preg_match_all()` — Call `preg_match_all()` and convert byte offsets into character offsets for `PREG_OFFSET_CAPTURE` flag. This is regardless of whether you use `/u` modifier.
1. `range()`
1. `rtrim()`
1. `str_pad()`
1. `str_split()`
1. `strcasecmp()`
1. `strcmp()`
1. `stripos()`
1. `strlen()`
1. `strncmp()`
1. `strpos()`
1. `strrev()`
1. `strspn()`
1. `strtolower()`, `lowercase()` is alias
1. `strtoupper()`, `uppercase()` is alias
1. `strtr()`
1. `substr()`
1. `substr_replace()`
1. `trim()`
1. `ucfirst()`
1. `ucwords()`

## Extra useful functions for UTF-8 encoding string

### Alphabetical order list:

1. `blocks_check()` — Check the data in UTF-8 charset on given ranges of the standard UNICODE. The suitable alternative to regular expressions.
1. `convert_case()` — Конвертирует регистр букв в данных в кодировке UTF-8. Массивы обходятся рекурсивно, при этом конвертируются только значения в элементах массива, а ключи остаются без изменений.
1. `convert_files_from()` — Recode the text files in a specified folder in the UTF-8. In the processing skipped binary files, files encoded in UTF-8, files that could not convert.
1. `convert_from()` — Encodes data from another character encoding to UTF-8.
1. `convert_to()` — Encodes data from UTF-8 to another character encoding.
1. `diactrical_remove()` — Remove combining diactrical marks, with possibility of the restore. Удаляет диакритические знаки в тексте, с возможностью восстановления (опция)
1. `diactrical_restore()` — Restore combining diactrical marks, removed by diactrical_remove(). Восстанавливает диакритические знаки в тексте, при условии, что их символьные позиции и кол-во символов не изменились!
1. `from_unicode()` — Converts a UNICODE codepoints to a UTF-8 string
1. `has_binary()` — Check the data accessory to the class of control characters in ASCII.
1. `html_entity_decode()` — Convert all HTML entities to native UTF-8 characters
1. `html_entity_encode()` — Convert special UTF-8 characters to HTML entities.
1. `is_ascii()` — Check the data accessory to the class of characters ASCII.
1. `is_utf8()` — Returns true if data is valid UTF-8 and false otherwise. For null, integer, float, boolean returns TRUE.
1. `preg_quote_case_insensitive()` — Make regular expression for case insensitive match
1. `str_limit()`, `truncate()` — Обрезает текст в кодировке UTF-8 до заданной длины,	причём последнее слово показывается целиком, а не обрывается на середине.	Html сущности корректно обрабатываются.
1. `strict()` — Strips out device control codes in the ASCII range.
1. `textarea_rows()` — Calculates the height of the edit text in \<textarea\> html tag by value and width.
1. `to_unicode()` — Converts a UTF-8 string to a UNICODE codepoints
1. `unescape()` — Decodes a string to UTF-8 string from some formats (can be mixed)
1. `unescape_request()` — Corrects the global arrays `$_GET`, `$_POST`, `$_COOKIE`, `$_REQUEST`, `$_FILES` decoded values from `%XX` and extended `%uXXXX` / `%u{XXXXXX}` format, for example, through an outdated JavaScript function `escape()`. Standard PHP5 cannot do it. Recode `$_GET`, `$_POST`, `$_COOKIE`, `$_REQUEST`, `$_FILES` from `$charset` encoding to UTF-8, if necessary. A side effect is a positive protection against XSS attacks with non-printable characters on the vulnerable PHP function. Thus web forms can be sent to the server in 2-encoding: `$charset` and UTF8. For example: `?тест[тест]=тест`
If in the `HTTP_COOKIE` there are parameters with the same name, takes the last value (as in the `QUERY_STRING`), not the first. Creates an array of `$_POST` for non-standard Content-Type, for example, `"Content-Type: application/octet-stream"`. Standard PHP5 creates an array for `"Content-Type: application/x-www-form-urlencoded"` and `"Content-Type: multipart/form-data"`.

Examples of `unescape()`

    '%D1%82%D0%B5%D1%81%D1%82'        => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #binary (regular)
    '0xD182D0B5D181D182'              => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #binary (compact)
    '%u0442%u0435%u0441%u0442'        => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #UCS-2  (U+0 — U+FFFF)
    '%u{442}%u{435}%u{0441}%u{00442}' => "\xD1\x82\xD0\xB5\xD1\x81\xD1\x82"  #UTF-8  (U+0 — U+FFFFFF)

Examples of `unescape_request()`

    '%F2%E5%F1%F2'                    => 'тест'  #CP1251 (regular)
    '0xF2E5F1F2'                      => 'тест'  #CP1251 (compact)
    '%D1%82%D0%B5%D1%81%D1%82'        => 'тест'  #UTF-8 (regular)
    '0xD182D0B5D181D182'              => 'тест'  #UTF-8 (compact)
    '%u0442%u0435%u0441%u0442'        => 'тест'  #UCS-2 (U+0 — U+FFFF)
    '%u{442}%u{435}%u{0441}%u{00442}' => 'тест'  #UTF-8 (U+0 — U+FFFFFF)

# Поддержка UTF8 в PHP5

## Возможности и преимущества

1. Совместимость с интерфейсом стандартных PHP функций, работающих с однобайтовыми кодировками
1. Возможность работы без PHP расширений ICONV и MBSTRING, если они есть, то активно используются! Используется наиболее быстрый из доступных методов между MBSTRING, ICONV, родной реализацией на PHP и хаками.
1. Полезные функции, отсутствующие в ICONV и MBSTRING
1. Методы, которые принимают и возвращают строку, умеют принимать и возвращать null. Это удобно при выборках значений из базы данных.
1. Несколько методов умеют обрабатывать массивы рекурсивно: `array_change_key_case()`, `convert_from()`, `convert_to()`, `strict()`, `is_utf8()`, `blocks_check()`, `convert_case()`, `lowercase()`, `uppercase()`, `unescape()`
1. Проверка у методов входных параметров на допустимые типы через рефлексию (можно отключить)
1. Единый интерфейс и инкапсуляция, можно унаследоваться и переопределить методы
1. Покрытие тестами
1. PHP >= 5.3.x

Example:

    $s = 'Hello, Привет';
    if (UTF8::is_utf8($s)) echo UTF8::strlen($s);
  
Project was exported from http://code.google.com/p/php5-utf8
