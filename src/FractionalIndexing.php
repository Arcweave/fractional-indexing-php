<?php

namespace Arcweave\FractionalIndexing;

use Exception;

class FractionalIndexing
{
  const BASE_62_DIGITS = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

  /**
   * $a may be empty string, $b is null or non-empty string
   * $a < $b lexicographically if $b is non-null.
   * no trailing zeros allowed.
   * digits is a string such as '0123456789' for base 10. Digits must be in
   * ascending character code order!
   * @param string|null $a
   * @param string|null $b
   * @param string $digits
   * @return string
   */
  private static function midpoint(string|null $a, string|null $b, string $digits)
  {
    $zero = $digits[0];

    if (null !== $b && $a >= $b) {
      throw new Exception($a . " >= " . $b);
    }

    if (substr($a, -1) === $zero || ($b && substr($b, -1) === $zero)) {
      throw new Exception("trailing zero");
    }

    if ($b) {
      // remove longest common prefix. pad '$a' with 0s as we
      // go. Note that we don't need to pad '$b', because it can't
      // end before '$a' while traversing the common prefix.
      $n = 0;
      while ((empty($a) ? $zero : $a[$n]) === $b[$n]) {
        $n++;
      }
      if ($n > 0) {
        return substr($b, 0, $n) . self::midpoint(substr($a, $n), substr($b, $n), $digits);
      }
    }

    // first digits (or lack of digit) are different
    $digitA = 0;
    if ($a || $a === '0') {
      $digitA = strpos($digits, $a[0]);
    }
    $digitB = strlen($digits);
    if ($b !== null) {
      $digitB = strpos($digits, $b[0]);
    }

    if ($digitB - $digitA > 1) {
      $midDigit = (int) round(0.5 * ($digitA + $digitB));
      return $digits[$midDigit];
    } else {
      // first digits are consecutive
      if ($b && strlen($b) > 1) {
        return substr($b, 0, 1);
      } else {
        // '$b' is null or has length 1 (a single digit).
        // the first digit of '$a' is the previous digit to '$b',
        // or 9 if '$b' is null.
        // given, for example, midpoint('49', '5'), return
        // '4' + midpoint('9', null), which will become
        // '4' + '9' + midpoint('', null), which is '495'
        return $digits[$digitA] . self::midpoint(substr($a, 1), null, $digits);
      }
    }
  }

  /**
   * @param string $int
   * @return void
   */
  private static function validateInteger(string $int)
  {
    if (strlen($int) !== self::getIntegerLength($int[0])) {
      throw new Exception("invalid integer part of order key: " . $int);
    }
  }

  /**
   * @param string $head
   * @return int
   */
  private static function getIntegerLength(string $head)
  {
    if ($head >= "a" && $head <= "z") {
      return mb_ord($head[0]) - mb_ord("a") + 2;
    } else if ($head >= "A" && $head <= "Z") {
      return mb_ord("Z") - mb_ord($head[0]) + 2;
    } else {
      throw new Exception("invalid order key head: " . $head);
    }
  }

  /**
   * @param string $key
   * @return string
   */
  private static function getIntegerPart(string $key)
  {
    $integerPartLength = self::getIntegerLength($key[0]);
    if ($integerPartLength > strlen($key)) {
      throw new Exception("invalid order key: " + $key);
    }
    return substr($key, 0, $integerPartLength);
  }

  /**
   * @param string $key
   * @param string $digits
   * @return void
   */
  private static function validateOrderKey(string $key, string $digits)
  {
    if ($key === "A" . str_repeat($digits[0], 26)) {
      throw new Exception("invalid order key: " . $key);
    }

    $i = self::getIntegerPart($key);
    $f = substr($key, strlen($i));

    if (substr($f, -1) === $digits[0]) {
      throw new Exception("invalid order key: " . $key);
    }
  }

  /**
   * This may return null, as there is a largest integer
   *
   * @param string $x
   * @param string $digits
   * @return string|null
   */
  private static function incrementInteger(string $x, string $digits)
  {
    self::validateInteger($x);
    $head = $x[0];
    $digs = substr($x, 1);
    $carry = true;

    for ($i = strlen($digs) - 1; $carry && $i >= 0; $i--) {
      $d = strpos($digits, $digs[$i]) + 1;
      if ($d === strlen($digits)) {
        $digs[$i] = $digits[0];
      } else {
        $digs[$i] = $digits[$d];
        $carry = false;
      }
    }

    if ($carry) {
      if ($head === "Z") {
        return "a" . $digits[0];
      }
      if ($head === "z") {
        return null;
      }
      $h = mb_chr(mb_ord($head) + 1);
      if ($h > "a") {
        $digs .= $digits[0];
      } else {
        $digs = substr($digs, 0, -1);
      }
      return $h . $digs;
    } else {
      return $head . $digs;
    }
  }

