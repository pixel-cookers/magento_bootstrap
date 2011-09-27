<?php


class Country
{
	private static $LOADED = false;
	private static $COUNTRIES = array(
		'code2name' => array(
			'AF' => 'Afghanistan',
			'ZA' => 'Afrique du Sud',
			'AX' => 'Åland',
			'AL' => 'Albanie',
			'DZ' => 'Algérie',
			'DE' => 'Allemagne',
			'AD' => 'Andorre',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctique',
			'AG' => 'Antigua-et-Barbuda',
			'AN' => 'Antilles néerlandaises',
			'SA' => 'Arabie saoudite',
			'AR' => 'Argentine',
			'AM' => 'Arménie',
			'AW' => 'Aruba',
			'AU' => 'Australie',
			'AT' => 'Autriche',
			'AZ' => 'Azerbaïdjan',
			'BS' => 'Bahamas',
			'BH' => 'Bahreïn',
			'BD' => 'Bangladesh',
			'BB' => 'Barbade',
			'BY' => 'Biélorussie',
			'BE' => 'Belgique',
			'BZ' => 'Belize',
			'BJ' => 'Bénin',
			'BM' => 'Bermudes',
			'BT' => 'Bhoutan',
			'BO' => 'Bolivie',
			'BA' => 'Bosnie-Herzégovine',
			'BW' => 'Botswana',
			'BV' => 'Île Bouvet',
			'BR' => 'Brésil',
			'BN' => 'Brunei',
			'BG' => 'Bulgarie',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KY' => 'Îles Caïmans',
			'KH' => 'Cambodge',
			'CM' => 'Cameroun',
			'CA' => 'Canada',
			'CV' => 'Cap-Vert',
			'CF' => 'République centrafricaine',
			'CL' => 'Chili',
			'CN' => 'Chine',
			'CX' => 'Île Christmas',
			'CY' => 'Chypre',
			'CC' => 'Îles Cocos',
			'CO' => 'Colombie',
			'KM' => 'Comores',
			'CG' => 'Congo',
			'CD' => 'République démocratique du Congo',
			'CK' => 'Îles Cook',
			'KR' => 'Corée du Sud',
			'KP' => 'Corée du Nord',
			'CR' => 'Costa Rica',
			'CI' => "Côte d'Ivoire",
			'HR' => 'Croatie',
			'CU' => 'Cuba',
			'DK' => 'Danemark',
			'DJ' => 'Djibouti',
			'DO' => 'République dominicaine',
			'DM' => 'Dominique',
			'EG' => 'Égypte',
			'SV' => 'Salvador',
			'AE' => 'Émirats arabes unis',
			'EC' => 'Équateur',
			'ER' => 'Érythrée',
			'ES' => 'Espagne',
			'EE' => 'Estonie',
			'US' => 'États-Unis',
			'ET' => 'Éthiopie',
			'FK' => 'Îles Malouines',
			'FO' => 'Îles Féroé',
			'FJ' => 'Fidji',
			'FI' => 'Finlande',
			'FR' => 'France',
			'GA' => 'Gabon',
			'GM' => 'Gambie',
			'GE' => 'Géorgie',
			'GS' => 'Géorgie du Sud-et-les Îles Sandwich du Sud',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Grèce',
			'GD' => 'Grenade',
			'GL' => 'Groenland',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernesey',
			'GN' => 'Guinée',
			'GW' => 'Guinée-Bissau',
			'GQ' => 'Guinée équatoriale',
			'GY' => 'Guyana',
			'GF' => 'Guyane Française',
			'HT' => 'Haïti',
			'HM' => 'Îles Heard-et-MacDonald',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hongrie',
			'IM' => 'Île de Man',
			'UM' => 'Îles mineures éloignées des États-Unis',
			'VG' => 'Îles Vierges britanniques',
			'VI' => 'Îles Vierges américaines',
			'IN' => 'Inde',
			'ID' => 'Indonésie',
			'IR' => 'Iran',
			'IQ' => 'Irak',
			'IE' => 'Irlande',
			'IS' => 'Islande',
			'IL' => 'Israël',
			'IT' => 'Italie',
			'JM' => 'Jamaïque',
			'JP' => 'Japon',
			'JE' => 'Jersey',
			'JO' => 'Jordanie',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KG' => 'Kirghizistan',
			'KI' => 'Kiribati',
			'KW' => 'Koweït',
			'LA' => 'Laos',
			'LS' => 'Lesotho',
			'LV' => 'Lettonie',
			'LB' => 'Liban',
			'LR' => 'Libéria',
			'LY' => 'Libye',
			'LI' => 'Liechtenstein',
			'LT' => 'Lituanie',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macédoine',
			'MG' => 'Madagascar',
			'MY' => 'Malaisie',
			'MW' => 'Malawi',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malte',
			'MP' => 'Îles Mariannes du Nord',
			'MA' => 'Maroc',
			'MH' => 'Îles Marshall',
			'MQ' => 'Martinique',
			'MU' => 'Île Maurice',
			'MR' => 'Mauritanie',
			'YT' => 'Mayotte',
			'MX' => 'Mexique',
			'FM' => 'Micronésie',
			'MD' => 'Moldavie',
			'MC' => 'Monaco',
			'MN' => 'Mongolie',
			'ME' => 'Monténégro',
			'MS' => 'Montserrat',
			'MZ' => 'Mozambique',
			'MM' => 'Birmanie',
			'NA' => 'Namibie',
			'NR' => 'Nauru',
			'NP' => 'Népal',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigéria',
			'NU' => 'Niué',
			'NF' => 'Île Norfolk',
			'NO' => 'Norvège',
			'NC' => 'Nouvelle-Calédonie',
			'NZ' => 'Nouvelle-Zélande',
			'IO' => "Territoire britannique de l'océan Indien",
			'OM' => 'Oman',
			'UG' => 'Ouganda',
			'UZ' => 'Ouzbékistan',
			'PK' => 'Pakistan',
			'PW' => 'Palaos',
			'PS' => 'Palestine',
			'PA' => 'Panamá',
			'PG' => 'Papouasie-Nouvelle-Guinée',
			'PY' => 'Paraguay',
			'NL' => 'Pays-Bas',
			'PE' => 'Pérou',
			'PH' => 'Philippines',
			'PN' => 'Îles Pitcairn',
			'PL' => 'Pologne',
			'PF' => 'Polynésie Française',
			'PR' => 'Porto Rico',
			'PT' => 'Portugal',
			'QA' => 'Qatar',
			'RE' => 'La Réunion',
			'RO' => 'Roumanie',
			'GB' => 'Royaume-Uni',
			'RU' => 'Russie',
			'RW' => 'Rwanda',
			'EH' => 'Sahara occidental',
			'BL' => 'Saint-Barthélemy',
			'KN' => 'Saint-Christophe-et-Niévès',
			'SM' => 'Saint-Marin',
			'MF' => 'Saint-Martin',
			'PM' => 'Saint-Pierre-et-Miquelon',
			'VA' => 'Vatican',
			'VC' => 'Saint-Vincent-et-les Grenadines',
			'SH' => 'Sainte-Hélène',
			'LC' => 'Sainte-Lucie',
			'SB' => 'Îles Salomon',
			'WS' => 'Samoa',
			'AS' => 'Samoa américaines',
			'ST' => 'São Tomé-et-Principe',
			'SN' => 'Sénégal',
			'RS' => 'Serbie',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapour',
			'SK' => 'Slovaquie',
			'SI' => 'Slovénie',
			'SO' => 'Somalie',
			'SD' => 'Soudan',
			'LK' => 'Sri Lanka',
			'SE' => 'Suède',
			'CH' => 'Suisse',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard et île Jan Mayen',
			'SZ' => 'Swaziland',
			'SY' => 'Syrie',
			'TJ' => 'Tadjikistan',
			'TW' => 'Taïwan',
			'TZ' => 'Tanzanie',
			'TD' => 'Tchad',
			'CZ' => 'République tchèque',
			'TF' => 'Terres Australes et Antarctiques Françaises',
			'TH' => 'Thaïlande',
			'TL' => 'Timor oriental',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinité-et-Tobago',
			'TN' => 'Tunisie',
			'TM' => 'Turkménistan',
			'TC' => 'Îles Turques-et-Caïques',
			'TR' => 'Turquie',
			'TV' => 'Tuvalu',
			'UA' => 'Ukraine',
			'UY' => 'Uruguay',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Viêt Nam',
			'WF' => 'Wallis-et-Futuna',
			'YE' => 'Yémen',
			'ZM' => 'Zambie',
			'ZW' => 'Zimbabwe',
		),
		'code2soft-cleaned-name' => null,
		'code2hard-cleaned-name' => null,
		'replacement' => array(
			'reunion' => 'RE',
			'france-metropolitaine' => 'FR',
			'guyane' => 'GF',
			'taaf' => 'TF',
			'terres-australes-francaises' => 'TF',
			'usa' => 'US',
			'hollande' => 'NL',
			'grande-bretagne' => 'GB',
			'vietnam' => 'VN',
			'belarus' => 'BY',
			'brunei-darussalam' => 'BN',
			'cayman' => 'KY',
			'caiman' => 'KY',
			'caimanes' => 'KY',
			'cocos-keeling' => 'CC',
			'keeling' => 'CC',
			'centrafrique' => 'CF',
			'congo-brazzaville' => 'CG',
			'congo-kinshasa' => 'CD',
			'zaire' => 'CD',
			'san-marin' => 'SM',
			'surinam' => 'SR',
			'myanmar' => 'MM',
			'saint-christophe-nevis' => 'KN',
			'saint-kitts-nevis' => 'KN',
			'falkland' => 'FK',
			'el-salvador' => 'SV',
			'lithuanie' => 'LT',
			'turks-caiques' => 'TC',
			'vierges-des-etats-unis' => 'VI',
			'etat-de-la-cite-du-vatican' => 'VA',
			'etats-federes-de-micronesie' => 'FM',
			'r-a-s-chinoise-de-hong-kong' => 'HK',
			'r-a-s-chinoise-de-macao' => 'MO',
		),
	);
	
