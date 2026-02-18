import { describe, it, expect } from 'vitest'
import '../../../resources/js/bootstrap'

describe('bootstrap', () => {
  it('attaches axios to window and sets default header', () => {
    expect(window.axios).toBeDefined()
    expect(window.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest')
  })
})
