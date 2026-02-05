// Override i18next default language to Italian
// This file must be loaded BEFORE wallets.js

// Store the original init function
const originalInit = window.i18next?.init || (() => {});

// Create a wrapper that intercepts i18next initialization
if (window.i18next) {
  const originalUse = window.i18next.use;
  
  window.i18next.use = function(...args) {
    // Call the original use
    const result = originalUse.apply(this, args);
    
    // Chain our own init call
    return {
      ...result,
      init: function(options, callback) {
        // Override the language default to Italian
        const modifiedOptions = {
          ...options,
          lng: 'it',  // Force Italian
          fallbackLng: 'it'
        };
        
        // Call the original init with modified options
        return this.init(modifiedOptions, callback);
      }
    };
  };
}

console.debug('i18next language override loaded: Italian set as default');
