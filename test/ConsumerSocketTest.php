<?php

require_once(dirname(__FILE__) . "/../lib/Analytics/Client.php");

class ConsumerSocketTest extends PHPUnit_Framework_TestCase {

  private $client;

  function setUp() {
    $this->client = new Analytics_Client("testsecret",
                                          array("consumer" => "socket"));
  }

  function testTrack() {
    $this->assertTrue($this->client->track(array(
      "user_id" => "some-user",
      "event" => "Socket PHP Event"
    )));
    $this->assertTrue($tracked);
  }

  function testIdentify() {
    $this->assertTrue($this->client->identify(array(
      "user_id" => "Calvin",
      "traits" => array(
        "loves_php" => false,
        "birthday" => time()
      )
    )));
  }

  function testAlias() {
    $this->assertTrue($this->client->alias(array(
      "previous_id" => "some-user",
      "user_id" => "new-user"
    )));
  }

  function testShortTimeout() {
    $client = new Analytics_Client("testsecret",
                                   array( "timeout"  => 0.01,
                                          "consumer" => "socket" ));

    $this->assertTrue($client->track(array(
      "user_id" => "some-user",
      "event" => "Socket PHP Event"
    )));

    $this->assertTrue($client->identify(array(
      "user_id" => "some-user",
      "traits" => array()
    )));

    $client->__destruct();
  }

  function testProductionProblems() {
    $client = new Analytics_Client("x", array(
        "consumer"      => "socket",
        "error_handler" => function () { throw new Exception("Was called"); }));

    # Shouldn't error out without debug on.
    $client->track(array("user_id" => "some-user", "event" => "Production Problems"));
    $client->__destruct();
  }

  function testDebugProblems() {

    $options = array(
      "debug"         => true,
      "consumer"      => "socket",
      "error_handler" => function ($errno, $errmsg) {
                            if ($errno != 400)
                              throw new Exception("Response is not 400"); }
    );

    $client = new Analytics_Client("x", $options);

    # Should error out with debug on.
    $client->track(array("user_id" => "some-user", "event" => "Socket PHP Event"));
    $client->__destruct();
  }


  function testLargeMessage () {
    $options = array(
      "debug"    => true,
      "consumer" => "socket"
    );

    $client = new Analytics_Client("testsecret", $options);

    $big_property = "";

    for ($i = 0; $i < 10000; $i++) {
      $big_property .= "a";
    }

    $this->assertTrue($client->track(array(
      "user_id" => "some-user",
      "event" => "Super Large PHP Event",
      "properties" => array("big_property" => $big_property)
    )));

    $client->__destruct();
  }
}
?>
