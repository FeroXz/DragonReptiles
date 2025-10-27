import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Calculator } from '@components/genetics/Calculator.js';

declare global {
  interface Window {
    __GENETICS_APP_MOUNTED__?: boolean;
  }
}

const container = document.querySelector<HTMLElement>('[data-genetics-root]');

if (container && !window.__GENETICS_APP_MOUNTED__) {
  const root = createRoot(container);
  root.render(
    <StrictMode>
      <Calculator />
    </StrictMode>
  );
  window.__GENETICS_APP_MOUNTED__ = true;
}
