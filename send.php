<?php

/**
 * require client
 */

require_once(__DIR__ . "/lib/Segment.php");

/**
 * Args
 */

$args = parse($argv);

/**
 * Make sure both are set
 */

if (!isset($args["secret"])) die("--secret must be given");
if (!isset($args["file"])) die("--file must be given");

$file = $args["file"];
if ($file[0] != '/') $file = __DIR__ . "/" . $file;

/**
 * Rename the file so we don't write the same calls
 * multiple times
 */

$dir = dirname($file);
$old = $file;

$newFileName = 'analytics-' . rand() . '.log';
$newFile = $dir . '/' . $newFileName;

if(file_exists($old)) {
    if (!rename($old, $newFile)) {
        print("error renaming from $old to $newFile\n");
        exit(1);
    }
}

foreach(scandir($dir) as $file) {
    if (substr($file, 0, strlen('analytics-')) !== 'analytics-') {
        continue;
    }
    if ($file === $newFileName) {
        continue;
    }

    /**
    * File contents.
    */

    $contents = file_get_contents($file);
    $lines = explode("\n", $contents);

    /**
    * Initialize the client.
    */

    Segment::init($args["secret"], array(
        "debug" => true,
        "error_handler" => function($code, $msg){
            print("$code: $msg\n");
            exit(1);
        }
    ));

    /**
    * Payloads
    */

    $total = 0;
    $successful = 0;
    foreach ($lines as $line) {
        if (!trim($line)) continue;
        $payload = json_decode($line, true);
        $dt = new DateTime($payload["timestamp"]);
        $ts = floatval($dt->getTimestamp() . "." . $dt->format("u"));
        $payload["timestamp"] = $ts;
        $type = $payload["type"];
        $ret = call_user_func_array(array("Segment", $type), array($payload));
        if ($ret) $successful++;
        $total++;
        if ($total % 100 === 0) Segment::flush();
    }

    Segment::flush();
    unlink($file);

    print("sent $successful from $total requests successfully");
}

/**
 * Sent
 */

exit(0);

/**
 * Parse arguments
 */

function parse($argv){
  $ret = array();

  for ($i = 0; $i < count($argv); ++$i) {
    $arg = $argv[$i];
    if ('--' != substr($arg, 0, 2)) continue;
    $ret[substr($arg, 2, strlen($arg))] = trim($argv[++$i]);
  }

  return $ret;
}
