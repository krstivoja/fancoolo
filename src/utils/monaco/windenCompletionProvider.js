/**
 * Winden Tailwind Autocomplete Provider for Monaco Editor
 *
 * Provides HTML class attribute autocomplete for Monaco editors in FanCoolo.
 * Integrates with Winden plugin's Tailwind class cache.
 *
 * @version 1.0.0
 */

/**
 * Check if Winden autocomplete data is available
 *
 * @returns {boolean} True if Winden data is loaded
 */
export function isWindenAvailable() {
	return !!(
		window.winden_autocomplete &&
		Array.isArray(window.winden_autocomplete) &&
		window.winden_autocomplete.length > 0
	);
}

/**
 * Get all available Tailwind classes from Winden
 *
 * @returns {string[]} Array of Tailwind class names
 */
export function getWindenClasses() {
	if (!isWindenAvailable()) {
		return [];
	}
	return window.winden_autocomplete;
}

/**
 * Get breakpoint prefixes from Winden
 *
 * @returns {string[]} Array of breakpoint names (e.g., ['sm', 'md', 'lg'])
 */
export function getWindenScreens() {
	if (!window.winden_autocomplete_screens) {
		return [];
	}
	return window.winden_autocomplete_screens;
}

/**
 * Accessor for Monaco-scoped Winden state to coordinate provider reuse
 *
 * @param {object} monaco - Monaco editor API instance
 * @returns {object} Shared state containing class cache and provider registry
 */
function getMonacoWindenState(monaco) {
	if (!monaco.__fanCoolo) {
		monaco.__fanCoolo = {};
	}

	if (!monaco.__fanCoolo.windenState) {
		monaco.__fanCoolo.windenState = {
			classes: new Set(),
			providers: new Map(),
		};
	}

	return monaco.__fanCoolo.windenState;
}

/**
 * Parse the current line to detect if we're inside a class attribute
 *
 * @param {string} lineText - Full text of the current line
 * @param {number} column - Current cursor column position
 * @returns {object|null} Context object with attribute info or null
 */
