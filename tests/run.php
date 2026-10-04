<?php
/**
 * Проверки движка без WordPress: php tests/run.php
 */

define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/includes/class-tipograf-engine.php';

$cases = array(
	// Короткие слова.
	'с вами'                                    => 'с&nbsp;вами',
	'Работаем на сайт и не всегда'              => 'Работаем на&nbsp;сайт и&nbsp;не&nbsp;всегда',
	'В Москве и в области'                      => 'В&nbsp;Москве и&nbsp;в&nbsp;области',
	'Для вас через час'                         => 'Для&nbsp;вас через&nbsp;час',
	'в «Москве»'                                => 'в&nbsp;«Москве»',
	'в &laquo;Москве&raquo;'                    => 'в&nbsp;&laquo;Москве&raquo;',
	'с 10:00 до 18:00'                          => 'с&nbsp;10:00 до&nbsp;18:00',
	'НА СКЛАДЕ'                                 => 'НА&nbsp;СКЛАДЕ',
	'вода в'                                    => 'вода в',
	'головная'                                  => 'головная',
	'Ростов-на-Дону'                            => 'Ростов-на-Дону',
	'из-за угла'                                => 'из-за угла',
	'уже&nbsp;с вами'                           => 'уже&nbsp;с&nbsp;вами',
	"для\nвас"                                  => 'для&nbsp;вас',
	// Через строчные теги.
	'в <a href="/x">доме</a>'                   => 'в&nbsp;<a href="/x">доме</a>',
	'<strong>в</strong> доме'                   => '<strong>в</strong>&nbsp;доме',
	'<p>Скейт-парк в</p><p>доме</p>'            => '<p>Скейт-парк в</p><p>доме</p>',
	'в <br>доме'                                => 'в <br>доме',
	// Теги, атрибуты, скрипты не трогаем.
	'<img alt="в доме" title="на сайт">'        => '<img alt="в доме" title="на сайт">',
	'<a data-x="a>b в доме" href="#">и я</a>'   => '<a data-x="a>b в доме" href="#">и&nbsp;я</a>',
	'<script>var s = "в доме";</script> в доме' => '<script>var s = "в доме";</script> в&nbsp;доме',
	'<style>.a > .b { }</style>'                => '<style>.a > .b { }</style>',
	'<pre>в доме</pre><code>на сайт</code>'     => '<pre>в доме</pre><code>на сайт</code>',
	'<head><title>в доме</title></head><body>в доме</body>' => '<head><title>в доме</title></head><body>в&nbsp;доме</body>',
	'<!-- в доме --> в доме'                    => '<!-- в доме --> в&nbsp;доме',
	'<textarea>в доме</textarea>'               => '<textarea>в доме</textarea>',
	'a &amp; в доме'                            => 'a &amp; в&nbsp;доме',
	// Частицы.
	'он же сказал'                              => 'он&nbsp;же сказал',
	'был ли он'                                 => 'был&nbsp;ли он',
	'если бы'                                   => 'если&nbsp;бы',
	'женщина'                                   => 'женщина',
	// Тире.
	'Москва — столица'                          => 'Москва&nbsp;— столица',
	'Москва &mdash; столица'                    => 'Москва&nbsp;&mdash; столица',
	'2010 – 2020'                               => '2010&nbsp;– 2020',
	'бизнес-план'                               => 'бизнес-план',
	// Числа, единицы, сокращения.
	'10 м и 5 шт'                               => '10&nbsp;м и&nbsp;5&nbsp;шт',
	'1,5 км'                                    => '1,5&nbsp;км',
	'в 2026 году'                               => 'в&nbsp;2026&nbsp;году',
	'500 руб'                                   => '500&nbsp;руб',
	'10 мест'                                   => '10 мест',
	'№ 5'                                       => '№&nbsp;5',
	'г. Москва, ул. Ленина, д. 5'               => 'г.&nbsp;Москва, ул.&nbsp;Ленина, д.&nbsp;5',
	// Ничего не ломаем.
	''                                          => '',
	"битая \xC3\x28 кодировка в доме"           => "битая \xC3\x28 кодировка в доме",
);

$fail = 0;
foreach ( $cases as $in => $want ) {
	$got = Tipograf_Engine::process( (string) $in );
	if ( $got !== $want ) {
		$fail++;
		echo "FAIL\n  in:   $in\n  want: $want\n  got:  $got\n";
	}
}

// Отключённые правила.
$off = Tipograf_Engine::process( 'с вами — он же 10 м', array( 'short_words' => false, 'particles' => false, 'dash' => false, 'units' => false ) );
if ( 'с вами — он же 10 м' !== $off ) {
	$fail++;
	echo "FAIL all rules off: $off\n";
}

// Свой список слов.
$own = Tipograf_Engine::process( 'с вами про дом', array( 'words' => 'про' ) );
if ( 'с вами про&nbsp;дом' !== $own ) {
	$fail++;
	echo "FAIL own words: $own\n";
}

$total = count( $cases ) + 2;
echo $fail ? "\n$fail of $total failed\n" : "OK, $total checks\n";
exit( $fail ? 1 : 0 );
