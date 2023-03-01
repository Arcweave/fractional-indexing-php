<?php

namespace Arcweave\FractionalIndexing;

class FractionalIndexing
{
    public const BASE_62_DIGITS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * $a is an order key or null (START).
     * $b is an order key or null (END).
     * $a < $b lexicographically if both are non-null.
     * $digits is a string such as '0123456789' for base 10. Digits must
     * be in ascending character code order!
     *
     * @param null|string $a
     * @param null|string $b
     *
     * @return string
     */
    public static function generateKeyBetween($a, $b, string $digits = self::BASE_62_DIGITS)
    {
        if (null !== $a) {
            self::validateOrderKey($a, $digits);
        }
        if (null !== $b) {
            self::validateOrderKey($b, $digits);
        }

        if (null !== $a && null !== $b && $a >= $b) {
            throw new \Exception($a.' >= '.$b);
        }
        if (null === $a) {
            if (null === $b) {
                return 'a'.$digits[0];
            }

            $ib = self::getIntegerPart($b);
            $fb = substr($b, strlen($ib));

            if ($ib === 'A'.str_repeat($digits[0], 26)) {
                return $ib.self::midpoint('', $fb, $digits);
            }

            if ($ib < $b) {
                return $ib;
            }

            $res = self::decrementInteger($ib, $digits);
            if (null === $res) {
                throw new \Exception('cannot decrement any more');
            }

            return $res;
        }

        if (null === $b) {
            $ia = self::getIntegerPart($a);
            $fa = substr($a, strlen($ia));

            $i = self::incrementInteger($ia, $digits);
            if (null === $i) {
                return $ia.self::midpoint($fa, null, $digits);
            }

            return $i;
        }

        $ia = self::getIntegerPart($a);
        $fa = substr($a, strlen($ia));
        $ib = self::getIntegerPart($b);
        $fb = substr($b, strlen($ib));

        if ($ia === $ib) {
            return $ia.self::midpoint($fa, $fb, $digits);
        }
        $i = self::incrementInteger($ia, $digits);

        if (null === $i) {
            throw new \Exception('cannot increment any more');
        }

        if ($i < $b) {
            return $i;
        }

        return $ia.self::midpoint($fa, null, $digits);
    }

    /**
     * same preconditions as generateKeysBetween.
     * n >= 0.
     * Returns an array of n distinct keys in sorted order.
     * If a and b are both null, returns [a0, a1, ...]
     * If one or the other is null, returns consecutive "integer"
     * keys. Otherwise, returns relatively short keys between a and b.
     *
     * @param null|string $a
     * @param null|string $b
     *
     * @return string[]
     */
    public static function generateNKeysBetween($a, $b, int $n, string $digits = self::BASE_62_DIGITS)
    {
        if (0 === $n) {
            return [];
        }
        if (1 === $n) {
            return [self::generateKeyBetween($a, $b, $digits)];
        }
        if (null === $b) {
            $c = self::generateKeyBetween($a, $b, $digits);
            $result = [$c];
            for ($i = 0; $i < $n - 1; ++$i) {
                $c = self::generateKeyBetween($c, $b, $digits);
                $result[] = $c;
            }

            return $result;
        }

        if (null === $a) {
            $c = self::generateKeyBetween($a, $b, $digits);
            $result = [$c];
            for ($i = 0; $i < $n - 1; ++$i) {
                $c = self::generateKeyBetween($a, $c, $digits);
                $result[] = $c;
            }

            return array_reverse($result);
        }

        $mid = (int) floor($n / 2);
        $c = self::generateKeyBetween($a, $b, $digits);

        return array_merge(self::generateNKeysBetween($a, $c, $mid, $digits), [$c], self::generateNKeysBetween($c, $b, $n - $mid - 1, $digits));
    }

