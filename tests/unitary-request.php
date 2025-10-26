<?php

use MaplePHP\Http\Request;
use MaplePHP\Http\Uri;
use MaplePHP\Unitary\Expect;
use MaplePHP\Unitary\TestCase;

$unit = new MaplePHP\Unitary\Unit();

// If you build your library right it will become very easy to mock, like I have below.

// Begin by adding a test
$unit->case("MaplePHP Request URI path test", function(TestCase $case) {

    $request = new Request(
        "POST", // The HTTP Method (GET, POST, PUT, DELETE, PATCH)
        "https://admin:mypass@example.com:65535/test.php?id=5221&place=stockholm", // The Request URI
        ["Content-Type" => "application/x-www-form-urlencoded"], // Add Headers, empty array is allowed
        ["email" => "john.doe@example.com"] // Post data
    );

    $case
        ->error("HTTP Request method is not POST")
        ->validate($request->getMethod(), function(Expect $inst) {
            $inst->isEqualTo("POST");
            //assert($inst->isEqualTo("GET")->isValid(), "wdqwwdqw dwq wqdwq");
        });

    $case->validate($request->getUri()->getPort(), function(Expect $inst) {
        $inst->isInt();
        $inst->min(1);
        $inst->max(65535);
        $inst->length(1, 5);
    });

    $this->add($request->getUri()->getUserInfo(), [
        "isString" => [],
        "User validation" => function($value) {
            $arr = explode(":", $value);
            return ($this->withValue($arr[0])->equal("admin") && $this->withValue($arr[1])->equal("mypass"));
        }

    ], "Is not a valid port number");

    $this->add((string)$request->withUri(new Uri("https://example.se"))->getUri(), [
        "equal" => ["https://example.se"],
    ], "GetUri expects https://example.se as result");
});

return $unit;