<?php

namespace App\Support;

/**
 * Byte-level JPEG metadata handling. Lets us remove EXIF/XMP/IPTC (GPS location, camera serials, editing history)
 * without decoding or re-compressing the image, so the pixels stay exactly the same.
 */
class JpegMeta
{
    /**
     * Splits a JPEG into its header segments and the compressed image data.
     *
     * @return array{segments: list<array{marker: int, bytes: string}>, image: string}|null
     */
    public static function parse(string $raw): ?array
    {
        if (strlen($raw) < 4 || substr($raw, 0, 2) !== "\xFF\xD8") {
            return null;
        }
        $pos = 2;
        $len = strlen($raw);
        $segments = [];
        while ($pos + 4 <= $len) {
            if ($raw[$pos] !== "\xFF") {
                return null;
            }
            $marker = ord($raw[$pos + 1]);
            if ($marker === 0xFF) { // fill byte
                $pos++;

                continue;
            }
            if ($marker === 0xDA) { // start of scan: the rest is image data
                return ['segments' => $segments, 'image' => substr($raw, $pos)];
            }
            $size = unpack('n', substr($raw, $pos + 2, 2))[1];
            if ($size < 2 || $pos + 2 + $size > $len) {
                return null;
            }
            $segments[] = ['marker' => $marker, 'bytes' => substr($raw, $pos, 2 + $size)];
            $pos += 2 + $size;
        }

        return null;
    }

    /** EXIF orientation (1–8), 1 if absent. */
    public static function orientation(array $parsed): int
    {
        foreach ($parsed['segments'] as $s) {
            if ($s['marker'] !== 0xE1 || substr($s['bytes'], 4, 6) !== "Exif\0\0") {
                continue;
            }
            $tiff = substr($s['bytes'], 10);
            $le = substr($tiff, 0, 2) === 'II';
            $u16 = fn ($o) => unpack($le ? 'v' : 'n', substr($tiff, $o, 2))[1] ?? 0;
            $u32 = fn ($o) => unpack($le ? 'V' : 'N', substr($tiff, $o, 4))[1] ?? 0;
            $ifd = $u32(4);
            $count = $u16($ifd);
            for ($i = 0; $i < $count && $ifd + 2 + ($i + 1) * 12 <= strlen($tiff); $i++) {
                $e = $ifd + 2 + $i * 12;
                if ($u16($e) === 0x0112) {
                    $o = $u16($e + 8);

                    return $o >= 1 && $o <= 8 ? $o : 1;
                }
            }
        }

        return 1;
    }

    /** The ICC colour-profile segments (APP2 "ICC_PROFILE"), concatenated. */
    public static function iccSegments(array $parsed): string
    {
        return collect($parsed['segments'])
            ->filter(fn ($s) => $s['marker'] === 0xE2 && substr($s['bytes'], 4, 12) === "ICC_PROFILE\0")
            ->pluck('bytes')->implode('');
    }

    /**
     * Rebuilds the JPEG keeping only what's needed to display it correctly: JFIF/Adobe headers, the colour profile,
     * quantisation/Huffman tables and frame info — plus a minimal EXIF block carrying just the rotation flag.
     * Drops EXIF (GPS, camera, dates), XMP, IPTC/Photoshop and comments. Pixels are untouched.
     */
    public static function strip(array $parsed, int $orientation): string
    {
        $keep = '';
        foreach ($parsed['segments'] as $s) {
            $m = $s['marker'];
            $drop = $m === 0xE1                                  // EXIF / XMP
                || $m === 0xED                                   // IPTC / Photoshop
                || $m === 0xFE                                   // comments
                || ($m >= 0xE3 && $m <= 0xEF && $m !== 0xEE)     // other app data (keep APP14 "Adobe": colour transform)
                || ($m === 0xE2 && substr($s['bytes'], 4, 12) !== "ICC_PROFILE\0"); // APP2 that isn't the profile
            if (! $drop) {
                $keep .= $s['bytes'];
            }
        }
        $exif = $orientation !== 1 ? self::orientationExif($orientation) : '';

        return "\xFF\xD8".self::afterJfif($keep, $exif).$parsed['image'];
    }

    /** Inserts segments (e.g. a colour profile) into a freshly encoded JPEG, after its JFIF header. */
    public static function insertAfterSoi(string $jpeg, string $segments): string
    {
        if ($segments === '' || substr($jpeg, 0, 2) !== "\xFF\xD8") {
            return $jpeg;
        }
        $parsed = self::parse($jpeg);
        if (! $parsed) {
            return $jpeg;
        }
        $headers = implode('', array_column($parsed['segments'], 'bytes'));

        return "\xFF\xD8".self::afterJfif($headers, $segments).$parsed['image'];
    }

    /** Places $insert right after a leading APP0 (JFIF) segment, or at the start. */
    private static function afterJfif(string $headers, string $insert): string
    {
        if ($insert !== '' && substr($headers, 0, 2) === "\xFF\xE0") {
            $len = 2 + unpack('n', substr($headers, 2, 2))[1];

            return substr($headers, 0, $len).$insert.substr($headers, $len);
        }

        return $insert.$headers;
    }

    /** A minimal APP1 EXIF segment containing only the Orientation tag. */
    private static function orientationExif(int $orientation): string
    {
        $tiff = 'MM'.pack('n', 42).pack('N', 8)   // big-endian TIFF header, first IFD at 8
            .pack('n', 1)                            // one entry
            .pack('nnN', 0x0112, 3, 1).pack('nn', $orientation, 0) // Orientation, SHORT, count 1, value
            .pack('N', 0);                           // no next IFD
        $body = "Exif\0\0".$tiff;

        return "\xFF\xE1".pack('n', strlen($body) + 2).$body;
    }
}
