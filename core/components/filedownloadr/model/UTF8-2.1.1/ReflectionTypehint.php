<?php
/**
 * Класс для проверки входящих параметров методов на принадлежность типам через рефлексию.
 * Используется как более удобный вариант множественным assert(), стоящим после объявления методов.
 *
 * В режиме отладки скриптов нужно использовать assert_options(ASSERT_ACTIVE, true)
 * В "боевом" режиме -- assert_options(ASSERT_ACTIVE, false)
 *
 * @link     http://www.ilia.ws/archives/205-Type-hinting-for-PHP-5.3.html
 * @link     http://php.net/manual/en/language.oop5.typehinting.php
 * @example  ReflectionTypehint_example.php
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat: http://orangetie.ru/, http://rin-nas.moikrug.ru/
 * @charset  UTF-8
 * @version  1.0.6
 */
class ReflectionTypehint
{
	private static $hints = array(
		'int'      => 'is_int',
		'integer'  => 'is_int',
		'digit'    => 'ctype_digit',
		#'number'   => 'ctype_digit',
		'float'    => 'is_float',
		'double'   => 'is_float',
		'real'     => 'is_float',
		'numeric'  => 'is_numeric',
		'string'   => 'is_string',
		'bool'     => 'is_bool',
		'boolean'  => 'is_bool',
		'null'     => 'is_null',
		'array'    => 'is_array',
		'object'   => 'is_object',
		'resource' => 'is_resource',
		'scalar'   => 'is_scalar',  #integer, float, string or boolean
		'callback' => 'is_callable',
	);

	#запрещаем создание экземпляра класса, вызов методов этого класса только статически!
	private function __construct() {}

	public static function isValid()
	{
		if (! assert_options(ASSERT_ACTIVE)) return true;
		$bt = self::debugBacktrace(null, 1);
		extract($bt);  //to $file, $line, $function, $class, $object, $type, $args
		$r = new ReflectionMethod($class, $function);
		$doc = $r->getDocComment();
		if (! preg_match_all('~[\r\n]++ [\x20\t]++ \* [\x20\t]++
                               @param
							   [\x20\t]++
                               ( [_a-z][_a-z\d]*+
                                 (?>[|/,][_a-z][_a-z\d]*+)*+
                               ) #1 types
                               [\x20\t]++
                               (&?+\$[_a-z][_a-z\d]*+) #2 name
                              ~sixSX', $doc, $params, PREG_SET_ORDER)) return true;
		foreach ($args as $i => $value)
		{
			if (! isset($params[$i])) return true;
			#$name  = $params[$i][2];
			$hints = preg_split('~[|/,]~sSX', $params[$i][1]);
			if (! self::checkValueTypes($hints, $value))
			{
				$param_num = $i + 1;
				$message = 'Argument %d passed to %s%s%s() must be an %s, %s given, ' . PHP_EOL
						 . 'called in %s on line %d ' . PHP_EOL
						 . 'and defined in %s on line %d';
				$message = sprintf($message, $param_num, $class, $type, $function, implode('|', $hints), (is_object($value) ? get_class($value) . ' ' : '') . gettype($value), $file, $line, $r->getFileName(), $r->getStartLine());
				trigger_error($message, E_USER_WARNING);
				return false;
			}
		}
		return true;
	}

	/**
	 * Return stacktrace. Correctly work with call_user_func*()
	 * (totally skip them correcting caller references).
	 * If $return_frame is present, return only $return_frame matched caller, not all stacktrace.
	 *
	 * @param   string|null  $re_ignore     example: '~^' . preg_quote(__CLASS__, '~') . '(?![a-zA-Z\d])~sSX'
	 * @param   int|null     $return_frame
	 * @return  array
	 */
	public static function debugBacktrace($re_ignore = null, $return_frame = null)
	{
		$trace = debug_backtrace();

		$a = array();
		$frames = 0;
		for ($i = 0, $n = count($trace); $i < $n; $i++)
		{
			$t = $trace[$i];
			if (! $t) continue;

			// Next frame.
			$next = isset($trace[$i+1])? $trace[$i+1] : null;

			// Dummy frame before call_user_func*() frames.
			if (! isset($t['file']) && $next)
			{
				$t['over_function'] = $trace[$i+1]['function'];
				$t = $t + $trace[$i+1];
				$trace[$i+1] = null; // skip call_user_func on next iteration
			}

			// Skip myself frame.
			if (++$frames < 2) continue;

			// 'class' and 'function' field of next frame define where this frame function situated.
			// Skip frames for functions situated in ignored places.
			if ($re_ignore && $next)
			{
				// Name of function "inside which" frame was generated.
				$frame_caller = (isset($next['class']) ? $next['class'] . $next['type'] : '')
							  . (isset($next['function']) ? $next['function'] : '');
				if (preg_match($re_ignore, $frame_caller)) continue;
			}

			// On each iteration we consider ability to add PREVIOUS frame to $a stack.
			if (count($a) === $return_frame) return $t;
			$a[] = $t;
		}
		return $a;
	}

	/**
	 * Проверяет переменную на соответствие указанным типам
	 *
	 * @param   array  $types
	 * @param   mixed  $value
	 * @return  bool
	 */
	public static function checkValueTypes(array $types, $value)
	{
		foreach ($types as $type)
		{
			$type = strtolower($type);
			if (array_key_exists($type, self::$hints) && call_user_func(self::$hints[$type], $value)) return true;
			if (is_object($value) && @is_a($value, $type)) return true;
			if ($type === 'mixed') return true;
		}
		return false;
	}
}