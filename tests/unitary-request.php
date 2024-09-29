<?php

$unit = new MaplePHP\Unitary\Unit();

// If you build your library right it will become very easy to mock, like I have below.
$request = new MaplePHP\Http\Request(
    "POST", // The HTTP Method (GET, POST, PUT, DELETE, PATCH)
    "https://admin:mypass@example.com:65535/test.php?id=5221&place=stockholm", // The Request URI
    ["Content-Type" => "application/x-www-form-urlencoded"], // Add Headers, empty array is allowed
    ["email" => "john.doe@example.com"] // Post data
);

// Begin by adding a test
$unit->case("MaplePHP Request URI path test", function() use($request) {

    // Test 1
    $this->add($request->getMethod(), function() {
        return $this->equal("POST");

    }, "HTTP Request method Type is not POST");
    // Adding a error message is not required, but it is highly recommended

    // Test 2
    $this->add($request->getUri()->getPort(), [
        "isInt" => [], // Has no arguments = empty array
        "min" => [1], // Strict way is to pass each argument to array
        "max" => 65535, // But if its only one argument then this it is acceptable
        "length" => [1, 5]

    ], "Is not a valid port number");

    // Test 3
    $this->add($request->getUri()->getUserInfo(), [
        "isString" => [],
        "User validation" => function($value) {
            $arr = explode(":", $value);
            return ($this->withValue($arr[0])->equal("admin") && $this->withValue($arr[1])->equal("mypass"));
        }

    ], "Is not a valid port number");
});

$unit->execute();