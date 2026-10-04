<?php
return array(
	'domain'       => 'tipograf',
	'plural-forms' => 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
	'language'     => 'ru_RU',
	'messages'     => array(
		'Hanging prepositions fix for Russian texts: keeps short words, particles, dashes and units on one line with the neighbouring word using non-breaking spaces.' => 'Убирает висячие предлоги в русских текстах: короткие слова, частицы, тире и единицы измерения не отрываются от соседнего слова (неразрывные пробелы).',
		'Typograph'                 => 'Типограф',
		'Settings'                  => 'Настройки',
		'Rules'                     => 'Правила',
		'Short words'               => 'Короткие слова',
		'Particles'                 => 'Частицы',
		'Dash'                      => 'Тире',
		'Numbers and abbreviations' => 'Числа и сокращения',
		'Prepositions, conjunctions and short pronouns stay with the next word: «с вами», «на сайт», «не всегда».' => 'Предлоги, союзы и короткие местоимения не отрываются от следующего слова: «с вами», «на сайт», «не всегда».',
		'же, ли, бы stay with the previous word: «он же», «был ли».' => 'Частицы же, ли, бы не отрываются от предыдущего слова: «он же», «был ли».',
		'A dash never starts a line: «Москва — столица».' => 'Тире не переносится в начало строки: «Москва — столица».',
		'A number stays with its unit and an abbreviation with the next word: «10 м», «5 шт», «№ 5», «г. Москва».' => 'Число не отрывается от единицы измерения, сокращение — от следующего слова: «10 м», «5 шт», «№ 5», «г. Москва».',
		'Text on the site pages is processed when the page is served; the texts in the database are not changed. After changing the settings the page cache is cleared automatically (WP Super Cache, WP Rocket, LiteSpeed); with another cache plugin, clear it manually.' => 'Текст обрабатывается при выдаче страницы, сами тексты в базе не меняются. После изменения настроек кеш страниц сбрасывается сам (WP Super Cache, WP Rocket, LiteSpeed); если у вас другой плагин кеша, сбросьте его вручную.',
		'Separated by spaces or commas, case does not matter.' => 'Через пробел или запятую, регистр не важен.',
	),
);
