<?php

namespace Drupal\editorjs_editor\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for EditorJS content.
 *
 * @Filter(
 *   id = "editorjs_filter",
 *   title = @Translation("EditorJS Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = 0
 * )
 */
class EditorJsFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // For now, just pass through the text as-is
    // The actual rendering is handled by the module's hooks
    return new FilterProcessResult($text);
  }

}
