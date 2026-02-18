import { describe, it, expect } from 'vitest'
import { createCounterElement } from '../../../../resources/js/ui'

describe('createCounterElement branch coverage', () => {
  it('uses fallback when span.textContent is falsy', () => {
    const el = createCounterElement(5)
    const span = el.querySelector('span')
    const btn = el.querySelector('button')
    if (!span || !btn) throw new Error('elements missing')
    // make textContent falsy to hit the || '0' branch
    span.textContent = ''
    btn.dispatchEvent(new Event('click'))
    expect(span.textContent).toBe('1')
  })
})
