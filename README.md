# Group Nine AWS SNS Module

This is a module meant to allow communication with Amazon's Simple Notification Service (SNS) through Drupal 8. The idea here is simple, users define topics in their settings, configure which entity types should send messages for each topic, and then let the magic of Drupal do the rest.

## Installation Process
TBD

## Setting up Topics 

Setting up topics is easy. Within the appropriate settings files, you'll need to do the following:

[1] Provide AWS Credentials.

In your settings file, you should define settings like so:
```
$config['aws_sns.settings']['aws_key'] = XXXXXX
$config['aws_sns.settings']['aws_secret'] = YYYYYY
```

[2] Define Topics.

Also in your settings file, you should define topics like so:
```
$config['aws_sns.settings']['topics'][TOPIC MACHINE NAME] = ARN OF TOPIC.
```

## Format of the Messages

Messages sent from this module have been standardized to look like the following:
```
{
    'entity_id' => (The ID of the entity object.),
    'entity_type' => (The entity type (e.g. node, taxonomy term),
    'bundle' => (The specific bundle within the entity type for the object.),
    'op' => (The entity operation being performed on the object (e.g. insert, update, delete)),
    'changed' => (The time the message is sent.)
}
```