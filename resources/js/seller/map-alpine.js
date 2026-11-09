// The address-map component is built on Alpine. The seller layout does not load it, so start it here.
import Alpine from 'alpinejs';

if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}
