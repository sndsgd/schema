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
