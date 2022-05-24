<?php

require __DIR__ . "/vendor/autoload.php";

$yaml = <<<YAML
---
name: accessToken
pluralName: accessTokens
storage:
  type: mysql
  pool: alpha
  primaryKey:
  - hashedToken
cacheRecords:
- type: model
  pool: alpha
  key: accessTokens:{hashedToken}
  ttl: 3600
- type: pointer
  pool: alpha
  key: userAccessTokens:{userId}
  ttl: !seconds 2 hours
attributes:
  hashedToken:
    type: string
    description: a hashed copy of the access token
    rules:
    - !rule/minLength 40
    - !rule/maxLength 60
  userId:
    type: integer
    description: the user's unique identifier
  clientId:
    type: integer
    description: the client's unique identifier
    rules:
    - !rule/min 0
    - !rule/max 18446744073709551615
    isNullable: true
  scopes:
    type: string
    description: a comma delimited string containing scope ids
    isNullable: true
  creationTime:
    type: integer
    rules:
    - !rule/min 0
    - !rule/max 4294967295
  expirationTime:
    type: integer
    rules:
    - !rule/min 0
    - !rule/max 4294967295
YAML;

$searchPaths = ["src"];
$excludePaths = ["tests", "vendor"];

$definedRules = \sndsgd\schema\DefinedRules::create();
$ruleLocator = new \sndsgd\schema\RuleLocator();
$ruleLocator->locate($definedRules, $searchPaths, $excludePaths);

print_r($definedRules->toSchema());
exit;

$definedTypes = \sndsgd\schema\DefinedTypes::create();
$parserContext = new \sndsgd\schema\YamlParserContext($definedTypes);
$parserCallbacks = array_merge(
    [\sndsgd\yaml\callbacks\SecondsCallback::class],
    $definedRules->getYamlCallbackClasses(),
    $definedTypes->getYamlCallbackClasses(),
);
$parser = new \sndsgd\yaml\Parser($parserContext, ...$parserCallbacks);

$data = $parser->parse($yaml);

try {
    $m = new sndsgd\model\schema\Model($data);
} catch (sndsgd\schema\exceptions\ValidationException $ex) {
    echo yaml_emit($ex->getValidationErrors()->toArray());
}


$renderer = new sndsgd\model\storage\renderers\SqlRenderer();
echo $renderer->render($m);
exit;


echo yaml_emit($m->jsonSerialize()) . "\n";

// var_dump($m->getStorage()->getType());
// var_dump(get_class($m->getStorage()));

// var_dump($m->getCacheRecords()[0]->getTtl());

// foreach ($m->getCacheRecords() as $record) {
//     print_r($record);
// }

// var_dump($m->getAttributes());

foreach ($m->getAttributes() as $name => $a) {
    var_dump($name);
    var_dump(get_class($a));
    if ($a->hasRules()) {
        var_dump($a->getRules());
    }
}

exit;

echo json_encode($m, 448);
exit;

var_dump($m->getName());
var_dump($m->getPluralName());
var_dump($m->getStorage()->getType());

// print_r($m);

exit;

print_r($data);
exit;


function renderClasses()
{
    $ignorePaths = [__DIR__ . "/.git", __DIR__ . "/vendor", __DIR__ . "/tests", __DIR__ . "/sndsgd-yaml"];
    $searchPaths = [__DIR__];

    // search the filesystem for defined rules
    echo "searching for rules... ";
    $ruleLocator = new sndsgd\schema\RuleLocator($ignorePaths);
    $definedRules = $ruleLocator->locateRules(...$searchPaths);
    echo "done\n";

    // search the filesystem for defined types
    echo "searching for types... ";
    $searchPaths = [__DIR__ . "/hopes-and-dreams"];
    $typeLocator = new sndsgd\schema\TypeLocator($ignorePaths, $definedRules);
    try {
        $definedTypes = $typeLocator->locateTypes(...$searchPaths);
    } catch (sndsgd\schema\exceptions\ValidationException $ex) {
        echo "failed!\n";
        echo "address the following errors and try again:\n";
        foreach ($ex->getValidationErrors()->toArray() as $error) {
            echo sprintf(
                "- %s → %s\n",
                $error["path"],
                $error["message"],
            );
        }
        exit;
    }
    echo "done\n";

    echo "rendering classes... ";
    $definedTypes->renderClasses(__DIR__ . "/build");
    echo "done\n";
}


