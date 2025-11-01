<?php

namespace App;

class ShipyardTheme
{
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
     */
    public const COLORS = [
        "primary" => "#0080ff",
        "secondary" => "#7e7e7e",
        "tertiary" => "#74c7ffff",
    ];

    public static function getColors(): array
    {
        return self::COLORS;
    }

    public static function getGhostColors(): array
    {
        return array_map(
            fn ($clr) => $clr . "77",
            self::COLORS
        );
    }
    #endregion

    #region fonts
    /**
     * type in the fonts as an array
     */
    public const FONTS = [
        "base" => ["Titillium Web", "sans-serif"],
        "heading" => ["Titillium Web", "sans-serif"],
        "mono" => ["Ubuntu Mono", "monospace"],
    ];

    // if fonts come from Google Fonts, add the URL here
    public const FONT_IMPORT_URL = 'https://fonts.googleapis.com/css2?family=Titillium+Web:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700&display=swap';
    #endregion
}
