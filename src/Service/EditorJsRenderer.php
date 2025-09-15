<?php

namespace Drupal\editorjs_editor\Service;

use Drupal\Component\Utility\Xss;

class EditorJsRenderer {
  /**
   * Render only the first paragraph block from Editor.js data.
   *
   * @param mixed $data
   *   JSON string or decoded array.
   */
  public function renderFirstParagraph($data): string {
    // Decode if string.
    if (is_string($data)) {
      $data = trim($data);
      if ($data === '') {
        return '';
      }
      if ($data[0] === '{' || $data[0] === '[') {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $data = $decoded;
        }
      }
    }

    if (!is_array($data)) {
      return '';
    }

    $blocks = $data['blocks'] ?? [];
    if (!is_array($blocks)) {
      return '';
    }

    foreach ($blocks as $block) {
      if (($block['type'] ?? '') !== 'paragraph') {
        continue;
      }
      $text = $this->sanitizeInlineHtml($block['data']['text'] ?? '');
      if ($text === '') {
        continue;
      }
      return '<div class="editorjs-content"><p class="editorjs-paragraph">' . $text . '</p></div>';
    }

    return '';
  }
  /**
   * Render Editor.js JSON string or array to HTML.
   *
   * @param mixed $data
   *   JSON string or decoded array/obj with blocks.
   *
   * @return string
   */
  public function render($data): string {
    if (is_string($data)) {
      $data = trim($data);
      if ($data === '') {
        return '';
      }
      if ($data[0] === '{' || $data[0] === '[') {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $data = $decoded;
        }
      }
    }

    if (!is_array($data)) {
      return '';
    }

    $blocks = $data['blocks'] ?? [];
    if (!is_array($blocks)) {
      return '';
    }

    $htmlParts = [];
    foreach ($blocks as $block) {
      $type = $block['type'] ?? '';
      $blockData = $block['data'] ?? [];
      $htmlParts[] = $this->renderBlock($type, $blockData);
    }
    $content = implode("\n", array_filter($htmlParts));
    
    // Wrap the content in our custom CSS classes
    return '<div class="editorjs-content">' . $content . '</div>';
  }

  private function renderBlock(string $type, array $data): string {
    switch ($type) {
      case 'paragraph':
        $text = $this->sanitizeInlineHtml($data['text'] ?? '');
        return $text === '' ? '' : '<p class="editorjs-paragraph">' . $text . '</p>';

      case 'header':
        $text = $this->sanitizeInlineHtml($data['text'] ?? '');
        $level = (int) ($data['level'] ?? 2);
        $level = ($level >= 1 && $level <= 6) ? $level : 2;
        return $text === '' ? '' : '<h' . $level . ' class="editorjs-header editorjs-header--level-' . $level . '">' . $text . '</h' . $level . '>';

      case 'list':
        $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $items = $data['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
          return '';
        }
        $lis = [];
        foreach ($items as $item) {
          $lis[] = '<li class="editorjs-list-item">' . $this->sanitizeInlineHtml((string) $item) . '</li>';
        }
        $listClass = 'editorjs-list editorjs-list--' . ($style === 'ol' ? 'ordered' : 'unordered');
        return '<' . $style . ' class="' . $listClass . '">' . implode('', $lis) . '</' . $style . '>';

      case 'quote':
        $text = $this->sanitizeInlineHtml($data['text'] ?? '');
        $caption = $this->sanitizeInlineHtml($data['caption'] ?? '');
        $inner = '<p>' . $text . '</p>';
        if ($caption !== '') {
          $inner .= '<cite>' . $caption . '</cite>';
        }
        return '<blockquote>' . $inner . '</blockquote>';

      case 'delimiter':
        return '<hr />';

      default:
        // Fallback: preformatted JSON for unknown blocks (developer-friendly).
        $safe = htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
        return '<pre data-editorjs-type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">' . $safe . '</pre>';
    }
  }

  private function sanitizeInlineHtml(string $html): string {
    // Allow a minimal set of inline tags commonly produced by Editor.js.
    $allowedTags = [
      'a', 'b', 'strong', 'i', 'em', 'u', 's', 'code', 'mark', 'sup', 'sub',
      'span', 'br'
    ];
    return Xss::filter($html, $allowedTags);
  }
}


