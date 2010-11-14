<?php
/**
 * Internationalisation file for Cite extension.
 *
 * @addtogroup Extensions
*/

$wgBibCiteMessages = array();

$wgBibCiteMessages['en'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Cite croaked; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Internal error; invalid $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Internal error; invalid key',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Internal error; invalid key',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Internal error; invalid stack key',

	# User errors
	'bibcite_error' => 'Cite error $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Invalid <code>&lt;ref&gt;</code> tag; name cannot be a simple integer, use a descriptive title',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Invalid <code>&lt;ref&gt;</code> tag; refs with no content must have a name',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Invalid <code>&lt;ref&gt;</code> tag; invalid names, e.g. too many',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Invalid <code>&lt;ref&gt;</code> tag; refs with no name must have content',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Invalid <code>&lt;references&gt;</code> tag; no input is allowed, use
<code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Invalid <code>&lt;references&gt;</code> tag; no parameters are allowed, use <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Ran out of custom backlink labels, define more in the \"''bibcite_references_link_many_format_backlink_labels''\" message",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_TEXT            => 'No text given.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',

	/*
	   Output formatting
	*/
	'bibcite_reference_link_key_with_num' => '$1_$2',
	# Ids produced by <ref>
	'bibcite_reference_link_prefix'       => '_ref-',
	'bibcite_reference_link_suffix'       => '',
	# Ids produced by <references>
	'bibcite_references_link_prefix'      => '_note-',
	'bibcite_references_link_suffix'      => '',

	'bibcite_reference_link'                              => '<sup id="$1" class="reference">[[#$2|<nowiki>[</nowiki>$3<nowiki>]</nowiki>]]</sup>',
	'bibcite_references_link_one'                         => '<li id="$1">[[#$2|↑]] $3 $4</li>',
	'bibcite_references_link_many'                        => '<li id="$1">↑ $2 $3 $4</li>',
	'bibcite_references_link_many_format'                 => '<sup>[[#$1|$2]]</sup>',
	'bibcite_references_bib_backlink'				      => '<sup>[[$1|$2]]</sup>',
	'bibcite_references_bib_backlink_fullurl'		      => '<sup class="reference">[$1 $2]</sup>',
	# An item from this set is passed as $3 in the message above
	'bibcite_references_link_many_format_backlink_labels' => 'a b c d e f g h i j k l m n o p q r s t u v w x y z',
	'bibcite_references_link_many_sep'                    => " ",
	'bibcite_references_link_many_and'                    => " ",

	# Although I could just use # instead of <li> above and nothing here that
	# will break on input that contains linebreaks
	'bibcite_references_prefix' => '<ol class="references">',
	'bibcite_references_suffix' => '</ol>',
);
$wgBibCiteMessages['cs'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Nefunkční citace; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Vnitřní chyba; neplatný $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Vnitřní chyba; neplatný klíč',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Vnitřní chyba; neplatný klíč',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Vnitřní chyba; neplatný klíč zásobníku',

	# User errors
	'bibcite_error' => 'Chybná citace $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Chyba v tagu <code>&lt;ref&gt;</code>; názvem nesmí být prosté číslo, použijte popisné označení',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Chyba v tagu <code>&lt;ref&gt;</code>; prázdné citace musí obsahovat název',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Chyba v tagu <code>&lt;ref&gt;</code>; chybné názvy, např. je jich příliš mnoho',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Chyba v tagu <code>&lt;ref&gt;</code>; citace bez názvu musí mít vlastní obsah',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Chyba v tagu <code>&lt;references&gt;</code>; zde není dovolen vstup, použijte <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Invalid <code>&lt;references&gt;</code> tag; no parameters are allowed, use <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Došla označení zpětných odkazů, přidejte jich několik do zprávy „''bibcite_references_link_many_format_backlink_labels''“",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);

$wgBibCiteMessages['de'] = array(
	# Internal errors
	'bibcite_croak'	=> 'Fehler im Referenz-System. $1: $2',
	'bibcite_error'	=> 'Referenz-Fehler $1: $2',
	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID			 => 'Interner Fehler: ungültiger $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1		 => 'Interner Fehler: Ungültiger „name“',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2		 => 'Interner Fehler: ungültiger „name“',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT		 => 'Interner Fehler: ungültiger „name“-stack',

	# User errors
	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY		 => 'Ungültige <code><nowiki><ref></nowiki></code>-Verwendung: „name“ darf kein ' .
									'reiner Zahlenwert sein, benutze einen beschreibenden Namen.',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY			 => 'Ungültige <code><nowiki><ref></nowiki></code>-Verwendung: „ref“ ohne Inhalt muss einen Namen haben.',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS		 => 'Ungültige <code><nowiki><ref></nowiki></code>-Verwendung: „name“ ist ungültig oder zu lang.',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT			 => 'Ungültige <code><nowiki><ref></nowiki></code>-Verwendung: „ref“ ohne Namen muss einen Inhalt haben.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT	 => 'Ungültige <code><nowiki><references></nowiki></code>-Verwendung: Es ist kein zusätzlicher Text erlaubt, ' .
									'verwende ausschließlich <code><nowiki><references /></nowiki></code>.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Ungültige <code><nowiki><reference></nowiki></code>-Verwendung: Es sind keine ' .
									'zusätzlichen Parameter erlaubt, ' .
									'verwende ausschließlich <code><nowiki><reference /></nowiki></code>.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL	 => 'Eine Referenz der Form <code><nowiki><ref name="…"/></nowiki></code> wird öfter ' .
									'benutzt als Buchstaben vorhanden sind. Ein Administrator muss <nowiki>[[MediaWiki:cite references link many format backlink labels]]</nowiki> um weitere Buchstaben/Zeichen ergänzen.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_TEXT            => 'Eine Referenz der Form <code><nowiki><ref name="…"/></nowiki></code> wird verwendet, ohne definiert worden zu sein.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);

$wgBibCiteMessages['fr'] = array(
	# Internal errors
	'bibcite_croak' => 'Citation corrompue ; $1 : $2',
	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Erreur interne ; $str attendue',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Erreur interne ; clé invalide',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Erreur interne ; clé invalide ',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Erreur interne ; clé de pile invalide',

	# User errors
	'bibcite_error' => 'Erreur de citation $1 ; $2',
	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Appel invalide ; clé non-intégrale attendue',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Appel invalide ; aucune clé spécifiée',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Appel invalide ; clés invalides, par exemple, trop de clés spécifiées ou clé erronée',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Appel invalide ; aucune entrée spécifiée',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Entrée invalide ; entrée attendue',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Arguments invalides ; argument attendu',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Exécution hors des étiquettes personnalisées, définissez plus dans le message « bibcite_references_link_many_format_backlink_labels »",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_TEXT            => 'Aucun texte indiqué.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['he'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'בהערה יש שגיאה; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'שגיאה פנימית; $str שגוי',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'שגיאה פנימית; מפתח שגוי',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'שגיאה פנימית; מפתח שגוי',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'שגיאה פנימית; מפתח שגוי בערימה',

	# User errors
	'bibcite_error' => 'שגיאת ציטוט $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'תגית <code>&lt;ref&gt;</code> שגויה; שם לא יכול להיות מספר פשוט, יש להשתמש בכותרת תיאורית',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'תגית <code>&lt;ref&gt;</code> שגויה; להערות שוליים ללא תוכן חייב להיות שם',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'תגית <code>&lt;ref&gt;</code> שגויה; שמות שגויים, למשל, רבים מדי',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'תגית <code>&lt;ref&gt;</code> שגויה; להערות שוליים ללא שם חייב להיות תוכן',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'תגית <code>&lt;references&gt;</code> שגויה; לא ניתן לכתוב תוכן, יש להשתמש בקוד <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'תגית <code>&lt;references&gt;</code> שגויה; לא ניתן להשתמש בפרמטרים, יש להשתמש בקוד <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "נגמרו תוויות הקישורים המותאמים אישית, אנא הגדירו נוספים בהודעת המערכת \"''bibcite_references_link_many_format_backlink_labels''\"",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_TEXT            => 'לא נכתב טקסט.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['id'] = array(
	# Internal errors
	'bibcite_croak' => 'Kegagalan pengutipan; $1: $2',
	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Kesalahan internal; $str tak sah',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Kesalahan internal; kunci tak sah',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Kesalahan internal; kunci tak sah',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Kesalahan internal; kunci stack tak sah',

	# User errors
	'bibcite_error' => 'Kesalahan pengutipan $1; $2',
	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Kesalahan pemanggilan; diharapkan suatu kunci non-integer',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Kesalahan pemanggilan; tidak ada kunci yang dispesifikasikan',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Kesalahan pemanggilan; kunci tak sah, contohnya karena terlalu banyak atau tidak ada kunci yang dispesifikasikan',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Kesalahan pemanggilan; tidak ada masukan yang dispesifikasikan',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Kesalahan masukan; seharusnya tidak ada',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Paramater tak sah; seharusnya tidak ada',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Kehabisan label pralana balik, tambakan pada pesan sistem \"''bibcite_references_link_many_format_backlink_labels''\"",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['it'] = array(

	# Internal errors
	'bibcite_croak' => 'Errore nella citazione: $1: $2',
	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Errore interno: $str errato',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Errore interno: chiave errata',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Errore interno: chiave errata',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Errore interno: chiave di stack errata',

	# User errors
	'bibcite_error' => 'Errore nella funzione Cite $1: $2',
	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Errore nell\'uso del marcatore <code>&lt;ref&gt;</code>: il nome non può essere un numero intero. Usare un titolo esteso',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Errore nell\'uso del marcatore <code>&lt;ref&gt;</code>: i ref vuoti non possono essere privi di nome',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Errore nell\'uso del marcatore <code>&lt;ref&gt;</code>: nomi non validi (ad es. numero troppo elevato)',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Errore nell\'uso del marcatore <code>&lt;ref&gt;</code>: i ref privi di nome non possono essere vuoti',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Errore nell\'uso del marcatoree <code>&lt;references&gt;</code>: input non ammesso, usare il marcatore
<code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Errore nell\'uso del marcatore <code>&lt;references&gt;</code>: parametri non ammessi, usare il marcatore <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Etichette di rimando personalizzate esaurite, aumentarne il numero nel messaggio \"''bibcite_references_link_many_format_backlink_labels''\"",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',

);

$wgBibCiteMessages['ja'] = array(

	# Internal errors
	'bibcite_croak' => '引用タグ機能の重大なエラー; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => '内部エラー; 無効な $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => '内部エラー; 無効なキー',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => '内部エラー; 無効なキー',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => '内部エラー; 無効なスタックキー',

	# User errors
	'bibcite_error' => '引用エラー $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => '無効な <code>&lt;ref&gt;</code> タグ: 名前に単純な数値は使用できません。',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => '無効な <code>&lt;ref&gt;</code> タグ: 引用句の内容がない場合には名前 （<code>name</code> 属性）が必要です',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => '無効な <code>&lt;ref&gt;</code> タグ: 無効な名前（多すぎる、もしくは誤った指定）',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => '無効な <code>&lt;ref&gt;</code> タグ: 名前 （<code>name</code> 属性）がない場合には引用句の内容が必要です',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => '無効な <code>&lt;references&gt;</code> タグ: 内容のあるタグは使用できません。 <code>&lt;references /&gt;</code> を用いてください。',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => '無効な <code>&lt;references&gt;</code> タグ: 引数のあるタグは使用できません。 <code>&lt;references /&gt;</code> を用いてください。',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "バックリンクラベルが使用できる個数を超えました。\"''bibcite_references_link_many_format_backlink_labels''\" メッセージでの定義を増やしてください。",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);

$wgBibCiteMessages['kk-kz'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Дәйексөз алу сәтсіз бітті; $1: $2 ',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Ішкі қате; жарамсыз $str ',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Ішкі қате; жарамсыз кілт',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Ішкі қате; жарамсыз кілт',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Ішкі қате; жарамсыз стек кілті',

	# User errors
	'bibcite_error' => 'Дәйексөз алу $1 қатесі; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Жарамсыз <code>&lt;ref&gt;</code> белгішесі; атау кәдімгі бүтін сан болуы мүмкін емес, сиппатауыш атау қолданыңыз',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Жарамсыз <code>&lt;ref&gt;</code> белгішесі; мағлұматсыз түсініктемелерде атау болуы қажет',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Жарамсыз <code>&lt;ref&gt;</code> белгіше; жарамсыз атаулар, мысалы, тым көп',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Жарамсыз <code>&lt;ref&gt;</code> белгіше; атаусыз түсініктемелерде мағлұматы болуы қажет',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Жарамсыз <code>&lt;references&gt;</code> белгіше; еш кіріс рұқсат етілмейді, былай <code>&lt;references /&gt;</code> қолданыңыз',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Жарамсыз <code>&lt;references&gt;</code> белгіше; еш баптар рұқсат етілмейді, былай <code>&lt;references /&gt;</code> қолданыңыз',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => 'Қосымша белгілердің саны бітті, одан әрі көбірек «\'\'bibcite_references_link_many_format_backlink_labels\'\'» жүйе хабарында белгілеңіз',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['kk-tr'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Däýeksöz alw sätsiz bitti; $1: $2 ',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'İşki qate; jaramsız $str ',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'İşki qate; jaramsız kilt',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'İşki qate; jaramsız kilt',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'İşki qate; jaramsız stek kilti',

	# User errors
	'bibcite_error' => 'Däýeksöz alw $1 qatesi; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Jaramsız <code>&lt;ref&gt;</code> belgişesi; ataw kädimgi bütin san bolwı mümkin emes, sïppatawış ataw qoldanıñız',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Jaramsız <code>&lt;ref&gt;</code> belgişesi; mağlumatsız tüsiniktemelerde ataw bolwı qajet',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Jaramsız <code>&lt;ref&gt;</code> belgişe; jaramsız atawlar, mısalı, tım köp',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Jaramsız <code>&lt;ref&gt;</code> belgişe; atawsız tüsiniktemelerde mağlumatı bolwı qajet',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Jaramsız <code>&lt;references&gt;</code> belgişe; eş kiris ruqsat etilmeýdi, bılaý <code>&lt;references /&gt;</code> qoldanıñız',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Jaramsız <code>&lt;references&gt;</code> belgişe; eş baptar ruqsat etilmeýdi, bılaý <code>&lt;references /&gt;</code> qoldanıñız',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => 'Qosımşa belgilerdiñ sanı bitti, odan äri köbirek «\'\'bibcite_references_link_many_format_backlink_labels\'\'» jüýe xabarında belgileñiz',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['kk-cn'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'دٵيەكسٶز الۋ سٵتسٸز بٸتتٸ; $1: $2 ',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'ٸشكٸ قاتە; جارامسىز $str ',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'ٸشكٸ قاتە; جارامسىز كٸلت',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'ٸشكٸ قاتە; جارامسىز كٸلت',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'ٸشكٸ قاتە; جارامسىز ستەك كٸلتٸ',

	# User errors
	'bibcite_error' => 'دٵيەكسٶز الۋ $1 قاتەسٸ; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'جارامسىز <code>&lt;ref&gt;</code> بەلگٸشەسٸ; اتاۋ كٵدٸمگٸ بٷتٸن سان بولۋى مٷمكٸن ەمەس, سيپپاتاۋىش اتاۋ قولدانىڭىز',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'جارامسىز <code>&lt;ref&gt;</code> بەلگٸشەسٸ; ماعلۇماتسىز تٷسٸنٸكتەمەلەردە اتاۋ بولۋى قاجەت',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'جارامسىز <code>&lt;ref&gt;</code> بەلگٸشە; جارامسىز اتاۋلار, مىسالى, تىم كٶپ',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'جارامسىز <code>&lt;ref&gt;</code> بەلگٸشە; اتاۋسىز تٷسٸنٸكتەمەلەردە ماعلۇماتى بولۋى قاجەت',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'جارامسىز <code>&lt;references&gt;</code> بەلگٸشە; ەش كٸرٸس رۇقسات ەتٸلمەيدٸ, بىلاي <code>&lt;references /&gt;</code> قولدانىڭىز',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'جارامسىز <code>&lt;references&gt;</code> بەلگٸشە; ەش باپتار رۇقسات ەتٸلمەيدٸ, بىلاي <code>&lt;references /&gt;</code> قولدانىڭىز',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => 'قوسىمشا بەلگٸلەردٸڭ سانى بٸتتٸ, ودان ٵرٸ كٶبٸرەك «\'\'bibcite_references_link_many_format_backlink_labels\'\'» جٷيە حابارىندا بەلگٸلەڭٸز',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['kk'] = $wgBibCiteMessages['kk-kz'];
$wgBibCiteMessages['lt'] = array(
	# Internal errors
	'bibcite_croak' => 'Cituoti nepavyko; $1: $2',
	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Vidinė klaida; neleistinas $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Vidinė klaida; neleistinas raktas',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Vidinė klaida; neleistinas raktas',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Vidinė klaida; neleistinas steko raktas',

	# User errors
	'bibcite_error' => 'Citavimo klaida $1; $2',
	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Neleistina <code>&lt;ref&gt;</code> gairė; vardas negali būti tiesiog skaičius, naudokite tekstinį pavadinimą',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Neleistina <code>&lt;ref&gt;</code> gairė; nuorodos be turinio turi turėti vardą',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Neleistina <code>&lt;ref&gt;</code> gairė; neleistini vardai, pvz., per daug',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Neleistina <code>&lt;ref&gt;</code> gairė; nuorodos be vardo turi turėti turinį',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Neleistina <code>&lt;references&gt;</code> gairė; neleistina jokia įvestis, naudokite <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Neleistina <code>&lt;references&gt;</code> gairė; neleidžiami jokie parametrai, naudokite <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Baigėsi antraštės, nurodykite daugiau \"''bibcite_references_link_many_format_backlink_labels''\" sisteminiame tekste",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['nl'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Probleem met Cite; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Interne fout; onjuiste $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Interne fout; onjuiste sleutel',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Interne fout; onjuiste sleutel',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Interne fout; onjuiste stacksleutel',

	# User errors
	'bibcite_error' => 'Citefout $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Onjuiste tag <code>&lt;ref&gt;</code>; de naam kan geen simplele integer zijn, gebruik een beschrijvende titel',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Onjuiste tag <code>&lt;ref&gt;</code>; refs zonder inhoud moeten een naam hebben',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Onjuiste tag <code>&lt;ref&gt;</code>; onjuiste namen, bijvoorbeeld te veel',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Onjuiste tag <code>&lt;ref&gt;</code>; refs zonder naam moeten inhoud hebben',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Onjuiste tag <code>&lt;references&gt;</code>; invoer is niet toegestaan, gebruik <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Onjuiste tag <code>&lt;references&gt;</code>; parameters zijn niet toegestaan, gebruik <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Ran out of custom backlink labels, define more in the \"''bibcite_references_link_many_format_backlink_labels''\" message",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['pt'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Citação com problemas; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Erro interno; $str inválido',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Erro interno; chave inválida',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Erro interno; chave inválida',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Erro interno; chave fixa inválida',

	# User errors
	'bibcite_error' => 'Erro de citação $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Código <code>&lt;ref&gt;</code> inválido; o nome não pode ser um número. Utilize um nome descritivo',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Código <code>&lt;ref&gt;</code> inválido; refs sem conteúdo devem ter um parâmetro de nome',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Código <code>&lt;ref&gt;</code> inválido; nomes inválidos (por exemplo, nome muito extenso)',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Código <code>&lt;ref&gt;</code> inválido; refs sem parâmetro de nome devem possuir conteúdo a elas associado',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Código <code>&lt;references&gt;</code> inválido; no input is allowed, use
<code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Código <code>&lt;references&gt;</code> inválido; não são permitidos parâmetros. Utilize como <code>&lt;references /&gt;</code>',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Ran out of custom backlink labels, define more in the \"''bibcite_references_link_many_format_backlink_labels''\" message",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['ru'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Цитата сдохла; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Внутренняя ошибка: неверный $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Внутренняя ошибка: неверный ключ',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Внутренняя ошибка: неверный ключ',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Внутренняя ошибка: неверный ключ стека ',

	# User errors
	'bibcite_error' => 'Ошибка цитирования $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Неправильный вызов: ожидался нечисловой ключ',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Неправильный вызов: ключ не был указан',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Неправильный вызов: неверные ключи, например было указано слишком много ключей или ключ был неправильным',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Неверный вызов: нет входных данных',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Входные данные недействительны, так как не предполагаются',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Переданы недействительные параметры; их вообще не предусмотрено.',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => 'Не хватает символов для возвратных гиперссылок; следует расширить системную переменную «bibcite_references_link_many_format_backlink_labels».',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',

	/*
	   Output formatting
	*/
	'bibcite_references_link_many_format_backlink_labels' => 'а б в г д е ё ж з и й к л м н о п р с т у ф х ц ч ш щ ъ ы ь э ю я',
);
$wgBibCiteMessages['sk'] = array(
	/*
	    Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => 'Citát je už neaktuálny; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => 'Vnútorná chyba; neplatný $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => 'Vnútorná chyba; neplatný kľúč',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => 'Vnútorná chyba; neplatný kľúč',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => 'Vnútorná chyba; neplatný kľúč zásobníka',

	# User errors
	'bibcite_error' => 'Chyba citácie $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => 'Neplatné volanie; očakáva sa neceločíselný typ kľúča',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => 'Neplatné volanie; nebol špecifikovaný kľúč',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => 'Neplatné volanie; neplatné kľúče, napr. príliš veľa alebo nesprávne špecifikovaný kľúč',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => 'Neplatné volanie; nebol špecifikovaný vstup',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => 'Neplatné volanie; neočakával sa vstup',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => 'Neplatné parametre; neočakávli sa žiadne',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "Minuli sa generované návestia spätných odkazov, definujte viac v správe \"''bibcite_references_link_many_format_backlink_labels''\"",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['yue'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => '引用阻塞咗; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => '內部錯誤; 無效嘅 $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => '內部錯誤; 無效嘅匙',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => '內部錯誤; 無效嘅匙',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => '內部錯誤; 無效嘅堆疊匙',

	# User errors
	'bibcite_error' => '引用錯誤 $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => '無效嘅呼叫; 需要一個非整數嘅匙',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => '無效嘅呼叫; 未指定匙',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => '無效嘅呼叫; 無效嘅匙, 例如: 太多或者指定咗一個錯咗嘅匙',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => '無效嘅呼叫; 未指定輸入',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => '無效嘅輸入; 唔需要有嘢',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => '無效嘅參數; 唔需要有嘢',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "用晒啲自定返回標籤, 響 \"''bibcite_references_link_many_format_backlink_labels''\" 信息再整多啲",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['zh-hans'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => '引用阻塞; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => '内部错误；非法的 $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => '内部错误；非法键值',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => '内部错误；非法键值',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => '内部错误；非法堆栈键值',

	# User errors
	'bibcite_error' => '引用错误 $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => '无效呼叫；需要一个非整数的键值',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => '无效呼叫；没有指定键值',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => '无效呼叫；非法键值，例如：过多或错误的指定键值',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => '无效呼叫；没有指定的输入',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => '无效输入；需求为空',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => '非法参数；需求为空',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "自定义后退标签已经用完了，现在可在标签 \"''bibcite_references_link_many_format_backlink_labels''\" 定义更多信息",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['zh-hant'] = array(
	/*
		Debug and errors
	*/

	# Internal errors
	'bibcite_croak' => '引用阻塞; $1: $2',

	'bibcite_error_' . BIBCITE_ERROR_STR_INVALID         => '內部錯誤；非法的 $str',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_1       => '內部錯誤；非法鍵',
	'bibcite_error_' . BIBCITE_ERROR_KEY_INVALID_2       => '內部錯誤；非法鍵',
	'bibcite_error_' . BIBCITE_ERROR_STACK_INVALID_INPUT => '內部錯誤；非法堆疊鍵值',

	# User errors
	'bibcite_error' => '引用錯誤 $1; $2',

	'bibcite_error_' . BIBCITE_ERROR_REF_NUMERIC_KEY               => '無效呼叫；需要一個非整數的鍵',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_KEY                    => '無效呼叫；沒有指定鍵',
	'bibcite_error_' . BIBCITE_ERROR_REF_TOO_MANY_KEYS             => '無效呼叫；非法鍵值，例如：過多或錯誤的指定鍵',
	'bibcite_error_' . BIBCITE_ERROR_REF_NO_INPUT                  => '無效呼叫；沒有指定的輸入',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_INPUT      => '無效輸入；需求為空',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_INVALID_PARAMETERS => '非法參數；需求為空',
	'bibcite_error_' . BIBCITE_ERROR_REFERENCES_NO_BACKLINK_LABEL  => "自訂後退標籤已經用完了，現在可在標籤 \"''bibcite_references_link_many_format_backlink_labels''\" 定義更多信息",
	'bibcite_error_' . BIBCITE_ERROR_REFERENCE_NOT_FOUND           => 'BibTeX record not found: %1',
);
$wgBibCiteMessages['zh'] = $wgBibCiteMessages['zh-hans'];
$wgBibCiteMessages['zh-cn'] = $wgBibCiteMessages['zh-hans'];
$wgBibCiteMessages['zh-hk'] = $wgBibCiteMessages['zh-hant'];
$wgBibCiteMessages['zh-sg'] = $wgBibCiteMessages['zh-hans'];
$wgBibCiteMessages['zh-tw'] = $wgBibCiteMessages['zh-hant'];
$wgBibCiteMessages['zh-yue'] = $wgBibCiteMessages['yue'];