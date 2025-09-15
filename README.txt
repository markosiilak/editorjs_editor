EditorJS Editor Module
=====================

A Drupal module that provides EditorJS integration with inline editing capabilities for rich text content.

REQUIREMENTS
-----------
- Drupal 10 or 11
- PHP 8.1 or higher

INSTALLATION
------------
1. Download and extract the module to your modules directory
2. Enable the module via Drupal admin or Drush:
   drush en editorjs_editor
3. Clear cache:
   drush cache:rebuild

CONFIGURATION
-------------
1. Go to Configuration > Text formats and editors
2. Create or edit a text format
3. Select "EditorJS" as the text editor
4. Configure available tools in the EditorJS settings

USAGE
-----
The module automatically enables inline editing for EditorJS content:
- Click on any EditorJS content to start editing
- Use the EditorJS toolbar to modify content
- Click "Save" to save changes or "Cancel" to discard

FEATURES
--------
- EditorJS Integration: Full EditorJS editor support with configurable tools
- Inline Editing: Click-to-edit functionality on rendered EditorJS content
- AJAX Saving: Save changes without page refresh
- CSRF Protection: Secure AJAX requests with CSRF token validation
- Responsive Design: Mobile-friendly editing interface
- Reusable Components: Generic field templates and wrapper components

SUPPORTED FIELDS
----------------
The module works with any text field using EditorJS format:
- Body fields
- Service descriptions
- Any text_long field

THEME INTEGRATION
-----------------
The module requires specific Twig templates in your theme:
1. field--text-long.html.twig - Generic template for text_long fields
2. editorjs-field-wrapper.html.twig - Reusable wrapper component

Include the wrapper in your field templates:
{% include '@your_theme/field/editorjs-field-wrapper.html.twig' with {
  'item': item,
  'field_name': field_name,
  'element': element
} %}

CUSTOMIZATION
-------------
Adding New Tools:
1. Install EditorJS tool packages: npm install @editorjs/tool-name
2. Update editorjs_editor.libraries.yml
3. Configure the tool in EditorJS settings

Styling:
Customize the appearance by overriding CSS in your theme:
.codex-editor { /* Your custom styles */ }
[data-editorjs-content="true"] { /* Your custom styles */ }

TROUBLESHOOTING
---------------
1. Inline editing not appearing:
   - Check that the field template includes the wrapper component
   - Verify EditorJS data is present in the render array
   - Clear Drupal cache

2. JavaScript errors:
   - Check browser console for errors
   - Verify all dependencies are loaded
   - Check CSRF token is available

3. Save not working:
   - Verify user has edit permissions
   - Check AJAX endpoint is accessible
   - Verify CSRF token is valid

SECURITY
--------
- CSRF Protection: All AJAX requests include CSRF tokens
- Access Control: Inline editing requires appropriate permissions
- Input Validation: Content is validated before saving
- XSS Prevention: Output is properly escaped

PERFORMANCE
-----------
- Asset Loading: CSS/JS are loaded only when needed
- Caching: EditorJS content is cached appropriately
- Lazy Loading: EditorJS tools are loaded on demand

MAINTAINER
----------
Marko Siilak (marko@siilak.com)

LICENSE
-------
This module is licensed under the GPL-2.0+ license.
