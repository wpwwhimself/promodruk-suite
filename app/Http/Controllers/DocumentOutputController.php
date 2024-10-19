<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

class DocumentOutputController extends Controller
{
    private const MM_TO_TWIP = 56.6929133858;

    public function downloadOffer(int $offer_id)
    {
        $offer = Offer::find($offer_id);

        $document = $this->initDocument();
        $section = $document->addSection([
            "paperSize" => "A4",
            "marginLeft" => 15 * self::MM_TO_TWIP,
            "marginRight" => 15 * self::MM_TO_TWIP,
            "marginTop" => 15 * self::MM_TO_TWIP,
            "marginBottom" => 15 * self::MM_TO_TWIP,
        ]);

        foreach ($offer->positions as $position) {
            $line = $section->addTextRun($this->style(["h_separated"]));
            $line->addText("$position[name] ($position[original_color_name]) ", $this->style(["h2"]));
            $line->addText($position["id"], $this->style(["ghost", "bold"]));

            $line = $section->addTextRun();
            $line->addText("Opis: ", $this->style(["bold"]));
            $line->addText(Str::words($position["description"], 12 * 3, "..."));

            $section->addText("Dostępne kolory:", $this->style(["bold"]), $this->style(["p_tight"]));
            $line = $section->addTextRun();
            Http::acceptJson()->get(env("MAGAZYN_API_URL") . "products/$position[product_family_id]/1")
                ->collect()
                ->map(fn ($p) => $p["color"])
                ->each(function ($color) use ($line) {
                    $line->addShape("rect", [
                        "roundness" => 0.2,
                        "frame" => [
                            "width" => 15,
                            "height" => 15,
                        ],
                        "fill" => ["color" => $color["color"]],
                    ]);
                    $line->addText(" ");
                });

            if ($position["show_ofertownik_link"] ?? false) {
                $line = $section->addTextRun();
                $line->addText("Szczegóły/więcej zdjęć: ", $this->style(["bold"]));
                $line->addLink(env("OFERTOWNIK_URL") . "produkty/$position[id]", "kliknij tutaj", $this->style(["link"]));
            }

            $line = $section->addTextRun();
            collect($position["thumbnail_urls"])
                ->transform(fn ($url, $i) => $url ?? $position["image_urls"][$i])
                ->take(3)
                ->each(function ($url) use ($line) {
                    $img = file_get_contents($url);
                    $dimensions = getimagesizefromstring($img);
                    $line->addImage($img, $this->style([
                        ($dimensions[0] < $dimensions[1]) ? "img_by_height" : "img"
                    ]));
                });

            foreach ($position["calculations"] as $i => $calculation) {
                $section->addText(
                    count($position["calculations"]) > 1
                        ? "Kalkulacja ".($i + 1)
                        : "Kalkulacja",
                    $this->style(["h2"]),
                    $this->style(["h_separated"])
                );

                $section->addText("Znakowanie:", $this->style(["bold"]), $this->style(["h_separated"]));
                $table = $section->addTable($this->style(["table"]));
                foreach ($calculation["items"] as $item_i => ["code" => $code, "marking" => $marking]) {
                    if ($item_i % 3 == 0)
                        $table->addRow();

                    $cell = $table->addCell(null, $this->style(["table_cell"]));
                    $cell->addText("$marking[position]:", $this->style(["underline"]), $this->style(["p_tight"]));
                    $technique_line = $cell->addTextRun($this->style(["p_tight"]));
                    $technique_line->addText(self::simplifyTechniqueName($marking["technique"]));
                    if (Str::contains($code, "_")) { // modifier active, retrieving name
                        $technique_line->addText(" – " . Str::afterLast($code, "_"));
                    }
                    $cell->addText("Maks. obszar znak.: " . $marking["print_size"], $this->style(["ghost", "small"]));
                    if ($marking["images"]) {
                        $img = file_get_contents($marking["images"][0]);
                        $dimensions = getimagesizefromstring($img);
                        $cell->addImage($img, $this->style([
                            ($dimensions[0] < $dimensions[1]) ? "img_by_height" : "img",
                            "h_separated",
                        ]));
                    }
                }

                $section->addText("Cena (netto):", $this->style(["bold"]), $this->style(["p_tight", "h_separated"]));
                foreach ($calculation["summary"] as $qty => $sum) {
                    $list = $section->addListItemRun(0, null, $this->style(["p_tight"]));
                    $list->addText("$qty szt.: " . as_pln($sum));
                }
            }

            $section->addText(" ", null, $this->style(["h_separated"]));
            $section->addLine([
                "weight" => 1,
                "width" => 500,
                "height" => 0,
            ]);
        }

        $filename = Str::slug($offer->name) . ".docx";

        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        IOFactory::createWriter($document, "Word2007")
            ->save("php://output");
    }

    //////////////////

    private static function simplifyTechniqueName(string $technique): string
    {
        if (Str::contains($technique, ["tampodruk", "sitodruk"], true))
            $technique = Str::replace(["tampodruk", "sitodruk"], "Nadruk", $technique, false);

        return $technique;
    }

    //////////////////

    private function initDocument()
    {
        $document = new PhpWord();
        $document->setDefaultFontName("Calibri");
        $document->setDefaultFontSize(11);
        $document->getSettings()->setThemeFontLang(new Language("pl-PL"));
        return $document;
    }

    private function style(array $styles): array
    {
        $definitions = collect([
            "h2" => [
                "size" => 16,
                "bold" => true,
            ],
            "h3" => [
                "size" => 13,
                "bold" => true,
            ],
            "small" => [
                "size" => 10,
            ],
            "h_separated" => [
                "spaceBefore" => 3 * self::MM_TO_TWIP,
            ],
            "p_tight" => [
                "spaceAfter" => 0,
            ],
            "ghost" => [
                "color" => "808080",
            ],
            "bold" => [
                "bold" => true,
            ],
            "underline" => [
                "underline" => "single",
            ],
            "link" => [
                "color" => "0000ff",
                "underline" => "single",
            ],
            "img" => [
                "width" => 500 / 3,
                "wrappingStyle" => "inline",
            ],
            "img_by_height" => [
                "height" => 400 / 3,
                "wrappingStyle" => "inline",
            ],
            "hr" => [
                "borderBottomSize" => 6,
                "marginLeft" => 30 * self::MM_TO_TWIP,
                "marginRight" => 30 * self::MM_TO_TWIP,
                "marginTop" => 60 * self::MM_TO_TWIP,
                "marginBottom" => 60 * self::MM_TO_TWIP,
            ],
            "table" => [
            ],
            "table_cell" => [
                "width" => 58 * self::MM_TO_TWIP,
            ],
        ]);

        return $definitions->filter(fn ($s, $name) => in_array($name, $styles))
            ->flatMap(fn ($el) => $el)
            ->toArray();
    }
}