    /**
     * $a may be empty string, $b is null or non-empty string
     * $a < $b lexicographically if $b is non-null.
     * no trailing zeros allowed.
     * digits is a string such as '0123456789' for base 10. Digits must be in
     * ascending character code order!
     *
     * @param null|string $a
     * @param null|string $b
     *
     * @return string
     */
    private static function midpoint($a, $b, string $digits)
    {
        $zero = $digits[0];

        if (null !== $b && $a >= $b) {
            throw new \Exception($a.' >= '.$b);
        }

        if (substr($a, -1) === $zero || ($b && substr($b, -1) === $zero)) {
            throw new \Exception('trailing zero');
        }

        if ($b) {
            // remove longest common prefix. pad '$a' with 0s as we
            // go. Note that we don't need to pad '$b', because it can't
            // end before '$a' while traversing the common prefix.
            $n = 0;
            while ((empty($a) ? $zero : $a[$n]) === $b[$n]) {
                ++$n;
            }
            if ($n > 0) {
                return substr($b, 0, $n).self::midpoint(substr($a, $n), substr($b, $n), $digits);
            }
        }

        // first digits (or lack of digit) are different
        $digitA = 0;
        if ($a || '0' === $a) {
            $digitA = strpos($digits, $a[0]);
        }
        $digitB = strlen($digits);
        if (null !== $b) {
            $digitB = strpos($digits, $b[0]);
        }

        if ($digitB - $digitA > 1) {
            $midDigit = (int) round(0.5 * ($digitA + $digitB));

            return $digits[$midDigit];
        }
        // first digits are consecutive
        if ($b && strlen($b) > 1) {
            return substr($b, 0, 1);
        }
        // '$b' is null or has length 1 (a single digit).
        // the first digit of '$a' is the previous digit to '$b',
        // or 9 if '$b' is null.
        // given, for example, midpoint('49', '5'), return
        // '4' + midpoint('9', null), which will become
        // '4' + '9' + midpoint('', null), which is '495'
        return $digits[$digitA].self::midpoint(substr($a, 1), null, $digits);
    }

    private static function validateInteger(string $int)
    {
        if (strlen($int) !== self::getIntegerLength($int[0])) {
            throw new \Exception('invalid integer part of order key: '.$int);
        }
    }

    /**
     * @return int
     */
    private static function getIntegerLength(string $head)
    {
        if ($head >= 'a' && $head <= 'z') {
            return mb_ord($head[0]) - mb_ord('a') + 2;
        }
        if ($head >= 'A' && $head <= 'Z') {
            return mb_ord('Z') - mb_ord($head[0]) + 2;
        }

        throw new \Exception('invalid order key head: '.$head);
    }

    /**
     * @return string
     */
    private static function getIntegerPart(string $key)
    {
        $integerPartLength = self::getIntegerLength($key[0]);
        if ($integerPartLength > strlen($key)) {
            throw new \Exception('invalid order key: ' + $key);
        }

        return substr($key, 0, $integerPartLength);
    }

    private static function validateOrderKey(string $key, string $digits)
    {
        if ($key === 'A'.str_repeat($digits[0], 26)) {
            throw new \Exception('invalid order key: '.$key);
        }

        $i = self::getIntegerPart($key);
        $f = substr($key, strlen($i));

        if (substr($f, -1) === $digits[0]) {
            throw new \Exception('invalid order key: '.$key);
        }
    }

    /**
     * This may return null, as there is a largest integer.
     *
     * @return null|string
     */
    private static function incrementInteger(string $x, string $digits)
    {
        self::validateInteger($x);
        $head = $x[0];
        $digs = substr($x, 1);
        $carry = true;

        for ($i = strlen($digs) - 1; $carry && $i >= 0; --$i) {
            $d = strpos($digits, $digs[$i]) + 1;
            if ($d === strlen($digits)) {
                $digs[$i] = $digits[0];
            } else {
                $digs[$i] = $digits[$d];
                $carry = false;
            }
        }

        if ($carry) {
            if ('Z' === $head) {
                return 'a'.$digits[0];
            }
            if ('z' === $head) {
                return null;
            }
            $h = mb_chr(mb_ord($head) + 1);
            if ($h > 'a') {
                $digs .= $digits[0];
            } else {
                $digs = substr($digs, 0, -1);
            }

            return $h.$digs;
        }

        return $head.$digs;
    }

    /**
     * * This may return null, as there is a smallest integer.
     *
     * @return null|string
     */
    private static function decrementInteger(string $x, string $digits)
    {
        self::validateInteger($x);

        $head = $x[0];
        $digs = substr($x, 1);
        $borrow = true;

        for ($i = strlen($digs) - 1; $borrow && $i >= 0; --$i) {
            $d = strpos($digits, $digs[$i]) - 1;
            if (-1 === $d) {
                $digs[$i] = substr($digits, -1);
            } else {
                $digs[$i] = $digits[$d];
                $borrow = false;
            }
        }

        if ($borrow) {
            if ('a' === $head) {
                return 'Z'.substr($digits, -1);
            }
            if ('A' === $head) {
                return null;
            }
            $h = mb_chr(mb_ord($head) - 1);
            if ($h < 'Z') {
                $digs .= substr($digits, -1);
            } else {
                $digs = substr($digs, 0, -1);
            }

            return $h.$digs;
        }

        return $head.$digs;
    }
}
