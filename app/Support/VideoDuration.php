<?php

namespace App\Support;

/**
 * Reads the playing time of an uploaded product video without an external binary.
 *
 * MP4/MOV files expose it in the `mvhd` box inside `moov`; WebM files keep a
 * `Duration` element in `Info`, scaled by `TimecodeScale`. When the file cannot be
 * parsed the caller must decide what to do; this class returns null rather than guess.
 */
final class VideoDuration
{
    /** Seconds of playback, or null when the container is not readable. */
    public static function seconds(string $path): ?float
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }
        $head = fread($handle, 16);
        fclose($handle);

        if (! is_string($head) || strlen($head) < 12) {
            return null;
        }

        if (substr($head, 4, 4) === 'ftyp') {
            return self::mp4Seconds($path);
        }

        if (str_starts_with($head, "\x1A\x45\xDF\xA3")) {
            return self::webmSeconds($path);
        }

        return null;
    }

    private static function mp4Seconds(string $path): ?float
    {
        $size = filesize($path);
        $handle = fopen($path, 'rb');
        if ($handle === false || $size === false) {
            return null;
        }

        try {
            $offset = 0;
            while ($offset + 8 <= $size) {
                fseek($handle, $offset);
                $header = fread($handle, 8);
                if (! is_string($header) || strlen($header) < 8) {
                    return null;
                }

                $length = unpack('N', substr($header, 0, 4))[1];
                $type = substr($header, 4, 4);
                $headerSize = 8;

                if ($length === 1) {
                    $large = fread($handle, 8);
                    if (! is_string($large) || strlen($large) < 8) {
                        return null;
                    }
                    $length = unpack('J', $large)[1];
                    $headerSize = 16;
                } elseif ($length === 0) {
                    $length = $size - $offset;
                }

                if ($length < $headerSize) {
                    return null;
                }

                if ($type === 'moov') {
                    return self::mvhdSeconds($handle, $offset + $headerSize, $length - $headerSize);
                }

                $offset += $length;
            }

            return null;
        } finally {
            fclose($handle);
        }
    }

    private static function mvhdSeconds($handle, int $start, int $length): ?float
    {
        $offset = $start;
        $end = $start + $length;

        while ($offset + 8 <= $end) {
            fseek($handle, $offset);
            $header = fread($handle, 8);
            if (! is_string($header) || strlen($header) < 8) {
                return null;
            }

            $size = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);
            $headerSize = 8;

            if ($size === 1) {
                $large = fread($handle, 8);
                if (! is_string($large) || strlen($large) < 8) {
                    return null;
                }
                $size = unpack('J', $large)[1];
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = $end - $offset;
            }

            if ($size < $headerSize) {
                return null;
            }

            if ($type === 'mvhd') {
                $payload = fread($handle, min($size - $headerSize, 32));
                if (! is_string($payload) || strlen($payload) < 20) {
                    return null;
                }

                if (ord($payload[0]) === 1) {
                    if (strlen($payload) < 32) {
                        return null;
                    }
                    $timescale = unpack('N', substr($payload, 20, 4))[1];
                    $duration = unpack('J', substr($payload, 24, 8))[1];
                } else {
                    $timescale = unpack('N', substr($payload, 12, 4))[1];
                    $duration = unpack('N', substr($payload, 16, 4))[1];
                }

                return $timescale > 0 ? $duration / $timescale : null;
            }

            $offset += $size;
        }

        return null;
    }

    private static function webmSeconds(string $path): ?float
    {
        $data = @file_get_contents($path);
        if (! is_string($data) || $data === '') {
            return null;
        }

        // The Info id also appears inside SeekHead entries, so only a block that really
        // parses as Info (and exposes a Duration) is used.
        $searchFrom = 0;
        while (($infoAt = strpos($data, "\x15\x49\xA9\x66", $searchFrom)) !== false) {
            $seconds = self::webmInfoSeconds($data, $infoAt);
            if ($seconds !== null) {
                return $seconds;
            }
            $searchFrom = $infoAt + 4;
        }

        return null;
    }

    private static function webmInfoSeconds(string $data, int $infoAt): ?float
    {
        [$sizeLength, $size, $unknown] = self::ebmlSize($data, $infoAt + 4);
        if ($sizeLength === 0 || $unknown || $size <= 0) {
            return null;
        }

        $position = $infoAt + 4 + $sizeLength;
        $end = min(strlen($data), $position + $size);
        $scale = 1000000.0;
        $duration = null;

        while ($position < $end) {
            [$idLength, $id] = self::ebmlId($data, $position);
            if ($idLength === 0) {
                break;
            }
            $position += $idLength;

            [$lengthBytes, $length, $unknownSize] = self::ebmlSize($data, $position);
            if ($lengthBytes === 0) {
                break;
            }
            $position += $lengthBytes;
            if ($unknownSize || $position + $length > $end) {
                break;
            }

            $value = substr($data, $position, $length);
            if ($id === "\x44\x89") {
                $duration = match (strlen($value)) {
                    4 => unpack('G', $value)[1],
                    8 => unpack('E', $value)[1],
                    default => null,
                };
            } elseif ($id === "\x2A\xD7\xB1") {
                $scale = 0.0;
                foreach (str_split($value) as $byte) {
                    $scale = $scale * 256 + ord($byte);
                }
                $scale = $scale > 0 ? $scale : 1000000.0;
            }

            $position += $length;
        }

        return $duration === null ? null : ($duration * $scale) / 1_000_000_000;
    }

    /** @return array{0:int,1:int,2:bool} [bytes used, value, size unknown] */
    private static function ebmlSize(string $data, int $position): array
    {
        if (! isset($data[$position])) {
            return [0, 0, false];
        }

        $first = ord($data[$position]);
        $length = 1;
        $mask = 0x80;
        while ($length <= 8 && ($first & $mask) === 0) {
            $mask >>= 1;
            $length++;
        }
        if ($length > 8 || ! isset($data[$position + $length - 1])) {
            return [0, 0, false];
        }

        $value = $first & ($mask - 1);
        $unknown = $value === $mask - 1;
        for ($i = 1; $i < $length; $i++) {
            $byte = ord($data[$position + $i]);
            $value = $value * 256 + $byte;
            $unknown = $unknown && $byte === 0xFF;
        }

        return [$length, $value, $unknown];
    }

    /** @return array{0:int,1:string} [bytes used, raw element id] */
    private static function ebmlId(string $data, int $position): array
    {
        if (! isset($data[$position])) {
            return [0, ''];
        }

        $first = ord($data[$position]);
        $length = 1;
        $mask = 0x80;
        while ($length <= 4 && ($first & $mask) === 0) {
            $mask >>= 1;
            $length++;
        }
        if ($length > 4 || ! isset($data[$position + $length - 1])) {
            return [0, ''];
        }

        return [$length, substr($data, $position, $length)];
    }
}
