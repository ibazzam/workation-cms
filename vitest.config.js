export default {
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['tests/js/**/*.test.*']
  },
  coverage: {
    provider: 'v8',
    reporter: ['text', 'lcov'],
    // Minimum coverage thresholds (percent)
    statements: 80,
    branches: 75,
    functions: 80,
    lines: 80
  }
}
