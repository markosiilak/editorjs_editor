# EditorJS Editor Module

A Drupal module that provides EditorJS integration with inline editing capabilities for rich text content.

## Features

- **EditorJS Integration**: Full EditorJS editor support with configurable tools
- **Inline Editing**: Click-to-edit functionality on rendered EditorJS content
- **AJAX Saving**: Save changes without page refresh
- **Reusable Components**: Generic field templates and wrapper components
- **CSRF Protection**: Secure AJAX requests with CSRF token validation
- **Responsive Design**: Mobile-friendly editing interface

## Installation

1. Place the module in `web/modules/custom/editorjs_editor/`
2. Enable the module via Drupal admin or Drush:
   ```bash
   ddev drush en editorjs_editor
   ```
3. Clear cache:
   ```bash
   ddev drush cache:rebuild
   ```

## Configuration

### Text Format Setup

1. Go to **Configuration > Text formats and editors**
2. Create or edit a text format
3. Select "EditorJS" as the text editor
4. Configure available tools in the EditorJS settings

### Field Configuration

1. Go to **Structure > Content types > [Your Content Type] > Manage fields**
2. Add or edit a text field (Long text)
3. Set the text format to use EditorJS
4. Configure field display settings

## Usage

### Inline Editing

The module automatically enables inline editing for EditorJS content:

1. **View Mode**: Click on any EditorJS content to start editing
2. **Edit Mode**: Use the EditorJS toolbar to modify content
3. **Save**: Click the "Save" button to save changes
4. **Cancel**: Click "Cancel" to discard changes

### Supported Fields

The module works with any text field using EditorJS format:

- **Body fields** (`field--node--body.html.twig`)
- **Service descriptions** (`field--field-service-description.html.twig`)
- **Any text_long field** (`field--text-long.html.twig`)

## File Structure

```
editorjs_editor/
├── README.md                           # This file
├── editorjs_editor.info.yml           # Module definition
├── editorjs_editor.module             # Main module file with hooks
├── editorjs_editor.routing.yml        # Route definitions
├── editorjs_editor.libraries.yml     # Asset library definitions
├── editorjs_editor.services.yml       # Service definitions
├── editorjs_editor.schema.yml         # Configuration schema
├── drush.services.yml                 # Drush command definitions
├── css/
│   └── editorjs.css                   # EditorJS styling
├── js/
│   └── editorjs-inline.js            # Inline editing JavaScript
└── src/
    └── Controller/
        └── InlineEditController.php   # AJAX save endpoint
```

## Theme Integration

### Required Templates

The module requires specific Twig templates in your theme:

1. **`field--text-long.html.twig`** - Generic template for text_long fields
2. **`editorjs-field-wrapper.html.twig`** - Reusable wrapper component

### Template Usage

Include the wrapper in your field templates:

```twig
{% include '@your_theme/field/editorjs-field-wrapper.html.twig' with {
  'item': item,
  'field_name': field_name,
  'element': element
} %}
```

## API Reference

### Hooks

#### `hook_entity_view_alter()`
- **File**: `editorjs_editor.module`
- **Purpose**: Processes EditorJS content and adds inline editing data
- **Parameters**: `$build`, `$entity`, `$display`

#### `hook_page_attachments_alter()`
- **File**: `editorjs_editor.module`
- **Purpose**: Attaches CSRF token for AJAX requests
- **Parameters**: `$attachments`

### Routes

#### `/editorjs-inline-save`
- **Method**: POST
- **Controller**: `InlineEditController::save()`
- **Purpose**: Saves inline edited content via AJAX
- **Access**: Authenticated users (configurable)

### JavaScript API

#### `editorjs-inline.js`
- **Purpose**: Handles inline editing functionality
- **Dependencies**: Drupal, jQuery, once, drupalSettings
- **Features**:
  - Click detection on EditorJS content
  - EditorJS initialization
  - AJAX save functionality
  - Error handling and user feedback

## Customization

### Adding New Tools

1. Install EditorJS tool packages:
   ```bash
   npm install @editorjs/tool-name
   ```

2. Update `editorjs_editor.libraries.yml`:
   ```yaml
   editorjs.core:
     js:
       js/editorjs-core.js: {}
       js/editorjs-tool-name.js: {}
   ```

3. Configure the tool in EditorJS settings

### Styling

Customize the appearance by overriding CSS in your theme:

```css
/* Override EditorJS styles */
.codex-editor {
  /* Your custom styles */
}

/* Override inline editing styles */
[data-editorjs-content="true"] {
  /* Your custom styles */
}
```

### JavaScript Customization

Extend the inline editing functionality:

```javascript
// In your theme's JavaScript
Drupal.behaviors.customEditorJS = {
  attach: function (context, settings) {
    // Your custom EditorJS enhancements
  }
};
```

## Troubleshooting

### Common Issues

1. **Inline editing not appearing**
   - Check that the field template includes the wrapper component
   - Verify EditorJS data is present in the render array
   - Clear Drupal cache

2. **JavaScript errors**
   - Check browser console for errors
   - Verify all dependencies are loaded
   - Check CSRF token is available

3. **Save not working**
   - Verify user has edit permissions
   - Check AJAX endpoint is accessible
   - Verify CSRF token is valid

### Debug Mode

Enable debug logging in JavaScript:

```javascript
// Add to editorjs-inline.js
const DEBUG = true;
if (DEBUG) {
  console.log('Debug information:', data);
}
```

## Security

- **CSRF Protection**: All AJAX requests include CSRF tokens
- **Access Control**: Inline editing requires appropriate permissions
- **Input Validation**: Content is validated before saving
- **XSS Prevention**: Output is properly escaped

## Performance

- **Asset Loading**: CSS/JS are loaded only when needed
- **Caching**: EditorJS content is cached appropriately
- **Lazy Loading**: EditorJS tools are loaded on demand

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This module is licensed under the GPL-2.0+ license.

## Support

For issues and questions:
- Create an issue in the project repository
- Check the Drupal documentation
- Review the EditorJS documentation

## Changelog

### Version 1.0.0
- Initial release
- EditorJS integration
- Inline editing functionality
- AJAX save capability
- Reusable wrapper components
- CSRF protection
- Responsive design
