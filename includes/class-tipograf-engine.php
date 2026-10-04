<?php
/**
 * Движок типографа: ставит неразрывные пробелы в готовом HTML.
 *
 * Правила взяты из Типографа Муравьёва (mdash.ru, общественное достояние) и переписаны:
 * работает только с видимым текстом — теги, атрибуты, скрипты, стили, <head>, код и
 * HTML-сущности остаются как были. Если что-то пошло не так, HTML возвращается без изменений.
 */

defined( 'ABSPATH' ) || exit;

final class Tipograf_Engine {

	const DEFAULT_WORDS = 'в во с со к ко у о об обо на над под по при про без до из от за для через перед а и но да или ни не я мы вы он их ей им';

	// Метки на время обработки (символы из области для частного использования Юникода).
	const TAG_INLINE = "\u{E000}"; // строчный тег: <a>, <span>, <strong>…
	const TAG_BLOCK  = "\u{E001}"; // прочие теги и непрозрачные блоки (<script>, <pre>…)
	const ENT_SPACE  = "\u{E010}"; // &nbsp; и его числовые записи
	const ENT_OPEN   = "\u{E011}"; // открывающие кавычки: &laquo; &bdquo; &ldquo; &quot;
	const ENT_DASH   = "\u{E012}"; // &mdash; &ndash;
	const ENT_OTHER  = "\u{E013}"; // остальные сущности
	const NBSP       = "\u{E020}"; // наш неразрывный пробел, в конце станет &nbsp;

	// Элементы, внутрь которых не заходим.
	const OPAQUE = 'head|script|style|textarea|pre|code|kbd|samp|var|svg|math|template';

	// Строчные теги: слово по обе стороны такого тега остаётся словом той же строки.
	const INLINE = 'a|abbr|b|bdi|bdo|cite|del|dfn|em|font|i|ins|label|mark|q|s|small|span|strong|sub|sup|time|u';

	const UNITS = 'мм|см|дм|км|м|м²|м³|м2|м3|га|кг|мг|г|гг|т|л|мл|шт|руб|р|₽|€|\$|%|‰|°|тыс|млн|млрд|ч|мин|сек|с|кВт|Вт|год|года|году|годом|лет';

	const ABBR = 'г|гг|ул|пер|пр|просп|пл|наб|бул|ш|д|кв|корп|стр|рис|илл|гл|ст|п|см|им|тел';

	/** @var string[] */
	private $queue = array();

	/**
	 * @param string $html    Готовая страница или её фрагмент.
	 * @param array  $options short_words, particles, dash, units (bool) и words (строка через пробел).
	 */
	public static function process( $html, array $options = array() ) {
		$options = array_merge(
			array(
				'short_words' => true,
				'particles'   => true,
				'dash'        => true,
				'units'       => true,
				'words'       => self::DEFAULT_WORDS,
			),
			$options
		);

		if ( ! is_string( $html ) || '' === $html || preg_match( '/[\x{E000}-\x{E020}]/u', $html ) !== 0 ) {
			return $html; // пусто, битая кодировка или уже есть наши метки
		}

		$result = ( new self() )->run( $html, $options );

		return null === $result ? $html : $result;
	}

	/**
	 * JSON-ответ AJAX (фильтры, поиск, «Показать ещё»): обрабатываются только строки с HTML-тегами,
	 * простые строки могут быть данными (адреса, ключи, значения полей) — их не трогаем.
	 *
	 * @param string $json    Тело ответа.
	 * @param array  $options Как в process().
	 */
	public static function process_json( $json, array $options = array() ) {
		$data = json_decode( $json );
		if ( null === $data || is_scalar( $data ) && ! is_string( $data ) ) {
			return $json; // не JSON или просто число
		}
		$changed = false;
		$data    = self::walk_json( $data, $options, $changed );
		if ( ! $changed ) {
			return $json;
		}
		$out = json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return false === $out ? $json : $out;
	}

	private static function walk_json( $value, array $options, &$changed ) {
		if ( is_string( $value ) ) {
			if ( preg_match( '/<[a-z!\/]/i', $value ) ) {
				$new     = self::process( $value, $options );
				$changed = $changed || $new !== $value;
				return $new;
			}
			return $value;
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			foreach ( $value as $key => $item ) {
				if ( '' === $key ) {
					continue; // пустое имя свойства у объекта не записать обратно
				}
				$item = self::walk_json( $item, $options, $changed );
				if ( is_array( $value ) ) {
					$value[ $key ] = $item;
				} else {
					$value->$key = $item;
				}
			}
		}
		return $value;
	}

