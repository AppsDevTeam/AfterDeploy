<?php

namespace ADT\Deployment;

class AnsiToHtml {
	/**
	 * Whether colour codes are enabled or not
	 *
	 * Valid options:
	 * null - Auto-detected. Color codes will be enabled on all systems except Windows, unless it
	 * has a valid ANSICON environment variable
	 * (indicating that AnsiCon is installed and running)
	 * false - will strip all tags and NOT output any ANSI colour codes
	 * true - will always output color codes
	 */
	public static $enabled = null;
	public static $tags = array(
		'<black>' => "color: black",
		'<red>' => "color: red",
		'<green>' => "color: green",
		'<yellow>' => "color: yellow>'",
		'<blue>' => "color: blue",
		'<magenta>' => "color: magenta",
		'<cyan>' => "color: cyan>'",
		'<white>' => "color: white",
		'<gray>' => "color: gray",
		'<darkRed>' => "color: darkRed",
		'<darkGreen>' => "color: darkGreen",
		'<darkYellow>' => "color: darkYellow",
		'<darkBlue>' => "color: darkBlue",
		'<darkMagenta>' => "color: darkMagenta",
		'<darkCyan>' => "color: darkCyan",
		'<darkWhite>' => "color: darkWhite",
		'<darkGray>' => "color: darkGray",
		'<bgBlack>' => "background-color: black; color:white;",
		'<bgRed>' => "background-color: red; color:white;",
		'<bgGreen>' => "background-color: green; color:white;",
		'<bgYellow>' => "background-color: yellow",
		'<bgBlue>' => "background-color: blue; color:white;",
		'<bgMagenta>' => "background-color: magneta",
		'<bgCyan>' => "background-color: cyan",
		'<bgWhite>' => "background-color: white",
		'<bold>' => "font-weight: bold",
		'<italics>' => "font-style: italic",
		'<reset>' => "",
	);

	/**
	 * This is the primary function for converting tags to ANSI color codes
	 * (see the class description for the supported tags)
	 *
	 * For safety, this function always appends a <reset> at the end, otherwise the console may stick
	 * permanently in the colors you have used.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function tagsToColors($string)
	{
		if (static::$enabled === null) {
			static::$enabled = TRUE;
		}
		if (!static::$enabled) {
			// Strip tags (replace them with an empty string)
			return static::stripTags($string);
		}

		$tagsValues = array_map(function($tagKey, $tag){
			if ($tagKey === '<reset>') return '</span>';

			return '<span style="'. $tag .'">';
		}, array_keys(static::$tags), static::$tags);

		return str_replace(array_keys(static::$tags), $tagsValues, $string);
	}

	/**
	 * Removes all occurances of ANSI tags from a string
	 * (used when static::$enabled == false or can be called directly if outputting strings to a text file)
	 *
	 * Note: the reason we don't use PHP's strip_tags() here is so that we only remove the ANSI-related ones
	 *
	 * @param string $string Text possibly containing ANSI tags such as <red>, <bold> etc.
	 * @return string Stripped of all valid ANSI tags
	 */
	public static function stripTags($string)
	{
		return str_replace(array_keys(static::$tags), '', $string);
	}
}