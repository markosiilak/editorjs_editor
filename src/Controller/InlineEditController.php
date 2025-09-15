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

      // Set the field value
      $entity->set($field_id, $field_data);

      // Save the entity
      $entity->save();

      return new JsonResponse(['success' => TRUE, 'message' => 'Content saved successfully']);

    } catch (\Exception $e) {
      \Drupal::logger('editorjs_editor')->error('Error saving inline content: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Internal server error'], 500);
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
