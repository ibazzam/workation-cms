import { describe, it, expect, beforeEach } from 'vitest'

describe('bootstrap import', () => {
  it('attaches axios to window and sets default header', async () => {
    // import module to run side-effects
    await import('../../../resources/js/bootstrap.js')
    // axios should be attached to window
    expect(window.axios).toBeDefined()
    expect(window.axios.defaults).toBeDefined()
    expect(window.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest')
  })
})
