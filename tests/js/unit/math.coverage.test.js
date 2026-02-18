import { describe, it, expect } from 'vitest'
import { add, multiply, average } from '../../../resources/js/lib/math'

describe('math full coverage', () => {
  it('add handles zero, negative and large numbers', () => {
    expect(add(0, 0)).toBe(0)
    expect(add(-5, 3)).toBe(-2)
    expect(add(1.5, 2.25)).toBeCloseTo(3.75)
  })

  it('multiply handles zero, negative and float multiplication', () => {
    expect(multiply(0, 42)).toBe(0)
    expect(multiply(-3, 4)).toBe(-12)
    expect(multiply(1.5, 2)).toBeCloseTo(3)
  })

  it('average computes correctly for single and multiple items', () => {
    expect(average([5])).toBe(5)
    expect(average([1, 2, 3, 4])).toBe(2.5)
    const arr = Array.from({ length: 10 }, (_, i) => i + 1)
    expect(average(arr)).toBe((1 + 10) / 2)
  })
})
