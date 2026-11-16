<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** Generates abstract placeholder artwork for seeded demo portfolios (no external image hosts needed). */
class ArtGenerator
{
    private const PALETTES = [
        [[255, 122, 47], [24, 24, 27], [253, 230, 138]],
        [[47, 107, 255], [238, 241, 255], [12, 12, 16]],
        [[52, 199, 89], [12, 12, 16], [236, 251, 239]],
        [[139, 92, 246], [250, 245, 255], [244, 114, 182]],
        [[14, 116, 144], [207, 250, 254], [251, 191, 36]],
        [[190, 18, 60], [255, 241, 242], [24, 24, 27]],
        [[202, 138, 4], [28, 25, 23], [254, 249, 195]],
        [[15, 23, 42], [148, 163, 184], [56, 189, 248]],
    ];

    public static function make(string $path, int $seed, int $w = 1200, int $h = 900, ?string $label = null): string
    {
        mt_srand($seed);
        $p = self::PALETTES[$seed % count(self::PALETTES)];
        $img = imagecreatetruecolor($w, $h);
        imageantialias($img, true);
        $c = fn ($rgb, $a = 0) => imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $a);

        // vertical gradient background
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $mix = fn ($i) => (int) ($p[1][$i] * (1 - $t * .35) + $p[0][$i] * ($t * .35));
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, $mix(0), $mix(1), $mix(2)));
        }
        $style = $seed % 4;
        if ($style === 0) { // concentric circles
            $cx = mt_rand((int) ($w * .3), (int) ($w * .7)); $cy = mt_rand((int) ($h * .3), (int) ($h * .7));
            for ($r = 900; $r > 60; $r -= 90) {
                imagefilledellipse($img, $cx, $cy, $r, $r, $c(($r / 90) % 2 ? $p[0] : $p[2], 20));
            }
        } elseif ($style === 1) { // tilted card mockup
            $pts = [(int) ($w * .22), (int) ($h * .32), (int) ($w * .78), (int) ($h * .18), (int) ($w * .84), (int) ($h * .7), (int) ($w * .28), (int) ($h * .84)];
            imagefilledpolygon($img, array_map(fn ($v, $i) => $v + ($i % 2 ? 22 : 18), $pts, array_keys($pts)), $c([0, 0, 0], 95));
            imagefilledpolygon($img, $pts, $c($p[0]));
            imagefilledellipse($img, (int) ($w * .42), (int) ($h * .48), 120, 120, $c($p[2]));
            for ($i = 0; $i < 4; $i++) {
                imagefilledrectangle($img, (int) ($w * .52), (int) ($h * .44) + $i * 34, (int) ($w * .72) - $i * 30, (int) ($h * .44) + $i * 34 + 14, $c($p[1]));
            }
        } elseif ($style === 2) { // grid of shapes (pattern / type specimen)
            $cell = 150;
            for ($x = 0; $x < $w; $x += $cell) {
                for ($y = 0; $y < $h; $y += $cell) {
                    $k = mt_rand(0, 3);
                    $col = $c([$p[0], $p[2], $p[1], [12, 12, 16]][mt_rand(0, 3)], 10);
                    match ($k) {
                        0 => imagefilledellipse($img, $x + $cell / 2, $y + $cell / 2, $cell - 24, $cell - 24, $col),
                        1 => imagefilledrectangle($img, $x + 12, $y + 12, $x + $cell - 12, $y + $cell - 12, $col),
                        2 => imagefilledarc($img, $x + 12, $y + $cell - 12, ($cell - 24) * 2, ($cell - 24) * 2, 270, 360, $col, IMG_ARC_PIE),
                        default => imagefilledpolygon($img, [$x + 12, $y + $cell - 12, $x + $cell / 2, $y + 12, $x + $cell - 12, $y + $cell - 12], $col),
                    };
                }
            }
        } else { // device frame + blocks (UI design)
            imagefilledrectangle($img, (int) ($w * .14) + 16, (int) ($h * .14) + 20, (int) ($w * .86) + 16, (int) ($h * .96) + 20, $c([0, 0, 0], 100));
            imagefilledrectangle($img, (int) ($w * .14), (int) ($h * .14), (int) ($w * .86), (int) ($h * .96), $c([255, 255, 255]));
            imagefilledrectangle($img, (int) ($w * .14), (int) ($h * .14), (int) ($w * .86), (int) ($h * .2), $c($p[1]));
            imagefilledrectangle($img, (int) ($w * .18), (int) ($h * .26), (int) ($w * .82), (int) ($h * .52), $c($p[0]));
            for ($i = 0; $i < 3; $i++) {
                $x0 = (int) ($w * .18) + $i * (int) ($w * .22);
                imagefilledrectangle($img, $x0, (int) ($h * .58), $x0 + (int) ($w * .19), (int) ($h * .8), $c($i === 1 ? $p[2] : [244, 244, 245]));
            }
            imagefilledrectangle($img, (int) ($w * .18), (int) ($h * .85), (int) ($w * .4), (int) ($h * .9), $c([12, 12, 16]));
        }
        if ($label) {
            $font = 5;
            $tw = imagefontwidth($font) * strlen($label);
            imagefilledrectangle($img, 40, $h - 80, 40 + $tw + 32, $h - 40, $c([12, 12, 16], 20));
            imagestring($img, $font, 56, $h - 68, $label, $c([255, 255, 255]));
        }
        ob_start();
        imagejpeg($img, null, 82);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($img);

        return $path;
    }
}
