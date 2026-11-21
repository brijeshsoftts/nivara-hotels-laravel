<?php

namespace App\Support;

final class Cuid
{
    private const BLOCK_SIZE = 4;

    private const MAX_BLOCK_VALUE = 1679615;

    private static int $counter = 0;

    private static ?string $fingerprint = null;

    public static function generate(): string
    {
        $timestamp = base_convert((string) (int) floor(microtime(true) * 1000), 10, 36);
        $counter = self::$counter++ % (self::MAX_BLOCK_VALUE + 1);
        $fingerprint = self::$fingerprint ??= self::randomBlock();

        return 'c'
            . str_pad($timestamp, 8, '0', STR_PAD_LEFT)
            . self::padBlock($counter)
            . $fingerprint
            . self::randomBlock()
            . self::randomBlock();
    }

    private static function randomBlock(): string
    {
        return self::padBlock(random_int(0, self::MAX_BLOCK_VALUE));
    }

    private static function padBlock(int $value): string
    {
        return str_pad(base_convert((string) $value, 10, 36), self::BLOCK_SIZE, '0', STR_PAD_LEFT);
    }
}
