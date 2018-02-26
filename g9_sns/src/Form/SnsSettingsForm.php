<?php

namespace Drupal\g9_sns\Form;

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
 * @package Drupal\g9_sns\Form
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
        return 'g9_sns.settings';
    }

    protected function getEditableConfigNames() {
        return ['g9_sns.settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = \Drupal::config('g9_sns.settings');
        $entity_types = \Drupal::entityTypeManager()->getDefinitions();
        $enabled_sns_entities = $config->get('enabled_sns_entities');

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
                $bundles = $this->getEntityBundles($entity_type->id());
                foreach ($bundles as $bundle) {
                    $allowed = $enabled_sns_entities[$topic][$bundle]['allow'];
                    $ops = $enabled_sns_entities[$topic][$bundle]['ops'];

                    $form['enabled_sns_entities'][$topic][$bundle]['allow'] = [
                        '#type' => 'checkbox',
                        '#title' => $bundle,
                        '#default_value' => $allowed
                    ];

                    $form['enabled_sns_entities'][$topic][$bundle]['ops'] = [
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
        $config = $this->config('g9_sns.settings');
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

         return array_keys($bundle_info->getBundleInfo($entity_type_name));
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