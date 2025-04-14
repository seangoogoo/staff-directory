/**
 * PostCSS plugin to convert Tailwind breakpoints to CSS variables
 * This plugin reads the breakpoints from tailwind.config.js and
 * generates CSS custom properties that can be used in JavaScript.
 */
const postcss = require('postcss');
const path = require('path');

module.exports = (opts = {}) => {
  return {
    postcssPlugin: 'postcss-tailwind-to-css-vars',
    Once(root, { result }) {
      // Import the Tailwind config from the project root
      const tailwindConfigPath = path.resolve(__dirname, '../../tailwind.config.js');
      const tailwindConfig = require(tailwindConfigPath);
      
      // Extract the screens (breakpoints) from the config
      const screens = tailwindConfig.theme.extend.screens || tailwindConfig.theme.screens;
      
      if (!screens) {
        console.warn('No screens found in Tailwind config');
        return;
      }
      
      // Create a new rule to hold our CSS variables
      const rule = postcss.rule({ selector: ':root' });
      
      // Add each breakpoint as a CSS variable
      Object.entries(screens).forEach(([name, value]) => {
        // Add the original value with px
        rule.append({ prop: `--breakpoint-${name}`, value: value });
        
        // Also add the numeric value without 'px' for easier JS usage
        const numericValue = value.replace('px', '').trim();
        rule.append({ 
          prop: `--breakpoint-${name}-value`, 
          value: numericValue
        });
      });
      
      // Add the rule to the beginning of the CSS
      if (rule.nodes.length > 0) {
        root.prepend(rule);
        console.log(`Generated CSS variables for breakpoints: ${Object.keys(screens).join(', ')}`);
      }
    }
  };
};

module.exports.postcss = true;