if ($argv[1] === "build") {
    renderClasses();
}


$data = [
    "userId" => "foasd",
    "datetime" => "1 minute ago",
    "thing" => [],
    "coordinates" => ["latitude" => "hello", "longitude" => "999.23", "nope" => 420],
];

$data = [
    "userId" => "foasd",
    "datetime" => "1 minute ago",
    "coordinates" => ["latitude" => 10, "longitude" => 12],
];


for ($i = 0; $i < 10; $i++) {
    $data["coordinates"]["latitude"] += $i;
    $data["coordinates"]["latitude"] -= $i;
    $list[] = $data;
}

// $data = [
//     "userId" => "foasd",
//     // "datetime" => "1 minute ago",
//     "coordinates" => ["latitude" => 42],
// ];

try {
    $start = microtime(true);
    $pos = new sndsgd\types\PositionList($list);
    echo json_encode($pos, 448) . "\n";
} catch (Exception $ex) {
    echo yaml_emit($ex->getValidationErrors()->toArray());
}

try {
    $start = microtime(true);
    $pos = new sndsgd\types\PositionList($list);
    echo json_encode($pos, 448) . "\n";
} catch (Exception $ex) {
    echo yaml_emit($ex->getValidationErrors()->toArray());
}

$totalTime = microtime(true) - $start;
echo $totalTime;
exit;


$data = [
    ["longitude" => 20, "latitude" => 12],
];


$start = microtime(true);
try {
    $coordList = new generated\types\CoordinateListType($data);
    echo json_encode($coordList);
    exit;
    print_r($coordList->getValues());
    exit;


    $position = new generated\types\CoordinatesType(["latitude" => 10, "longitude" => 99,"arg" => []]);
    var_dump($position->getLatitude());
    var_dump($position->getLongitude());
} catch (sndsgd\schema\exceptions\ValidationException $ex) {
    print_r($ex->getTraceAsString());
    echo yaml_emit($ex->getValidationErrors()->toArray());
} catch (sndsgd\schema\exceptions\RuleValidationException $ex) {
    print_r($ex->getTraceAsString());
    echo yaml_emit($ex->getValidationErrors()->toArray());
}
$totalTime = microtime(true) - $start;
echo $totalTime;
exit;
exit;


// $type = $definedTypes->getType("coordinates");
// $renderer = new sndsgd\schema\renderers\ObjectTypeRenderer($type);
// $php = $renderer->render($type);
// $path = sndsgd\schema\renderers\RenderHelper::getTypePsr4Path(__DIR__ . "/build", $type);
// $dir = dirname($path);
// if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
//     die("failed to create dir\n");
// }

// file_put_contents($path, $php);
// exit;



// $dump = var_export($definedTypes, true);
// echo $dump;
// exit;

// $type = $definedTypes->getType("latitude");
// var_dump($type->validate(-73));
// exit;

$type = $definedTypes->getType("position");

$totalTime = microtime(true) - $start;
echo number_format($totalTime, 6) . "\n";
$userInput = [
    "userId" => "123",
    "coordinates" => [
        // "name" => 123,
        "latitude" => 12,
        "longitude" => 123,
        // "asd" => [],
    ]
];


try {
    $result = $type->validate($userInput);
} catch (sndsgd\schema\exceptions\ValidationException $ex) {
    print_r($ex->getMap());
}

echo json_encode($result, 448) . "\n";

exit;





exit;
$definedTypes = $locator->locateTypes(__DIR__ . "/tests");

print_r($definedTypes);


// print_r(get_declared_classes());
