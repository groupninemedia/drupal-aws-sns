<?php

namespace Drupal\aws_sns\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;

/**
 * Class SnsSettingsForm
 * 
 * @package Drupal\aws_sns\Form
 */

class SnsSettingsForm extends ConfigFormBase {

    /**
     * The configuration factory.
     * 
     * @var Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    public function __construct(ConfigFactoryInterface $config_factory) {
        $this->configFactory = $config_factory;

    }

    public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        return new static(
            $container->get('config.factory')
        );
        
    }

    public function getFormId() {
        return 'aws_sns.settings';
    }

    protected function getEditableConfigNames() {
        return ['aws_sns.settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = \Drupal::config('aws_sns.settings');
        $entity_types = \Drupal::entityTypeManager()->getDefinitions();
        $enabled_sns_entities = $config->get('enabled_sns_entities');

        $form['directions'] = [
            '#markup' => 'All topics should be defined in your site\'s settings file. Each topic will show up here, and you can go through each of the bundles and select the node operations (insert/update/delete) you want to enable.'
        ];

        $form['enabled_sns_entities'] = [
            '#type' => 'fieldset',
            '#title' => 'Enabled SNS Entities',
            '#tree' => TRUE,
        ];

        $topics = $config->get('topics');
        foreach ($topics as $topic => $arn) {
            $form['enabled_sns_entities'][$topic] = [ 
                '#type' => 'details',
                '#title' => $this->t("Topic Settings for $topic"),
                '#tree' => TRUE,
                '#open' => FALSE,
            ];

            foreach ($entity_types as $entity_type) {
                $entity_type_id = $entity_type->id();
                $form['enabled_sns_entities'][$topic][$entity_type_id] = [
                    '#type' => 'fieldset',
                    '#title' => "$entity_type_id Bundles",
                    '#tree' => TRUE,
                ];

                $bundles = $this->getEntityBundles($entity_type->id());
                foreach ($bundles as $bundle => $label) {
                    $ops = $enabled_sns_entities[$topic][$entity_type_id][$bundle]['ops'];
                    $open = !empty(array_filter(array_values($ops)));

                    $form['enabled_sns_entities'][$topic][$entity_type_id][$bundle] = [
                        '#type' => 'details',
                        '#title' => $label,
                        '#tree' => TRUE,
                        '#open' => $open,
                    ];

                    $form['enabled_sns_entities'][$topic][$entity_type_id][$bundle]['ops'] = [
                        '#type' => 'checkboxes',
                        '#title' => 'Entity Operations',
                        '#options' => $this->getEntityOps(),
                        '#default_value' => $ops,
                    ];
                }
            }
        }
        
        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $config = $this->config('aws_sns.settings');
        $config->set('enabled_sns_entities', $form_state->getValue('enabled_sns_entities'));
        $config->save();
        parent::submitForm($form, $form_state);
    }

    /**
     * Retrieves a list of bundle names for the entity type. 
     * 
     * @param string $entity_type_name
     *   The name of the entity (e.g. "node", "taxonomy_term").
     * 
     * @return array
     *   An array of strings whose values represent the different bundles within the entity type.
     */
    private function getEntityBundles($entity_type_name) {
        $bundle_info = new EntityTypeBundleInfo(\Drupal::entityTypeManager(),
         \Drupal::languageManager(),
         \Drupal::moduleHandler(),
         \Drupal::typedDataManager(), 
         \Drupal::cache());

         $info = $bundle_info->getBundleInfo($entity_type_name);
         $bundles = [];
         foreach ($info as $name => $i) {
            $bundles[$name] = $i['label'];

         }
         return $bundles;
    }

    /**
     * Lists the available entity operations compatible with the SNS module.
     * 
     * @return array
     *   An associative array with the available entity operations.
     */
    private function getEntityOps() {
        return [
            'insert' => 'Insert',
            'update' => 'Update',
            'delete' => 'Delete',
        ];
    }

}