import { describe, it, expect } from 'vitest'
import { add, multiply, average } from '../../../resources/js/lib/math'

describe('math utilities', () => {
  it('adds two numbers', () => {
    expect(add(2, 3)).toBe(5)
    expect(add(-1, 1)).toBe(0)
  })

  it('multiplies correctly', () => {
    expect(multiply(3, 4)).toBe(12)
    expect(multiply(0, 5)).toBe(0)
  })

  it('computes average', () => {
    expect(average([1, 2, 3])).toBe(2)
    expect(average([])).toBe(0)
  })
})