function detectClassAttributeContext(lineText, column) {
	// Get text up to cursor position
	const textBeforeCursor = lineText.substring(0, column - 1);

	// Pattern to match class attribute: class="..." or class='...'
	const classAttrPattern = /class\s*=\s*["']([^"']*)$/i;
	const match = textBeforeCursor.match(classAttrPattern);

	if (!match) {
		return null;
	}

	// Extract existing classes and determine position
	const classesText = match[1];
	const classes = classesText.split(/\s+/).filter(Boolean);

	// Check if we're after a space or at the start of class attribute
	const lastChar = textBeforeCursor[textBeforeCursor.length - 1];
	const isAfterSpace = lastChar === ' ' || lastChar === '"' || lastChar === "'";

	return {
		isInClassAttribute: true,
		existingClasses: classes,
		isAfterSpace,
		classesText,
		attributeStartColumn: column - classesText.length,
	};
}

/**
 * Get the current word being typed (including dashes for Tailwind classes)
 *
 * @param {object} model - Monaco editor model
 * @param {object} position - Current cursor position
 * @param {object} context - Class attribute context
 * @returns {object} Word info with startColumn, endColumn, and word text
 */
function getCurrentWord(model, position, context) {
	// Get the line content
	const lineText = model.getLineContent(position.lineNumber);
	const textBeforeCursor = lineText.substring(0, position.column - 1);

	// Find the last class attribute opening quote
	const classAttrMatch = textBeforeCursor.match(/class\s*=\s*["']([^"']*)$/i);

	if (classAttrMatch) {
		const insideClassAttr = classAttrMatch[1];

		// Split by spaces to get individual classes
		const classes = insideClassAttr.split(/\s+/);
		const currentClass = classes[classes.length - 1]; // Get the last (current) class being typed

		// Calculate the start column of the current word
		const startColumn = position.column - currentClass.length;

		return {
			startColumn: startColumn,
			endColumn: position.column,
			word: currentClass,
		};
	}

	// Fallback: Match Tailwind class pattern from the cursor position backwards
	const tailwindClassPattern = /[\w\-:\/\[\]%.#]+$/;
	const match = textBeforeCursor.match(tailwindClassPattern);

	if (match) {
		const word = match[0];
		return {
			startColumn: position.column - word.length,
			endColumn: position.column,
			word: word,
		};
	}

	// Last fallback to Monaco's default word detection
	const word = model.getWordUntilPosition(position);
	return {
		startColumn: word.startColumn,
		endColumn: word.endColumn,
		word: word.word,
	};
}

/**
 * Filter Tailwind classes based on search term
 *
 * Supports:
 * - Partial matching (e.g., "bg-r" matches "bg-red-500")
 * - Breakpoint prefixes (e.g., "sm:" shows sm:* variants)
 * - State variants (e.g., "hover:" shows hover:* variants)
 *
 * @param {string[]} classes - All available Tailwind classes
 * @param {string} searchTerm - Current search/filter term
 * @param {number} limit - Maximum number of suggestions (default: 100)
 * @returns {string[]} Filtered array of class names
 */
function filterClasses(classes, searchTerm, limit = 100) {
	if (!searchTerm) {
		// Return most common/useful classes when no search term
		const commonClasses = ['flex', 'grid', 'block', 'inline-block', 'hidden', 'container',
		                       'relative', 'absolute', 'fixed', 'sticky',
		                       'p-4', 'p-2', 'p-6', 'm-4', 'm-2', 'm-6',
		                       'w-full', 'h-full', 'text-center', 'text-left', 'text-right'];
		const filtered = classes.filter(c => commonClasses.includes(c));
		const remaining = classes.filter(c => !commonClasses.includes(c));
		return [...filtered, ...remaining].slice(0, limit);
	}

	const search = searchTerm.toLowerCase();

	// Only show starts-with matches for better UX
	const matches = [];

	for (const cls of classes) {
		const lowerCls = cls.toLowerCase();

		if (lowerCls.startsWith(search)) {
			matches.push(cls);
		}

		// Stop early if we have enough matches
		if (matches.length >= limit * 2) {
			break;
		}
	}

	return matches.slice(0, limit);
}

/**
 * Create Monaco completion provider for Tailwind classes in HTML class attributes
 *
 * This provider detects when the cursor is inside class="..." or class='...'
 * and provides autocomplete suggestions for Tailwind classes.
 *
 * @param {object} monaco - Monaco editor instance
 * @param {string[]} languages - Array of language IDs to register for (default: ['php', 'html'])
 * @param {object} options - Configuration options
 * @param {number} options.maxSuggestions - Maximum number of suggestions (default: 100)
 * @param {string[]} options.customClasses - Additional custom classes to suggest
 * @returns {IDisposable} Disposable completion provider
 */
export function createWindenCompletionProvider(monaco, languages = ['php', 'html'], options = {}) {
	const { maxSuggestions = 100, customClasses = [] } = options;

	if (!isWindenAvailable()) {
		return null;
	}

	const windenState = getMonacoWindenState(monaco);
	const initialClasses = [...getWindenClasses(), ...customClasses].filter(Boolean);

	initialClasses.forEach((className) => {
		windenState.classes.add(className);
	});

	const getClassesList = () => Array.from(windenState.classes);
	const classCount = windenState.classes.size;

	// Generate a unique ID for this provider instance
	const instanceId = Math.random().toString(36).substr(2, 9);

	// Register completion provider for each language (reusing global providers when available)
	const managedLanguages = [];

	languages.forEach((language) => {
		const existingProvider = windenState.providers.get(language);

		if (existingProvider) {
			existingProvider.refCount += 1;
			managedLanguages.push({ language });
			return;
		}

		const disposable = monaco.languages.registerCompletionItemProvider(language, {
			triggerCharacters: ['"', "'", ' ', ':', '-'],

			provideCompletionItems: function (model, position, monacoContext) {
				const classes = getClassesList();

				// Get the model language to avoid duplicate processing
				const modelLanguage = model.getLanguageId();

				// IMPORTANT: Only provide suggestions if this provider's language matches the model
				// This prevents duplicates when multiple providers are registered
				if (modelLanguage !== language) {
					return { suggestions: [] };
				}

				// Get the text - but Monaco completion runs BEFORE the character is added
				// So we need to work with what's there and use getWordAtPosition/getWordUntilPosition
				const lineText = model.getLineContent(position.lineNumber);
				const textBeforeCursor = model.getValueInRange({
					startLineNumber: position.lineNumber,
					startColumn: 1,
					endLineNumber: position.lineNumber,
					endColumn: position.column
				});

				const classAttrContext = detectClassAttributeContext(lineText, position.column);

				// Only provide suggestions if we're inside a class attribute
				if (!classAttrContext || !classAttrContext.isInClassAttribute) {
					return { suggestions: [] };
				}

				const wordInfo = getCurrentWord(model, position, classAttrContext);
				const searchTerm = wordInfo.word;

				// Filter classes based on search term
				const filteredClasses = filterClasses(classes, searchTerm, maxSuggestions);

				// Create Monaco completion items
				// Use a Set to track seen labels and prevent duplicates
				const seenLabels = new Set();
				const suggestions = filteredClasses
					.filter((className) => {
						if (seenLabels.has(className)) {
							return false;
						}
						seenLabels.add(className);
						return true;
					})
					.map((className) => {
						// Determine if this is a responsive or state variant
						let kind = monaco.languages.CompletionItemKind.Value;
						let documentation = `Tailwind CSS class: ${className}`;

						if (className.includes(':')) {
							kind = monaco.languages.CompletionItemKind.Keyword;
							documentation = `Tailwind CSS variant: ${className}`;
						}

						return {
							label: className,
							kind,
							insertText: className,
							insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
							documentation,
							range: {
								startLineNumber: position.lineNumber,
								endLineNumber: position.lineNumber,
								startColumn: wordInfo.startColumn,
								endColumn: wordInfo.endColumn,
							},
							sortText: className.startsWith(searchTerm) ? `0_${className}` : `1_${className}`,
						};
					});

				return {
					suggestions,
					// Tell Monaco not to include word-based suggestions
					incomplete: false,
				};
			},
		});

		windenState.providers.set(language, {
			disposable,
			refCount: 1,
			instanceId,
		});

		managedLanguages.push({ language });
	});

	// Return a combined disposable
	return {
		dispose: () => {
			managedLanguages.forEach(({ language }) => {
				const entry = windenState.providers.get(language);

				if (!entry) {
					return;
				}

				entry.refCount -= 1;

				if (entry.refCount <= 0) {
					entry.disposable.dispose();
					windenState.providers.delete(language);
				}
			});
		},
	};
}

/**
 * Initialize Winden autocomplete for a Monaco editor instance
 *
 * Convenience function that registers the completion provider on editor mount.
 * Includes retry logic to handle cases where Winden loads after Monaco.
 *
 * @param {object} editor - Monaco editor instance
 * @param {object} monaco - Monaco API object
 * @param {object} options - Configuration options
 * @param {string[]} options.languages - Languages to enable autocomplete for (default: ['php', 'html'])
 * @param {number} options.maxSuggestions - Maximum number of suggestions (default: 100)
 * @param {string[]} options.customClasses - Additional custom classes to suggest
 * @param {number} options.retryAttempts - Number of retry attempts (default: 5)
 * @param {number} options.retryDelay - Delay between retries in ms (default: 500)
 * @returns {IDisposable|null} Disposable completion provider or null if failed
 */
export function initializeWindenAutocomplete(editor, monaco, options = {}) {
	const {
		languages = ['php', 'html'],
		retryAttempts = 5,
		retryDelay = 500,
		...providerOptions
	} = options;

	// If Winden is already available, initialize immediately
	if (isWindenAvailable()) {
		return createWindenCompletionProvider(monaco, languages, providerOptions);
	}

	// Otherwise, retry with polling
	let attempts = 0;
	let disposable = null;
	let retryInterval = null;

	const attemptInitialization = () => {
		attempts++;

		if (isWindenAvailable()) {
			if (retryInterval) {
				clearInterval(retryInterval);
				retryInterval = null;
			}
			disposable = createWindenCompletionProvider(monaco, languages, providerOptions);
		} else if (attempts >= retryAttempts) {
			if (retryInterval) {
				clearInterval(retryInterval);
				retryInterval = null;
			}
		}
	};

	// Start retry interval
	retryInterval = setInterval(attemptInitialization, retryDelay);

	// Return a disposable that cleans up both the interval and the provider
	return {
		dispose: () => {
			if (retryInterval) {
				clearInterval(retryInterval);
			}
			if (disposable && typeof disposable.dispose === 'function') {
				disposable.dispose();
			}
		},
	};
}

/**
 * Debug helper to check Winden integration status
 *
 * @returns {object} Status information
 */
export function getWindenStatus() {
	return {
		isAvailable: isWindenAvailable(),
		classCount: getWindenClasses().length,
		screenCount: getWindenScreens().length,
		screens: getWindenScreens(),
		sampleClasses: getWindenClasses().slice(0, 10),
	};
}
