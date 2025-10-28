import '@testing-library/jest-dom';

if (!window.requestAnimationFrame) {
  window.requestAnimationFrame = (callback: FrameRequestCallback): number => {
    return window.setTimeout(() => callback(Date.now()), 0);
  };
}

if (!window.cancelAnimationFrame) {
  window.cancelAnimationFrame = (handle: number): void => {
    clearTimeout(handle);
  };
}
