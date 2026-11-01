// Registra Select2 en jQuery. La versión empaquetada para módulos ES exporta la función de
// inicialización de CommonJS (root, jQuery), así que hay que invocarla explícitamente.
import $ from './jquery_global.js';
import select2 from 'select2';

select2(window, $);