	public static function loadCache() {
		$filename = 'cache/countries';
		if (!file_exists($filename)) {
			self::$COUNTRIES['code2soft-cleaned-name'] = array();
			self::$COUNTRIES['code2hard-cleaned-name'] = array();
			foreach (self::$COUNTRIES['code2name'] as $_code => $_name) {
				$soft_cleaned = self::softClean($_name);
				self::$COUNTRIES['code2soft-cleaned-name'][$_code] = $soft_cleaned;
				self::$COUNTRIES['code2hard-cleaned-name'][$_code] = self::hardClean($soft_cleaned);
			}
			file_put_contents('cache/countries',serialize(self::$COUNTRIES));
		} else {
			self::$COUNTRIES = unserialize(file_get_contents('cache/countries'));
		}
	}

	public static function getCountryNameByCode($code) {
		return isset(self::$COUNTRIES['code2name'][$code]) ? self::$COUNTRIES['code2name'][$code] : null;
	}

	public static function getCountryCodeByName($name, $name_type=null) {
		$suffix = isset($name_type) ? $name_type.'-' : '';
		if (!self::$LOADED) self::loadCache();
		$code = array_search($name, self::$COUNTRIES['code2'.$suffix.'name']);
		return $code!==false ? $code : null;
	}

	public static function getCountryCodeByReplacedName($cleaned_name) {
		if (isset(self::$COUNTRIES['replacement'][$cleaned_name])) return self::$COUNTRIES['replacement'][$cleaned_name];
		return null;
	}

