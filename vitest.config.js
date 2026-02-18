export default {
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['tests/js/**/*.test.*']
  },
  coverage: {
    provider: 'v8',
    reporter: ['text', 'lcov', 'json-summary'],
    // Only collect coverage for application JS (ignore infra, backend, node_modules)
    all: false,
    include: ['resources/js/**'],
    exclude: ['**/node_modules/**', 'infra/**', 'vendor/**'],
    // Minimum coverage thresholds (percent)
    statements: 95,
    branches: 90,
    functions: 95,
    lines: 95
  }
}
