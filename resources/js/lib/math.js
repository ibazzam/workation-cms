export function add(a, b) {
  return a + b;
}

export function multiply(a, b) {
  return a * b;
}

export function average(values) {
  if (!values || values.length === 0) return 0;
  const sum = values.reduce((s, v) => s + v, 0);
  return sum / values.length;
}
