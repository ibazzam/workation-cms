export default {
  test: {
    globals: true,
    environment: 'jsdom',
    include: ['tests/js/**/*.test.*']
  }
}

export const coverage = {
  reporter: ['text', 'lcov'],
  // output directory is coverage by default
}
