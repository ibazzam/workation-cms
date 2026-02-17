export function createCounterElement(initial = 0) {
  const el = document.createElement('div');
  el.className = 'counter';
  const span = document.createElement('span');
  span.textContent = String(initial);
  const btn = document.createElement('button');
  btn.textContent = 'Increment';
  btn.addEventListener('click', () => {
    const n = Number(span.textContent || '0') + 1;
    span.textContent = String(n);
  });
  el.appendChild(span);
  el.appendChild(btn);
  return el;
}
