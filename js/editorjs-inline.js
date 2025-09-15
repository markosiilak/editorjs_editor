/* eslint-disable no-undef */
(function () {
  'use strict';
  
  // Wait for Drupal and dependencies to be available
  function waitForDependencies() {
    if (typeof window.Drupal === 'undefined' || 
        typeof window.jQuery === 'undefined' || 
        typeof window.once === 'undefined' || 
        typeof window.drupalSettings === 'undefined') {
      setTimeout(waitForDependencies, 100);
      return;
    }
    
    // Now that dependencies are available, initialize
    initializeEditorJSInline();
  }
  
  function initializeEditorJSInline() {
    const Drupal = window.Drupal;
    const $ = window.jQuery;
    const once = window.once;
    const drupalSettings = window.drupalSettings;

  const inlineInstances = new Map();
  const originalContent = new Map();

  async function loadEditorJS() {
    const mod = await import('https://esm.sh/@editorjs/editorjs@2');
    const EditorJS = mod.default || mod.EditorJS;
    // Load common tools.
    const HeaderMod = await import('https://esm.sh/@editorjs/header@2');
    const ListMod = await import('https://esm.sh/@editorjs/list@1');
    const ParagraphMod = await import('https://esm.sh/@editorjs/paragraph@2');
    const Header = HeaderMod.default || HeaderMod;
    const List = ListMod.default || ListMod;
    const Paragraph = ParagraphMod.default || ParagraphMod;
    return { EditorJS, Header, List, Paragraph };
  }

  function createInlineEditor(element, fieldData) {
    console.log('EditorJS Inline: Creating inline editor');
    const fieldId = element.getAttribute('data-field-id');
    const entityId = element.getAttribute('data-entity-id');
    const entityType = element.getAttribute('data-entity-type');
    
    console.log('EditorJS Inline: Field data:', { fieldId, entityId, entityType });
    
    if (!fieldId || !entityId || !entityType) {
      console.warn('Missing required data attributes for inline editing');
      return;
    }

    // Store original content
    originalContent.set(fieldId, element.innerHTML);
    
    // Create editor container
    const editorContainer = document.createElement('div');
    editorContainer.className = 'editorjs-inline-container';
    editorContainer.style.cssText = `
      position: relative;
      min-height: 200px;
      border: 2px solid #3b82f6;
      border-radius: 8px;
      padding: 16px;
      background: white;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    `;

    // Create toolbar
    const toolbar = document.createElement('div');
    toolbar.className = 'editorjs-inline-toolbar';
    toolbar.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      padding: 8px 12px;
      background: #f8fafc;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
    `;

    const title = document.createElement('span');
    title.textContent = 'Editing content...';
    title.style.cssText = `
      font-weight: 600;
      color: #374151;
    `;

    const buttonContainer = document.createElement('div');
    buttonContainer.style.cssText = `
      display: flex;
      gap: 8px;
    `;

    const saveButton = document.createElement('button');
    saveButton.textContent = 'Save';
    saveButton.className = 'btn btn-primary btn-sm';
    saveButton.style.cssText = `
      background: #3b82f6;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    `;

    const cancelButton = document.createElement('button');
    cancelButton.textContent = 'Cancel';
    cancelButton.className = 'btn btn-secondary btn-sm';
    cancelButton.style.cssText = `
      background: #6b7280;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    `;

    // Add hover effects
    saveButton.addEventListener('mouseenter', () => {
      saveButton.style.background = '#2563eb';
    });
    saveButton.addEventListener('mouseleave', () => {
      saveButton.style.background = '#3b82f6';
    });

    cancelButton.addEventListener('mouseenter', () => {
      cancelButton.style.background = '#4b5563';
    });
    cancelButton.addEventListener('mouseleave', () => {
      cancelButton.style.background = '#6b7280';
    });

    buttonContainer.appendChild(saveButton);
    buttonContainer.appendChild(cancelButton);
    toolbar.appendChild(title);
    toolbar.appendChild(buttonContainer);

    // Create editor holder
    const editorHolder = document.createElement('div');
    editorHolder.className = 'editorjs-inline-holder';
    editorHolder.style.cssText = `
      min-height: 150px;
      padding: 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      background: white;
    `;

    editorContainer.appendChild(toolbar);
    editorContainer.appendChild(editorHolder);

    // Replace element content
    element.innerHTML = '';
    element.appendChild(editorContainer);

    // Initialize EditorJS
    console.log('EditorJS Inline: Loading EditorJS libraries...');
    loadEditorJS().then(({ EditorJS, Header, List, Paragraph }) => {
      console.log('EditorJS Inline: Libraries loaded, creating editor instance');
      let editorInstance;
      
      // Parse existing content if it's EditorJS JSON
      let initialData = undefined;
      if (fieldData && fieldData.trim().startsWith('{')) {
        try {
          initialData = JSON.parse(fieldData);
        } catch (e) {
          console.warn('Could not parse existing EditorJS data:', e);
        }
      }

      editorInstance = new EditorJS({
        holder: editorHolder,
        placeholder: 'Start editing...',
        data: initialData,
        autofocus: true,
        tools: {
          paragraph: {
            class: Paragraph,
            inlineToolbar: true,
          },
          header: {
            class: Header,
            inlineToolbar: true,
            levels: [2, 3, 4, 5, 6],
            defaultLevel: 2,
          },
          list: {
            class: List,
            inlineToolbar: true,
          },
        },
      });

      // Store instance
      inlineInstances.set(fieldId, {
        editor: editorInstance,
        container: editorContainer,
        element: element,
        fieldId: fieldId,
        entityId: entityId,
        entityType: entityType
      });
      
      // Remove the creating flag
      element.removeAttribute('data-creating-editor');

      // Save button handler
      saveButton.addEventListener('click', async () => {
        try {
          saveButton.disabled = true;
          saveButton.textContent = 'Saving...';
          
          const output = await editorInstance.save();
          const jsonData = JSON.stringify(output);
          
          // Send AJAX request to save using Drupal's AJAX system
          const response = await $.ajax({
            url: '/editorjs-inline-save',
            method: 'POST',
            data: JSON.stringify({
              field_id: fieldId,
              entity_id: entityId,
              entity_type: entityType,
              data: jsonData
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
              // Use Drupal's CSRF token
              const token = drupalSettings.csrf_token || '';
              console.log('EditorJS Inline: Sending CSRF token:', token ? 'present' : 'missing');
              xhr.setRequestHeader('X-CSRF-Token', token);
            }
          });

          // jQuery AJAX returns the data directly on success
          // Success - update content with new data and refresh the page
          showMessage('Content saved successfully!', 'success');
          
          // Refresh the page to show updated content
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } catch (error) {
          console.error('Error saving content:', error);
          showMessage('Error saving content. Please try again.', 'error');
          saveButton.disabled = false;
          saveButton.textContent = 'Save';
        }
      });

      // Cancel button handler
      cancelButton.addEventListener('click', () => {
        element.innerHTML = originalContent.get(fieldId);
        inlineInstances.delete(fieldId);
        originalContent.delete(fieldId);
      });

      // Handle escape key
      const handleEscape = (e) => {
        if (e.key === 'Escape') {
          cancelButton.click();
        }
      };
      document.addEventListener('keydown', handleEscape);
      
      // Clean up event listener when editor is destroyed
      const cleanup = () => {
        document.removeEventListener('keydown', handleEscape);
      };
      
      // Store cleanup function
      const instance = inlineInstances.get(fieldId);
      if (instance) {
        instance.cleanup = cleanup;
      }
    }).catch(error => {
      console.error('EditorJS Inline: Error loading EditorJS:', error);
      // Remove the creating flag on error
      element.removeAttribute('data-creating-editor');
      // Restore original content
      element.innerHTML = originalContent.get(fieldId);
      showMessage('Error loading editor. Please try again.', 'error');
    });
  }

  function showMessage(text, type) {
    const message = document.createElement('div');
    message.textContent = text;
    message.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 6px;
      color: white;
      font-weight: 500;
      z-index: 10000;
      transition: opacity 0.3s ease;
      ${type === 'success' ? 'background: #10b981;' : 'background: #ef4444;'}
    `;
    
    document.body.appendChild(message);
    
    setTimeout(() => {
      message.style.opacity = '0';
      setTimeout(() => {
        if (message.parentNode) {
          message.parentNode.removeChild(message);
        }
      }, 300);
    }, 3000);
  }

  function initializeInlineEditing() {
    // Add click handlers to EditorJS content
    const editorjsElements = document.querySelectorAll('[data-editorjs-content="true"]');
    
    console.log('EditorJS Inline: Found', editorjsElements.length, 'editable elements');
    
    editorjsElements.forEach(element => {
      element.addEventListener('click', (e) => {
        console.log('EditorJS Inline: Click detected on element');
        
        // Don't trigger on links or buttons (check if target or any parent is a link/button)
        let target = e.target;
        console.log('EditorJS Inline: Click target:', target.tagName, target.className);
        while (target && target !== element) {
          if (target.tagName === 'A' || target.tagName === 'BUTTON') {
            console.log('EditorJS Inline: Click on link/button, ignoring');
            return;
          }
          target = target.parentElement;
        }
        
        // Don't trigger if already editing (check for the container)
        if (element.querySelector('.editorjs-inline-container')) {
          console.log('EditorJS Inline: Already editing, ignoring click');
          return;
        }
        
        // Don't trigger if we're in the process of creating an editor
        if (element.hasAttribute('data-creating-editor')) {
          console.log('EditorJS Inline: Already creating editor, ignoring click');
          return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        // Mark that we're creating an editor
        element.setAttribute('data-creating-editor', 'true');
        
        // Get the field data from the data attribute
        const fieldData = element.getAttribute('data-field-data');
        console.log('EditorJS Inline: Creating inline editor with data:', fieldData ? 'present' : 'missing');
        createInlineEditor(element, fieldData);
      });
      
      // Add visual indicator that content is editable
      element.style.cursor = 'pointer';
      element.style.transition = 'box-shadow 0.2s ease';
      
      element.addEventListener('mouseenter', () => {
        if (!element.querySelector('.editorjs-inline-container')) {
          element.style.boxShadow = '0 2px 8px rgba(59, 130, 246, 0.2)';
        }
      });
      
      element.addEventListener('mouseleave', () => {
        if (!element.querySelector('.editorjs-inline-container')) {
          element.style.boxShadow = '';
        }
      });
    });
  }

    // Initialize immediately since dependencies are now available
    console.log('EditorJS Inline: Dependencies ready, initializing');
    initializeInlineEditing();
  }
  
  // Start waiting for dependencies
  console.log('EditorJS Inline script loaded, waiting for Drupal...');
  waitForDependencies();

})();
