<?php

namespace Drupal\editorjs_editor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling EditorJS image uploads.
 */
class ImageUploadController extends ControllerBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ImageUploadController object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system')
    );
  }

  /**
   * Upload image file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with upload result.
   */
  public function uploadFile(Request $request) {
    try {
      $uploaded_file = $request->files->get('image');
      
      if (!$uploaded_file) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'No file uploaded'
        ], 400);
      }

      // Validate file type - check both MIME type and file extension
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      
      $mime_type = $uploaded_file->getMimeType();
      $extension = strtolower(pathinfo($uploaded_file->getClientOriginalName(), PATHINFO_EXTENSION));
      
      // Log for debugging
      $this->getLogger('editorjs_editor')->info('Upload attempt: MIME type: @mime, Extension: @ext', [
        '@mime' => $mime_type,
        '@ext' => $extension
      ]);
      
      if (!in_array($mime_type, $allowed_types) && !in_array($extension, $allowed_extensions)) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'Invalid file type. Only images are allowed. Detected: ' . $mime_type
        ], 400);
      }

      // Validate file size (max 10MB)
      if ($uploaded_file->getSize() > 10 * 1024 * 1024) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'File too large. Maximum size is 10MB.'
        ], 400);
      }

      // Generate unique filename
      $filename = $uploaded_file->getClientOriginalName();
      $extension = pathinfo($filename, PATHINFO_EXTENSION);
      $unique_filename = 'editorjs_' . uniqid() . '_' . time() . '.' . $extension;

      // Read file content and save it directly to public:// (root of public files)
      $file_content = file_get_contents($uploaded_file->getPathname());
      
      // Use the file system service to write the file
      $file_system = \Drupal::service('file_system');
      $public_path = $file_system->realpath('public://');
      $full_path = $public_path . '/' . $unique_filename;
      
      // Write file directly to filesystem
      if (file_put_contents($full_path, $file_content) !== FALSE) {
        // Try to create file entity for image styles
        try {
          $file_entity = \Drupal\file\Entity\File::create([
            'uri' => $destination,
            'status' => 1,
            'uid' => $this->currentUser()->id(),
            'filename' => $unique_filename,
            'filesize' => filesize($full_path),
          ]);
          $file_entity->save();
          
          // Use Drupal's file URL generator for proper image style support
          $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file_entity->getFileUri());
        } catch (\Exception $e) {
          // Fallback to manual URL generation if file entity creation fails
          $base_url = \Drupal::request()->getSchemeAndHttpHost();
          $url = $base_url . '/sites/default/files/' . $unique_filename;
        }
        
        return new JsonResponse([
          'success' => 1,
          'url' => $url,
          'name' => $unique_filename,
          'size' => filesize($full_path),
        ]);
      } else {
        return new JsonResponse([
          'success' => 0,
          'error' => 'Failed to save file'
        ], 500);
      }

    } catch (\Exception $e) {
      $this->getLogger('editorjs_editor')->error('Image upload error: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse([
        'success' => 0,
        'error' => 'Upload failed: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Upload image from URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with upload result.
   */
  public function uploadUrl(Request $request) {
    try {
      $data = Json::decode($request->getContent());
      $url = $data['url'] ?? '';

      if (empty($url)) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'No URL provided'
        ], 400);
      }

      // Validate URL
      if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'Invalid URL'
        ], 400);
      }

      // Download image
      $image_data = file_get_contents($url);
      if ($image_data === FALSE) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'Failed to download image from URL'
        ], 400);
      }

      // Get image info
      $image_info = getimagesizefromstring($image_data);
      if ($image_info === FALSE) {
        return new JsonResponse([
          'success' => 0,
          'error' => 'Invalid image data'
        ], 400);
      }

      // Generate filename
      $extension = image_type_to_extension($image_info[2], FALSE);
      $filename = 'editorjs_url_' . time() . '.' . $extension;

      // Save file directly to public:// (root of public files)
      $destination = 'public://' . $filename;
      
      // Use the file system service to write the file
      $file_system = \Drupal::service('file_system');
      $public_path = $file_system->realpath('public://');
      $full_path = $public_path . '/' . $filename;
      
      // Write file directly to filesystem
      if (file_put_contents($full_path, $image_data) !== FALSE) {
        // Try to create file entity for image styles
        try {
          $file_entity = \Drupal\file\Entity\File::create([
            'uri' => $destination,
            'status' => 1,
            'uid' => $this->currentUser()->id(),
            'filename' => $filename,
            'filesize' => filesize($full_path),
          ]);
          $file_entity->save();
          
          // Use Drupal's file URL generator for proper image style support
          $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file_entity->getFileUri());
        } catch (\Exception $e) {
          // Fallback to manual URL generation if file entity creation fails
          $base_url = \Drupal::request()->getSchemeAndHttpHost();
          $url = $base_url . '/sites/default/files/' . $filename;
        }
        
        return new JsonResponse([
          'success' => 1,
          'url' => $url,
          'name' => $filename,
          'size' => filesize($full_path),
        ]);
      } else {
        return new JsonResponse([
          'success' => 0,
          'error' => 'Failed to save file'
        ], 500);
      }

    } catch (\Exception $e) {
      $this->getLogger('editorjs_editor')->error('Image URL upload error: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse([
        'success' => 0,
        'error' => 'Upload failed: ' . $e->getMessage()
      ], 500);
    }
  }
}
