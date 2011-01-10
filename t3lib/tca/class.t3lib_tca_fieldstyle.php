<?php

class t3lib_TCA_FieldStyle {
	/**
	 * Cache for the factory.
	 *
	 * @var array
	 */
	protected static $instances = array();

	protected static $defaultColorScheme = array();

	protected static $defaultClassScheme = array();

	protected static $defaultFieldStyle = array();

	protected static $defaultBorderStyle = array();

	protected $colorScheme;

	protected $classScheme;

	protected $fieldStyle;

	protected $borderStyle;

	protected function __construct(array $color_style_parts) {
		// TODO check if the (!isset...) clauses may be replaced/removed because of the default styles
		if (strcmp($color_style_parts[0], '')) {
			$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
			if (!isset($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])])) {
				$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
			}
		}
		// TODO: add getter and setter for _wrapBorder
		if (strcmp($color_style_parts[1], '')) {
			$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
			if (!isset($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])])) {
				$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
			}
		}
		if (strcmp($color_style_parts[2], '')) {
			if (count($color_style_parts) > 0) $this->_wrapBorder = true;
			$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
			if (!isset($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])])) {
				$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
			}
		}
	}

	/**
	 * Creates a style object from a style string as given in a TCEforms type configuration
	 *
	 * @param  $styleDefinition A string like "1-2-3", containing the style definition as mentioned in the Core API
	 * @return t3lib_TCA_FieldStyle
	 * @static
	 *
	 * @see TYPO3 Core API, ch 4.4 "Visual style of TCEforms"
	 */
	public static function createFromDefinition($styleDefinition, t3lib_TCA_FieldStyle $currentFieldStyle = NULL) {
		if (isset($styleDefinition)) {
			$color_style_parts = t3lib_div::trimExplode('-', $styleDefinition);
			$key = $styleDefinition;
		} else {
			$color_style_parts = array();
			$key = 'default';
		}

		if (!self::$instances[$key]) {
			if ($currentFieldStyle instanceof t3lib_TCA_FieldStyle) {
				if ($styleDefinition !== '') {
					$fieldStyle = new t3lib_TCA_FieldStyle($color_style_parts);
					$fieldStyle->colorScheme = $currentFieldStyle->colorScheme;
					$fieldStyle->classScheme = $currentFieldStyle->classScheme;
					$fieldStyle->borderStyle = $currentFieldStyle->borderStyle;
					$fieldStyle->fieldStyle = $currentFieldStyle->fieldStyle;
				} else {
					$fieldStyle = $currentFieldStyle;
				}
			} else {
				$fieldStyle = new t3lib_TCA_FieldStyle($color_style_parts);
			}
			self::$instances[$key] = $fieldStyle;
		}

		return self::$instances[$key];
	}

	public static function initDefaultStyles() {
		self::$defaultColorScheme =  $GLOBALS['TBE_STYLES']['colorschemes'][0];
		self::$defaultFieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
		self::$defaultBorderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
	}

	/**
	 * Setting the current color scheme ($this->colorScheme) based on $this->defaultColorScheme plus input string.
	 *
	 * @param  string  A color scheme string.
	 * @return void
	 */
	protected function setColorScheme($scheme) {
		$this->colorScheme = self::$defaultColorScheme;
		$this->classScheme = self::$defaultClassScheme;

		$parts = t3lib_div::trimExplode(',', $scheme);
		foreach ($parts as $key => $col) {
			// Split for color|class:
			list($color, $class) = t3lib_div::trimExplode('|', $col);

			// Handle color values:
			if ($color) {
				$this->colorScheme[$key] = $color;
			}
			if ($color == '-') {
				$this->colorScheme[$key] = '';
			}

			// Handle class values:
			if ($class) {
				$this->classScheme[$key] = $class;
			}
			if ($class == '-') {
				$this->classScheme[$key] = '';
			}
		}
	}

	public function getColorScheme() {
		return $this->colorScheme;
	}

	public function getClassScheme() {
		return $this->classScheme;
	}

	public function getFieldStyle() {
		return $this->fieldStyle;
	}

	public function getBorderStyle() {
		return $this->borderStyle;
	}
}

t3lib_TCA_FieldStyle::initDefaultStyles();