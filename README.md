# Group Nine AWS SNS Module

This is a module meant to allow communication with Amazon's Simple Notification Service (SNS) through Drupal 8. The idea here is simple, users define topics in their settings, configure which entity types should send messages for each topic, and then let the magic of Drupal do the rest.

## Installation Process
This process assumes that you are using [Composer](https://getcomposer.org/) to manage dependencies for your project. 

(1) Add this repository to your project's `composer.json` file: `composer config repositories.g9 vcs https://github.com/groupninemedia/drupal-aws-sns`. Without this line, composer will try to either add a Drupal or Packagist package of the same name. 

(2) Add the project as a dependency: `composer require groupninemedia/drupal-aws-sns`.

(3) Install the module: `composer install`. (In some cases, composer may ask you to run a `composer update`).

(4) Enable the module on your site. It'll be called "Amazon SNS Interface" under the AWS group.

After following those steps, you will find the module in the `web/modules/contrib` directory. With this, we're ready to configure settings and start sending SNS messages. 

## Setting up Topics 

Setting up topics is easy. Within the appropriate settings files, you'll need to do the following:

*[1] Provide AWS Credentials.*

In your settings file, you should define settings like so:
```
$config['aws_sns.settings']['aws_key'] = XXXXXX
$config['aws_sns.settings']['aws_secret'] = YYYYYY
```

*[2] Define Topics.*

Also in your settings file, you should define topics like so:
```
$config['aws_sns.settings']['topics'][TOPIC MACHINE NAME] = ARN OF TOPIC.
```

*[3] Configure which topics should receive messages.*
If you head to `YOURSITE.com/admin/config/services/aws_sns`, you will see a list of topics that you've defined in the previous step. For each topic, all entity types your system recognizes will be available for configuration. To configure a specific bundle, just find it's entity type, expand its menu, and select the operations you would like to send a message for. (As of this writing, the supported node operations are `insert`, `update`, and `delete`.)


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