	private function run( $html, array $o ) {
		$in = '(?:' . self::TAG_INLINE . ')*';

		// 1. Теги и непрозрачные блоки → метки.
		$s = preg_replace_callback(
			'~<(' . self::OPAQUE . ')\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>.*?</\1\s*>'
			. '|<!--.*?-->|<!\[CDATA\[.*?\]\]>|<![^>]*>'
			. '|</?([a-zA-Z][a-zA-Z0-9-]*)(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~isu',
			function ( $m ) {
				$this->queue[] = $m[0];
				$inline = '<!--' === substr( $m[0], 0, 4 )
					|| ( isset( $m[2] ) && '' !== $m[2] && preg_match( '/^(?:' . self::INLINE . ')$/i', $m[2] ) );
				return $inline ? self::TAG_INLINE : self::TAG_BLOCK;
			},
			$html
		);
		if ( null === $s ) {
			return null;
		}
		$tags        = $this->queue;
		$this->queue = array();

		// 2. HTML-сущности → метки.
		$s = preg_replace_callback(
			'/&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i',
			function ( $m ) {
				$this->queue[] = $m[0];
				$e             = strtolower( $m[0] );
				if ( in_array( $e, array( '&nbsp;', '&#160;', '&#xa0;' ), true ) ) {
					return self::ENT_SPACE;
				}
				if ( in_array( $e, array( '&laquo;', '&bdquo;', '&ldquo;', '&lsquo;', '&quot;', '&#171;', '&#8222;', '&#8220;', '&#34;' ), true ) ) {
					return self::ENT_OPEN;
				}
				if ( in_array( $e, array( '&mdash;', '&ndash;', '&#8212;', '&#8211;', '&#x2014;', '&#x2013;' ), true ) ) {
					return self::ENT_DASH;
				}
				return self::ENT_OTHER;
			},
			$s
		);
		if ( null === $s ) {
			return null;
		}
		$entities = $this->queue;

		// 3. Правила. Заменяется только обычный пробел (или перенос строки) — метки остаются на местах.
		$rules = array();

		if ( $o['units'] ) {
			// Число и единица: «10 м», «5 шт», «2026 года».
			$rules[ '/(?<![\p{L}\p{N}.,-])(\d+(?:[.,]\d+)?)(' . $in . ')[ \t\r\n]+(' . $in . ')(?=(?:' . self::UNITS . ')(?![\p{L}\p{N}]))/u' ] = '$1$2' . self::NBSP . '$3';
			// Знак номера и параграфа: «№ 5», «§ 3».
			$rules[ '/([№§])(' . $in . ')[ \t\r\n]+(' . $in . ')(?=\d)/u' ] = '$1$2' . self::NBSP . '$3';
			// Сокращения: «г. Москва», «ул. Ленина», «рис. 3».
			$rules[ '/(?<![^\s\x{A0}(\x{E000}\x{E001}\x{E010}\x{E020}])((?:' . self::ABBR . ')\.)(' . $in . ')[ \t\r\n]+(' . $in . ')(?=[\p{L}\p{N}«"\x{E011}])/iu' ] = '$1$2' . self::NBSP . '$3';
		}

		if ( $o['short_words'] ) {
			$words = $this->words_pattern( $o['words'] );
			if ( '' !== $words ) {
				// Короткое слово держится за следующее: «с вами», «и в доме».
				$rules[ '/(?<![^\s\x{A0}(\[«„“"\'\x{E000}\x{E001}\x{E010}\x{E011}\x{E012}\x{E013}\x{E020}—–])(' . $words . ')(' . $in . ')[ \t\r\n]+(' . $in . ')(?=[\p{L}\p{N}«„“"(\[\x{E011}])/iu' ] = '$1$2' . self::NBSP . '$3';
			}
		}

		if ( $o['particles'] ) {
			// Частица держится за предыдущее слово: «он же», «был ли», «если бы».
			$rules[ '/(?<=[\p{L}\p{N}])(' . $in . ')[ \t\r\n]+(' . $in . ')(же|ли|бы|ж|б)(?![\p{L}\p{N}-])/iu' ] = '$1' . self::NBSP . '$2$3';
		}

		if ( $o['dash'] ) {
			// Тире не начинает строку: «Москва — столица».
			$rules[ '/(?<=[\p{L}\p{N}»“”"\')\].,!?…:;\x{E013}])(' . $in . ')[ \t\r\n]+(' . $in . ')(—|–|\x{E012})(?=[\s\x{A0}\x{E000}\x{E001}\x{E010}]|$)/u' ] = '$1' . self::NBSP . '$2$3';
		}

		foreach ( $rules as $pattern => $replacement ) {
			$s = preg_replace( $pattern, $replacement, $s );
			if ( null === $s ) {
				return null;
			}
		}

		// 4. Возвращаем сущности и теги в прежнем порядке.
		$s = $this->restore( $s, '/[\x{E010}-\x{E013}]/u', $entities );
		$s = null === $s ? null : $this->restore( $s, '/[\x{E000}\x{E001}]/u', $tags );

		return null === $s ? null : str_replace( self::NBSP, '&nbsp;', $s );
	}

	private function restore( $s, $pattern, array $items ) {
		$i = 0;
		$s = preg_replace_callback(
			$pattern,
			function () use ( &$i, $items ) {
				return $items[ $i++ ];
			},
			$s
		);
		return $i === count( $items ) ? $s : null;
	}

	private function words_pattern( $words ) {
		$list = preg_split( '/[\s,]+/u', mb_strtolower( (string) $words ), -1, PREG_SPLIT_NO_EMPTY );
		$list = array_unique( $list );
		usort(
			$list,
			function ( $a, $b ) {
				return mb_strlen( $b ) - mb_strlen( $a );
			}
		);
		return implode(
			'|',
			array_map(
				function ( $w ) {
					return preg_quote( $w, '/' );
				},
				$list
			)
		);
	}
}
