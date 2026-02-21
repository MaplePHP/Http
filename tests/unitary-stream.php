<?php

declare(strict_types=1);

use MaplePHP\Http\Stream;
use MaplePHP\Unitary\{Expect, TestCase};


group('MaplePHP\Http\Stream', function (TestCase $case) {

    // -------------------------------------------------
    // Simple value expectations
    // -------------------------------------------------

    $case->expect((new Stream())->getStream())
        ->isString()
        ->isEqualTo('php://temp')
        ->validate();

    $case->expect(is_resource((new Stream())->getResource()))
        ->isTrue()
        ->validate();


    // -------------------------------------------------
    // write / read behaviour
    // -------------------------------------------------
    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $bytes = $s->write('Hello');

        $expect->expect($bytes)
            ->isInt()
            ->isGreaterThan( 1);
    });


    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $s->write('HelloWorld');
        $s->rewind();

        $first = $s->read(5);
        $rest  = $s->getContents();

        $expect->expect($first)->isEqualTo('Hello');
        $expect->expect($rest)->isEqualTo('World');
    });


    // -------------------------------------------------
    // __toString rewind behaviour
    // -------------------------------------------------

    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $s->write('ABC');
        $s->read(1);

        $expect->expect((string)$s)
            ->isString()
            ->isEqualTo('ABC');
    });


    // -------------------------------------------------
    // seek / tell
    // -------------------------------------------------

    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $s->write('ABCDE');
        $s->rewind();
        $s->read(2);

        $expect->expect($s->tell())
            ->isInt()
            ->isEqualTo(2);
    });


    // -------------------------------------------------
    // getLines
    // -------------------------------------------------

    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $s->write("A\nB\nC\nD\n");

        $expect->expect($s->getLines(2, 3))
            ->isString()
            ->isEqualTo("B\nC\n");
    });


    // -------------------------------------------------
    // clean
    // -------------------------------------------------

    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $s->write('12345');
        $s->clean();

        $expect->expect($s->getSize())
            ->isInt()
            ->isEqualTo(0);
    });


    // -------------------------------------------------
    // close / detach
    // -------------------------------------------------

    $case->expect(function (Expect $expect) {

        $s = new Stream(Stream::TEMP, 'w+');
        $s->close();

        $expect->expect($s->getResource())
            ->isNull();
    });

});