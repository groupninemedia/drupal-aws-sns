<?php

namespace Drupal\aws_sns;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Aws\Sns\SnsClient;

/**
 * Class AwsSns.
 *
 * @package Drupal\aws_sns
 */
class SnsService implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $snsConfig;

  /**
   * The SNS Messenger.
   *
   * @var \Drupal\aws_sns\SnsMessenger
   */
  protected $snsMessenger = NULL;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Constructs a new AwsSns instance.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactory $logger) {
    $this->snsConfig = $config_factory->get('aws_sns.settings');
    $this->logger = $logger->get('aws_sns');


      try {
        $config = [
          'credentials' => [
            'key' => $this->snsConfig->get('aws_key'),
            'secret' => $this->snsConfig->get('aws_secret'),
          ],
          'region' => 'us-east-1',
          'version' => 'latest',
        ];
        \Drupal::logger('khalid log')->notice(print_r($config, TRUE));
        $this->snsMessenger = SnsClient::factory($config);
      }
      catch (\Exception $e) {
        $this->logger->notice("Cannot instantiate the AWS SNS client because of error '{$e->getMessage()}'.");
      }
    }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Creates a new SNS topic.
   *
   * @param string $topic_name
   *   Name for the new topic that will be created.
   */
  public function createTopic($topic_name) {
    $this->snsMessenger->createNewTopic($topic_name);
  }

  /**
   * Subscribes a subscriber to a SNS topic using a particular protocol.
   *
   * @param string $topic_name
   *   Name of the topic to subscribe to.
   * @param string $subscriber
   *   The endpoint you want to subscribe.
   * @param string $protocol
   *   Where we send messages from the subscriber.
   */
  public function subscribe($topic_name, $subscriber, $protocol = 'sqs') {
    $this->snsMessenger->subscribe($topic_name, $subscriber, $protocol);
  }

  /**
   * Creates and sends a message to a given SNS topic.
   *
   * @param string $topic_name
   *   Name of the topic.
   * @param array $message
   *   An array containing the message info (nid, op, type).
   *
   * @return bool
   *   When everything goes through, return true.
   */
  public function sendMessage($topic_name, array $message) {
    if ($this->snsMessenger) {
      $message['default'] = json_encode($message);
      $data = [
        'TopicArn' => $topic_name,
        'Message' => json_encode($message),
        'MessageStructure' => 'json',
      ];

      $message_id = $this->snsMessenger->publish($data);
      return $message_id;
    }
    else {
      \Drupal::logger('aws_sns.SnsService')->error('Cannot send message: ' . print_r($message, TRUE));
      return FALSE;
    }
  }

}