	public static function softClean($input) {
		$input = mb_strtolower($input,'UTF-8');
		$input = preg_replace('/[ -\.]+/','-',trim($input));
		//echo '"'.$input.'" => ';
		$input = str_replace(
			array(' ','ç','é','è','ê','ë','à','á','â','ä','ã','å','ô','ö','ù','û','ü','î','ï','ÿ'),
			array('-','c','e','e','e','e','a','a','a','a','a','a','o','o','u','u','u','i','i','y'),
			$input
		);
		//echo '"'.$input.'", ';
		$input = preg_replace("/^st-/",'saint-',$input);
		$input = preg_replace("/^ste-/",'sainte-',$input);
		$input = preg_replace("/-&-/",'-et-',$input);
		return $input;
	}

	public static function hardClean($input) {
		//$output = preg_match('/ivoire/',$input);
		//if ($output) echo '"'.$input.'" => ';
		$input = preg_replace("/^(?:les|le|la|l'|ile|iles)-/",'',$input);
		$input = preg_replace("/-(?:ile|iles)$/",'',$input);
		$input = preg_replace("/-(?:(?:et|les)-)+/",'-',$input);
		$input = preg_replace("/(?:[^a-z0-9]+)/",'-',$input);
		//if ($output) echo '"'.$input.'",<br/>';
		return $input;
	}

}

class AddressFilter extends Country 
{
	private $data;
	private $code;
	private $name;
	private $classes;
	private $label;
	private $arranged;
	private $address_filters_list;

	public function AddressFilter($data) {
		$this->data = $data;
		$this->classes = array();
		$this->address_filters_list = null;
		$this->parse();
	}

