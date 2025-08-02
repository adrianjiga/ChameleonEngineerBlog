import js from "@eslint/js";
import globals from "globals";
import tseslint from "typescript-eslint";

export default [
  // Base ESLint recommended config
  js.configs.recommended,
  
  // TypeScript recommended configs
  ...tseslint.configs.recommended,
  
  // Base configuration for all JS/TS files
  {
    files: ["**/*.{js,mjs,cjs,ts,mts,cts}"],
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node, // Add Node.js globals
      },
    },
  },
  
  // Specific configuration for config files
  {
    files: ["*.config.{js,ts}", "**/*.config.{js,ts}"],
    languageOptions: {
      globals: {
        ...globals.node, // Config files are Node.js environment
      },
    },
  },
  
  // Ignore patterns
  {
    ignores: [
      "node_modules/**",
      "dist/**",
      "build/**",
      ".next/**",
    ],
  },
];