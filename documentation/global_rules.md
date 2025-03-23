# Windsurf Global Rules

## Project-Specific Rules

1. Local test server is: https://staffdirectory.local
2. Only edit scss files in public/assets/scss folder for styling the application
3. SCSS files are compiled using the Live Sass Compiler plugin
4. Avoids creating redundant CSS rules
5. .env file is placed in staff_dir_env folder
6. Think to add comments to your code
7. Always refer to documentation/devbook.md for project documentation
8. Prefer using public/includes/functions.php file to implement new Php functions
9. Always use composers libraries that match Php 7.4 version for this project
10. When I just ask a question without mentioning to specifically operate a modification on the codebase, don't operate any modification by yourself
11. In JavaScript, never add unnecessary semicolons at the end of the line except if it breaks the code.

## Browser Tools Rules

### Console and Network Monitoring Tools

1. **getConsoleLogs** - Use to retrieve JavaScript console logs from the browser for monitoring application output and debugging information.

2. **getConsoleErrors** - Use to fetch error messages from the console to identify JavaScript errors and exceptions.

3. **getNetworkErrors** - Use to monitor network requests that resulted in errors, helpful for identifying failed API calls or resource loading issues.

4. **getNetworkLogs** - Use to get a comprehensive log of all network activity for analyzing API calls, resource loading, and network performance.

5. **wipeLogs** - Use to clear all browser logs from memory when starting fresh debugging sessions.

### DOM Inspection Tools

6. **getSelectedElement** - Use to retrieve detailed information about the currently selected element in the browser, including tag name, attributes, dimensions, and content.

### Visual Tools

7. **takeScreenshot** - Use to capture the current state of the browser tab for documenting issues or sharing visual references.

### Audit Tools

8. **runAccessibilityAudit** - Use to analyze the page for accessibility issues, ensuring the application is usable for people with disabilities.

9. **runPerformanceAudit** - Use to evaluate the page's performance metrics, identifying bottlenecks and areas for optimization.

10. **runSEOAudit** - Use to check the page for search engine optimization best practices.

11. **runBestPracticesAudit** - Use to assess the page against web development best practices.

12. **runNextJSAudit** - Use only for Next.js applications to check for framework-specific optimizations.

### Special Modes

13. **runDebuggerMode** - Use to activate a structured debugging workflow that includes:
   - Reflecting on potential problem sources
   - Focusing on the most likely causes
   - Adding strategic logging
   - Gathering logs from both client and server
   - Analyzing the issue comprehensively
   - Implementing and validating fixes

14. **runAuditMode** - Use to implement a systematic approach to auditing and improving an application by:
   - Running multiple audits in sequence
   - Analyzing results comprehensively
   - Identifying areas for improvement
   - Creating a step-by-step improvement plan
   - Implementing changes systematically
   - Re-running audits to verify improvements

## How to Use Browser Tools in Windsurf

- **Development Phase**: Use console and network monitoring tools to ensure your code is functioning correctly.

- **Debugging Phase**: Use runDebuggerMode to systematically identify and fix issues, combined with console and network tools to gather evidence.

- **Optimization Phase**: Use runAuditMode and individual audit tools to identify areas for improvement in performance, accessibility, and SEO.

- **Documentation**: Use takeScreenshot to capture visual evidence of issues or improvements.

- **Analysis**: Use getSelectedElement to inspect specific DOM elements when making targeted improvements.

## Additional Browser Tools Guidelines

1. When using audit tools, always run them in the recommended sequence for comprehensive results.

2. Do not use takeScreenshot during audit mode unless specifically requested.

3. Always follow the structured approach provided by runDebuggerMode when troubleshooting issues.

4. Use runNextJSAudit only if the application is actually using Next.js.

5. Combine browser tools with project-specific practices like SCSS organization and PHP function implementation for optimal development workflow.
