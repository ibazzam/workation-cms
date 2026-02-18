import { describe, it, expect } from 'vitest'

describe('app import', () => {
  it('imports app which imports bootstrap (side effects)', async () => {
    await import('../../../resources/js/app.js')
    // importing app should ensure bootstrap side-effects have run
    expect(window.axios).toBeDefined()
  })
})
