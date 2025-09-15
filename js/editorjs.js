/* eslint-disable no-undef */
(function (Drupal, $, once, drupalSettings) {
  'use strict';

  const instances = new Map();
  const callbacks = new Map();
  const attaching = new Set();

  async function loadEditorJS() {
    const mod = await import('https://esm.sh/@editorjs/editorjs@2');
    const EditorJS = mod.default || mod.EditorJS;
    // Load common tools.
    const HeaderMod = await import('https://esm.sh/@editorjs/header@2');
    const ListMod = await import('https://esm.sh/@editorjs/list@1');
    const Header = HeaderMod.default || HeaderMod;
    const List = ListMod.default || ListMod;
    return { EditorJS, Header, List };
  }

  // Drag and Drop functionality
  function initializeDragAndDrop(editorInstance, holder) {
    let draggedElement = null;
    let dragOverElement = null;
    let dropIndicator = null;

    // Create drop indicator element
    function createDropIndicator() {
      const indicator = document.createElement('div');
      indicator.className = 'editorjs-drop-indicator';
      indicator.style.cssText = `
        height: 2px;
        background: #3b82f6;
        margin: 4px 0;
        border-radius: 1px;
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
      `;
      return indicator;
    }

    // Add drag and drop styles
    function addDragDropStyles() {
      if (document.getElementById('editorjs-drag-drop-styles')) return;
      const style = document.createElement('style');
      style.id = 'editorjs-drag-drop-styles';
      style.textContent = `
        .editorjs-block {
          transition: transform 0.2s ease, opacity 0.2s ease;
          cursor: move;
          position: relative;
        }
        .editorjs-block:hover {
          transform: translateY(-1px);
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .editorjs-block:hover::before {
          content: '';
          position: absolute;
          left: -20px;
          top: 50%;
          transform: translateY(-50%);
          width: 16px;
          height: 16px;
          background: #6b7280;
          border-radius: 2px;
          opacity: 0.6;
        }
        .editorjs-block:hover::after {
          content: '⋮⋮';
          position: absolute;
          left: -18px;
          top: 50%;
          transform: translateY(-50%);
          color: white;
          font-size: 8px;
          line-height: 1;
          opacity: 0.8;
        }
        .editorjs-block.dragging {
          opacity: 0.5;
          transform: rotate(2deg);
        }
        .editorjs-block.drag-over {
          transform: translateY(4px);
        }
        .editorjs-drop-indicator {
          height: 2px;
          background: #3b82f6;
          margin: 4px 0;
          border-radius: 1px;
          opacity: 0;
          transition: opacity 0.2s ease;
          pointer-events: none;
        }
        .editorjs-drop-indicator.active {
          opacity: 1;
        }
        .editorjs-holder.drag-over {
          background-color: rgba(16, 185, 129, 0.05);
          border-color: #10b981;
        }
        .editorjs-holder.file-drag-over {
          background-color: rgba(59, 130, 246, 0.05);
          border-color: #3b82f6;
          border-style: dashed;
        }
        .editorjs-file-drop-overlay {
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: rgba(59, 130, 246, 0.95);
          color: white;
          padding: 16px 32px;
          border-radius: 12px;
          font-size: 16px;
          font-weight: 500;
          pointer-events: none;
          z-index: 1000;
          display: flex;
          align-items: center;
          gap: 12px;
          box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .editorjs-file-drop-overlay svg {
          width: 24px;
          height: 24px;
          flex-shrink: 0;
        }
      `;
      document.head.appendChild(style);
    }

    // Wait for editor to be ready and add drag handlers
    setTimeout(() => {
      addDragDropStyles();
      
      // Add drag handles to existing blocks
      const blocks = holder.querySelectorAll('.editorjs-block');
      blocks.forEach(block => {
        addDragHandlers(block);
      });

      // Observer for new blocks
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
              const blocks = node.querySelectorAll ? node.querySelectorAll('.editorjs-block') : [];
              blocks.forEach(block => {
                if (!block.hasAttribute('data-drag-handlers-added')) {
                  addDragHandlers(block);
                }
              });
            }
          });
        });
      });

      observer.observe(holder, { childList: true, subtree: true });
    }, 1000);

    function addDragHandlers(block) {
      if (block.hasAttribute('data-drag-handlers-added')) return;
      
      block.setAttribute('data-drag-handlers-added', 'true');
      block.draggable = true;
      
      // Add drag start handler
      block.addEventListener('dragstart', (e) => {
        draggedElement = block;
        block.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', block.outerHTML);
      });

      // Add drag end handler
      block.addEventListener('dragend', (e) => {
        block.classList.remove('dragging');
        if (dropIndicator && dropIndicator.parentNode) {
          dropIndicator.parentNode.removeChild(dropIndicator);
        }
        draggedElement = null;
        dragOverElement = null;
        dropIndicator = null;
      });

      // Add drag over handler
      block.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        
        if (draggedElement === block) return;
        
        const rect = block.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;
        
        if (e.clientY < midpoint) {
          // Insert above
          if (dropIndicator) {
            dropIndicator.remove();
          }
          dropIndicator = createDropIndicator();
          block.parentNode.insertBefore(dropIndicator, block);
          dropIndicator.classList.add('active');
        } else {
          // Insert below
          if (dropIndicator) {
            dropIndicator.remove();
          }
          dropIndicator = createDropIndicator();
          block.parentNode.insertBefore(dropIndicator, block.nextSibling);
          dropIndicator.classList.add('active');
        }
      });

      // Add drag leave handler
      block.addEventListener('dragleave', (e) => {
        if (!block.contains(e.relatedTarget)) {
          if (dropIndicator) {
            dropIndicator.classList.remove('active');
          }
        }
      });

      // Add drop handler
      block.addEventListener('drop', (e) => {
        e.preventDefault();
        
        if (draggedElement && draggedElement !== block) {
          const rect = block.getBoundingClientRect();
          const midpoint = rect.top + rect.height / 2;
          
          if (e.clientY < midpoint) {
            // Insert above
            block.parentNode.insertBefore(draggedElement, block);
          } else {
            // Insert below
            block.parentNode.insertBefore(draggedElement, block.nextSibling);
          }
          
          // Trigger save to update the data
          if (editorInstance && editorInstance.save) {
            editorInstance.save().then(() => {
              // Data is automatically saved via onChange callback
            });
          }
        }
        
        if (dropIndicator) {
          dropIndicator.remove();
        }
      });
    }
  }


  // File drag and drop functionality
  function initializeFileDragDrop(editorInstance, holder) {
    let fileDropOverlay = null;

    holder.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      holder.classList.add('file-drag-over');
      
      // Create file drop overlay if it doesn't exist
      if (!fileDropOverlay) {
        fileDropOverlay = document.createElement('div');
        fileDropOverlay.className = 'editorjs-file-drop-overlay';
        fileDropOverlay.innerHTML = `
          <svg fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V8a1 1 0 112 0v2.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
          Drop files here to add content
        `;
        holder.appendChild(fileDropOverlay);
      }
    });

    holder.addEventListener('dragleave', (e) => {
      if (!holder.contains(e.relatedTarget)) {
        holder.classList.remove('file-drag-over');
        if (fileDropOverlay && fileDropOverlay.parentNode) {
          fileDropOverlay.parentNode.removeChild(fileDropOverlay);
          fileDropOverlay = null;
        }
      }
    });

    holder.addEventListener('drop', (e) => {
      e.preventDefault();
      holder.classList.remove('file-drag-over');
      if (fileDropOverlay && fileDropOverlay.parentNode) {
        fileDropOverlay.parentNode.removeChild(fileDropOverlay);
        fileDropOverlay = null;
      }
      
      const files = Array.from(e.dataTransfer.files);
      files.forEach(file => {
        if (file.type.startsWith('image/')) {
          handleImageDrop(file, editorInstance, holder, e);
        } else if (file.type === 'text/plain') {
          handleTextDrop(file, editorInstance, holder, e);
        }
      });
    });

    function handleImageDrop(file, editorInstance, holder, e) {
      const reader = new FileReader();
      reader.onload = (event) => {
        const imageData = event.target.result;
        
        // Create a simple image block (you might want to use a proper image tool)
        const imageBlock = {
          type: 'paragraph',
          data: {
            text: `<img src="${imageData}" alt="${file.name}" style="max-width: 100%; height: auto;" />`
          }
        };
        
        insertBlockAtPosition(imageBlock, editorInstance, holder, e);
      };
      reader.readAsDataURL(file);
    }

    function handleTextDrop(file, editorInstance, holder, e) {
      const reader = new FileReader();
      reader.onload = (event) => {
        const textContent = event.target.result;
        
        // Create a paragraph block with the text content
        const textBlock = {
          type: 'paragraph',
          data: {
            text: textContent
          }
        };
        
        insertBlockAtPosition(textBlock, editorInstance, holder, e);
      };
      reader.readAsText(file);
    }

    function insertBlockAtPosition(blockData, editorInstance, holder, e) {
      // Find insertion point
      const blocks = holder.querySelectorAll('.editorjs-block');
      let targetBlock = null;
      let insertPosition = 'end';
      
      for (let block of blocks) {
        const rect = block.getBoundingClientRect();
        if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
          targetBlock = block;
          const midpoint = rect.top + rect.height / 2;
          insertPosition = e.clientY < midpoint ? 'before' : 'after';
          break;
        }
      }
      
      // Insert the new block
      if (editorInstance && editorInstance.blocks) {
        const currentData = editorInstance.save();
        currentData.then((data) => {
          const newBlocks = data.blocks || [];
          
          if (targetBlock) {
            // Find the index of the target block
            const targetIndex = Array.from(blocks).indexOf(targetBlock);
            const insertIndex = insertPosition === 'before' ? targetIndex : targetIndex + 1;
            newBlocks.splice(insertIndex, 0, blockData);
          } else {
            newBlocks.push(blockData);
          }
          
          // Update the editor with new data
          editorInstance.render({
            blocks: newBlocks
          });
        });
      }
    }
  }

  function buildWrapper(element) {
    const wrapper = document.createElement('div');
    wrapper.className = 'editorjs-wrapper form-control';
    
    
    const holder = document.createElement('div');
    holder.className = 'editorjs-holder textarea textarea-bordered w-full min-h-48 prose max-w-none';
    
    wrapper.appendChild(holder);
    element.parentNode.insertBefore(wrapper, element.nextSibling);
    return { wrapper, holder };
  }

  function ensureStylesInjected() {
    if (document.getElementById('editorjs-inline-styles')) return;
    const style = document.createElement('style');
    style.id = 'editorjs-inline-styles';
    style.textContent = `
      .editorjs-wrapper { margin-top: 0.25rem; }
      .editorjs-holder { min-height: 12rem; padding: 0.75rem; }
      .editorjs-holder:focus { outline: 2px solid rgba(59,130,246,0.6); outline-offset: 0; }
    `;
    document.head.appendChild(style);
  }

  Drupal.editors.editorjs = {
    async attach(element, format) {
      const key = element;
      if (instances.has(key) || attaching.has(key)) return;
      
      // Only show EditorJS editor if the format is 'basic_html'
      if (format && format.format !== 'basic_html') {
        return;
      }
      
      attaching.add(key);

      ensureStylesInjected();

      // Hide original textarea and insert holder after it.
      element.setAttribute('data-editorjs-original-display', element.style.display || '');
      element.style.display = 'none';

      const { wrapper, holder } = buildWrapper(element);

      try {
        const { EditorJS, Header, List } = await loadEditorJS();
        const ParagraphMod = await import('https://esm.sh/@editorjs/paragraph@2');
        const Paragraph = ParagraphMod.default || ParagraphMod;

        // Initial data: try to parse JSON from textarea, else start empty.
        let data = undefined;
        if (element.value && element.value.trim().startsWith('{')) {
          try { data = JSON.parse(element.value); } catch (e) { /* ignore */ }
        }

        let editorInstance; // declared for onChange to capture.
        const saveAndSync = Drupal.debounce(async () => {
          try {
            const output = await editorInstance.save();
            element.value = JSON.stringify(output);
            const cb = callbacks.get(key);
            if (cb) cb();
          } catch (e) { /* ignore */ }
        }, 400, true);

        editorInstance = new EditorJS({
          holder: holder,
          placeholder: 'Start writing…',
          data: data,
          onChange: saveAndSync,
          autofocus: false,
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

        // Initialize drag and drop functionality
        initializeDragAndDrop(editorInstance, holder);
        
        // Initialize file drag and drop
        initializeFileDragDrop(editorInstance, holder);

        instances.set(key, { editor: editorInstance, wrapper });
      } catch (e) {
        if (wrapper && wrapper.parentNode) {
          wrapper.parentNode.removeChild(wrapper);
        }
        element.style.display = element.getAttribute('data-editorjs-original-display') || '';
        element.removeAttribute('data-editorjs-original-display');
        throw e;
      } finally {
        attaching.delete(key);
      }
    },

    detach(element, format, trigger) {
      const key = element;
      const instance = instances.get(key);
      if (!instance) return;
      
      // Only detach EditorJS editor if the format is 'basic_html'
      if (format && format.format !== 'basic_html') {
        return;
      }

      if (trigger === 'serialize') {
        // Ensure textarea has latest data.
        try {
          instance.editor.save().then((output) => {
            element.value = JSON.stringify(output);
          });
        } catch (e) { /* noop */ }
        return;
      }

      try {
        // Editor.js destroys on GC; no explicit destroy needed, but cleanup DOM.
      } catch (e) { /* noop */ }
      if (instance.wrapper && instance.wrapper.parentNode) {
        instance.wrapper.parentNode.removeChild(instance.wrapper);
      }
      element.style.display = element.getAttribute('data-editorjs-original-display') || '';
      element.removeAttribute('data-editorjs-original-display');
      callbacks.delete(key);
      instances.delete(key);
      $(element).off('.editorjs');
    },

    onChange(element, callback) {
      const key = element;
      callbacks.set(key, Drupal.debounce(callback, 400, true));
      $(element).on('input.editorjs', Drupal.debounce(callback, 400, true));
    },
  };
})(Drupal, jQuery, once, drupalSettings);
