import { describe, it, expect } from 'vitest'
import { createCounterElement } from '../../../resources/js/ui'

describe('ui integration', () => {
  it('increments counter on button click', async () => {
    const el = createCounterElement(5)
    const span = el.querySelector('span')
    const btn = el.querySelector('button')
    expect(span.textContent).toBe('5')
    // simulate clicks
    btn.click()
    expect(span.textContent).toBe('6')
    btn.click()
    expect(span.textContent).toBe('7')
  })
})
