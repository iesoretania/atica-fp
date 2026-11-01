// Los plugins de jQuery (Bootstrap, Select2...) y los scripts en línea de las plantillas
// esperan encontrar jQuery como variable global.
import $ from 'jquery';

window.$ = window.jQuery = $;

export default $;
