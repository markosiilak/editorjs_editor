<?php

namespace Drupal\editorjs_editor\Twig;

use Drupal\editorjs_editor\Service\EditorJsRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EditorJsExtension extends AbstractExtension {
  public function __construct(private readonly EditorJsRenderer $renderer) {}

  public function getFilters(): array {
    return [
      new TwigFilter('editorjs', [$this, 'render'], ['is_safe' => ['html']]),
      new TwigFilter('editorjs_first_paragraph', [$this, 'renderFirstParagraph'], ['is_safe' => ['html']]),
    ];
  }

  public function render($data): string {
    return $this->renderer->render($data);
  }

  public function renderFirstParagraph($data): string {
    return $this->renderer->renderFirstParagraph($data);
  }
}


