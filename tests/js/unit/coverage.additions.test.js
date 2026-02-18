import { describe, it, expect } from 'vitest'
import { average } from '../../../resources/js/lib/math'

describe('coverage additions', () => {
  it('average returns 0 for null and empty array', () => {
    expect(average(null)).toBe(0)
    expect(average([])).toBe(0)
  })

  it('importing bootstrap sets window.axios and default header', async () => {
    // import bootstrap side-effects
    await import('../../../resources/js/bootstrap')
    expect(window.axios).toBeDefined()
    expect(window.axios.defaults).toBeDefined()
    expect(window.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest')
  })

  it('importing app executes bootstrap import', async () => {
    // importing app also imports bootstrap
    await import('../../../resources/js/app')
    expect(window.axios).toBeDefined()
  })
})
