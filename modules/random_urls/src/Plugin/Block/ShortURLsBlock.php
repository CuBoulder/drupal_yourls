<?php

namespace Drupal\random_urls\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Short URLs' Block.
 *
 * @Block(
 *   id = "drupal_yourls_results_block",
 *   admin_label = @Translation("All Short URLs Block"),
 *   category = @Translation("Drupal YOURLs"),
 * )
 */
class ShortURLsBlock extends BlockBase {
    /**
    * {@inheritdoc}
    */
    public function defaultConfiguration() {
      return array('label' => 'YOURLs Results Block',);
    }
    /**
    * {@inheritdoc}
    */
    public function build() {
        return [
            '#theme' => 'short-urls-results-template'
        ];
    }

}