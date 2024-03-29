<?php

namespace Drupal\DrupalExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\YamlFileLoader,
    Symfony\Component\Config\FileLocator,
    Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use Behat\Behat\Extension\ExtensionInterface;

class Extension implements ExtensionInterface {

  /**
   * Loads a specific configuration.
   *
   * @param array $config
   *   Extension configuration (from behat.yml).
   * @param ContainerBuilder $container
   *   ContainerBuilder instance.
   */
  public function load(array $config, ContainerBuilder $container) {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
    $loader->load('services.yml');
    $container->setParameter('drupal.drupal.default_driver', $config['default_driver']);

    // Store config in parameters array to be passed into the DrupalContext.
    $drupal_parameters = array();
    foreach ($config as $key => $value) {
      $drupal_parameters[$key] = $value;
    }
    $container->setParameter('drupal.parameters', $drupal_parameters);

    $container->setParameter('drupal.region_map', $config['region_map']);

    // Setup any drivers if requested.
    if (isset($config['blackbox'])) {
      $loader->load('drivers/blackbox.yml');
    }

    if (isset($config['drupal'])) {
      $loader->load('drivers/drupal.yml');
      $container->setParameter('drupal.driver.drupal.drupal_root', $config['drupal']['drupal_root']);
      $container->setParameter('drupal.driver.drupal.uri', $config['drupal']['uri']);
    }

    if (isset($config['drush'])) {
      $loader->load('drivers/drush.yml');
      if (!isset($config['drush']['alias'])) {
        throw new \RuntimeException('Drush alias is required for the Drush driver.');
      }
      $container->setParameter('drupal.driver.drush.alias', $config['drush']['alias']);
    }
  }

  /**
   * Setup configuration for this extension.
   *
   * @param ArrayNodeDefinition $builder
   *   ArrayNodeDefinition instance.
   */
  public function getConfig(ArrayNodeDefinition $builder) {
    $builder->
      children()->
        arrayNode('basic_auth')->
          children()->
            scalarNode('username')->end()->
            scalarNode('password')->end()->
          end()->
        end()->
        scalarNode('default_driver')->
          defaultValue('blackbox')->
        end()->
        scalarNode('api_driver')->
          defaultValue('drush')->
        end()->
        arrayNode('region_map')->
          useAttributeAsKey('key')->
          prototype('variable')->end()->
        end()->
        arrayNode('text')->
          addDefaultsIfNotSet()->
          children()->
            scalarNode('log_in')->
              defaultValue('Log in')->
            end()->
            scalarNode('log_out')->
              defaultValue('Log out')->
            end()->
            scalarNode('password_field')->
              defaultValue('Password')->
            end()->
            scalarNode('username_field')->
              defaultValue('Username')->
            end()->
          end()->
        end()->
        arrayNode('markup_id')->
          children()->
            scalarNode('search_input')->
            end()->
            scalarNode('search_submit')->
            end()->
          end()->
        end()->
        // Drupal drivers.
        arrayNode('blackbox')->
        end()->
        arrayNode('drupal')->
          children()->
            scalarNode('drupal_root')->end()->
            scalarNode('uri')->
              defaultValue('default')->
            end()->
          end()->
        end()->
        arrayNode('drush')->
          children()->
            scalarNode('alias')->
          end()->
        end()->
      end()->
    end();
  }

  /**
   * Returns compiler passes used by mink extension.
   *
   * @return array
   */
  public function getCompilerPasses() {
    return array(
      new Compiler\DriverPass(),
    );
  }
}
