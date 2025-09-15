<?php

namespace Drupal\editorjs_editor\Commands;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;

class EditorjsEditorCommands extends DrushCommands {
  /**
   * Generate sample article nodes with optional images.
   *
   * @command editorjs_editor:generate-articles
   * @aliases ejgen
   *
   * @param int $count
   *   Number of articles to create.
   * @option with-images
   *   Attach images if available in public files.
   */
  public function generateArticles(int $count = 5, array $options = ['with-images' => TRUE]) : void {
    $bundle = $this->pickBundle();
    if (!$bundle) {
      $this->logger()->error('No node bundles available.');
      return;
    }

    $this->logger()->notice("Using bundle: {$bundle}");

    $images = [];
    if (!empty($options['with-images'])) {
      $images = $this->discoverImages();
      $this->logger()->notice('Discovered @num images under public://', ['@num' => count($images)]);
    }

    for ($i = 1; $i <= $count; $i++) {
      $title = $this->randomTitle();
      $json = $this->sampleEditorJs($title);

      $values = [
        'type' => $bundle,
        'title' => $title,
        'status' => 1,
        'uid' => 1,
      ];

      $node = Node::create($values);

      if ($node->hasField('body')) {
        $node->set('body', [
          'value' => json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
          'format' => 'plain_text',
        ]);
      }

      if ($node->hasField('field_image') && count($images) > 0) {
        $file = $this->getOrCreateFileEntity($images[($i - 1) % count($images)]);
        if ($file) {
          $node->set('field_image', [
            'target_id' => $file->id(),
            'alt' => $title,
            'title' => $title,
          ]);
        }
      }

      $node->save();
      $this->logger()->success("Created node/{$node->id()} ({$bundle})");
    }
  }

  private function pickBundle(): ?string {
    $storage = \Drupal::entityTypeManager()->getStorage('node_type');
    $types = $storage->loadMultiple();
    if (isset($types['article'])) {
      return 'article';
    }
    if (isset($types['page'])) {
      return 'page';
    }
    // Fallback to any available bundle.
    foreach (array_keys($types) as $machineName) {
      return $machineName;
    }
    return NULL;
  }

  private function discoverImages(): array {
    $fileSystem = \Drupal::service('file_system');
    $found = $fileSystem->scanDirectory('public://', '/\.(png|jpe?g|webp)$/i', ['recurse' => TRUE]);
    $uris = [];
    foreach ($found as $info) {
      if (!empty($info->uri)) {
        $uris[] = $info->uri;
      }
    }
    return $uris;
  }

  private function getOrCreateFileEntity(string $uri): ?File {
    // Try to find existing managed file for this URI.
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
    if ($files) {
      /** @var \Drupal\file\Entity\File $file */
      $file = reset($files);
      return $this->ensurePermanent($file);
    }
    if (!file_exists(\Drupal::service('file_system')->realpath($uri))) {
      return NULL;
    }
    $file = File::create([
      'uri' => $uri,
      'status' => 1,
      'uid' => 1,
    ]);
    $file->save();
    return $this->ensurePermanent($file);
  }

  private function ensurePermanent(File $file): File {
    if ($file->isPermanent()) {
      return $file;
    }
    $file->setPermanent();
    $file->save();
    return $file;
  }

  private function randomTitle(): string {
    $adjectives = ['Amazing', 'Brilliant', 'Curious', 'Delightful', 'Epic', 'Fresh', 'Grand', 'Happy', 'Inspiring', 'Joyful'];
    $nouns = ['Journey', 'Discovery', 'Insight', 'Story', 'Guide', 'Update', 'Release', 'Overview', 'Preview', 'Note'];
    return $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
  }

  private function sampleEditorJs(string $title): array {
    return [
      'time' => (int) (microtime(TRUE) * 1000),
      'blocks' => [
        [
          'type' => 'header',
          'data' => ['text' => $this->escape($title), 'level' => 2],
        ],
        [
          'type' => 'paragraph',
          'data' => ['text' => $this->escape('This is a generated article used for testing rendering and teaser output.')],
        ],
        [
          'type' => 'list',
          'data' => [
            'style' => 'unordered',
            'items' => [
              'Editor.js paragraphs render as HTML',
              'Teasers show only the first paragraph',
              'Full view shows all supported blocks',
            ],
          ],
        ],
      ],
      'version' => '2.0.0',
    ];
  }

  private function escape(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}


