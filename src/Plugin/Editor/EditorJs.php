<?php

namespace Drupal\editorjs_editor\Plugin\Editor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\editor\Attribute\Editor;
use Drupal\editor\Entity\Editor as EditorEntity;
use Drupal\editor\Plugin\EditorBase;

/**
 * Editor.js editor plugin.
 */
#[Editor(
  id: 'editorjs',
  label: new TranslatableMarkup('Editor.js'),
  supports_content_filtering: FALSE,
  supports_inline_editing: TRUE,
  is_xss_safe: FALSE,
  supported_element_types: ['textarea']
)]
class EditorJs extends EditorBase {
  /**
   * {@inheritdoc}
   */
  public function getJSSettings(EditorEntity $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(EditorEntity $editor) {
    return ['editorjs_editor/editorjs.editor'];
  }
}
