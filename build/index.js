/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/block.json"
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"telex/block-gatherpress-venue-hierarchy","version":"0.1.0","title":"Location Hierarchy Display","category":"widgets","icon":"location","description":"Displays the complete location hierarchy as inline text","example":{},"attributes":{"startLevel":{"type":"number","default":1},"endLevel":{"type":"number","default":999},"enableLinks":{"type":"boolean","default":false},"linkColor":{"type":"string","default":""},"showVenue":{"type":"boolean","default":false}},"usesContext":["postId","postType"],"supports":{"html":false,"align":true,"color":{"link":true,"text":true,"background":true}},"textdomain":"gatherpress-venue-hierarchy","editorScript":"file:./index.js","editorStyle":"file:./index.css","style":"file:./style-index.css","render":"file:./render.php"}');

/***/ },

/***/ "./src/edit.js"
/*!*********************!*\
  !*** ./src/edit.js ***!
  \*********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./editor.scss */ "./src/editor.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);
/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */


/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */






/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * Custom Dual Range Control Component
 *
 * A range slider with two handles for selecting start and end levels.
 */

function DualRangeControl({
  startLevel,
  endLevel,
  maxLevel,
  onChange
}) {
  const [isDragging, setIsDragging] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);
  const [trackRef, setTrackRef] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);
  const levelLabels = [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Country', 'gatherpress-venue-hierarchy'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('State', 'gatherpress-venue-hierarchy'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('City', 'gatherpress-venue-hierarchy'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Street', 'gatherpress-venue-hierarchy')];
  const getPositionFromLevel = level => {
    return (level - 1) / (maxLevel - 1) * 100;
  };
  const getLevelFromPosition = clientX => {
    if (!trackRef) return 1;
    const rect = trackRef.getBoundingClientRect();
    const position = (clientX - rect.left) / rect.width;
    const level = Math.round(position * (maxLevel - 1)) + 1;
    return Math.max(1, Math.min(maxLevel, level));
  };
  const handleMouseDown = handle => e => {
    e.preventDefault();
    setIsDragging(handle);
  };
  const handleMouseMove = e => {
    if (!isDragging) return;
    const newLevel = getLevelFromPosition(e.clientX);
    if (isDragging === 'start') {
      if (newLevel <= endLevel) {
        onChange({
          startLevel: newLevel,
          endLevel
        });
      }
    } else if (isDragging === 'end') {
      if (newLevel >= startLevel) {
        onChange({
          startLevel,
          endLevel: newLevel
        });
      }
    }
  };
  const handleMouseUp = () => {
    setIsDragging(null);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
      return () => {
        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
      };
    }
  }, [isDragging, startLevel, endLevel]);
  const startPos = getPositionFromLevel(startLevel);
  const endPos = getPositionFromLevel(endLevel);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
    className: "dual-range-control",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
      className: "dual-range-control__labels",
      children: levelLabels.slice(0, maxLevel).map((label, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("span", {
        className: "dual-range-control__label",
        children: label
      }, index))
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
      className: "dual-range-control__track-container",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
        ref: setTrackRef,
        className: "dual-range-control__track",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
          className: "dual-range-control__range",
          style: {
            left: `${startPos}%`,
            width: `${endPos - startPos}%`
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("button", {
          type: "button",
          className: `dual-range-control__handle dual-range-control__handle--start ${isDragging === 'start' ? 'is-dragging' : ''}`,
          style: {
            left: `${startPos}%`
          },
          onMouseDown: handleMouseDown('start'),
          "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Start level', 'gatherpress-venue-hierarchy')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("button", {
          type: "button",
          className: `dual-range-control__handle dual-range-control__handle--end ${isDragging === 'end' ? 'is-dragging' : ''}`,
          style: {
            left: `${endPos}%`
          },
          onMouseDown: handleMouseDown('end'),
          "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('End level', 'gatherpress-venue-hierarchy')
        })]
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
      className: "dual-range-control__output",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("span", {
        className: "dual-range-control__output-label",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Showing:', 'gatherpress-venue-hierarchy')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("strong", {
        children: [levelLabels[startLevel - 1] || '', startLevel !== endLevel && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
          children: [' ' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('to', 'gatherpress-venue-hierarchy') + ' ', levelLabels[endLevel - 1] || '']
        })]
      })]
    })]
  });
}

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
function Edit({
  attributes,
  setAttributes,
  context
}) {
  const {
    startLevel,
    endLevel,
    enableLinks,
    showVenue
  } = attributes;
  const [locationHierarchy, setLocationHierarchy] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('');
  const [venueName, setVenueName] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)('');
  const [maxDepth, setMaxDepth] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(4);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(true);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null);

  // Get the current post ID from the editor store
  const currentPostId = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.useSelect)(select => {
    return select('core/editor').getCurrentPostId();
  }, []);

  // Get the current post type from the editor store
  const currentPostType = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.useSelect)(select => {
    return select('core/editor').getCurrentPostType();
  }, []);

  // Use context values as fallback, but prefer the editor store values
  const postId = currentPostId || context.postId;
  const postType = currentPostType || context.postType;

  // Check if we're in a GatherPress event context
  if (postType && postType !== 'gatherpress_event') {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)(),
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('This block must be used within a GatherPress event', 'gatherpress-venue-hierarchy')
    });
  }
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (!postId) {
      setLocationHierarchy((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No post ID available', 'gatherpress-venue-hierarchy'));
      setIsLoading(false);
      return;
    }
    const fetchLocationData = async () => {
      try {
        setIsLoading(true);
        setError(null);

        // Fetch terms directly via REST API
        const terms = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
          path: `/wp/v2/gatherpress-location?post=${postId}&per_page=100`
        });

        // Fetch event data to get venue information
        let eventVenueName = '';
        try {
          const event = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_5___default()({
            path: `/wp/v2/gatherpress_event/${postId}`
          });
          if (event && event.venue) {
            eventVenueName = event.venue.name || '';
          }
        } catch (venueError) {
          console.log('Could not fetch venue information:', venueError);
        }
        setVenueName(eventVenueName);
        if (!terms || terms.length === 0) {
          if (showVenue && eventVenueName) {
            setLocationHierarchy(eventVenueName);
          } else {
            setLocationHierarchy((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No location hierarchy available for this event', 'gatherpress-venue-hierarchy'));
          }
          setIsLoading(false);
          return;
        }
        const buildTermPath = (term, allTerms) => {
          const path = [];
          let currentTerm = term;
          while (currentTerm) {
            path.unshift(currentTerm.name);
            if (currentTerm.parent && currentTerm.parent !== 0) {
              currentTerm = allTerms.find(t => t.id === currentTerm.parent);
            } else {
              break;
            }
          }
          return path;
        };

        // Find leaf terms (deepest terms in each hierarchy)
        const termIds = terms.map(t => t.id);
        const parentIds = terms.map(t => t.parent);
        const leafTerms = terms.filter(term => !parentIds.includes(term.id));
        const hierarchyPaths = leafTerms.map(term => buildTermPath(term, terms));

        // Calculate the maximum depth
        const calculatedMaxDepth = Math.max(...hierarchyPaths.map(path => path.length));
        setMaxDepth(Math.min(calculatedMaxDepth, 4)); // Cap at 4 levels

        // Filter paths based on start and end levels
        const filteredPaths = hierarchyPaths.map(path => {
          const actualStartLevel = Math.max(1, startLevel);
          const actualEndLevel = Math.min(endLevel, path.length);
          if (actualStartLevel > path.length) {
            return '';
          }
          return path.slice(actualStartLevel - 1, actualEndLevel).join(' > ');
        }).filter(path => path !== '');
        if (filteredPaths.length > 0) {
          let hierarchyText = filteredPaths.join(', ');

          // Add venue name if requested and available
          if (showVenue && eventVenueName) {
            hierarchyText += ' > ' + eventVenueName;
          }
          setLocationHierarchy(hierarchyText);
        } else {
          // If no filtered paths but venue is requested, show just venue
          if (showVenue && eventVenueName) {
            setLocationHierarchy(eventVenueName);
          } else {
            setLocationHierarchy((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No location hierarchy available at selected levels', 'gatherpress-venue-hierarchy'));
          }
        }
        setIsLoading(false);
      } catch (err) {
        console.error('Error fetching location data:', err);
        setError(err.message || 'Unknown error');

        // Even on error, try to show venue if requested
        if (showVenue && venueName) {
          setLocationHierarchy(venueName);
        } else {
          setLocationHierarchy((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Error loading location hierarchy', 'gatherpress-venue-hierarchy'));
        }
        setIsLoading(false);
      }
    };
    fetchLocationData();
  }, [postId, startLevel, endLevel, showVenue]);
  if (error) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("p", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)(),
      children: [(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Error: ', 'gatherpress-venue-hierarchy'), " ", error]
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.InspectorControls, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Hierarchy Levels', 'gatherpress-venue-hierarchy'),
        initialOpen: true,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(DualRangeControl, {
          startLevel: startLevel,
          endLevel: Math.min(endLevel, maxDepth),
          maxLevel: maxDepth,
          onChange: ({
            startLevel: newStart,
            endLevel: newEnd
          }) => {
            setAttributes({
              startLevel: newStart,
              endLevel: newEnd
            });
          }
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Display Options', 'gatherpress-venue-hierarchy'),
        initialOpen: true,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show venue', 'gatherpress-venue-hierarchy'),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Display the venue name at the end of the location hierarchy', 'gatherpress-venue-hierarchy'),
          checked: showVenue,
          onChange: value => setAttributes({
            showVenue: value
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Enable term links', 'gatherpress-venue-hierarchy'),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Link each location term to its archive page', 'gatherpress-venue-hierarchy'),
          checked: enableLinks,
          onChange: value => setAttributes({
            enableLinks: value
          })
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)(),
      children: isLoading ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Spinner, {}) : locationHierarchy
    })]
  });
}

/***/ },

/***/ "./src/editor.scss"
/*!*************************!*\
  !*** ./src/editor.scss ***!
  \*************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./src/index.js"
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./src/style.scss");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/edit.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src/block.json");
/**
* Registers a new block provided a unique name and an object defining its behavior.
*
* @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
*/


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * Internal dependencies
 */



/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__.name, {
  /**
   * @see ./edit.js
   */
  edit: _edit__WEBPACK_IMPORTED_MODULE_2__["default"]
});

/***/ },

/***/ "./src/style.scss"
/*!************************!*\
  !*** ./src/style.scss ***!
  \************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/block-editor"
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
(module) {

module.exports = window["wp"]["blockEditor"];

/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/data"
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["data"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "react/jsx-runtime"
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
(module) {

module.exports = window["ReactJSXRuntime"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"index": 0,
/******/ 			"./style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkblock_gatherpress_venue_hierarchy"] = globalThis["webpackChunkblock_gatherpress_venue_hierarchy"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["./style-index"], () => (__webpack_require__("./src/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map