	public function parse($recursive=true) {
		if (strlen($this->data['country_code'])==2) {
			$code = strtoupper($this->data['country_code']);
			$name = Country::getCountryNameByCode($code);
			
			if (isset($name)) {
				$this->code = $code;
				$this->name = $name;
				$this->classes[] = 'known';
			}
		}
		if (!isset($this->name)) {
			$code = Country::getCountryCodeByName($this->data['country_code']);
			if (!isset($code)) {
				$this->classes[] = 'soft-cleaned';
				$cleaned_name = Country::softClean($this->data['country_code']);
				$code = Country::getCountryCodeByName($cleaned_name,'soft-cleaned');
			}
			if (!isset($code)) {
				$this->classes[] = 'replaced';
				$code = Country::getCountryCodeByReplacedName($cleaned_name);
			}
			if (!isset($code)) {
				$this->classes[] = 'hard-cleaned';
				$cleaned_name = Country::hardClean($cleaned_name);
				$code = Country::getCountryCodeByName($cleaned_name,'hard-cleaned');
			}
			if (!isset($code)) {
				$this->classes[] = 'replaced';
				$code = Country::getCountryCodeByReplacedName($cleaned_name);
			}
			if (isset($code)) {
				$this->code = $code;
				$this->name = Country::getCountryNameByCode($code);
				$this->classes[] = 'known';
			} else {
				$this->classes[] = 'unknown';
			}
		}
		
		if ($recursive && $this->hasClass('unknown')) {
			if (!isset($cleaned_name)) $cleaned_name = Country::hardClean(Country::softClean($this->data['original']));
			switch ($cleaned_name) {
				case 'corse':
					$this->data = array(
						'exclusion' => false,
						'country_code' => 'FR',
						'region_codes' => '2A,2B',
						'original' => $this->data['original'],
					);
					$this->classes = array('replaced');
					$this->parse(false);
					break;
				case 'uk':
					$this->data = array(
						'exclusion' => false,
						'country_code' => 'GB',
						'region_codes' => '',
						'original' => $this->data['original'],
					);
					$this->classes = array('replaced');
					$this->parse(false);
					break;
				case 'union-europeenne':
				case 'ue':
					$this->createAddressFilterGroup(
						array('DE','AT','BE','BG','CY','DK','ES','EE','FI','FR','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','CZ','RO','GB','SK','SI','SE'),
						$code = 'UE',
						$name = 'Union européenne'
					);
					break;
				case 'dom':
					$this->createAddressFilterGroup(
						array('GP','MQ','GF','RE'),
						$code = 'DOM',
						$name = "Département d'Outre-Mer"
					);
					break;
				case 'com':
					$this->createAddressFilterGroup(
						array('PF','PM','WF','YT','MF','BL'),
						$code = 'COM',
						$name = "Collectivités d'Outre-Mer"
					);
					break;
				case 'outre-mer':
					$this->createAddressFilterGroup(
						array('DOM','COM','NC','TF'),
						$code = "Outre-Mer",
						$name = "Outre-Mer"
					);
					break;
			}
		}
		
		if ($this->hasClass('known')) {
			if ($this->hasClass('replaced') || $this->hasClass('hard-cleaned')) $this->label = '<span class="bad">'.$this->data['original'].'</span> '.$this->name;
			else $this->label = $this->name;
			if (isset($this->data['region_codes']) && $this->data['region_codes']!='') {
				$this->label .= ' ('.$this->data['region_codes'].')';
			}
		} else {
			$this->label = $this->data['original'];
		}
	}
	
	public function createAddressFilterGroup($countries, $code, $name) {
		$this->address_filters_list = array();
		foreach ($countries as $country_code) {
			$this->address_filters_list[] = new AddressFilter(array('country_code' => $country_code, 'original' => $country_code));
		}
		$this->classes = array('known');
		$this->code = $code;
		$this->name = $name;
	}

	public function hasClass($class) {
		return in_array($class,$this->classes);
	}
	
	public function __toString() {
		$output = '';
		if (isset($this->address_filters_list)) {
			$compact_value = $this->code;
			$full_value = $this->name;
			$output .= '<span class="preview-item address-filter preview-item-group"'
				.' full-value="'.$full_value.'" compact-value="'.$compact_value.'" original-value="'.$this->data['original'].'"><span class="preview-item-group-label">'.$this->label.'</span>';
			foreach ($this->address_filters_list as $address_filter) {
				$output .= $address_filter;
			}
			$output .= '</span>';
		} else {
			if (isset($this->code)) {
				$compact_value = $this->code.(isset($this->data['region_codes']) && $this->data['region_codes']!='' ? '('.$this->data['region_codes'].')' : '');
				$full_value = $this->name.(isset($this->data['region_codes']) && $this->data['region_codes']!='' ? ' ('.$this->data['region_codes'].')' : '');
			} else {
				$compact_value = $this->data['original'];
				$full_value = $this->data['original'];
			}
			$output .= '<span class="preview-item address-filter country-'.$this->code.' '.implode(' ',$this->classes)
				.'" country-code="'.$this->code.'" full-value="'.$full_value.'" compact-value="'.$compact_value.'" original-value="'.$this->data['original'].'">'
				.$this->label.'</span>';
		}
		return $output;
	}
}


?>