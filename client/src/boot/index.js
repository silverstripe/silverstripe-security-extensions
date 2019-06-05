/* global window */
import registerComponents from './registerComponents';
import registerTransformations from './registerTransformations';

window.document.addEventListener('DOMContentLoaded', () => {
  registerComponents();
  registerTransformations();
});
