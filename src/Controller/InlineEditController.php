<?php

namespace Drupal\editorjs_editor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for inline editing functionality.
 */
class InlineEditController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InlineEditController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Save inline edited content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response indicating success or failure.
   */
  public function save(Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);
      
      if (!$data || !isset($data['entity_type'], $data['entity_id'], $data['field_id'], $data['data'])) {
        return new JsonResponse(['error' => 'Invalid request data'], 400);
      }

      $entity_type = $data['entity_type'];
      $entity_id = $data['entity_id'];
      $field_id = $data['field_id'];
      $field_data = $data['data'];

      // Load the entity
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      if (!$entity) {
        return new JsonResponse(['error' => 'Entity not found'], 404);
      }

      // Check if the entity has the field
      if (!$entity->hasField($field_id)) {
        return new JsonResponse(['error' => 'Field not found'], 400);
      }

      // Check access to edit the entity
      if (!$entity->access('update')) {
        return new JsonResponse(['error' => 'Access denied'], 403);
      }

      // Validate the JSON data
      $decoded_data = json_decode($field_data, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return new JsonResponse(['error' => 'Invalid JSON data'], 400);
      }

      // Handle different field types
      $field_definition = $entity->getFieldDefinition($field_id);
      $field_type = $field_definition->getType();
      
      if ($field_type === 'image') {
        // For image fields, extract image data from EditorJS and create file entity
        $this->handleImageFieldUpdate($entity, $field_id, $decoded_data);
      } else {
        // For other fields, store the EditorJS JSON data (as JSON string)
        // Set the field value properly for text fields
        $entity->set($field_id, ['value' => $field_data]);
      }

      // Save the entity
      $entity->save();

      return new JsonResponse(['success' => TRUE, 'message' => 'Content saved successfully']);

    } catch (\Exception $e) {
      \Drupal::logger('editorjs_editor')->error('Error saving inline content: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Handle image field updates from EditorJS data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   * @param string $field_id
   *   The field ID.
   * @param array $editorjs_data
   *   The EditorJS data.
   */
  protected function handleImageFieldUpdate(EntityInterface $entity, $field_id, array $editorjs_data) {
    // Extract image data from EditorJS blocks
    $image_data = NULL;
    if (isset($editorjs_data['blocks'])) {
      foreach ($editorjs_data['blocks'] as $block) {
        if ($block['type'] === 'image' && isset($block['data']['file']['url'])) {
          $image_data = $block['data'];
          break;
        }
      }
    }

    if ($image_data) {
      $image_url = $image_data['file']['url'];
      $caption = $image_data['caption'] ?? '';
      
      // Download the image and create a file entity
      $file_entity = $this->createFileFromUrl($image_url);
      
      if ($file_entity) {
        // Set the image field value
        $entity->set($field_id, [
          'target_id' => $file_entity->id(),
          'alt' => $caption,
          'title' => $caption,
        ]);
      }
    } else {
      // Clear the field if no image data
      $entity->set($field_id, NULL);
    }
  }

  /**
   * Create a file entity from a URL.
   *
   * @param string $url
   *   The image URL.
   *
   * @return \Drupal\file\Entity\File|null
   *   The file entity or NULL on failure.
   */
  protected function createFileFromUrl($url) {
    try {
      // Download the image
      $image_data = file_get_contents($url);
      if ($image_data === FALSE) {
        return NULL;
      }

      // Get image info
      $image_info = getimagesizefromstring($image_data);
      if ($image_info === FALSE) {
        return NULL;
      }

      // Generate filename
      $extension = image_type_to_extension($image_info[2], FALSE);
      $filename = 'editorjs_image_' . time() . '.' . $extension;

      // Create directory if it doesn't exist
      $directory = 'public://editorjs-images';
      \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

      // Save file
      $destination = $directory . '/' . $filename;
      $file_entity = file_save_data($image_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

      if ($file_entity) {
        // Create file entity
        $file = \Drupal\file\Entity\File::create([
          'uri' => $file_entity->getFileUri(),
          'status' => 1,
          'uid' => $this->currentUser()->id(),
        ]);
        $file->save();
        return $file;
      }

      return NULL;
    } catch (\Exception $e) {
      \Drupal::logger('editorjs_editor')->error('Error creating file from URL: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Access callback for the save endpoint.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // Allow all users for testing (you can restrict this later)
    return AccessResult::allowed();
  }

}
