import { describe, it, expect } from 'vitest'
import { average } from '../../../resources/js/lib/math'

describe('math average branch', () => {
  it('returns 0 for null/undefined or empty arrays', () => {
    expect(average(null)).toBe(0)
    expect(average(undefined)).toBe(0)
    expect(average([])).toBe(0)
  })
})
