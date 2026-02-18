export default {
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['tests/js/**/*.test.*']
  },
  coverage: {
    provider: 'v8',
    reporter: ['text', 'lcov', 'json-summary'],
    // Minimum coverage thresholds (percent)
    statements: 95,
    branches: 90,
    functions: 95,
    lines: 95
  }
}
