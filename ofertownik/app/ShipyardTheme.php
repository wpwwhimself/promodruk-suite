<?php

namespace App;

use App\Theme\Shipyard\Theme;

class ShipyardTheme
{
    use Theme;

    #region theme
    /**
     * Available themes:
     * - origin - separated cells, clean background, contents floating in the middle
     * - austerity - broad background, main sections spread out
     */
    public const THEME = "origin";
    #endregion

    #region colors
    /**
     * App accent colors:
     * - primary - for background, primary (disruptive) actions and important text
     * - secondary - for default buttons and links
     * - tertiary - for non-disruptive interactive elements
     *
     * If value is an array, 2 different colors may be used for light/dark mode
     */
    public const COLORS = [
        "primary" => "#0099ff",
        "secondary" => "#ff9900",
        "tertiary" => "#8844ff",
    ];
    #endregion

    #region fonts
    /**
     * type in the fonts as an array
     */
    public const FONTS = [
        "base" => ["Raleway", "sans-serif"],
        "heading" => ["Krona One", "sans-serif"],
        "mono" => ["Ubuntu Mono", "monospace"],
    ];

    // if fonts come from Google Fonts, add the URL here
    public const FONT_IMPORT_URL = 'https://fonts.googleapis.com/css2?family=Krona+One&family=Raleway:ital,wght@0,100..900;1,100..900&family=Ubuntu+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap';
    #endregion
}