  /**
   * * This may return null, as there is a smallest integer
   *
   * @param string $x
   * @param string $digits
   * @return string|null
   */
  private static function decrementInteger(string $x, string $digits)
  {
    self::validateInteger($x);

    $head = $x[0];
    $digs = substr($x, 1);
    $borrow = true;

    for ($i = strlen($digs) - 1; $borrow && $i >= 0; $i--) {
      $d = strpos($digits, $digs[$i]) - 1;
      if ($d === -1) {
        $digs[$i] = substr($digits, -1);
      } else {
        $digs[$i] = $digits[$d];
        $borrow = false;
      }
    }

    if ($borrow) {
      if ($head === "a") {
        return "Z" . substr($digits, -1);
      }
      if ($head === "A") {
        return null;
      }
      $h = mb_chr(mb_ord($head) - 1);
      if ($h < "Z") {
        $digs .= substr($digits, -1);
      } else {
        $digs = substr($digs, 0, -1);
      }
      return $h . $digs;
    } else {
      return $head . $digs;
    }
  }

  /**
   * $a is an order key or null (START).
   * $b is an order key or null (END).
   * $a < $b lexicographically if both are non-null.
   * $digits is a string such as '0123456789' for base 10. Digits must
   * be in ascending character code order!
   *
   * @param string|null $a
   * @param string|null $b
   * @param string $digits
   * @return string
   */
  public static function generateKeyBetween(string|null $a, string|null $b, string $digits = self::BASE_62_DIGITS)
  {
    if ($a !== null) {
      self::validateOrderKey($a, $digits);
    }
    if ($b !== null) {
      self::validateOrderKey($b, $digits);
    }

    if ($a !== null && $b !== null && $a >= $b) {
      throw new Exception($a . " >= " . $b);
    }
    if ($a === null) {
      if ($b === null) {
        return "a" . $digits[0];
      }

      $ib = self::getIntegerPart($b);
      $fb = substr($b, strlen($ib));

      if ($ib === "A" . str_repeat($digits[0], 26)) {
        return $ib . self::midpoint("", $fb, $digits);
      }

      if ($ib < $b) {
        return $ib;
      }

      $res = self::decrementInteger($ib, $digits);
      if ($res === null) {
        throw new Exception("cannot decrement any more");
      }
      return $res;
    }

    if ($b === null) {
      $ia = self::getIntegerPart($a);
      $fa = substr($a, strlen($ia));

      $i = self::incrementInteger($ia, $digits);
      if ($i === null) {
        return $ia . self::midpoint($fa, null, $digits);
      }
      return $i;
    }

    $ia = self::getIntegerPart($a);
    $fa = substr($a, strlen($ia));
    $ib = self::getIntegerPart($b);
    $fb = substr($b, strlen($ib));

    if ($ia === $ib) {
      return $ia . self::midpoint($fa, $fb, $digits);
    }
    $i = self::incrementInteger($ia, $digits);

    if ($i === null) {
      throw new Exception("cannot increment any more");
    }

    if ($i < $b) {
      return $i;
    }

    return $ia . self::midpoint($fa, null, $digits);
  }

  /**
   * same preconditions as generateKeysBetween.
   * n >= 0.
   * Returns an array of n distinct keys in sorted order.
   * If a and b are both null, returns [a0, a1, ...]
   * If one or the other is null, returns consecutive "integer"
   * keys. Otherwise, returns relatively short keys between a and b.
   *
   * @param string|null $a
   * @param string|null $b
   * @param integer $n
   * @param string $digits
   * @return string[]
   */
  public static function generateNKeysBetween(string|null $a, string|null $b, int $n, string $digits = self::BASE_62_DIGITS)
  {
    if ($n === 0) {
      return [];
    }
    if ($n === 1) {
      return [self::generateKeyBetween($a, $b, $digits)];
    }
    if ($b === null) {
      $c = self::generateKeyBetween($a, $b, $digits);
      $result = [$c];
      for ($i = 0; $i < $n - 1; $i++) {
        $c = self::generateKeyBetween($c, $b, $digits);
        $result[] = $c;
      }
      return $result;
    }

    if ($a === null) {
      $c = self::generateKeyBetween($a, $b, $digits);
      $result = [$c];
      for ($i = 0; $i < $n - 1; $i++) {
        $c = self::generateKeyBetween($a, $c, $digits);
        $result[] = $c;
      }
      $result = array_reverse($result);
      return $result;
    }

    $mid = (int) floor($n / 2);
    $c = self::generateKeyBetween($a, $b, $digits);
    return array_merge(self::generateNKeysBetween($a, $c, $mid, $digits), [$c], self::generateNKeysBetween($c, $b, $n - $mid - 1, $digits));
  }
}
