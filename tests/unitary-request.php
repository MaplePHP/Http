<?php

$unit = new MaplePHP\Unitary\Unit();


$unit = new MaplePHP\Unitary\Unit();

// If you build your library right it will become very easy to mock, like I have below.
$request = new MaplePHP\Http\Request(
    "POST", // The HTTP Method (GET, POST, PUT, DELETE, PATCH)
    "https://admin:mypass@example.com:443/test.php?id=5221&place=stockholm", // The Request URI
    ["Content-Type" => "application/x-www-form-urlencoded"], // Add Headers, empty array is allowed
    ["email" => "john.doe@example.com"] // Post data
);

// Begin by adding a test
$unit->case("Checking data type", function() use($request) {

    // Add a test, each test
    $this->add($request->getMethod(), function() {

        return $this->equal("POST");

    }, "HTTP Request method Type is not POST");

    $this->add("ww", [
        "isInt" => [],
        "max" => [65535],
    ], "Is not a valid port number");


});

echo "ww";

$unit->execute();