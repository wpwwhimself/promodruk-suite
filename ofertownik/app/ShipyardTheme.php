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
        "primary" => "#bfaa40",
        "secondary" => "#86782f",
        "tertiary" => "#85ca56",
    ];
    #endregion

    #region fonts
    /**
     * type in the fonts as an array
     */
    public const FONTS = [
        "base" => ["Amazon Ember", "sans-serif"],
        "heading" => ["Amazon Ember", "sans-serif"],
        "mono" => ["Space Mono", "monospace"],
    ];

    // if fonts come from Google Fonts, add the URL here
    public const FONT_IMPORT_URL = [
        'https://fonts.cdnfonts.com/css/amazon-ember',
        'https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap'
    ];
    #endregion
}
