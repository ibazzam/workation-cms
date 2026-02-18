import { describe, it, expect } from 'vitest'
import { add, multiply, average } from '../../../../resources/js/lib/math'

describe('math edge cases', () => {
  it('handles negative and float numbers', () => {
    expect(add(-2.5, 1.5)).toBe(-1)
    expect(multiply(-3, -2)).toBe(6)
  })

  it('average handles floats and large arrays', () => {
    expect(average([1.5, 2.5, 3])).toBeCloseTo(2.3333333, 5)
    const arr = Array.from({ length: 50 }, (_, i) => i + 1)
    expect(average(arr)).toBe((1 + 50) / 2)
  })
})
