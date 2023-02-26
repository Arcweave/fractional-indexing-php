<?php

namespace Arcweave\FractionalIndexing;

use Exception;
use RuntimeException;
use PHPUnit\Framework\TestCase;

class FractionalIndexingTests extends TestCase
{

  protected function setUp(): void
  {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      throw new RuntimeException($errstr . " on line " . $errline . " in file " . $errfile);
    });
  }

  protected function tearDown(): void
  {
    restore_error_handler();
  }

  private function myTest($a, $b, $exp)
  {
    try {
      $act = FractionalIndexing::generateKeyBetween($a, $b);
    } catch (Exception $th) {
      $act = $th->getMessage();
    }

    $this->assertEquals($exp, $act, "{$exp} === {$act}");
  }

  public function testIndexes()
  {
    $this->myTest(null, null, "a0");
    $this->myTest(null, "a0", "Zz");
    $this->myTest(null, "Zz", "Zy");
    $this->myTest("a0", null, "a1");
    $this->myTest("a1", null, "a2");
    $this->myTest("a0", "a1", "a0V");
    $this->myTest("a1", "a2", "a1V");
    $this->myTest("a0V", "a1", "a0l");
    $this->myTest("Zz", "a0", "ZzV");
    $this->myTest("Zz", "a1", "a0");
    $this->myTest(null, "Y00", "Xzzz");
    $this->myTest("bzz", null, "c000");
    $this->myTest("a0", "a0V", "a0G");
    $this->myTest("a0", "a0G", "a08");
    $this->myTest("b125", "b129", "b127");
    $this->myTest("a0", "a1V", "a1");
    $this->myTest("Zz", "a01", "a0");
    $this->myTest(null, "a0V", "a0");
    $this->myTest(null, "b999", "b99");
    $this->myTest(
      null,
      "A00000000000000000000000000",
      "invalid order key: A00000000000000000000000000"
    );
    $this->myTest(null, "A000000000000000000000000001", "A000000000000000000000000000V");
    $this->myTest("zzzzzzzzzzzzzzzzzzzzzzzzzzy", null, "zzzzzzzzzzzzzzzzzzzzzzzzzzz");
    $this->myTest("zzzzzzzzzzzzzzzzzzzzzzzzzzz", null, "zzzzzzzzzzzzzzzzzzzzzzzzzzzV");
    $this->myTest("a00", null, "invalid order key: a00");
    $this->myTest("a00", "a1", "invalid order key: a00");
    $this->myTest("0", "1", "invalid order key head: 0");
    $this->myTest("a1", "a0", "a1 >= a0");
  }

  private function testN($a, $b, $n, $exp)
  {
    $BASE_10_DIGITS = "0123456789";

    try {
      $act = implode(" ", FractionalIndexing::generateNKeysBetween($a, $b, $n, $BASE_10_DIGITS));
    } catch (Exception $e) {
      $act = $e->getMessage();
    }

    $this->assertEquals($exp, $act, "{$exp} == {$act}");
  }

  public function testNIndexes()
  {
    $this->testN(null, null, 5, "a0 a1 a2 a3 a4");
    $this->testN("a4", null, 10, "a5 a6 a7 a8 a9 b00 b01 b02 b03 b04");
    $this->testN(null, "a0", 5, "Z5 Z6 Z7 Z8 Z9");
    $this->testN(
      "a0",
      "a2",
      20,
      "a01 a02 a03 a035 a04 a05 a06 a07 a08 a09 a1 a11 a12 a13 a14 a15 a16 a17 a18 a19"
    );
  }

  private function testBase95($a, $b, $exp)
  {
    $BASE_95_DIGITS =
      " !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~";

    try {
      $act = FractionalIndexing::generateKeyBetween($a, $b, $BASE_95_DIGITS);
    } catch (Exception $e) {
      $act = $e->getMessage();
    }

    $this->assertEquals($exp, $act, "{$exp} == {$act}");
  }

  public function testBase()
  {
    $this->testBase95("a00", "a01", "a00P");
    $this->testBase95("a0/", "a00", "a0/P");
    $this->testBase95(null, null, "a ");
    $this->testBase95("a ", null, "a!");
    $this->testBase95(null, "a ", "Z~");
    $this->testBase95("a0 ", "a0!", "invalid order key: a0 ");
    $this->testBase95(
      null,
      "A                          0",
      "A                          ("
    );
    $this->testBase95("a~", null, "b  ");
    $this->testBase95("Z~", null, "a ");
    $this->testBase95("b   ", null, "invalid order key: b   ");
    $this->testBase95("a0", "a0V", "a0;");
    $this->testBase95("a  1", "a  2", "a  1P");
    $this->testBase95(
      null,
      "A                          ",
      "invalid order key: A                          "
    );
  }
}
